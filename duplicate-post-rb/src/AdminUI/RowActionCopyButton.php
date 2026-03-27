<?php
/* 
*      RB Duplicate Post     
*      Version: 1.6.1
*      By RbPlugin
*
*      Contact: https://robosoft.co 
*      Created: 2025
*      Licensed under the GPLv3 license - http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace rbDuplicatePost\AdminUI;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Constants;
use rbDuplicatePost\Helpers\PostTypes;
use rbDuplicatePost\ProfileOptions;
use rbDuplicatePost\Profile\Profile;
use rbDuplicatePost\User;
use rbDuplicatePost\Utils;

/**
 * Adds a "Copy" link to the row actions
 * for all custom post types in admin list tables.
 */
class RowActionCopyButton {

    // Cache for profiles
    private static  $profiles = null;

    // Icons SVG paths
    private const ICONS = [
        'ContentCopyIcon' => '<path d="M16 1H4c-1.1 0-2 .9-2 2v14h2V3h12zm3 4H8c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h11c1.1 0 2-.9 2-2V7c0-1.1-.9-2-2-2m0 16H8V7h11z"></path>',
        'CopyAllIcon' => '<path d="M18 2H9c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h9c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2m0 14H9V4h9zM3 15v-2h2v2zm0-5.5h2v2H3zM10 20h2v2h-2zm-7-1.5v-2h2v2zM5 22c-1.1 0-2-.9-2-2h2zm3.5 0h-2v-2h2zm5 0v-2h2c0 1.1-.9 2-2 2M5 6v2H3c0-1.1.9-2 2-2"></path>',
        'FolderCopyIcon' => '<path d="M3 6H1v13c0 1.1.9 2 2 2h17v-2H3z"></path><path d="M21 4h-7l-2-2H7c-1.1 0-1.99.9-1.99 2L5 15c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2"></path>',

        'ControlPointIcon' => '<path d="M13 7h-2v4H7v2h4v4h2v-4h4v-2h-4zm-1-5C6.49 2 2 6.49 2 12s4.49 10 10 10 10-4.49 10-10S17.51 2 12 2m0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8"></path>',
        'BorderColorIcon' => '<path d="M22 24H2v-4h20zM13.06 5.19l3.75 3.75L7.75 18H4v-3.75zm4.82 2.68-3.75-3.75 1.83-1.83c.39-.39 1.02-.39 1.41 0l2.34 2.34c.39.39.39 1.02 0 1.41z"></path>',
        'AutorenewIcon' => '<path d="M12 6v3l4-4-4-4v3c-4.42 0-8 3.58-8 8 0 1.57.46 3.03 1.24 4.26L6.7 14.8c-.45-.83-.7-1.79-.7-2.8 0-3.31 2.69-6 6-6m6.76 1.74L17.3 9.2c.44.84.7 1.79.7 2.8 0 3.31-2.69 6-6 6v-3l-4 4 4 4v-3c4.42 0 8-3.58 8-8 0-1.57-.46-3.03-1.24-4.26"></path>',
        ];

    private const DEFAULT_COLOR = '#2271b1';

    private const ICON_TYPES = ['icon', 'icon-label'];

    /**
     * Constructor.
     */
    public function __construct() {

        if ( ! is_admin() ) {
            return;
        }

        // Register hooks for all custom post types ( set more priority )
        add_action( 'init', array( $this, 'hooks' ), Utils::getMaxInteger() );
    }

    /**
     * Register hooks for all custom post types.
     */
    public function hooks() {

        if ( ! User::isAllowForCurrentUser() ) {
            return;
        }

        if ( ! User::isEnableForPlace( 'list' ) ) {
            return;
        }

        if ( null === self::$profiles ) {
            self::$profiles = self::get_profiles_for_buttons();
        }

        $post_types = array_keys( PostTypes::get_all_types() );

        foreach ( $post_types as $post_type ) {
            if( !PostTypes::is_type_support($post_type) && $post_type !== PostTypes::POST_TYPE ){
                continue;
            }
            add_filter( $post_type . "_row_actions", array( self::class, 'add_copy_action' ), 10, 2 );
        }
    }

    private static function get_profiles_for_buttons(): array {

        $profiles = Profile::getProfiles( Constants::ACTION_BUTTONS_COUNT );

        $enabled_profiles = array();

        foreach ( $profiles as $profile_id ) {
            $options = ProfileOptions::getOptionsArray( $profile_id );

            if ( isset( $options[ 'enableButton' ][ 'value' ] ) && $options[ 'enableButton' ][ 'value' ] ) {
                $enabled_profiles[  ] = array(
                    'profile_id' => $profile_id,
                    'options'    => $options,
                 );
            } else if( $profile_id == Profile::getDefaultProfileId() ) {
                $enabled_profiles[  ] = array(
                    'profile_id' => $profile_id,
                    'options'    => self::get_default_options(),
                );

            }
        }

        return $enabled_profiles;
    }

    private static function normalize_button_profile(int $profile_id, array $options): array {

        return array(
            'profile_id' => (int) $profile_id,
            'label'      => $options['labelButton']['value'] ?? '',
            'icon'       => $options['iconButton']['value'] ?? '',
            'color'      => $options['colorButton']['value'] ?? self::DEFAULT_COLOR,
            'type'       => $options['typeButton']['value'] ?? 'label',
            'without_confirmation' => !empty($options['withoutConfirmationButton']['value']),
        );
    }

    /**
     * Add "Copy" action to row actions.
     *
     * @param array   $actions
     * @param \WP_Post $post
     * @return array
     */
    public static function add_copy_action( array $actions, \WP_Post $post ): array {

        if ( ! User::canEditPost( $post->ID ) ) {
            return $actions;
        }

        if(!PostTypes::is_type_support($post->post_type)){
            return $actions;
        }

        if (empty(self::$profiles) || !is_array(self::$profiles)) {
            return $actions;
        }

        foreach ( self::$profiles as $profile ) {
            $profile_id = $profile['profile_id'];
            $options = $profile['options'] ?? [];
            $button = self::normalize_button_profile($profile_id, $options);
            $key = sprintf('rb-duplicate-post-copy-button-%d', $profile_id);
            $actions[$key] = self::get_copy_action_html($post, $button);
        }

        return $actions;
    }

    /** 
     * Get default options for the default profile button
     */ 
    static private function get_default_options ( ){
        return  [
            'labelButton' => [
                'value' => __('Copy', 'duplicate-post-rb'),
            ],
            'withoutConfirmationButton' => [
                'value' => false,
            ],
        ];
    }

    /**
     * Build action URL.
     */
    protected static function get_action_url( int $post_id ): string {
        return add_query_arg(
            array(
                'action' => Constants::COPY_ACTION_NAME,
                'post'   => $post_id,
                '_nonce' => wp_create_nonce( Constants::COPY_ACTION_NAME ),
             ),
            admin_url( 'edit.php' )
        );
    }

    /**
     * Generate action HTML.
     */
    protected static function get_copy_action_html( \WP_Post $post, array $button ): string {

        $label = $button['label'] ?: __('Copy', 'duplicate-post-rb');

        $color = sanitize_text_field( $button['color'] ) ?: self::DEFAULT_COLOR;

        $style = 'color: '.esc_attr($color).';';

        $without_confirmation =  $button[ 'without_confirmation' ] ? 1 : 0;

        $icon = self::render_icon($button['icon'], $button['type'], $color);
    
        return sprintf(
            '<a href="#"
                class="rb-duplicate-post-copy-button"
                data-post-id="%d"
                data-profile-id="%d"
                data-without-confirmation="%d"
                style="%s"
                title="%s"
                >
                %s
                %s
            </a>',
            $post->ID,
            (int) $button[ 'profile_id' ],
            $without_confirmation,
            esc_attr( $style ),
            esc_attr( $label ),
            $icon,
            $button['type']!=='icon' ? esc_html( $label ) : '',
        );
    }


    private static function render_icon(string $icon_name, string $type, string $color): string {

        if (!in_array($type, self::ICON_TYPES, true)) {
            return '';
        }

        if (!isset(self::ICONS[$icon_name])) {
            return '';
        }

        return sprintf(
            '<svg style="height:19px; margin:-5px 0;" aria-hidden="true" viewBox="0 0 24 24" fill="%s">%s</svg>',
            esc_attr($color),
            self::ICONS[$icon_name]
        );
    }
}
