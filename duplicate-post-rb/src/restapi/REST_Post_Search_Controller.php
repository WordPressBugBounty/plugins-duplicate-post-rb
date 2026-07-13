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

namespace rbDuplicatePost\restapi;
defined('WPINC') || exit;

use \WP_REST_Request;
use \WP_REST_Response;
use \WP_REST_Server;
use \WP_Error;
use \WP_Post;

/**
 * REST API Post Search controller with query parameter support.
 *
 * @package RB DuplicatePost\restapi
 */
class REST_Post_Search_Controller extends REST_Controller
{
    const ALLOWED_POST_STATUSES = array('publish', 'private', 'draft', 'pending', 'future');

    public function __construct()
    {
        $this->rest_base = 'search';
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register routes.
     */
    public function register_routes()
    {
        register_rest_route(
            $this->namespace,
            '/search/post?s?/',
            array(
                'args' => array(
                    'search' => array(
                        'description' => __('Search query (post ID or title).', 'duplicate-post-rb'),
                        'type'        => 'string',
                        'required'    => false,
                        'sanitize_callback' => function($param, $request, $key) {
                            return sanitize_text_field($param);
                        },
                    ),

                    'check_id' => array(
                        'description' => __('Check only post ID and return only one result.', 'duplicate-post-rb'),
                        'type'        => 'string',
                        'validate_callback' => function($param, $request, $key) {
                            return is_numeric($param);
                        },
                        'required'    => false,
                        'sanitize_callback' => function($param, $request, $key) {
                            return absint($param);
                        },
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'search_posts_by_param'),
                    'permission_callback' => array($this, 'search_posts_permissions_check'),
                ),
            )
        );
        
        
    
    }

    /**
     * Search posts by search parameter.
     *
     * @param WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function search_posts_by_param($request)
    {
        $query = trim($request->get_param('search'));
        $check_id =  absint( $request->get_param('check_id') );
        
        if (empty($query) && !$check_id) {
            return new WP_Error(
                'rest_search_empty',
                __('Search parameter and check ID cannot be empty.', 'duplicate-post-rb'),
                array('status' => 400)
            );
        }

        $results = array();
        
        // Try as ID first
        if (is_numeric($query) || $check_id) {
            $post_id = $check_id ? $check_id : absint($query);
            $post = get_post($post_id);
            if ($post && $this->can_read_post($post)) {
                $results[] = $this->prepare_post_response($post);
            }
        }
        
        // If no ID match or search wasn't numeric, search by title
        if (empty($results) && !$check_id) {
            $search_args = array(
                's'              => $query,
                'post_type'      => 'any',
                'post_status'    => self::ALLOWED_POST_STATUSES,
                'posts_per_page' => 20,
                'orderby'        => 'relevance',
            );
            
            // Filter posts by user capabilities
            if (!current_user_can('edit_others_posts')) {
                $search_args['post_status'] = array('publish');
                $search_args['author'] = get_current_user_id();
            }
            
            $posts = get_posts($search_args);
            
            foreach ($posts as $post) {
                $results[] = $this->prepare_post_response($post);
            }
        }
        
        return rest_ensure_response($results);
    }

    /**
     * Check if current user can read the post.
     *
     * @param WP_Post $post Post object.
     * @return bool
     */
    private function can_read_post($post)
    {
        if( !($post instanceof WP_Post) ){
            return false;
        }

        if( in_array($post->post_status, self::ALLOWED_POST_STATUSES ) === false ){
            return false;
        }
        
        if ( !current_user_can( 'edit_post', $post->ID ) ) {
            return false;
        }
        
        return true;
    }

    /**
     * Prepare post response.
     *
     * @param WP_Post $post Post object.
     * @return array
     */
    private function prepare_post_response($post)
    {
        $response = array(
            'id'        => $post->ID,
            'title'     => get_the_title($post),
            'type'      => $post->post_type,
            'status'    => $post->post_status,
            'date'      => $post->post_date,
            'modified'  => $post->post_modified,
            'author'    => (int) $post->post_author,
            'permalink' => get_permalink($post),
        );
        
        return $response;
    }

    /**
     * Permission check.
     *
     * @param WP_REST_Request $request Request data.
     * @return bool|WP_Error
     */
    public function search_posts_permissions_check($request)
    {
        if (!is_user_logged_in()) {
            return new WP_Error(
                'rest_not_logged_in',
                __('You must be logged in to search.', 'duplicate-post-rb'),
                array('status' => 401)
            );
        }
        
        return true;
    }
}