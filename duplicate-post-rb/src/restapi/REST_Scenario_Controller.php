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

use rbDuplicatePost\Scenario\Scenario;
use rbDuplicatePost\User;
use WP_REST_Server;

/**
 * The Assistant's "try it on a post": POST /scenario/preview runs a scenario on
 * a post without copying anything and answers what would change.
 */
class REST_Scenario_Controller extends REST_Controller {

	public function __construct() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	public function register_routes() {
		register_rest_route( $this->namespace, '/scenario/preview', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => array( $this, 'preview' ),
			'permission_callback' => array( $this, 'permissions_check' ),
			'args'                => array(
				'post_id'  => array( 'type' => 'integer', 'required' => true ),
				'scenario' => array( 'type' => 'object', 'required' => true ),
				'answers'  => array( 'type' => 'object', 'default' => array() ),
			),
		) );
	}

	public function permissions_check( $request ) {
		return User::canManageOptions() && current_user_can( 'read_post', (int) $request['post_id'] );
	}

	public function preview( $request ) {
		$post = get_post( (int) $request['post_id'], ARRAY_A );
		if ( ! $post ) {
			return self::rest_response_error( 'invalid_post', 'Post not found', 404 );
		}
		$scenario            = Scenario::sanitize( (array) $request->get_param( 'scenario' ) );
		$scenario['enabled'] = true;
		$answers             = array();
		foreach ( (array) $request->get_param( 'answers' ) as $k => $v ) {
			if ( is_scalar( $v ) ) {
				$answers[ sanitize_key( (string) $k ) ] = sanitize_text_field( (string) $v );
			}
		}
		$env   = Scenario::env( $scenario, $post, $answers );
		$after = Scenario::run_phase( 'data', $scenario, $post, $env );

		$fields = array();
		foreach ( array( 'post_title' => 'title', 'post_excerpt' => 'excerpt', 'post_content' => 'content', 'post_status' => 'status' ) as $key => $name ) {
			$fields[ $name ] = array(
				'before'  => (string) $post[ $key ],
				'after'   => (string) $after[ $key ],
				'changed' => (string) $post[ $key ] !== (string) $after[ $key ],
			);
		}

		// the steps on the copy itself (custom fields, terms) describe what they would do
		$notes = array();
		$types = Scenario::step_types();
		foreach ( $scenario['steps'] as $step ) {
			$type = $types[ $step['type'] ] ?? null;
			if ( ! $type ) {
				$notes[] = array( 'type' => $step['type'], 'text' => __( 'This step needs Duplicate Post RB PRO and is skipped.', 'duplicate-post-rb' ), 'skipped' => true );
				continue;
			}
			if ( empty( $step['on'] ) ) {
				continue;
			}
			if ( $type['phase'] === 'after' && isset( $type['describe'] ) ) {
				$notes[] = array( 'type' => $step['type'], 'text' => (string) call_user_func( $type['describe'], $step, $post, $env ), 'skipped' => false );
			}
			if ( $step['type'] === 'replace_text' && ( $step['mode'] ?? '' ) === 'regex' && ! Scenario::regex_allowed() ) {
				$notes[] = array( 'type' => $step['type'], 'text' => __( 'Patterns (regular expressions) need Duplicate Post RB PRO: this step is skipped.', 'duplicate-post-rb' ), 'skipped' => true );
			}
		}

		return self::rest_response( array( 'fields' => $fields, 'notes' => $notes, 'tokens' => $env['tokens'] ) );
	}
}
