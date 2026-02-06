<?php
/* 
*      RB Duplicate Post     
*      Version: 1.5.8
*      By RbPlugin
*
*      Contact: https://robosoft.co 
*      Created: 2025
*      Licensed under the GPLv3 license - http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace rbDuplicatePost\AdminUI;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Constants;
use rbDuplicatePost\Profile\Profile;
use rbDuplicatePost\Utils;

/**
 * Adds a meta box to the classic editor showing duplication history.
 */
class HistoryMetaBox {


    public function __construct() {
        if ( ! is_admin() ) {
            return;
        }
        self::hooks();
    }

    /**
     * Registers the meta box.
     */
    public static function hooks() {
        add_action( 'add_meta_boxes', array( self::class, 'add_meta_box' ) );
    }

    /**
     * Adds the meta box to post edit screen.
     */
    public static function add_meta_box() {
        $post_types = get_post_types( array( 'public' => true ) );
        
        foreach ( $post_types as $post_type ) {
            add_meta_box(
                'rb_duplicate_post_history',
                __( 'RB Duplicate Post - History', 'duplicate-post-rb' ),
                array( self::class, 'render_meta_box' ),
                $post_type,
                'side',
                'high'
            );
        }
    }

    /**
     * Renders the meta box content.
     */
    public static function render_meta_box( $post ) {
        $history = get_post_meta( $post->ID, Constants::HISTORY_META_KEY, true );

        if ( empty( $history ) || ! is_array( $history ) ) {
            echo '<p>' . esc_html__( 'No duplication history found.', 'duplicate-post-rb' ) . '</p>';
            return;
        }

        // Validate required fields
        $source_id = isset( $history['post_source_id'] ) ? (int) $history['post_source_id'] : 0;
        $source_blog_id = isset( $history['post_source_blog_id'] ) ? (int) $history['post_source_blog_id'] : 0;
        $source_title = isset( $history['post_source_title'] ) ? sanitize_text_field( $history['post_source_title'] ) : '';
        $profile_id = isset( $history['profile_id'] ) ? (int) $history['profile_id'] : 0;
        $date = isset( $history['date'] ) ? sanitize_text_field( $history['date'] ) : '';

        $source_blog_name = self::get_blog_name( $source_blog_id );

        if ( ! $source_id || ! $source_title ) {
            echo '<p>' . esc_html__( 'Incomplete history data.', 'duplicate-post-rb' ) . '</p>';
            return;
        }

        $date_formatted = $date ? date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $date ) ) : '';

        $source_url = self::get_post_url( $source_id, $source_blog_id );

        $profile = Profile::getProfile($profile_id);
        $profile_name = $profile ? $profile->post_title : '';

        ?>
        <div class="rb-duplicate-history">
            <?php if ( $source_blog_name && ( ! is_multisite() || $source_blog_id !== get_current_blog_id() ) ): ?>
                <p>
                    <?php esc_html_e( 'Source Site:', 'duplicate-post-rb' ); ?>
                    <strong>
                        <?php echo esc_html( $source_blog_name ); ?>
                        <?php if ( $source_blog_id ): ?>
                            <em>(ID: <?php echo (int) $source_blog_id; ?>)</em>
                        <?php endif; ?>
                    </strong>
                </p>
            <?php endif; ?>
            <?php if ( $source_url ): ?>
                <p>
                    <?php esc_html_e( 'Source:', 'duplicate-post-rb' ); ?>
                    <strong><a href="<?php echo esc_url( $source_url ); ?>" target="_blank" rel="noopener">
                        <?php echo esc_html( $source_title ); ?>
                    </a></strong>
                </p>
            <?php else: ?>
                <p>
                    <?php esc_html_e( 'Source:', 'duplicate-post-rb' ); ?>
                    <strong><?php echo esc_html( $source_title ); ?></strong>
                    <em> (<?php esc_html_e( 'URL not available', 'duplicate-post-rb' ); ?>)</em>
                </p>
            <?php endif; ?>

            <?php if ( $date_formatted ): ?>
                <p>
                    <?php esc_html_e( 'Copied on:', 'duplicate-post-rb' ); ?>
                    <strong><?php echo esc_html( $date_formatted ); ?></strong>
                </p>
            <?php endif; ?>

            <?php if ( $profile_name ): ?>
                <p>
                   <?php esc_html_e( 'Use Profile:', 'duplicate-post-rb' ); ?>
                    <strong><a href="<?php echo esc_url( self::get_settings_url( $profile_id ) ); ?>"><?php echo esc_html( $profile_name ); ?></a></strong>
                </p>
            <?php endif; ?>
        </div>
        <style> .rb-duplicate-history p { margin: 8px 0; } </style>
        <?php
    }

    private static function get_settings_url( $profile_id = 0 ) {
        return Utils::getSettingsPageUrl() . ( $profile_id ? '#profile_id=' . (int) $profile_id : '');
    }

    /**
     * Gets the URL of the source post (handles Multisite).
     */
    private static function get_post_url( $post_id, $source_blog_id ) {
        $url = '';

        // Handle Multisite
        if ( is_multisite() && $source_blog_id !== get_current_blog_id() ) {
            // Switch to source blog to get URL
            $restore = switch_to_blog( $source_blog_id );
            if ( get_post( $post_id ) ) {
                $url = get_permalink( $post_id );
            }
            if ( $restore ) {
                restore_current_blog();
            }
        } else {
            $url = get_permalink( $post_id );
        }

        return $url;
    }

    private static function get_blog_name( $blog_id ) {
        if ( ! is_multisite() || $blog_id <= 0 ) {
            return '';
        }
        $blog_details = get_blog_details( $blog_id );
        return $blog_details ? $blog_details->blogname : sprintf( __( 'Site %d', 'duplicate-post-rb' ), $blog_id );
    }
}