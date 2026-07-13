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

namespace rbDuplicatePost\AdminUI;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Utils;
 use rbDuplicatePost\User;
 use rbDuplicatePost\Profile\Profile;

class AdminBarMenu {

    public function __construct() {
        add_action('init', array( self::class, 'hooks' ));
    }

    public static function hooks() {

        if(!is_admin() || !is_admin_bar_showing()){
            return;    
        }

        if ( ! User::isAllowForCurrentUser() ) {
            return;
        }

        if ( ! User::isEnableForPlace( 'admin-bar-menu' ) ) {
            return;
        }

	//add_action( 'admin_head', array( self::class, 'add_menu_icon_styles') );

        add_action( 'wp_before_admin_bar_render', array( self::class, 'admin_bar_render' ) );
    }

    public static function is_edit_screen() {
        $screen = Utils::getCurrentScreen();
        return $screen==='post.php' || $screen==='post-new.php';
    }

	/**
	 * Add admin bar menu items
	 */
    public static function admin_bar_render() {
		global $wp_admin_bar;

		self::add_menu_item_to_new_menu($wp_admin_bar);

		if(self::is_edit_screen()){
            self::add_menu_item_to_copy_menu($wp_admin_bar);
        }
		
	}

	/**
	 * Add admin bar menu item to copy menu
	 */
	public static function add_menu_item_to_copy_menu($wp_admin_bar) {

		$post = self::get_current_post_id();

		if ( ! $post ) {
			return;
		}

		$menu_args = array(
            'id'    => 'rb-duplicate-post-copy',
            'title' => '<span class="rb-duplicate-post-copy-button" data-post-id="'.$post->ID.'" data-no-refresh="1" ><span class="ab-icon"></span><span class="ab-label">' . \__( 'Copy', 'duplicate-post-rb' ) . '</span></span>',
            'href'  => '#',
            'parent' => '',
            'meta' => array(
                'class' => 'rb-duplicate-post-copy-button',
                'data-post-id' => $post->ID,
            ),            
		);
		
		$wp_admin_bar->add_menu( $menu_args );  
			
			// $wp_admin_bar->add_menu(
			// 	[
			// 		'id'     => 'new-draft',
			// 		'parent' => 'rb-duplicate-post-copy',
			// 		'title'  => __( 'Copy v 2', 'duplicate-post-rb' ),
			// 		'href'   => '#', 
			// 	]
			// );
	}

	public static function add_menu_item_to_new_menu($wp_admin_bar) {

	    //$svg_icon = '<svg focusable="false" aria-hidden="true" viewBox="0 0 24 24"><path d="M18.41 5.8 17.2 4.59c-.78-.78-2.05-.78-2.83 0l-2.68 2.68L3 15.96V20h4.04l8.74-8.74 2.63-2.63c.79-.78.79-2.05 0-2.83M6.21 18H5v-1.21l8.66-8.66 1.21 1.21zM11 20l4-4h6v4z"></path></svg>';

        $profile_id = (int) Profile::getDefaultProfileId();

         if ( ! User::isEnableForPlace( 'admin-bar-menu' ) ) {
            return;
        }

        $profiles = Profile::getProfilesWithSourceId('buttonTypeNew');

        if(empty($profiles)){
            return;
        }

        foreach ($profiles as $profile) {
            $menu_args = [
                'id'     => 'create-new-post-with-duplicate-profile-' . $profile['id'],
                'parent' => 'new-content',
                'title'  => $profile['labelButton'],
                'href'   => '#', 
                'meta'   => [
                    'class' => 'rb-duplicate-post-copy-button',
                    'title' => esc_attr__( 'Create a new post using Duplicate Post RB plugin with a predefined template', 'duplicate-post-rb' ),
                    'html' => '<span 
                                style="display:none;" 
                                class="rb-duplicate-post-copy-button-data"
                                data-duplicate-action-type="create" 
                                data-no-refresh="0" 
                                data-profile-id="' . $profile['id']. '" 
                                data-without-confirmation="0"
                            >
                            </span>', 
                ],
            ];
            $wp_admin_bar->add_menu($menu_args);
        }
	}


    public static function get_current_post_id() {
		global $wp_the_query;

		if ( is_admin() ) {
			$post = get_post();
		}
		else {
			$post = $wp_the_query->get_queried_object();
		}

		if ( empty( $post ) || ! $post instanceof \WP_Post ) {
			return false;
		}

		return $post;
	}
}
