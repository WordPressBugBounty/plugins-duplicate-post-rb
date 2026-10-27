<?php
/* 
*      RB Duplicate Post     
*      Version: 1.6.8
*      By RbPlugin
*
*      Contact: https://rbplugins.com 
*      Created: 2025
*      Licensed under the GPLv3 license - http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace rbDuplicatePost;

defined('WPINC') || exit;

use rbDuplicatePost\Constants;
use rbDuplicatePost\User;
use rbDuplicatePost\Core\DuplicatorFactory;

/**
 * Class Admin_Duplicate_Action_Handler
 *
 * Handles the custom admin action URL for duplicating posts.
 * Example: /wp-admin/edit.php?action=rb_duplicate_action&post=123
 */
class ButtonCopyActionHandler
{
    /**
     * Constructor.
     * Registers the admin action hook.
     */
    public function __construct()
    {
        $this->init_hooks();
    }

    /**
     * Register the WordPress admin action hook.
     */
    protected function init_hooks()
    {
        add_action('admin_action_' . Constants::COPY_ACTION_NAME, array($this, 'handle'));
    }

    /**
     * Handles the duplication action.
     */
    public function handle()
    {
        $nonce = isset( $_GET['_nonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_nonce'] ) ) : '';
        if( !$nonce ) {
            wp_die('Invalid security token.');
        }

        if(  !wp_verify_nonce(  $nonce, Constants::COPY_ACTION_NAME ) ) {
            wp_die('Invalid security token.');
        }

        // Validate request
        if (!is_admin() || !isset($_GET['post'])) {
            wp_die('Invalid request.');
        }

        $post_id = absint($_GET['post']);

        if (!$post_id) {
            wp_die('Invalid post ID.');
        }

        // Check user capability
        if (!User::isAllowForCurrentUser() || !User::canEditPost($post_id)) {
            wp_die('You do not have permission to duplicate this post.');
        }

        $duplicator = DuplicatorFactory::make( 'post' );

        if ( $duplicator==null) {
            wp_die('Duplicate Post handler class not found.');
        } 
            
        try {
            $duplicator->duplicate( $post_id );
        } catch ( \InvalidArgumentException $e ) {
            wp_die( esc_html( $e->getMessage() ) );
        }

        // Redirect back to the referring list page
        $redirect_url = $this->get_redirect_url();
        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Determines the redirect URL after the action is performed.
     *
     * @return string
     */
    protected function get_redirect_url(): string
    {
        // Try to use the referring admin page, fallback to post list
        $referer = wp_get_referer();

        if ($referer && strpos($referer, 'edit.php') !== false) {
            return esc_url_raw($referer);
        }

        return admin_url('edit.php');
    }

}
