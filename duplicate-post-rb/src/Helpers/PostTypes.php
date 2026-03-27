<?php
namespace rbDuplicatePost\Helpers;

defined( 'WPINC' ) || exit;


use rbDuplicatePost\GeneralOptions;



class PostTypes {

    // post types that are disabled
    const DISABLED_POST_TYPES = array(
        'revision',
    );

    // post types that are need specail processing
    const SPECIAL_POST_TYPES = array(
        'acf' => array(
            'acf-field-group',
            'acf-field',
            'acf-post-type',
            'acf-taxonomy'
        ),
    );

    // post types that are built-in wordpress
    const BUILTIN_POST_TYPES = array(
        //'post',                
        //'page',               
        'attachment',          
        //'revision',   moved to ignore list
        'nav_menu_item',       
        'custom_css',          
        'customize_changeset',
    );

    const POST_TYPE = 'post';
    const PAGE_TYPE = 'page';
    const ATTACHMENT_TYPE = 'attachment';

    /**
     * Get list of custom post types (slug => label)
     * Format: slug => label
     * 
     * @param bool $only_ui_types Only post types with UI
     * @param int|null $blog_id Optional blog ID for multisite
     * @return array<string>
     */
    public static function get_custom_types(  bool $only_ui_types = false, ?int $blog_id = null ): array {
        $exclude_builtin = true;
        return self::get_types($exclude_builtin, $only_ui_types, $blog_id ); 
    }

    /**
     * Get all post types 
     * 
     * @param bool $exclude_builtin Exclude built-in post types
     * @param bool $only_ui_types Only post types with UI
     * @param int|null $blog_id Optional blog ID for multisite
     * @return array<string>
     */
    public static function get_types(
        bool $exclude_builtin = false,
        bool $only_ui_types = false,
        ?int $blog_id = null
    ): array {
        return self::with_blog( $blog_id, function () use ( $exclude_builtin, $only_ui_types ) {

            $args = array(); 

            if($exclude_builtin){
                $args['_builtin'] = false;
            }

            if($only_ui_types){
                $args['show_ui'] = true;
            }

            return get_post_types( $args, 'names' );
        });
    }

    /**
     * Check if custom post type exists
     * 
     * @param string $slug
     * @param int|null $blog_id Optional blog ID for multisite
     * @return bool
     */
    public static function exists(
        string $slug,
        ?int $blog_id = null
    ): bool {
        return self::with_blog( $blog_id, function () use ( $slug ) {
            return post_type_exists( $slug );
        });
    }

    /**
     * Helper to switch blog if needed
     * 
     * @param int|null $blog_id Optional blog ID for multisite
     * @param callable $callback
     * @return mixed
     */
    private static function with_blog( ?int $blog_id, callable $callback ) {
        $switched = false;

        if (
            $blog_id &&
            is_multisite() &&
            get_current_blog_id() !== $blog_id
        ) {
            switch_to_blog( $blog_id );
            $switched = true;
        }

        try {
            return $callback();
        } finally {
            if ( $switched ) {
                restore_current_blog();
            }
        }
    }

    /**
     * Get  supported  type 
     * 
     * @param  int|null $blog_id Optional blog ID for multisite
     * @return array
     */
    public static function get_all_types( ?int $blog_id = null ): array {
        $types = self::get_types( true, true, $blog_id );

        if(!isset($types['post'])){
            $types['post']='post';
        }
        if(!isset($types['page'])){
            $types['page']='page';
        }

        // Allow other developers to modify supported post types via filter
        $types = apply_filters('rb_duplicate_post_supported_types', $types, $blog_id); //

        return  $types;
    }

    /**
     * Check supported  type
     * 
     * @param string $type  type slug
     * @param int|null $blog_id Optional blog ID for multisite
     * @return bool
     */
    public static function is_type_support( string $type, ?int $blog_id = null ): bool {

        $type = trim($type);

        if($type===''){
            return false; //surely it's not allowed
        }

        // check if type is disabled in predefined list
        if( in_array($type, self::DISABLED_POST_TYPES) ){
            return false; //surely it's not allowed
        }

        // check if type is post and restricted
        $post_type_restricted = self::is_restricted_post_type($type);
        if( $post_type_restricted !== null ){
            return !$post_type_restricted;
        }

        // check if type is page and restricted
        $page_type_restricted = self::is_restricted_page_type($type);
        if( $page_type_restricted !== null ){
            return !$page_type_restricted;
        }

        // check if type is disabled in predefined list
        $disabledCustomPostTypes = self::get_user_disabled_types();
        if(in_array($type, $disabledCustomPostTypes)){
            return false; //surely it's not allowed
        }

        // check if type is special
         $special_type = self::is_allowed_special_type($type);
        if( $special_type !== -1 ){
            return $special_type ? true : false;
        }

        // check if type is built-in
        if( in_array($type, self::BUILTIN_POST_TYPES) ){
            return true;
        }
 
        if( !GeneralOptions::getOptionValue( 'enableCustomPostType' ) ){
            return false;
        }

        $types = self::get_all_types( $blog_id );
        if(!array_key_exists($type, $types)){
            return false;
        }

        return true;
    }

    private static function is_restricted_post_type($type){
        if( $type !==self::POST_TYPE) {
            return null;
        }
        return (bool) !GeneralOptions::getOptionValue( 'enablePostPostType' );
    }

    private static function is_restricted_page_type($type){
        if( $type !==self::PAGE_TYPE) {
            return null;
        }
        return (bool) !GeneralOptions::getOptionValue( 'enablePagePostType' );
    }

    /**
     * Check if special post type is allowed based on settings
     * 
     * @param string $type Post type slug
     * @return int 1 if allowed, 0 if not allowed, -1 if not a special post type
     */
    public static function is_allowed_special_type( string $type ): int {
        
        if ( self::is_special_type( $type, 'yoast_seo' ) ) {
            return  GeneralOptions::getOptionValue( 'enableTypeYoastSEO' ) ? 1 : 0;
        }

        if ( self::is_special_type( $type, 'acf' ) ) {
            return GeneralOptions::getOptionValue( 'enableTypeACF' ) ? 1 : 0;
        }

        return -1   ; // not a special type 
    }

    private static function is_special_type($type, $special_type){
        if(!array_key_exists($special_type, self::SPECIAL_POST_TYPES)){
            return false;
        }
        return in_array($type, self::SPECIAL_POST_TYPES[$special_type], true);
    }



    // Get disabled types from user settings
    public static function get_user_disabled_types() : array{
        $disabledCustomPostTypes =  GeneralOptions::getOptionValue( 'disabledCustomPostTypes' );

        if (!is_string($disabledCustomPostTypes)) {
            return array();
        }

        // Normalize line breaks (\r\n, \r → ;)
        $lines = str_replace(["\r\n", "\r", ";"], ",", $disabledCustomPostTypes);

        // Split into lines
        $lines = explode(",", $lines);

        // Sanitize each line
        $sanitized_lines = array_map(function($line) {
            return sanitize_text_field(trim($line));
        }, $lines);

        // Remove empty lines
        $sanitized_lines = array_filter($sanitized_lines, function($line) {
            return trim($line) !== '';
        });

        return  $sanitized_lines;
    }
}
