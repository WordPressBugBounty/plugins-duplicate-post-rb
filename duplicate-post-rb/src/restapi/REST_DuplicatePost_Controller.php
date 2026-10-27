<?php
/* 
*      RB Duplicate Post     
*      Version: 1.6.8
*      By RbPlugin
*
*      Contact: https://rbplugins.com 
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
use rbDuplicatePost\ProCheck;
use rbDuplicatePost\Helpers\Multisite;

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
                    'target_blog_id' => array(
                        'description'       => __( 'Target Multisite blog ID (0 = current).', 'duplicate-post-rb' ),
                        'type'              => 'integer',
                        'required'          => false,
                        'default'           => 0,
                        'sanitize_callback' => 'rbDuplicatePost\restapi\Sanitize::sanitize_blog_id',
                    ),
                    'source_blog_id' => array(
                        'description'       => __( 'Source Multisite blog ID (0 = current).', 'duplicate-post-rb' ),
                        'type'              => 'integer',
                        'required'          => false,
                        'default'           => 0,
                        'sanitize_callback' => 'rbDuplicatePost\restapi\Sanitize::sanitize_blog_id',
                    ),
                ),
            ),
        );

        register_rest_route(
            $this->namespace,
            '/sites',
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( self::class, 'handle_list_sites' ),
                'permission_callback' => array( self::class, 'handle_list_sites_permissions_check' ),
            ),
        );

        register_rest_route(
            $this->namespace,
            '/create_from_profile/(?P<profile_id>[0-9]+)',
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( self::class, 'handle_create' ),
                'permission_callback' => array( self::class, 'handle_create_permissions_check' ),
                'args'                => array(
                    'profile_id' => array(
                        'description'       => __( 'RB Duplicate Post Profile ID.', 'duplicate-post-rb' ),
                        'type'              => 'integer',
                        'sanitize_callback' => 'rbDuplicatePost\restapi\Sanitize::sanitize_profile_id',   
                        'validate_callback' => 'rbDuplicatePost\restapi\Validations::validate_profile_id',
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
                    'target_blog_id' => array(
                        'description'       => __( 'Target Multisite blog ID (0 = current).', 'duplicate-post-rb' ),
                        'type'              => 'integer',
                        'required'          => false,
                        'default'           => 0,
                        'sanitize_callback' => 'rbDuplicatePost\restapi\Sanitize::sanitize_blog_id',
                    ),
                    'source_blog_id' => array(
                        'description'       => __( 'Source Multisite blog ID (0 = current).', 'duplicate-post-rb' ),
                        'type'              => 'integer',
                        'required'          => false,
                        'default'           => 0,
                        'sanitize_callback' => 'rbDuplicatePost\restapi\Sanitize::sanitize_blog_id',
                    ),
                ),
            ),

        );

        
    }


    public static function handle_create( WP_REST_Request $request ) //: WP_REST_Response
     {

        $profile_id =  absint( $request->get_param( 'profile_id' ) );
     
         if ( !$profile_id ) {
             return self::rest_response_error(
                 'invalid_request',  
                 'Missing Profile ID',
                 400 
             );
         }
        
        $duplicator = DuplicatorFactory::make( 'post' );

         if (  $duplicator==null ) {
             return self::rest_response_error(
                 'invalid_request',  
                 'Service not available',
                 500
             );
         }

        $no_refresh = $request->get_param( 'no_refresh' ) ? 1 : 0 ;

        // the answers to the Assistant's questions, from the copy dialog
        \rbDuplicatePost\Scenario\Scenario::set_answers( (array) $request->get_param( 'answers' ) );

        $results    = array();
        $id         = 0;

        try {
            $target_blog_id = absint( $request->get_param( 'target_blog_id' ) );
            $ids = $duplicator->create_duplicate( $profile_id, $target_blog_id );
            
            if( is_array($ids) && isset($ids['source_id']) && isset($ids['new_id']) ){
                $id = $ids['source_id'] ?? 0;
                $new_id = $ids['new_id'] ?? 0;

                 $results[] = array(
                    'id'    => $id,
                    'duplicate_id'=> $new_id,
                    'edit_url' => ( ! $target_blog_id || $target_blog_id === get_current_blog_id() ) ? (string) get_edit_post_link( $new_id, 'raw' ) : '',
                    'title'    => ( ! $target_blog_id || $target_blog_id === get_current_blog_id() ) ? get_the_title( $new_id ) : '',
                    'success' => true,
                    'code' => 'create_success',
                );
            } else {
                $results[] = array(
                    'id'    => 0,
                    'duplicate_id'=> false,
                    'success' => false,
                    'code' => 'error_during_creation',
                    'error' => 'Unexpected result from duplicator',
                );
            }
           
        } catch ( \Exception $e ) {
            $results[] =  array(
                'id'    => $id,
                'duplicate_id'=> false,
                'success' => false,
                'code' => 'error_during_creation',
                'error' => $e->getMessage(),
            );
            //TODO : add error to log
            // $results[ $id ] = array( 'error' => $e->getMessage() );
        }

        if(!$no_refresh){
            $notification = new Notification();
            $notification->set_notification(  array( 
                'type'=>Constants::NOTIFICATION_TYPE_POST_CREATED, 
                'data' => $results
             ) );
        }

        return self::rest_response(
            $results, 
            'success', 
            'Operation success', 
            201
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
 
        // 1 to 100 copies, as the dialog says: more is refused, not silently changed
        if ( absint( $request->get_param( 'copies' ) ) > 100 ) {
            return self::rest_response_error(
                'invalid_request',
                __( 'At most 100 copies at a time.', 'duplicate-post-rb' ),
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

        // "Advanced settings" of the copy dialog: which elements this copy gets, for this request only
        $overrides = self::element_overrides( $request->get_param( 'options' ) );
        $override_filter = null;
        if ( $overrides ) {
            $override_filter = function ( $options ) use ( $overrides ) {
                foreach ( $overrides as $key => $value ) {
                    if ( isset( $options[ $key ] ) && is_array( $options[ $key ] ) ) {
                        $options[ $key ]['value'] = $value;
                    }
                }
                return $options;
            };
            add_filter( 'rb_duplicate_post_copy_options', $override_filter );
        }

        $copies = max( 1, absint( $request->get_param( 'copies' ) ) );

        $no_refresh = $request->get_param( 'no_refresh' ) ? 1 : 0 ;

        // the answers to the Assistant's questions, from the copy dialog
        \rbDuplicatePost\Scenario\Scenario::set_answers( (array) $request->get_param( 'answers' ) );

        $results    = array();

        $success_count = 0;
        $unsuccess_count = 0;
    
        foreach ( $ids as $id ) {
            for($i=0; $i<$copies; $i++) {
                try {
                    if( $duplicator->is_allowed_special_post($id)===1 && !ProCheck::isActive()) {
                        $results[] =  array(
                            'id'    => $id,
                            'duplicate_id'=> 0,
                            'success' => false,
                            'code' => 'pro_version_not_active',
                            'error' => 'Duplicate Post Pro is not active',
                        );
                        ++$unsuccess_count;
                        continue;
                    }

                    $target_blog_id = absint( $request->get_param( 'target_blog_id' ) );
                    $source_blog_id = absint( $request->get_param( 'source_blog_id' ) );
                    $newId = $duplicator->duplicate( $id, $profile_id, $target_blog_id, $source_blog_id );
                    $results[] = array(
                        'id'    => $id,
                        'duplicate_id'=> $newId,
                        'edit_url' => ( ! $target_blog_id || $target_blog_id === get_current_blog_id() ) ? (string) get_edit_post_link( $newId, 'raw' ) : '',
                        'title'    => ( ! $target_blog_id || $target_blog_id === get_current_blog_id() ) ? get_the_title( $newId ) : '',
                        'target_blog_id' => $target_blog_id ? $target_blog_id : get_current_blog_id(),
                        'success' => true,
                        'code' => 'duplicate_success',
                    );
                    ++$success_count;
                } catch ( \Exception $e ) {
                    $results[] =  array(
                        'id'    => $id,
                        'duplicate_id'=> false,
                        'success' => false,
                        'code' => 'error_during_duplication',
                        'error' => $e->getMessage(),
                    );
                    ++$unsuccess_count;
                    //TODO : add error to log
                    // $results[ $id ] = array( 'error' => $e->getMessage() );
                }
            }
        }

        if ( $override_filter ) {
            remove_filter( 'rb_duplicate_post_copy_options', $override_filter );
        }

        if(!$no_refresh){
            $notification = new Notification();
            $notification->set_notification(  array( 
                'type'=>Constants::NOTIFICATION_TYPE_POST_COPIED, 
                'data' => $results
             ) );
        }

        return self::rest_response(
            $results, 
            'success', 
            'Operation success', 
            201
        );
    }

    /**
     * The element switches a copy may change for itself: copy* options only, as booleans.
     */
    private static function element_overrides( $raw ) {
        if ( ! is_array( $raw ) ) {
            return array();
        }
        $out = array();
        foreach ( $raw as $key => $value ) {
            if ( is_string( $key ) && preg_match( '/^copy[A-Z][A-Za-z]+$/', $key ) ) {
                $out[ $key ] = (bool) rest_sanitize_boolean( $value );
            }
        }
        return $out;
    }

    public static function handle_create_permissions_check( $request ) {
        $id = absint( $request->get_param( "profile_id" ) );

        if(!$id) {
            return false;
        }

        // a template is used, not edited: any profile serves whoever may copy here (as in the
        // "+ New" menu); what it could give beyond the user's own rights is checked on the way -
        // its source post here, the copy's author and status while the copy is made
        if ( ! User::isAllowForCurrentUser() || ! User::canPublishPosts() ) {
               return self::rest_response_error(
                 'cannot_access',
                 __( 'Sorry, you cannot access.', 'duplicate-post-rb' ),
                 rest_authorization_required_code(),
               );
        }

        $source_id = Profile::getProfileSourceId( $id );
        if ( $source_id && ! Profile::canUseAsTemplate( $source_id ) ) {
               return self::rest_response_error(
                 'cannot_access',
                 __( 'Sorry, you cannot access.', 'duplicate-post-rb' ),
                 rest_authorization_required_code(),
               );
        }

        return true;
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

        // the roles allowed in the settings, as for the Copy buttons; the posts themselves
        // are checked on the site they are copied from (on a network, IDs repeat across sites)
        $source_blog_id = absint( $request->get_param( 'source_blog_id' ) );
        if ( ! User::isAllowForCurrentUser() || ! Multisite::can_user_edit_posts_on_blog( $ids, $source_blog_id ) ) {
               return self::rest_response_error(
                 'cannot_access',
                 __( 'Sorry, you cannot access.', 'duplicate-post-rb' ),
                 rest_authorization_required_code(),
               );
        }

        return true;
    }


    public static function handle_list_sites_permissions_check( $request ) {
        return is_user_logged_in() && current_user_can( 'edit_posts' );
    }

    /**
     * List Multisite blogs the current user can copy posts to.
     */
    public static function handle_list_sites( WP_REST_Request $request ) {
        $sites = \rbDuplicatePost\Helpers\Multisite::get_available_target_blogs();
        return self::rest_response(
            array(
                'sites'              => $sites,
                'cross_site_enabled' => \rbDuplicatePost\Helpers\Multisite::is_cross_site_enabled(),
                'media_mode'         => \rbDuplicatePost\Helpers\Multisite::get_media_mode(),
                'current_blog_id'    => get_current_blog_id(),
                'is_multisite'       => is_multisite(),
            ),
            'success',
            'OK',
            200
        );
    }
}
