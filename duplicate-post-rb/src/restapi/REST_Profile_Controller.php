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

namespace rbDuplicatePost\restapi;

defined('WPINC') || exit;

// Import required classes
use rbDuplicatePost\Constants;
use rbDuplicatePost\Profile\Profile;
use rbDuplicatePost\User;
use \WP_REST_Request;
use \WP_REST_Response;
use \WP_REST_Server;
use \WP_Error;

/**
 * REST API Setting Options controller class.
 *
 * @package RB DuplicatePost\restapi
 */
class REST_Profile_Controller extends REST_Controller
{
    public function __construct()
    {
        $this->rest_base = 'profiles';

        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register routes.
     *
     * @since 4.7.0
     */
    public function register_routes()
    {
        register_rest_route(
            $this->namespace,
            '/profiles',
            array(
                'args'   => array(),
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_items'),
                    'permission_callback' => array($this, 'get_items_permissions_check'),
                ),
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'create_item'),
                    'permission_callback' => array($this, 'create_item_permissions_check'),
                    'args'                => array(
                        'title' => array(
                            'description'       => __('RB Duplicate Profile Title.', 'duplicate-post-rb'),
                            'type'              => 'string',
                            'required'          => true,
                            'sanitize_callback' => 'rbDuplicatePost\restapi\Sanitize::sanitize_profile_title',
                            'validate_callback' => 'rbDuplicatePost\restapi\Validations::validate_profile_title',
                        ),
                    ),
                ),
                //'schema' => array($this, 'get_public_item_schema'),
            )
        );

        register_rest_route(
            $this->namespace,
            '/profiles/(?P<profile_id>[0-9]+)(?:/last_viewed)?',
            array(
                'args'   => array(),
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_item'),
                    'permission_callback' => array($this, 'get_item_permissions_check'),
                    'args'                => array(
                        'profile_id' => array(
                            'description'       => __('RB Duplicate Profile ID.', 'duplicate-post-rb'),
                            'type'              => 'integer',
                            'sanitize_callback' => 'rbDuplicatePost\restapi\Sanitize::sanitize_profile_id',
                            'validate_callback' => 'rbDuplicatePost\restapi\Validations::validate_profile_id',
                        )
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'update_item'),
                    'permission_callback' => array($this, 'update_item_permissions_check'),
                    'args'                => array(
                        'profile_id' => array(
                            'description'       => __('RB Duplicate Profile ID.', 'duplicate-post-rb'),
                            'type'              => 'integer',
                            'sanitize_callback' => 'rbDuplicatePost\restapi\Sanitize::sanitize_profile_id',
                            'validate_callback' => 'rbDuplicatePost\restapi\Validations::validate_profile_id',
                        ),
                    ),
                ),
                array(
                    'methods'             => WP_REST_Server::DELETABLE,
                    'callback'            => array($this, 'delete_item'),
                    'permission_callback' => array($this, 'delete_item_permissions_check'),
                    'args'                => array(
                        'profile_id' => array(
                            'description'       => __('RB Duplicate Profile ID.', 'duplicate-post-rb'),
                            'type'              => 'integer',
                            'sanitize_callback' => 'rbDuplicatePost\restapi\Sanitize::sanitize_profile_id',
                            'validate_callback' => 'rbDuplicatePost\restapi\Validations::validate_profile_id',
                        ),
                    ),
                ),
                //'schema' => array($this, 'get_public_item_schema'),
            )
        );
    }

    

    /**
     * Return all profiles.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function get_item($request)
    {
        $profile_id = absint($request->get_param("profile_id"));
        $last_viewed = str_ends_with( $request->get_route(), '/last_viewed' );

        if (! $profile_id) {
            return self::rest_response_error(
                'rest_profile_id_invalid',
                __('Invalid profile id.', 'duplicate-post-rb'),
                404
            );
        }
        $profiles = $this->get_profiles($profile_id);

        if($last_viewed) {
            Profile::setLastViewedProfile($profile_id);
        }

        $data = array();

        if (! is_array($profiles) || count($profiles) == 0) {
            return self::rest_response_error(
                'rest_profile_id_invalid',
                __('Invalid profile id.', 'duplicate-post-rb'),
                404
            );
        }
        $data = reset($profiles);

        return self::rest_response($data);
    }

    /**
     * Create  a  profiles.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function create_item($request)
    {
        $title = sanitize_text_field($request->get_param("title"));
        $title = substr($title, 0, 200);

        $profile_id = Profile::createProfile($title);

        if (! $profile_id || $profile_id <= 0) {
            return self::rest_response_error(
                'rest_profile_create_failed',
                __('Failed to create profile.', 'duplicate-post-rb'),
                array('status' => 500)
            );
        }

        $data = array(
            'id'    => $profile_id,
            'title' => $title,
            'default_profile' => false,
        );

        return self::rest_response($data, 'rest_profile_success', 'Operation success', 201);
    }

    /**
     * Update a profiles.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function update_item($request)
    {
        $profile_id = absint($request->get_param("profile_id"));

        $title = sanitize_text_field($request->get_param("title"));
        $title = substr($title, 0, 200);

        if (! $profile_id ) { //|| ! $title
            return self::rest_response_error();
        }

        $update_profile_id = Profile::updateProfile($profile_id, $title);

        if (! $update_profile_id || $update_profile_id <= 0) {
            return self::rest_response_error();
        }

        $default_profile_id = (int) get_option(Constants::OPTION_NAME_DEFAULT_PROFILE, 0);

        $data = array(
            'id'    => $update_profile_id,
            'title' => $title,
            'default_profile' => $update_profile_id ==$default_profile_id
        );

        return self::rest_response($data);
    }

    /**
     * Delete a profiles.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function delete_item($request)
    {
        $profile_id = absint($request->get_param("profile_id"));

        if (! $profile_id) {
             return self::rest_response_error(
                'rest_profile_delete_failed',
                __('Not found profile.', 'duplicate-post-rb'),
                array('status' => 404)
            );
        }

        $success = Profile::deleteProfile($profile_id);

        if (! $success) {
            return self::rest_response_error(
                'rest_profile_delete_failed',
                __('Failed to delete profile.', 'duplicate-post-rb'),
                array('status' => 500)
            );
        }

        return self::rest_response( $profile_id);
    }

    /**
     * Return all profiles.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function get_items($request)
    {
        $profiles = $this->get_profiles();

        $data = array();

        foreach ($profiles as $id => $profile) {
            $data[  ] = $profile;
        }

        return self::rest_response( $data );
    }

    /**
     * Get all profiles or a specific profile.
     *
     * @return array|WP_Error
     */
    public function get_profiles($profile_id = 0)
    {
        $profiles = self::get_profiles_from_db($profile_id);

        $filtered_profiles = array();

        $default_profile_id = Profile::getDefaultProfileId();

        foreach ($profiles as $id => $profile) {
            $profile[ 'default_profile' ] = $default_profile_id == $id ? true : false;
            $filtered_profiles[ $id ]     = $profile;
        }

        if (empty($filtered_profiles)) {
            return array();
        }

        return $filtered_profiles;
    }

    /**
     * Read profiles from database.
     *
     * @param integer $profile_id Profile ID.
     * @return array|WP_Error
     */
    public static function get_profiles_from_db($profile_id)
    {

        $args = array(
            'post_type'      => RB_DUPLICATE_POST_PROFILE_TYPE_POST,
            'posts_per_page' => -1,
            //'post_status'    => 'publish',
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        if ($profile_id) {
            $args[ 'include' ] = array($profile_id);
        }

        $all_profiles = get_posts($args);

        if (empty($all_profiles)) {
            return array();
        }

        $default_profile_id = (int) get_option(Constants::OPTION_NAME_DEFAULT_PROFILE, 0);

        $profiles = array();
        foreach ($all_profiles as $profile) {
            $profiles[ $profile->ID ] = array(
                'id'              => $profile->ID,
                'title'           => $profile->post_title,
                'default_profile' => $default_profile_id == $profile->ID,
            );
        }

        // if (! is_array($profiles)) {
        //     $profiles = array();
        // }

        return $profiles;
    }

    /**
     * Makes sure the current user has access to READ the profile APIs.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     */
    public function get_item_permissions_check($request)
    {
        $profile_id = absint($request->get_param("profile_id"));

        if (! $profile_id || ! User::canEditPost($profile_id) ) {
            return self::rest_response_error(
                'cannot_view',  
                'Sorry, you cannot list resources.',
                rest_authorization_required_code()
            );
        }
        return true;
    }

    /**
     * Makes sure the current user has access to READ the settings APIs.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     */
    public function get_items_permissions_check($request)
    {
        if (! User::canEditPosts()) {
            return self::rest_response_error(
                'cannot_view',  
                'Sorry, you cannot list resources.',
                rest_authorization_required_code()
            );
        }

        return true;
    }

    /**
     * Makes sure the current user has access to CREATE the profile APIs.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     */
    public function create_item_permissions_check($request)
    {
        if (! User::canEditPosts()) {
             return self::rest_response_error(
                'cannot_create',  
                'Sorry, you cannot create this resource.',
                rest_authorization_required_code()
            );
        }

        return true;
    }

    /**
     * Makes sure the current user has access to DELETE the profile APIs.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     */
    public function delete_item_permissions_check($request)
    {
        if (! User::canEditPosts()) {
            return self::rest_response_error(
                'cannot_delete',  
                'Sorry, you cannot delete this resource.',
                rest_authorization_required_code()
            );
        }

        return true;
    }

    /**
     * Makes sure the current user has access to WRITE the settings APIs.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     */
    public function update_item_permissions_check($request)
    {
        $profile_id = absint($request->get_param("profile_id"));
        if (! User::canEditPost($profile_id)) {
            return self::rest_response_error(
                'cannot_edit',  
                'Sorry, you cannot edit this resource.',
                rest_authorization_required_code()
            );
        }

        return true;
    }

    /**
     * Makes sure the current user has access to WRITE the settings APIs.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     */
    public function update_items_permissions_check($request)
    {
        if (! User::canEditPosts()) {
             return self::rest_response_error(
                'cannot_edit',  
                'Sorry, you cannot edit resources.',
                rest_authorization_required_code()
            );
        }

        return true;
    }
}
