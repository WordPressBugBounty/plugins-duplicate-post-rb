<?php
/*
*      RB Duplicate Post
*      By RbPlugin
*
*      Contact: https://rbplugins.com
*      Created: 2025
*      Licensed under the GPLv3 license - http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace rbDuplicatePost\restapi;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Log\Logger;
use rbDuplicatePost\Log\Actions;
use rbDuplicatePost\User;
use WP_REST_Request;
use WP_REST_Server;

/**
 * The copy log for the settings screen: GET /log (filtered, paged), DELETE /log.
 */
class REST_Log_Controller extends REST_Controller {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/log',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'action'   => array( 'type' => 'string', 'default' => '' ),
						'from'     => array( 'type' => 'string', 'default' => '' ),
						'to'       => array( 'type' => 'string', 'default' => '' ),
						'page'     => array( 'type' => 'integer', 'default' => 1, 'minimum' => 1 ),
						'per_page' => array( 'type' => 'integer', 'default' => 20, 'minimum' => 1, 'maximum' => 5000 ),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_items' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);
	}

	public function permissions_check() {
		return User::canManageOptions();
	}

	/** a YYYY-MM-DD date, or '' */
	private static function day( $value ) {
		$value = (string) $value;
		return preg_match( '/^\d{4}-\d{2}-\d{2}$/', $value ) ? $value : '';
	}

	/** a post of a log row; $known_title names a post that is gone (removed by a cleanup) */
	private static function post_info( $id, $known_title = '' ) {
		$id = (int) $id;
		if ( ! $id ) {
			return null;
		}
		$post = get_post( $id );
		return array(
			'id'       => $id,
			'title'    => $post ? get_the_title( $post ) : ( $known_title !== '' ? $known_title : '#' . $id ),
			'edit_url' => $post && current_user_can( 'edit_post', $id ) ? get_edit_post_link( $id, 'raw' ) : '',
		);
	}

	public function get_items( $request ) {
		$action = (string) $request->get_param( 'action' );
		$args   = array(
			'action_type' => Actions::isValid( $action ) ? $action : '',
			'from'        => self::day( $request->get_param( 'from' ) ),
			'to'          => self::day( $request->get_param( 'to' ) ),
		);
		$per   = (int) $request->get_param( 'per_page' );
		$page  = (int) $request->get_param( 'page' );
		$rows  = Logger::getAll( $args + array( 'limit' => $per, 'offset' => ( $page - 1 ) * $per ) );
		$users = array();
		$items = array();
		foreach ( $rows as $row ) {
			$uid = (int) $row->user_id;
			if ( ! isset( $users[ $uid ] ) ) {
				$user          = $uid ? get_userdata( $uid ) : null;
				// no user: WP-CLI or the scheduled cleanup
				$users[ $uid ] = $user ? $user->display_name : ( $uid ? '#' . $uid : __( 'System', 'duplicate-post-rb' ) );
			}
			$meta    = $row->metadata ? json_decode( (string) $row->metadata, true ) : array();
			$items[] = array(
				'id'     => (int) $row->id,
				'date'   => (string) $row->date,
				'action' => (string) $row->action_type,
				'source' => self::post_info( $row->source_post_id, isset( $meta['title'] ) ? (string) $meta['title'] : '' ),
				'target' => self::post_info( $row->target_post_id ),
				'user'   => $users[ $uid ],
			);
		}
		return self::rest_response( array(
			'items' => $items,
			'total' => Logger::count( $args ),
		) );
	}

	public function delete_items( $request ) {
		return self::rest_response( array( 'deleted' => Logger::clear() ) );
	}
}
