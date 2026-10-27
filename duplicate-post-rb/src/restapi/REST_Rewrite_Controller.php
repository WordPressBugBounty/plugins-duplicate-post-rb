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

use rbDuplicatePost\Helpers\PostTypes;
use rbDuplicatePost\Rewrite\Republisher;
use rbDuplicatePost\Rewrite\RewriteRepublish;
use WP_REST_Server;

/**
 * Rewrite & Republish: POST /rewrite/{id} makes (or returns) the rewrite of a
 * published post; POST /rewrite/{id}/republish puts a rewrite into its original.
 */
class REST_Rewrite_Controller extends REST_Controller {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route( $this->namespace, '/rewrite/(?P<id>\d+)', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'create' ),
			'permission_callback' => array( $this, 'can_create' ),
		) );
		register_rest_route( $this->namespace, '/rewrite/(?P<id>\d+)/republish', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'republish' ),
			'permission_callback' => array( $this, 'can_republish' ),
		) );
	}

	public function can_create( $request ) {
		$post = get_post( (int) $request['id'] );
		return RewriteRepublish::enabled() && $post && PostTypes::is_type_support( $post->post_type ) && Republisher::can_rewrite( $post );
	}

	/** the rewrite may be edited, and the original published, by this user */
	public function can_republish( $request ) {
		return Republisher::can_republish( (int) $request['id'] );
	}

	public function create( $request ) {
		try {
			$id = Republisher::create( (int) $request['id'] );
		} catch ( \Exception $e ) {
			return self::rest_response_error( 'rewrite_failed', $e->getMessage(), 400 );
		}
		return self::rest_response( array(
			'rewrite_id' => $id,
			'edit_url'   => (string) get_edit_post_link( $id, 'raw' ),
		), 'success', 'Operation success', 201 );
	}

	public function republish( $request ) {
		try {
			// the block editor may still read the rewrite while this answer travels: remove it at the end
			$original = Republisher::republish( (int) $request['id'], false );
		} catch ( \Exception $e ) {
			return self::rest_response_error( 'republish_failed', $e->getMessage(), 400 );
		}
		return self::rest_response( array(
			'original_id' => $original,
			'edit_url'    => add_query_arg( 'rb_dp_republished', 1, (string) get_edit_post_link( $original, 'raw' ) ),
		) );
	}
}
