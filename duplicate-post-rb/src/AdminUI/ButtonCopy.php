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
class ButtonCopy
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

        if ( !User::isEnableForPlace('list') ) {
            return;
        }

        add_filter('post_row_actions', array( self::class, 'add_copy_action' ), 10, 2);
        add_filter('page_row_actions', array( self::class, 'add_copy_action' ), 10, 2);
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
     * Add the "Copy" link to the row actions list.
     *
     * @param array $actions
     * @param \WP_Post $post
     * @return array
     */
    public static function add_copy_action(array $actions, \WP_Post $post): array
    {
        // Permission 
        if (!User::canEditPost($post->ID)) {
            return $actions;
        }

        // Type checks
        if ( !self::is_supported_post_type($post)) {
            return $actions;
        }

        $actions['rb_copy'] = self::get_copy_action_html($post);

        return $actions;
    }

    /**
     * Build the URL for triggering the duplicate action.
     *
     * @param int $post_id
     * @return string
     */
    protected static function get_action_url(int $post_id): string
    {
        // Build a proper admin URL that triggers your action
        $args = [
            'action' => Constants::COPY_ACTION_NAME,
            'post'   => $post_id,
            '_nonce'  => wp_create_nonce( Constants::COPY_ACTION_NAME ),
        ];

        return add_query_arg($args, admin_url('edit.php'));
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
        $action_url = esc_url(self::get_action_url($post->ID));

        return sprintf(
            '<a href="#" class="rb-duplicate-post-copy-button" data-post-id="%d">%s</a>', //%s
         //   $action_url,
            $post->ID,
            $label
        );
    }
}