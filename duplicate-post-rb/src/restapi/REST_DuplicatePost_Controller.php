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

defined( 'WPINC' ) || exit;

use rbDuplicatePost\Core\DuplicatorFactory;
use rbDuplicatePost\IDsParser;
use rbDuplicatePost\Profile\Profile;
use rbDuplicatePost\Notification;
use rbDuplicatePost\Constants;
use rbDuplicatePost\User;

use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class REST_DuplicatePost_Controller extends REST_Controller {

    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    public function register_routes() {

        register_rest_route(
            $this->namespace,
            '/duplicate/(?P<post_ids>[0-9,]+)',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( self::class, 'handle_duplicate' ),
                'permission_callback' => array( self::class, 'handle_duplicate_permissions_check' ),
                'args'                => array(
                    'post_ids' => array(
                        'description'       => __( 'RB Duplicate Post ID.', 'duplicate-post-rb' ),
                        'type'              => 'string',
                        'sanitize_callback' => 'rbDuplicatePost\restapi\Sanitize::sanitize_post_ids',
                        'validate_callback' => 'rbDuplicatePost\restapi\Validations::validate_post_ids',
                    ),
                ),
            ),
        );

        register_rest_route(
            $this->namespace,
            '/duplicate/(?P<post_ids>[0-9,]+)/profile/(?P<profile_id>[0-9]+)',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( self::class, 'handle_duplicate' ),
                'permission_callback' => array( self::class, 'handle_duplicate_permissions_check' ),
                'args'                => array(
                    'profile_id' => array(
                        'description'       => __( 'RB Duplicate Post Profile ID.', 'duplicate-post-rb' ),
                        'type'              => 'integer',
                        'sanitize_callback' => 'rbDuplicatePost\restapi\Sanitize::sanitize_profile_id',   
                        'validate_callback' => 'rbDuplicatePost\restapi\Validations::validate_profile_id',
                    ),
                    'post_ids'   => array(
                        'description'       => __( 'RB Duplicate Post IDs.', 'duplicate-post-rb' ),
                        'type'              => 'string',
                        'sanitize_callback' => 'rbDuplicatePost\restapi\Sanitize::sanitize_post_ids',
                        'validate_callback' => 'rbDuplicatePost\restapi\Validations::validate_post_ids',
                    ),
                ),
            ),

        );
    }

    public static function handle_duplicate( WP_REST_Request $request ) //: WP_REST_Response
     {

        $ids =  $request->get_param( 'post_ids' );
     
        if ( empty( $ids ) ) {
            return self::rest_response_error(
                'invalid_request',  
                'Missing Post ID',
                400 
            );
        }
 
        $profile_id = absint( $request->get_param( 'profile_id' ) );
        if ( !$profile_id ) {
            $profile_id = Profile::getDefaultProfileId();
        }

        
        $duplicator = DuplicatorFactory::make( 'post' );

         if (  $duplicator==null ) {
             return self::rest_response_error(
                 'invalid_request',  
                 'Service not available',
                 500
             );
         }

        $copies = absint($request->get_param( 'copies' ));
        $copies = !$copies || $copies>100 ? 1 : $copies;

        $no_refresh = $request->get_param( 'no_refresh' ) ? 1 : 0 ;

        $results    = array();
    
        foreach ( $ids as $id ) {
            for($i=0; $i<$copies; $i++) {
                try {
                    $newId = $duplicator->duplicate( $id, $profile_id );
                    $results[] = array(
                        'id'    => $id,
                        'duplicate_id'=> $newId,
                        'success' => true,
                    );
                } catch ( \Exception $e ) {
                    $results[] =  array(
                        'id'    => $id,
                        'duplicate_id'=> false,
                        'success' => false
                    );
                    //TODO : add error to log
                    // $results[ $id ] = array( 'error' => $e->getMessage() );
                }
            }
        }

        if(!$no_refresh){
            $notification = new Notification();
            $notification->set_notification(  array( 
                'type'=>Constants::NOTIFICATION_TYPE_POST_COPIED, 
                'data' => $results
             ) );
        }

        return self::rest_response(
            $results, 'success', 'Operation success', 201
        );
    }

    public static function handle_duplicate_permissions_check( $request ) {
        $ids = $request->get_param( "post_ids" ) ;

        if(!is_array($ids)|| empty($ids)) {
            return false;
        }
        
        $ids = array_map('absint', $ids);
        $ids = array_filter($ids);

        if (empty($ids)) {
            return false;
        }

        if ( ! User::canEditPost( $ids ) ) {
               return self::rest_response_error(
                 'cannot_access',
                 __( 'Sorry, you cannot access.', 'duplicate-post-rb' ),
                 rest_authorization_required_code(),
               );
        }

        return true;
    }
}
