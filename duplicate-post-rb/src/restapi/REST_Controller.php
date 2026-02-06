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

// amazonq-ignore-next-line
defined( 'WPINC' ) || exit;

use rbDuplicatePost\Constants;

/**
 * Abstract Rest Controller Class
 *
 * @package RB DuplicatePost
 */
abstract class REST_Controller extends \WP_REST_Controller {

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'rb_duplicate_post/v1';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = '';

    public const MESSAGE_CODE = 'duplicate_post';

    public static function rest_response_error(
        $code = 'invalid_request',
        $message = 'Invalid request',
        $status = 400
    ) {
        $data             = array();
        $data[ 'result' ] = Constants::FAIL;
        $data[ 'status' ] = $status;
        $data[ 'data' ]   = array();

        return new \WP_Error(
            'rest_' . self::MESSAGE_CODE . '_' . $code,
            $message,
            $data
        );
    }

    public static function rest_response(
        $response_data = array(),
        $code = 'success',
        $desc = 'Operation success',
        $status = 200
    ) {
        $data             = array();
        $data[ 'result' ] = Constants::SUCCESS;
        $data[ 'status' ] = $status;
        $data[ 'data' ]   = $response_data;

        $response = new \WP_REST_Response(
            array(
                'code'    => 'rest_' . self::MESSAGE_CODE . '_' . $code,
                'message' => $desc,
                // 'status'  => $status,
                // 'result'  => Constants::SUCCESS,
                'data'    => $data
            )
        );
        $response->set_status( $status );
        return $response;
    }
}
