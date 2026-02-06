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

namespace rbDuplicatePost;

defined( 'WPINC' ) || exit;

class User {

    const PLACES = array(
        'list',
        'edit',
        'admin-bar-menu',
        'bulk',
        'gutenberg',
    );

    public static function canDuplicatePosts(): bool {
        return current_user_can( 'manage_options' );
    }

    public static function canUpdateProfile(): bool {
        return current_user_can( 'manage_options' );
    }

    public static function canManageOptions(): bool {
        return current_user_can( 'manage_options' );
    }

    /**
     * Check if the current user can edit posts.
     *
     * @return bool
     */
    public static function canEditPosts(): bool
    { 
        return current_user_can('edit_posts');
    }

    public static function canPublishPosts(): bool
    { 
        return current_user_can('publish_posts');
    }
    /**
     * Check if the current user can edit posts.
     *
     * @return bool
     */
    public static function canEditPost( $ids ): bool
    {   
        if(!$ids){
            return false;
        }

        if(!is_array($ids)) {
            $ids = explode(',', $ids);
        }   

        $ids = array_map('absint', $ids);
        $ids = array_filter($ids);

        if (empty($ids)) {
            return false;
        }

        $canEdit = current_user_can('edit_post', $ids);

        if($canEdit) {
            return true;
        }

        foreach ($ids as $id) {
            if(!current_user_can('edit_post', $id)) {
                return false;
            }
        }
        return true;
    }

    static function getUserRolesByUserId( int $user_id ) {
        $user = get_userdata( $user_id );
        return empty( $user ) ? array() : $user->roles;
    }

    public static function is_user_in_role( int $user_id, string $role ) {
        return in_array( $role, self::getUserRolesByUserId( $user_id ) );
    }

    /**
     * Check if allow for current user.
     *
     * @return bool
     */
    public static function isAllowForCurrentUser(): bool {
        $user_id = self::get_user_id();

        if ( 0 === $user_id ) {
            return false;
        }

        $user_rules = self::getUserRolesByUserId( $user_id );
        if ( empty( $user_rules ) ) {
            return false;
        }

        $allowForSuperAdmins = GeneralOptions::getOptionValue( 'allowForSuperAdmins' );
        if ( $allowForSuperAdmins && in_array( 'superadmin', $user_rules ) ) {
            return true;
        }

        $allowForAdministrators = GeneralOptions::getOptionValue( 'allowForAdministrators' );
        if ( $allowForAdministrators && in_array( 'administrator', $user_rules ) ) {
            return true;
        }

        $allowForEditors = GeneralOptions::getOptionValue( 'allowForEditors' );
        if ( $allowForEditors && in_array( 'editor', $user_rules ) ) {
            return true;
        }

        $allowForAuthors = GeneralOptions::getOptionValue( 'allowForAuthors' );
        if ( $allowForAuthors && in_array( 'author', $user_rules ) ) {
            return true;
        }

        $allowForContributors = GeneralOptions::getOptionValue( 'allowForContributors' );
        if ( $allowForContributors && in_array( 'contributor', $user_rules) ) {
            return true;
        }

        $allowForSubscribers = GeneralOptions::getOptionValue( 'allowForSubscribers' );
        if ( $allowForSubscribers && in_array( 'subscribers', $user_rules ) ) {
            return true;
        }

        return false;
    }

    /**
     * Check if allow place for display.
     *
     * @param string $place
     * @return bool
     */

    public static function isEnableForPlace( string $place ): bool {
        $place = strtolower( $place );

        if ( '' === $place ) {
            return false;
        }

        if ( ! in_array( $place, self::PLACES ) ) {
            return false;
        }

        if( $place==='list' ){
            return  GeneralOptions::getOptionValue( 'showOnLists' ) ? true : false;
        }

        if( $place==='edit' ){
            return  GeneralOptions::getOptionValue( 'showOnEdit' ) ? true : false;
        }

        if( $place==='admin-bar-menu' ){
            return  GeneralOptions::getOptionValue( 'showOnAdminBar' ) ? true : false;
        }

        if( $place==='gutenberg' ){
            return  GeneralOptions::getOptionValue( 'showOnGutenberg' ) ? true : false;
        }

        if( $place==='bulk' ){
            return  GeneralOptions::getOptionValue( 'showOnBulkActions' ) ? true : false;
        }
        
        return false;
    }


    public static function get_user_id( ) {
        if (  is_user_logged_in() ) {
            $current_user_id = get_current_user_id();
            if($current_user_id ){
                return $current_user_id;
            }
        }
        return false;
    }


    /**
     * Returns the ID of a user with administrator or super admin capabilities.
     *
     * In Multisite:
     *   - First tries to find a **super admin** (highest privilege)
     *   - Falls back to a regular **administrator** if no super admin is found
     *
     * In single-site:
     *   - Returns the first user with the 'administrator' role
     *
     * @return int|false User ID on success, false if no admin user found.
     */
    public static function get_admin_user_id() {

        // Multisite: try super admins first
        if ( is_multisite() ) {
            $super_admins = get_super_admins();
            if ( ! empty( $super_admins ) ) {
                // Get ID of the first super admin
                $first_super_admin_login = $super_admins[0];
                $user = get_user_by( 'login', $first_super_admin_login );
                if ( $user ) {
                    return $user->ID;
                }
            }
        }

        // Fallback: find any user with 'administrator' role
        $admin_users = get_users( array(
            'role'    => 'administrator',
            'number'  => 1,
            'fields'  => 'ID',
        ) );

        if ( ! empty( $admin_users ) ) {
            return (int) $admin_users[0]->ID;
        }

        return false;
    }

    /**
     * Checks if a given user ID has admin or super admin capabilities.
     *
     * @param int $user_id
     * @return bool
     */
    public static function is_user_admin_or_super_admin( $user_id ) {
        if ( ! $user_id ) {
            return false;
        }

        if ( is_multisite() && is_super_admin( $user_id ) ) {
            return true;
        }

        return user_can( $user_id, 'manage_options' );
    }

}
