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
use rbDuplicatePost\ProfileOptionsConfig;
use rbDuplicatePost\ProfileOptions;
use rbDuplicatePost\Profile\Profile;
use rbDuplicatePost\User;
use rbDuplicatePost\restapi\RestUtils;
use rbDuplicatePost\restapi\Validations;

use \WP_REST_Request;
use \WP_REST_Response;
use \WP_REST_Server;
use \WP_Error;

/**
 * REST API Profile Options controller class.
 *
 * @package RB DuplicatePost\restapi
 */
class REST_Profile_Options_Controller extends REST_Controller
{

    public function __construct()
    {
        $this->rest_base = 'options';
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
            '/profiles/(?P<profile_id>[0-9]+)/options(?:/last_viewed)?',
            array(
                'args'   => array( ),
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_items'),
                    'permission_callback' => array($this, 'get_items_permissions_check'),
                    'args'                => array(
                        'profile_id' => array(
                            'description'       => __('RB Duplicate Post Profile ID.', 'duplicate-post-rb'),
                            'type'              => 'integer',
                            'sanitize_callback' => 'rbDuplicatePost\restapi\Sanitize::sanitize_profile_id', //for documentation only, not used in WP
                            'validate_callback' => 'rbDuplicatePost\restapi\Validations::validate_profile_id', //for documentation only, not used in WP
                        )
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'update_items'),
                    'permission_callback' => array($this, 'update_items_permissions_check'),
                    'args'                =>array(
                        'profile_id' => array(
                            'description'       => __('RB Duplicate Post Profile ID.', 'duplicate-post-rb'),
                            'type'              => 'integer',
                            'sanitize_callback' => 'rbDuplicatePost\restapi\Sanitize::sanitize_profile_id', //for documentation only, not used in WP
                            'validate_callback' => 'rbDuplicatePost\restapi\Validations::validate_profile_id', //for documentation only, not used in WP
                        ),
                    ),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/profiles/(?P<profile_id>[0-9]+)/options/(?P<option_id>[a-zA-Z0-9_-]+)',
            array(
                'args'   => array( ),
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_item'),
                    'permission_callback' => array($this, 'get_item_permissions_check'),
                    'args'                => array(
                        'option_id'  => array(
                            'description'       => __('RB Duplicate Profile Option id.', 'duplicate-post-rb'),
                            'type'              => 'string',
                            'sanitize_callback' => 'rbDuplicatePost\restapi\Sanitize::sanitize_option_id',
                            'validate_callback' => 'rbDuplicatePost\restapi\Validations::validate_option_id',
                        ),
                        'profile_id' => array(
                            'description'       => __('RB Duplicate Profile ID.', 'duplicate-post-rb'),
                            'type'              => 'integer',
                            'sanitize_callback' => 'rbDuplicatePost\restapi\Sanitize::sanitize_profile_id', //for documentation only, not used in WP
                            'validate_callback' => 'rbDuplicatePost\restapi\Validations::validate_profile_id', //for documentation only, not used in WP
                        ),
                        
                    ),
                ),
                array(
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'update_item'),
                    'permission_callback' => array($this, 'update_items_permissions_check'),
                    'args'                => array(
                        'option_id'  => array(
                            'description'       => __('RB Duplicate Profile Option id.', 'duplicate-post-rb'),
                            'type'              => 'string',
                            'sanitize_callback' => 'rbDuplicatePost\restapi\Sanitize::sanitize_option_id', 
                            'validate_callback' => 'rbDuplicatePost\restapi\Validations::validate_option_id',
                        ),
                        'profile_id' => array(
                            'description'       => __('RB Duplicate Profile ID.', 'duplicate-post-rb'),
                            'type'              => 'integer',
                            'sanitize_callback' => 'rbDuplicatePost\restapi\Sanitize::sanitize_profile_id', 
                            'validate_callback' => 'rbDuplicatePost\restapi\Validations::validate_profile_id',
                        ),
                    ),
                ),
            )
        );
    }


    /**
     * Return all options in a group.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function get_item($request)
    {

        $profile_id = absint($request['profile_id']);
        $option_id = sanitize_text_field($request['option_id']);
   
        $options = $this->get_option($profile_id, $option_id);

        if (is_wp_error($options)) {
            return $options;
        }

    
        $data = array();

        foreach ($options as $option) {
            $option = $this->prepare_item_for_response($option, $request);
            $option = $this->prepare_response_for_collection($option);
            if (RestUtils::isValidOptionType($option['type'])) {
                $data[] = $option;
            }
        }

        return rest_ensure_response($data);
    }

    /**
     * Return all options.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function get_items($request)
    {
        $profile_id = absint($request['profile_id']);
        $last_viewed = str_ends_with( $request->get_route(), '/last_viewed' );

        $options = $this->get_options($profile_id );

        if (is_wp_error($options)) {
            return $options;
        }

        if($last_viewed) {
            Profile::setLastViewedProfile($profile_id);
        }


        $data = array();

        foreach ($options as $option) {
            $option = $this->prepare_item_for_response($option, $request);
            $option = $this->prepare_response_for_collection($option);
            if (RestUtils::isValidOptionType($option['type'])) {
                $data[] = $option;
            }
        }

        return rest_ensure_response($data);
    }

    

    /**
     * Return a profile's option.
     *
     * @param  integer $profile_id $request .
     * @param  string  $option_id $request .
     * @return WP_Error|array
     */
    public function get_option($profile_id, $option_id)
    {
        return $this->get_options($profile_id, $option_id);
    }

    /**
     * Get all profile options.
     *
     * @return array|WP_Error
     */
    public function get_options($profile_id, $option_id = "")
    {
        $options = ProfileOptions::getOptionsFromDb($profile_id);

        $filtered_options = array();

        foreach ($options as $key => $option) {
            if ($option_id && $option['option_id'] !== $option_id) {
                continue;
            }
            $filtered_options[] = $option;
        }

        if (empty($filtered_options)) {
            return new \WP_Error(
                'rest_options_option_invalid',
                __('Invalid option id.', 'duplicate-post-rb'),
                array('status' => 404)
            );
        }

        return $filtered_options;
    }

    

    /**
     * Update a single setting.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function update_item($request)
    {

        if (!isset($request['value'])) {
            return new \WP_Error(
                'rest_options_options_empty',
                __('Empty option value.', 'duplicate-post-rb'),
                array('status' => 400)
            );
        }

        $request['items'] = array(
            array(
                "option_id" => $request['option_id'],
                "value"     => $request['value'],
            ),
        );
        unset($request['value']);

        return $this->update_items($request);
    }


    /**
     * Convert options to name array
     *
     * @param array $options
     * @return array
     */ 

    public function options_to_name_array($options)
    {
        $data = array();
        foreach ($options as $i => $option) {
            if (isset($option['option_id']) && $option['option_id']) {
                $data[$option['option_id']] = $option;
            }
        }
        return $data;
    }

    /**
     * Update options in a group.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function update_items($request)
    {
        $options = $this->get_options($request['profile_id']);

        if (is_wp_error($options)) {
            return $options;
        }

        $options = $this->options_to_name_array($options);

        if (!isset($request['items']) || !is_array($request['items']) || empty($request['items'])) {
            return new \WP_Error(
                'rest_options_options_empty',
                __('Empty options.', 'duplicate-post-rb'),
                array('status' => 400)
            );
        }

        $options_array = ProfileOptionsConfig::getOptions();

        $items = $request['items'];

        $data = array();

        foreach ($items as $key => $item) {

            if (!isset($item['option_id']) || !isset($item['value']) || !array_key_exists($item['option_id'], $options_array)) {
                continue;
            }

            $option_id = $item['option_id'];
            $value_in  = $item['value'];
            $option    = $options[$option_id];

            if (is_callable(array(Validations::class, 'validate_' . $option['type'] . '_field'))) {
                $value = Validations::{'validate_' . $option['type'] . '_field'}($value_in, $option);
            } else {
                $value =Validations::validate_text_field($value_in, $option);
            }

            if (is_wp_error($value)) {
                return $value;
            }

            $data[$option_id] = $value;
        }

        $profile_id = absint($request['profile_id']);

        ProfileOptions::updateOptionsInDb( $profile_id, $data);

        return $this->get_items($request);
    }

    /**
     * Update options in a group.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function update_group_items($request)
    {
        return $this->update_items($request);
    }


    /**
     * Read options from DB.
     *
     * @param integer $profile_id Gallery ID.
     * @return array|WP_Error
     */
    public static function get_options_from_db($profile_id)
    {
        $options = get_post_meta(
            (int) $profile_id,
            Constants::OPTION_NAME,
            true
        );

        if (!is_array($options)) {
            $options = array();
        }

        $options_data = ProfileOptionsConfig::getOptions();
        $return_data  = array();

        foreach ($options_data as $key => $option) {
            $option['value']   = array_key_exists($key, $options) ? $options[$key] : $option['default'];
            $return_data[$key] = $option;
        }

        return $return_data;
    }
    

    /**
     * Prepare a single setting object for response.
     *
     * @param array          $item Setting array.
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response $response Response data.
     */
    public function prepare_item_for_response($item, $request)
    {
        $data     = $this->filter_options($item);
        $data     = $this->add_additional_fields_to_object($data, $request);
        $data     = $this->filter_response_by_context($data, empty($request['context']) ? 'view' : $request['context']);
        $response = rest_ensure_response($data);
        return $response;
    }

    /**
     * Makes sure the current user has access to READ the settings APIs.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     */
    public function get_item_permissions_check($request)
    {
        $profile_id = absint($request->get_param("profile_id"));

        if (! $profile_id || ! User::canEditPost($profile_id) ) {
            return new \WP_Error(
                'rest_cannot_view',
                __('Sorry, you cannot list resources.', 'duplicate-post-rb'),
                array('status' => rest_authorization_required_code(),
                )
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
        $profile_id = absint($request->get_param("profile_id"));
        if (! $profile_id || ! User::canEditPost($profile_id) ) {
            return new \WP_Error(
                'rest_cannot_view',
                __('Sorry, you cannot list resources.', 'duplicate-post-rb'),
                array('status' => rest_authorization_required_code(),
                )
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
        $profile_id = absint($request->get_param("profile_id"));
        if (! $profile_id || ! User::canEditPost($profile_id) ) {
            return new \WP_Error(
                'rest_cannot_edit',
                __('Sorry, you cannot edit this resource.', 'duplicate-post-rb'),
                array('status' => rest_authorization_required_code(),
                )
            );
        }

        return true;
    }

    /**
     * Filters out bad values from the options array/filter so we
     * only return known values via the API.
     *
     * @since 3.0.0
     * @param  array $options Settings.
     * @return array
     */
    public function filter_options($options)
    {
        $options = array_intersect_key(
            $options,
            array_flip(array_filter(array_keys($options), array(RestUtils::class, 'isAllowedOptionIds')))
        );

        return $options;
    }
}
