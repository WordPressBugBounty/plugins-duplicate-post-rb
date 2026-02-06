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
use rbDuplicatePost\GeneralOptionsConfig;
use rbDuplicatePost\GeneralOptions;
use rbDuplicatePost\User;
use rbDuplicatePost\restapi\RestUtils;
use rbDuplicatePost\restapi\Validations;


use \WP_REST_Request;
use \WP_REST_Response;
use \WP_REST_Server;
use \WP_Error;

/**
 * REST API General Options controller class.
 *
 * @package RB DuplicatePost\restapi
 */
class REST_General_Options_Controller extends REST_Controller
{

    public function __construct()
    {
        $this->rest_base = 'general-options';
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
            '/general-options',
            array(
                'args'   => array( ),
                array(
                    'methods'             => \WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_items'),
                    'permission_callback' => array($this, 'get_items_permissions_check'),
                    'args'                => array(),
                ),
                array(
                    'methods'             => \WP_REST_Server::EDITABLE,
                    'callback'            => array($this, 'update_items'),
                    'permission_callback' => array($this, 'update_items_permissions_check'),
                    'args'                =>array(),
                ),
            )
        );
    }

    /**
     * Return all general options.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function get_items($request)
    {
        $options = $this->get_options();

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
     * Get all general options.
     *
     * @return array|WP_Error
     */
    public function get_options( $option_id = "")
    {
        $options = GeneralOptions::getOptionsFromDb();

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
     * Convert options to name array
     *
     * @param array $options
     * @return array
     */ 

    public static function optionsToNameArray($options)
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
        $options = $this->get_options();

        if (is_wp_error($options)) {
            return $options;
        }

        $options = self::optionsToNameArray($options);

        if (!isset($request['items']) || !is_array($request['items']) || empty($request['items'])) {
            return new \WP_Error(
                'rest_options_options_empty',
                __('Empty options.', 'duplicate-post-rb'),
                array('status' => 400)
            );
        }

        $options_array = GeneralOptionsConfig::getOptions();

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

        GeneralOptions::updateOptionsInDb( $data);

        return $this->get_items($request);
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
        if (!User::canManageOptions()) {
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
        if (! User::canManageOptions()) {
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

        if (! User::canManageOptions()) {
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
