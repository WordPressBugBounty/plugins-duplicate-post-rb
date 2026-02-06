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

use rbDuplicatePost\Core\DuplicatorFactory;
use rbDuplicatePost\Constants;
use rbDuplicatePost\Profile\Profile;
use rbDuplicatePost\Notification;
use rbDuplicatePost\User;
use rbDuplicatePost\Utils;

/**
 * Adds a "Copy" link to the row actions under post and page titles
 * in the WordPress admin list table.
 */
class ButtonBulkCopy
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

        if ( !User::isEnableForPlace('bulk') ) {
            return;
        }

        add_action( 'admin_enqueue_scripts', array(self::class, 'enqueue_admin_scripts_bulk' ) );

        add_filter( 'bulk_actions-edit-post', array(self::class, 'add_bulk_copy_actions') );
        add_filter( 'bulk_actions-edit-page', array(self::class, 'add_bulk_copy_actions') );

        add_action( 'handle_bulk_actions-edit-post',  array(self::class, 'bulk_copy_action_handler'), 10, 3 );
        add_action( 'handle_bulk_actions-edit-page',  array(self::class, 'bulk_copy_action_handler'), 10, 3 );
    }

    /** 
     * Enqueue scripts
     * 
     * @return void
     */
    public static function enqueue_admin_scripts_bulk() {
        wp_enqueue_script(
            RB_DUPLICATE_POST_ASSETS_PREFIX . 'admin-button-bulk',
            RB_DUPLICATE_POST_URL . 'assets/js/bulk-action/script.js',
            array(  ),
            RB_DUPLICATE_POST_VERSION,
            true
        );
    }

    public static function bulk_copy_action_handler( $redirect_to, $doAction, $ids ) {

        //check if it's our action
        if ( $doAction !== 'rb-duplicate-post-bulk-action' ) {
            return $redirect_to;
        }

        //check if user can edit posts
        if ( !User::canEditPosts()) {
            return $redirect_to;
        }

         $duplicator = DuplicatorFactory::make( 'post' );

         if ( $duplicator==null ) {
             return $redirect_to;
         }

        $profile_id = Profile::getDefaultProfileId();

        $results    = array();

        foreach ( $ids as $id ) {
            try {
                $newId = $duplicator->duplicate( $id, $profile_id );
                $results[] = array(
                    'id'    => $id,
                    'duplicate_id'=> $newId,
                    'success' => true,
                );
            } catch ( \Exception $e ) {
                 $results[  ] =  array(
                    'id'    => $id,
                    'duplicate_id'=> false,
                    'success' => false
                );
                 //TODO : add error to log
            }
        }

        $notification = new Notification();

        $notification->set_notification(  [ 
            'type'=>Constants::NOTIFICATION_TYPE_POST_COPIED, 
            'data' => $results
        ] );

        return $redirect_to;
    }
 

    public static function add_bulk_copy_actions($bulk_actions) {

        if ( !User::canEditPosts()) {
            return $bulk_actions;
        }

        if(!is_array($bulk_actions)){
            $bulk_actions = array();
        }

        $bulk_actions = Utils::arrayInsertAfter(
            $bulk_actions, 
            'edit', 
            'rb-duplicate-post-bulk-action', 
            __('Copy', 'duplicate-post-rb')
        );

        return $bulk_actions;
    }

    

}