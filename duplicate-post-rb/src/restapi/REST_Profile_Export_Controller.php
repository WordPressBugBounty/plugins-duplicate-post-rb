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
use rbDuplicatePost\User;
use rbDuplicatePost\Profile\ProfileExporter;
use \WP_REST_Request;
use \WP_REST_Response;
use \WP_REST_Server;
use \WP_Error;

/**
 * REST API Setting Options controller class.
 *
 * @package RB DuplicatePost\restapi
 */
class REST_Profile_Export_Controller extends REST_Controller
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
            '/profiles/export',
            array(
                'args'   => array(),
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array($this, 'get_items'),
                    'permission_callback' => array($this, 'get_items_permissions_check'),
                ),
            )
        );
    }

    /**
     * Return all profiles.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function get_items($request)
    {
        return ProfileExporter::export_all_profiles();
    }

    /**
     * Makes sure the current user has access to READ the profiles APIs.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     */
    public function get_items_permissions_check($request)
    {
        if (! User::canUpdateProfile()) {
            return self::rest_response_error(
                'cannot_view',  
                'Sorry, you cannot list resources.',
                rest_authorization_required_code()
            );
        }

        return true;
    }
}
