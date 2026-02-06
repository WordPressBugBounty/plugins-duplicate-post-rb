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
 * Adds a "Copy" link to the Gutenberg editor.
 */
class GutenbergEditorButtonCopy
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
    public static function hooks()
    {
         if ( !User::isAllowForCurrentUser() ) {
            return;
        }

        if ( !User::isEnableForPlace('gutenberg') ) {
            return;
        }
        
        add_action( 'enqueue_block_editor_assets', array(self::class,  'add_copy_button' ) );
    
    }

     public static function enqueue_editor_script() {
        wp_register_script(
            RB_DUPLICATE_POST_ASSETS_PREFIX . 'gutenberg-copy-button',
            RB_DUPLICATE_POST_URL.'assets/js/gutenberg-editor/main.js',
            array( 'wp-plugins', 'wp-edit-post', 'wp-components', 'wp-element' , 'wp-data' ),
            false,
            true
        );
        wp_enqueue_script( RB_DUPLICATE_POST_ASSETS_PREFIX . 'gutenberg-copy-button' );
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
     * @return void
     */
    public static function add_copy_button( ) : void
    {
        global $post;
        if ( ! $post || ! $post instanceof \WP_Post ) {
            return;
        }

        // Permission and type checks
        if (!User::canEditPost($post->ID) || !self::is_supported_post_type($post)) {
            return ;
        }

        self::enqueue_editor_script();
    }
}