<?php
/*
*      RB Duplicate Post
*      Version: 2.0.0
*      By RbPlugin
*
*      Contact: https://rbplugins.com
*      Created: 2025
*      Licensed under the GPLv3 license - http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace rbDuplicatePost\Rewrite;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\GeneralOptions;
use rbDuplicatePost\Helpers\PostTypes;
use rbDuplicatePost\User;

/**
 * Rewrite & Republish in WordPress: the "Rewrite & Republish" row action and
 * admin bar item, publishing a rewrite in the block editor, the classic editor
 * and on schedule, and the notes that tell a rewrite and its original apart.
 */
class RewriteRepublish {

    public const ACTION = 'rb_dp_rewrite';

    public function __construct() {
        // a scheduled rewrite is published by cron: these run on every request
        add_filter( 'wp_insert_post_data', array( self::class, 'hold_publish' ), 10, 2 );
        add_action( 'wp_after_insert_post', array( self::class, 'scheduled_publish' ), 10, 4 );
        add_action( 'before_delete_post', array( self::class, 'forget' ) );
        add_action( 'wp_trash_post', array( self::class, 'forget' ) );

        if ( is_admin() ) {
            add_action( 'admin_init', array( self::class, 'admin_hooks' ) );
            add_action( 'admin_action_' . self::ACTION, array( self::class, 'handle_create' ) );
            add_filter( 'redirect_post_location', array( self::class, 'classic_publish' ), 20, 2 );
            add_action( 'enqueue_block_editor_assets', array( self::class, 'editor_script' ) );
            add_action( 'admin_notices', array( self::class, 'notices' ) );
            add_filter( 'display_post_states', array( self::class, 'post_state' ), 10, 2 );
        }
        add_action( 'admin_bar_menu', array( self::class, 'admin_bar' ), 90 );
    }

    public static function enabled(): bool {
        return (bool) GeneralOptions::getOptionValue( 'enableRewriteRepublish' ) && User::isAllowForCurrentUser();
    }

    public static function admin_hooks() {
        if ( ! self::enabled() ) {
            return;
        }
        foreach ( array_keys( PostTypes::get_all_types() ) as $type ) {
            if ( PostTypes::is_type_support( $type ) || $type === PostTypes::POST_TYPE ) {
                add_filter( $type . '_row_actions', array( self::class, 'row_action' ), 20, 2 );
            }
        }
        add_filter( 'post_row_actions', array( self::class, 'row_action' ), 20, 2 );
        add_filter( 'page_row_actions', array( self::class, 'row_action' ), 20, 2 );
    }

    public static function create_url( int $post_id ): string {
        return wp_nonce_url( admin_url( 'admin.php?action=' . self::ACTION . '&post=' . $post_id ), self::ACTION . '_' . $post_id );
    }

    public static function row_action( $actions, $post ) {
        if ( ! $post instanceof \WP_Post || ! PostTypes::is_type_support( $post->post_type ) || ! Republisher::can_rewrite( $post ) ) {
            return $actions;
        }
        $rewrite = Republisher::rewrite_of( $post->ID );
        $actions['rb_dp_rewrite'] = $rewrite
            ? sprintf( '<a href="%s">%s</a>', esc_url( get_edit_post_link( $rewrite ) ), esc_html__( 'Edit the rewrite', 'duplicate-post-rb' ) )
            : sprintf( '<a href="%s" class="rb-duplicate-post-rewrite">%s</a>', esc_url( self::create_url( $post->ID ) ), esc_html__( 'Rewrite & Republish', 'duplicate-post-rb' ) );
        return $actions;
    }

    public static function admin_bar( \WP_Admin_Bar $bar ) {
        if ( ! is_admin() || ! self::enabled() ) {
            return;
        }
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        $post   = $screen && $screen->base === 'post' ? get_post() : null;
        if ( ! $post || ! PostTypes::is_type_support( $post->post_type ) || ! Republisher::can_rewrite( $post ) ) {
            return;
        }
        $rewrite = Republisher::rewrite_of( $post->ID );
        $bar->add_node( array(
            'id'    => 'rb-duplicate-post-rewrite',
            'title' => $rewrite ? esc_html__( 'Edit the rewrite', 'duplicate-post-rb' ) : esc_html__( 'Rewrite & Republish', 'duplicate-post-rb' ),
            'href'  => $rewrite ? get_edit_post_link( $rewrite ) : self::create_url( $post->ID ),
        ) );
    }

    public static function handle_create() {
        $post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
        check_admin_referer( self::ACTION . '_' . $post_id );
        if ( ! self::enabled() ) {
            wp_die( esc_html__( 'Rewrite & Republish is switched off.', 'duplicate-post-rb' ), 403 );
        }
        try {
            $rewrite = Republisher::create( $post_id );
        } catch ( \Exception $e ) {
            wp_die( esc_html( $e->getMessage() ), 403 );
        }
        wp_safe_redirect( get_edit_post_link( $rewrite, 'raw' ) );
        exit;
    }

    /**
     * Publishing a rewrite saves it as a draft: the rewrite itself never goes
     * live. The editor (or the classic form) then republishes it into the original.
     */
    public static function hold_publish( $data, $postarr ) {
        $id = isset( $postarr['ID'] ) ? (int) $postarr['ID'] : 0;
        if ( ! $id || ! in_array( $data['post_status'], array( 'publish', 'future' ), true ) || ! Republisher::original_of( $id ) ) {
            return $data;
        }
        // now or on schedule, a rewrite goes into its original only for someone who may republish it:
        // cron has no user, so a schedule is checked here, when it is set
        if ( is_user_logged_in() && ! Republisher::can_republish( $id ) ) {
            $data['post_status'] = 'draft';
            return $data;
        }
        if ( $data['post_status'] === 'publish' ) {
            $data['post_status'] = 'draft';
            // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- rb_dp_ is this plugin's prefix
            $GLOBALS['rb_dp_republish_requested'][ $id ] = true;
        }
        return $data;
    }

    /** a scheduled rewrite: cron publishes it (wp_publish_post skips the filter above, which checked the schedule) */
    public static function scheduled_publish( $post_id, $post, $update, $before ) {
        if ( $post->post_status !== 'publish' || ! $before || $before->post_status !== 'future' ) {
            return;
        }
        if ( ! Republisher::original_of( (int) $post_id ) ) {
            return;
        }
        try {
            Republisher::republish( (int) $post_id );
        } catch ( \Exception $e ) {
            // the original is gone: the rewrite stays as it is
        }
    }

    /** classic editor: Publish on a rewrite republishes it and opens the original */
    public static function classic_publish( $location, $post_id ) {
        if ( empty( $GLOBALS['rb_dp_republish_requested'][ $post_id ] ) || ! Republisher::can_republish( (int) $post_id ) ) {
            return $location;
        }
        try {
            $original = Republisher::republish( (int) $post_id );
        } catch ( \Exception $e ) {
            return $location;
        }
        return add_query_arg( 'rb_dp_republished', 1, get_edit_post_link( $original, 'url' ) );
    }

    /** the block editor's part: a note on a rewrite, and republish once Publish has saved */
    public static function editor_script() {
        $post = get_post();
        if ( ! $post ) {
            return;
        }
        $original = Republisher::original_of( $post->ID );
        if ( ! $original ) {
            return;
        }
        $handle = RB_DUPLICATE_POST_ASSETS_PREFIX . 'rewrite-editor';
        wp_enqueue_script( $handle, RB_DUPLICATE_POST_URL . 'assets/admin/rewrite-editor.js', array( 'wp-data', 'wp-i18n', 'wp-api-fetch', 'wp-editor', 'wp-notices' ), RB_DUPLICATE_POST_VERSION, true );
        wp_set_script_translations( $handle, 'duplicate-post-rb' );
        wp_localize_script( $handle, 'RBDuplicatePostRewrite', array(
            'rewriteId'     => $post->ID,
            'originalTitle' => get_the_title( $original ),
            'originalUrl'   => get_edit_post_link( $original, 'raw' ),
            'republishPath' => '/rb_duplicate_post/v1/rewrite/' . $post->ID . '/republish',
        ) );
    }

    public static function notices() {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || $screen->base !== 'post' ) {
            return;
        }
        $post = get_post();
        if ( ! $post ) {
            return;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- only chooses which notice to show after the redirect
        if ( ! empty( $_GET['rb_dp_republished'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'The rewrite is republished: this post now has its changes.', 'duplicate-post-rb' ) . '</p></div>';
            return;
        }
        $original = Republisher::original_of( $post->ID );
        if ( $original && ! $screen->is_block_editor() ) {
            printf(
                '<div class="notice notice-info"><p>%s</p></div>',
                sprintf(
                    /* translators: %s: link to the original post */
                    esc_html__( 'This is a rewrite of %s. Publish puts its changes into the original and removes this draft.', 'duplicate-post-rb' ),
                    '<a href="' . esc_url( get_edit_post_link( $original ) ) . '">' . esc_html( get_the_title( $original ) ) . '</a>'
                )
            );
            return;
        }
        $rewrite = Republisher::rewrite_of( $post->ID );
        if ( $rewrite && ! $screen->is_block_editor() ) {
            printf(
                '<div class="notice notice-warning"><p>%s</p></div>',
                sprintf(
                    /* translators: %s: link to the rewrite */
                    esc_html__( 'A rewrite of this post is in progress: changes made here are replaced when it is republished. %s', 'duplicate-post-rb' ),
                    '<a href="' . esc_url( get_edit_post_link( $rewrite ) ) . '">' . esc_html__( 'Edit the rewrite', 'duplicate-post-rb' ) . '</a>'
                )
            );
        }
    }

    public static function post_state( $states, $post ) {
        $original = Republisher::original_of( $post->ID );
        if ( $original ) {
            /* translators: %s: title of the original post */
            $states['rb_dp_rewrite'] = sprintf( esc_html__( 'Rewrite of %s', 'duplicate-post-rb' ), esc_html( get_the_title( $original ) ) );
        } elseif ( Republisher::rewrite_of( $post->ID ) ) {
            $states['rb_dp_rewrite'] = esc_html__( 'Rewrite in progress', 'duplicate-post-rb' );
        }
        return $states;
    }

    /** a rewrite or an original goes away: its partner forgets it */
    public static function forget( $post_id ) {
        $post_id  = (int) $post_id;
        $original = Republisher::original_of( $post_id );
        if ( $original && (int) get_post_meta( $original, Republisher::META_REWRITE, true ) === $post_id ) {
            delete_post_meta( $original, Republisher::META_REWRITE );
        }
        $rewrite = (int) get_post_meta( $post_id, Republisher::META_REWRITE, true );
        if ( $rewrite ) {
            delete_post_meta( $rewrite, Republisher::META_ORIGINAL );
        }
    }
}
