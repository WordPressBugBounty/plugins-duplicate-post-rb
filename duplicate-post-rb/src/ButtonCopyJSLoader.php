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

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Profile\Profile;
use rbDuplicatePost\User;
use rbDuplicatePost\Utils;
use rbDuplicatePost\ProCheck;

/**
 * Class ButtonCopyJSLoader
 *
 * Loads the Copy dialog (assets/admin/copy-dialog.js) on the admin screens of
 * users who can copy, with the profiles it offers.
 */
class ButtonCopyJSLoader {

    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    static function isJsCodeNeed() {
        if ( is_admin() && User::canEditPosts() && ! wp_doing_ajax() ) {
            return true;
        }
        return false;
    }

    /**
     * The profiles as the dialog needs them: name, template source, what
     * happens after copying and which elements are copied.
     */
    public static function profiles(): array {
        $default = (int) Profile::getDefaultProfileId();
        $out     = array();
        foreach ( Profile::getProfiles( -1, '' ) as $profile ) {
            $options  = ProfileOptions::getOptionsArray( (int) $profile->ID );
            $elements = array();
            foreach ( $options as $key => $option ) {
                if ( 0 === strpos( $key, 'copy' ) ) {
                    $elements[ $key ] = (bool) $option['value'];
                }
            }
            $out[] = array(
                'id'       => (int) $profile->ID,
                'title'    => $profile->post_title !== '' ? $profile->post_title : sprintf( '#%d', $profile->ID ),
                'default'  => (int) $profile->ID === $default,
                'source'   => Profile::getProfileSourceId( (int) $profile->ID ),
                'after'    => isset( $options['afterCopy']['value'] ) ? (string) $options['afterCopy']['value'] : 'stay',
                'elements' => $elements,
                // the Assistant's questions, asked by the dialog
                'vars'     => ! empty( $options['scenario']['value']['enabled'] ) ? array_values( (array) ( $options['scenario']['value']['vars'] ?? array() ) ) : array(),
            );
        }
        return $out;
    }

    public function enqueue_admin_scripts() {

        if ( ! self::isJsCodeNeed() ) {
            return;
        }

        $handle = RB_DUPLICATE_POST_ASSETS_PREFIX . 'copy-dialog';

        wp_enqueue_script(
            $handle,
            RB_DUPLICATE_POST_URL . 'assets/admin/copy-dialog.js',
            array( 'wp-i18n' ),
            RB_DUPLICATE_POST_VERSION,
            true
        );
        wp_set_script_translations( $handle, 'duplicate-post-rb' );

        $multisite = null;
        if ( is_multisite() ) {
            $multisite = array(
                'cross'   => \rbDuplicatePost\Helpers\Multisite::is_cross_site_enabled(),
                'current' => get_current_blog_id(),
                'sites'   => \rbDuplicatePost\Helpers\Multisite::get_available_target_blogs(),
            );
        }

        wp_localize_script(
            $handle,
            'RBDuplicatePostDialog',
            array(
                'restUrl'   => esc_url_raw( rest_url( 'rb_duplicate_post/v1/' ) ),
                'nonce'     => wp_create_nonce( 'wp_rest' ),
                'pro'       => ProCheck::isActive(),
                'buyUrl'    => 'https://rbplugins.com/wordpress-plugins/duplicate-post-rb/?pk_campaign=wp_dp_dialog#pricing',
                'profiles'  => self::profiles(),
                'multisite' => $multisite,
                'isMultisite' => is_multisite(),
            )
        );

        // read by the block editor's Copy panel
        wp_localize_script(
            $handle,
            'rbDuplicatePost',
            array( 'settings_url' => esc_url_raw( Utils::getSettingsPageUrl() ) )
        );
    }
}
