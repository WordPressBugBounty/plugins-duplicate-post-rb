<?php
/* 
*      RB Duplicate Post     
*      Version: 1.6.7
*      By RbPlugin
*
*      Contact: https://robosoft.co 
*      Created: 2025
*      Licensed under the GPLv3 license - http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace rbDuplicatePost;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Profile\Profile;
use rbDuplicatePost\User;
use rbDuplicatePost\Utils;
use rbDuplicatePost\Notification;
use rbDuplicatePost\ProCheck;

/**
 * Class ButtonCopyJSLoader
 *
 * Enqueues the JavaScript file and exposes localized variables
 * for authorized users inside the WordPress admin area.
 */
class ButtonCopyJSLoader {

    /**
     * Constructor.
     * Registers hooks for enqueueing admin scripts.
     */
    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action( 'admin_footer', array( $this, 'print_inline_js' ) );
    }

    public function print_inline_js() {

        if ( ! self::isJsCodeNeed() ) {
            return;
        }
        $blockPro = ProCheck::isActive()== false;
        $profile_id = (int) Profile::getDefaultProfileId();
        
        $notificationShow = false;

        $notificationData = array(
            'duplicateSuccess'=>0,
            'duplicateFail'=>0,
            'duplicateProRequery'=>0,
        );

        $notification = new Notification();
        $user_notification = $notification->get_notification_once();
        $notification_types = array(Constants::NOTIFICATION_TYPE_POST_COPIED, Constants::NOTIFICATION_TYPE_POST_CREATED);

        if($user_notification && isset($user_notification['type']) && isset($user_notification['data'])) {
            $notification_type = $user_notification['type'];
            if( in_array($notification_type, $notification_types) ) {
                $notificationShow = true;
                foreach ($user_notification['data'] as $key => $value) {
                    if( isset($value['success']) && $value['success'] ) {
                        ++$notificationData['duplicateSuccess'] ;
                    } else {
                        if($value['code'] == 'pro_version_not_active') {
                            ++$notificationData['duplicateProRequery'] ;
                        } else {
                            ++$notificationData['duplicateFail'] ;
                        }
                    }
                }
            }
        }
        echo '<div class="rbDuplicatePostDialog" rbDuplicatePost_id="' . $profile_id. '"></div>';
        echo "<script type='text/javascript'>" . self::getJSCode( $blockPro, $notificationShow, $notificationData ) . "</script>";
    }

    static function getJSCode($blockPro = false, $notificationShow = false, $notificationData = array(['duplicateSuccess'=>0,'duplicateFail'=>0])) {

        $duplicateSuccess = isset($notificationData['duplicateSuccess']) ? (int) $notificationData['duplicateSuccess']: 0;

        $duplicateFail = isset($notificationData['duplicateFail']) ? (int) $notificationData['duplicateFail']: 0;

        $duplicateProRequery = isset($notificationData['duplicateProRequery']) ? (int) $notificationData['duplicateProRequery']: 0;

        $jsCode   = 'window["rb_duplicate_post_dialog_cfg"] = {'
        . 'rb_duplicate_post_dialog_url: "' . RB_DUPLICATE_POST_URL . 'assets/js/dialog/",'
        . 'imagesUrl: "' . RB_DUPLICATE_POST_URL . 'assets/js/dialog/",'
        . 'restUrl: "' . esc_url( get_rest_url() ) . '",'
        . 'wp_rest: "' . esc_js( wp_create_nonce( 'wp_rest' ) ) . '",'
            . 'blockPro: ' . ( $blockPro ? 'true' : 'false' ) . ','
            . 'debug: true,'
            . 'messageShow: ' . ( $notificationShow ? 'true' : 'false' ) . ','
            . 'duplicateSuccess: ' . $duplicateSuccess. ','
            . 'duplicateFail: ' . $duplicateFail . ','
            . 'duplicateProRequery: ' . $duplicateProRequery . ','
            . 'settingsUrl: "' . esc_url( Utils::getSettingsPageUrl() ) . '",'
            . '};';

        $jsCode .= "
const toPositiveInt = (value)  =>{ const num = Number(value); return Number.isInteger(num) && num > 0 ? num : 0; }
const handleRbDuplicatePostButton = (event)=>{
    if(!event || !event.currentTarget){
        return;
    }
    const el = event.currentTarget;

    const typeDataExists = el.getAttribute('data-duplicate-action-type')!==null;
    const dataEl = !typeDataExists && el.querySelector('.rb-duplicate-post-copy-button-data') ? el.querySelector('.rb-duplicate-post-copy-button-data') : el;
    const duplicateActionType = dataEl.getAttribute('data-duplicate-action-type') || 'duplicate';

    if(duplicateActionType === 'create') {
        runRbDuplicatePostCreateAction(dataEl);
    }
    if(duplicateActionType === 'duplicate') {
        runRbDuplicatePostDuplicateAction(dataEl);
    }
}

const runRbDuplicatePostDuplicateAction = (el) => {
    if (typeof window.rb_duplicate_post_dialog !== 'function') {
        console.warn('Function rb_duplicate_post_dialog not found.');
        return;
    }

    const postId = toPositiveInt(el.getAttribute('data-post-id'));
    if ( postId <= 0) {
        console.warn('Incorrect post ID');
        return;
    }
            
    const no_refresh = toPositiveInt(el.getAttribute('data-no-refresh')) ? 1 : 0 ;
    const profileId = toPositiveInt(el.getAttribute('data-profile-id'));
    const withoutConfirmation = toPositiveInt(el.getAttribute('data-without-confirmation')) ? 1 : 0;
    window.rb_duplicate_post_dialog([parseInt(postId, 10)], no_refresh, profileId , withoutConfirmation);
}

const runRbDuplicatePostCreateAction = (el) => {
    if (typeof window.rb_duplicate_post_dialog_new !== 'function') {
        console.warn('Function rb_duplicate_post_dialog_new not found.');
        return;
    }
    const no_refresh = toPositiveInt(el.getAttribute('data-no-refresh')) ? 1 : 0 ;
    const profileId = toPositiveInt(el.getAttribute('data-profile-id'));
    const withoutConfirmation = toPositiveInt(el.getAttribute('data-without-confirmation')) ? 1 : 0;
    window.rb_duplicate_post_dialog_new(profileId, no_refresh , withoutConfirmation);
}

const handleRbDuplicatePostMouse = (event) => {
    if (event.type === 'click' && event.button !== 0) return;
    if (event.type === 'mouseup' && event.button !== 1) return;
    event.preventDefault();
    event.stopPropagation();
    handleRbDuplicatePostButton(event);
};
document.addEventListener('DOMContentLoaded',  ()=> {
    const buttons = document.querySelectorAll('.rb-duplicate-post-copy-button');
    buttons.forEach( (button)=> {
        button.addEventListener('click',     handleRbDuplicatePostMouse );
        button.addEventListener('mouseup',   handleRbDuplicatePostMouse );
    });
});
";
        return $jsCode;
    }

    static function isJsCodeNeed() {
        if ( is_admin() && User::canEditPosts() && ! wp_doing_ajax() ) {
            return true;
        }
        return false;
    }

    /**
     * Enqueue admin JS and localize variables for authorized users.
     */
    public function enqueue_admin_scripts() {

        if ( ! self::isJsCodeNeed() ) {
            return;
        }

        $handle = RB_DUPLICATE_POST_ASSETS_PREFIX.'admin-button-row-js';
        $src    = RB_DUPLICATE_POST_URL . 'assets/js/dialog/main.js';

        wp_enqueue_script(
            $handle,
            $src,
            array(  ),
            RB_DUPLICATE_POST_VERSION,
            true
        );

        $params = array(
            'settings_url' => esc_attr( Utils::getSettingsPageUrl() ),
        );

        // Localize variables accessible in JS
        wp_localize_script(
            $handle,
            'rbDuplicatePost',
            $params 
        );
    }
}
