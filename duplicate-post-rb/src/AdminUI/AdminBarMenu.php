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

use rbDuplicatePost\Utils;
 use rbDuplicatePost\User;

class AdminBarMenu {

    public function __construct() {
        add_action('init', array( self::class, 'hooks' ));
    }

    public static function hooks() {

        if(!self::isEditScreen()){
            return;
        }

        if(!is_admin() || !is_admin_bar_showing()){
            return;    
        }

        if ( ! User::isAllowForCurrentUser() ) {
            return;
        }

        if ( ! User::isEnableForPlace( 'admin-bar-menu' ) ) {
            return;
        }

        add_action( 'wp_before_admin_bar_render', array( self::class, 'admin_bar_render' ) );
    }

    public static function isEditScreen() {
        $screen = Utils::getCurrentScreen();
        return $screen==='post.php' || $screen==='post-new.php';
    }

    public static function admin_bar_render() {
		global $wp_admin_bar;

		$post = self::get_current_post_id();

		if ( ! $post ) {
			return;
		}
			$wp_admin_bar->add_menu(
				[
					'id'    => 'rb-duplicate-post-copy',
					'title' => '<span class="rb-duplicate-post-copy-button" data-post-id="'.$post->ID.'" data-no-refresh="1" ><span class="ab-icon"></span><span class="ab-label">' . \__( 'Copy', 'duplicate-post-rb' ) . '</span></span>',
					'href'  => '#',
                    'meta' => array(
                        'class' => 'rb-duplicate-post-copy-button',
                        'data-post-id' => $post->ID,
                    ),
                    
				]
			);  
			// $wp_admin_bar->add_menu(
			// 	[
			// 		'id'     => 'new-draft',
			// 		'parent' => 'rb-duplicate-post-copy',
			// 		'title'  => __( 'Copy v 2', 'duplicate-post-rb' ),
			// 		'href'   => '#', 
			// 	]
			// );
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
