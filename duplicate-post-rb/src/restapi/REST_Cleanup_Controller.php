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

use rbDuplicatePost\Cleanup\DuplicateFinder;
use rbDuplicatePost\Cleanup\Trasher;
use rbDuplicatePost\Constants;
use rbDuplicatePost\User;
use WP_REST_Server;

/**
 * Cleanup of duplicates for the settings screen: GET /cleanup/duplicates finds
 * groups of posts with the same title (or title and content), POST
 * /cleanup/remove moves the chosen ones to the trash or deletes them.
 */
class REST_Cleanup_Controller extends REST_Controller {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route( $this->namespace, '/cleanup/duplicates', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => array( $this, 'find' ),
			'permission_callback' => array( $this, 'permissions_check' ),
			'args'                => array(
				'post_type' => array( 'type' => 'string', 'default' => 'post' ),
				'match'     => array( 'type' => 'string', 'default' => DuplicateFinder::MATCH_TITLE, 'enum' => DuplicateFinder::MATCHES ),
				'statuses'  => array( 'type' => 'string', 'default' => implode( ',', DuplicateFinder::STATUSES ) ),
			),
		) );
		register_rest_route( $this->namespace, '/cleanup/remove', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'remove' ),
			'permission_callback' => array( $this, 'permissions_check' ),
			'args'                => array(
				'ids'         => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ), 'required' => true ),
				'permanently' => array( 'type' => 'boolean', 'default' => false ),
				// {removed id: id of the post that stays}, and whether to redirect (PRO, with RB Links Redirection)
				'keep'        => array( 'type' => 'object', 'default' => array() ),
				'redirect'    => array( 'type' => 'boolean', 'default' => false ),
			),
		) );
	}

	public function permissions_check() {
		return User::canManageOptions();
	}

	public function find( $request ) {
		$type = sanitize_key( (string) $request->get_param( 'post_type' ) );
		$type_object = get_post_type_object( $type );
		if ( ! $type_object || $type === RB_DUPLICATE_POST_PROFILE_TYPE_POST ) {
			return self::rest_response_error( 'invalid_post_type', 'Unknown post type', 400 );
		}
		$statuses = array_filter( array_map( 'sanitize_key', explode( ',', (string) $request->get_param( 'statuses' ) ) ) );
		$groups   = DuplicateFinder::groups( $type, (string) $request->get_param( 'match' ), $statuses );

		$out = array();
		foreach ( $groups as $ids ) {
			$items = array();
			foreach ( $ids as $id ) {
				$post = get_post( $id );
				if ( ! $post ) {
					continue;
				}
				$author  = get_userdata( (int) $post->post_author );
				$items[] = array(
					'id'       => $id,
					'title'    => get_the_title( $post ),
					'status'   => $post->post_status,
					'date'     => $post->post_date,
					'author'   => $author ? $author->display_name : '',
					'is_copy'  => (bool) get_post_meta( $id, Constants::HISTORY_META_KEY, true ),
					'edit_url' => (string) get_edit_post_link( $id, 'raw' ),
					'view_url' => (string) get_permalink( $id ),
				);
			}
			if ( count( $items ) > 1 ) {
				$out[] = $items;
			}
		}
		return self::rest_response( array( 'groups' => $out ) );
	}

	public function remove( $request ) {
		$ids = array_filter( array_map( 'intval', (array) $request->get_param( 'ids' ) ) );
		if ( ! $ids ) {
			return self::rest_response_error( 'invalid_request', 'Nothing to remove', 400 );
		}
		$keep = array();
		foreach ( (array) $request->get_param( 'keep' ) as $removed => $stays ) {
			if ( (int) $removed > 0 && (int) $stays > 0 && (int) $removed !== (int) $stays ) {
				$keep[ (int) $removed ] = (int) $stays;
			}
		}
		return self::rest_response( Trasher::remove( $ids, (bool) $request->get_param( 'permanently' ), true, array(
			'keep'     => $keep,
			'redirect' => (bool) $request->get_param( 'redirect' ),
		) ) );
	}
}
