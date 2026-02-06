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

defined('WPINC') || exit;

use rbDuplicatePost\Constants;
use rbDuplicatePost\User;

/**
 * Adds a "Copy" link to the row actions under post and page titles
 * in the WordPress admin list table.
 */
class ClassicEditorButtonCopy
{

    /**
     * Constructor.
     * Initializes WordPress hooks.
     */
    public function __construct()
    {
        if ( !is_admin() ) {
            return;
        }
        add_action('init', array( self::class, 'hooks' ));
    }

    /**
     * Register WordPress hooks (only in admin area).
     *
     * @return void
     */
    public static function hooks(): void
    {
         if ( !User::isAllowForCurrentUser() ) {
            return;
        }

        if ( !User::isEnableForPlace('edit') ) {
            return;
        }
        
        add_action( 'post_submitbox_misc_actions', array(self::class,  'add_copy_action' ) ); 
        //post_submitbox_misc_actions
        //post_submitbox_start
    }

    /**
     * Check if the given post type is supported.
     *
     * @param \WP_Post $post
     * @return bool
     */
    protected static function is_supported_post_type(\WP_Post $post): bool
    {
        // Allow other developers to modify supported post types via filter
        $supported = apply_filters('rb_duplicate_post_supported_types', Constants::ALLOWED_POST_TYPES);
        return in_array($post->post_type, (array) $supported, true);
    }

    /**
     * Add the "Copy" link to the editor.
     *
     * @param \WP_Post $post
     * @return void
     */
    public static function add_copy_action( $post ) : void
    {
        global $pagenow;
        if($pagenow && $pagenow=='post-new.php'){
            return;
        }

        if ( ! $post || ! $post instanceof \WP_Post ) {
            return;
        }

        // Permission and type checks
        if (!User::canEditPost($post->ID) || !self::is_supported_post_type($post)) {
            return ;
        }

        echo '<div class="misc-pub-section rb-duplicate-post-copy-button-container" >';
        echo self::get_copy_action_html($post);
        echo '</div>';
    }


    /**
     * Generate the "Copy" action link HTML.
     *
     * @param \WP_Post $post
     * @return string
     */
    protected static function get_copy_action_html(\WP_Post $post): string
    {
        $label = esc_html__('Copy', 'duplicate-post-rb');

        return sprintf(
            '<a href="#" role="button" class="rb-duplicate-post-copy-button" data-post-id="%d" data-no-refresh="1">%s</a>',
            $post->ID,
            $label
        );
    }
}