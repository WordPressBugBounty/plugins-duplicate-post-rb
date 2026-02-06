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
use rbDuplicatePost\Profile\ProfileImporter;
use \WP_REST_Request;
use \WP_REST_Response;
use \WP_REST_Server;
use \WP_Error;

/**
 * REST API Profile Import controller class.
 *
 * @package RB DuplicatePost\restapi
 */
class REST_Profile_Import_Controller extends REST_Controller
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
            '/profiles/import',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array($this, 'create_item'),
                    'permission_callback' => array($this, 'create_item_permissions_check'),
                ),
            )
        );
    }

    /**
     * Import profiles from JSON data.
     *
     * @param  WP_REST_Request $request Request data.
     * @return WP_Error|WP_REST_Response
     */
    public function create_item($request)
    {

         // Check if file was uploaded
        if ( ! isset( $_FILES['profile_file'] ) ) {
            return self::rest_response_error(
                'no_file_uploaded',
                'No file was uploaded.',
                400
            );
        }

        $file = $_FILES['profile_file'];
         // Validate upload errors
        if ( $file['error'] !== UPLOAD_ERR_OK ) {
            $error_messages = array(
                UPLOAD_ERR_INI_SIZE   => 'File size exceeds upload_max_filesize directive.',
                UPLOAD_ERR_FORM_SIZE  => 'File size exceeds MAX_FILE_SIZE directive.',
                UPLOAD_ERR_PARTIAL   => 'File was only partially uploaded.',
                UPLOAD_ERR_NO_FILE    => 'No file was uploaded.',
                UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                UPLOAD_ERR_EXTENSION  => 'File upload stopped by extension.',
            );
            
            $error_msg = isset( $error_messages[ $file['error'] ] ) 
                ? $error_messages[ $file['error'] ] 
                : 'Unknown upload error.';
                
            return self::rest_response_error(
                'upload_error',
                $error_msg,
                400
            );
        }

         // Validate file type (should be .txt)
        $file_extension = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );
        if ( $file_extension !== 'txt' ) {
            return self::rest_response_error(
                'invalid_file_type',
                'Only .txt files are allowed for profile import.',
                400
            );
        }

        // Validate file size (optional - you can adjust limit)
        $max_file_size = 5 * 1024 * 1024; // 5MB
        if ( $file['size'] > $max_file_size ) {
            return self::rest_response_error(
                'file_too_large',
                'File size exceeds 5MB limit.',
                400
            );
        }

        // Read file content
        $file_content = file_get_contents( $file['tmp_name'] );
        if ( $file_content === false ) {
            return self::rest_response_error(
                'file_read_error',
                'Failed to read uploaded file.',
                500
            );
        }

        // Validate JSON content
        $decoded = json_decode( $file_content, true );
        if ( json_last_error() !== JSON_ERROR_NONE ) {
            return self::rest_response_error(
                'invalid_json',
                'Uploaded file does not contain valid JSON: ' . json_last_error_msg(),
                400
            );
        }

        // Validate input
        if ( empty( $decoded ) ) {
            return self::rest_response_error(
                'invalid_json',
                'No valid JSON data provided for import.',
                400
            );
        }

         // Perform import
        $result = ProfileImporter::import_profiles( $file_content );

        if ( ! $result['success'] && empty( $result['imported'] ) && empty( $result['skipped'] ) ) {
            $error_message = ! empty( $result['errors'] ) 
                ? implode( '; ', $result['errors'] )
                : 'Import failed with no specific error.';
                
            return self::rest_response_error(
                'import_failed',
                $error_message,
                400
            );
        }

        // Clean up temp file
        @unlink( $file['tmp_name'] );

        // Return success response
        return self::rest_response(
            $result,
            'import_success',
            'Profiles imported successfully.',
            200
        );
    }

    /**
     * Makes sure the current user has access to CREATE/IMPORT profiles.
     *
     * @param WP_REST_Request $request Full data about the request.
     * @return WP_Error|boolean
     */
    public function create_item_permissions_check($request)
    {
        if (! User::canUpdateProfile()) {
            return self::rest_response_error(
                'cannot_create',
                'Sorry, you do not have permission to import profiles.',
                rest_authorization_required_code()
            );
        }

        return true;
    }

    /**
     * Get the item schema for the controller.
     *
     * @return array
     */
    public function get_item_schema()
    {
        if ( $this->schema ) {
            return $this->add_additional_fields_schema( $this->schema );
        }

        $this->schema = array(
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'profile_import',
            'type'       => 'object',
            'properties' => array(
                'profiles' => array(
                    'description' => 'Array of profile objects to import.',
                    'type'        => 'array',
                    'items'       => array(
                        'type'       => 'object',
                        'properties' => array(
                            'ID'           => array( 'type' => 'integer' ),
                            'title'        => array( 'type' => 'string' ),
                            'slug'         => array( 'type' => 'string' ),
                            'date_created' => array( 'type' => 'string' ),
                            'date_updated' => array( 'type' => 'string' ),
                            'post_type'    => array( 'type' => 'string' ),
                            'meta'         => array( 'type' => 'object' ),
                        ),
                    ),
                ),
                'export_info' => array(
                    'description' => 'Export metadata information.',
                    'type'        => 'object',
                ),
            ),
        );

        return $this->add_additional_fields_schema( $this->schema );
    }

}