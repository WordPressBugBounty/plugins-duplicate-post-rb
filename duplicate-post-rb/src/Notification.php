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

namespace rbDuplicatePost;

defined('WPINC') || exit;

use rbDuplicatePost\Constants;

/**
 * Class Notification
 *
 * Storage for notifications.
 * 
 */
class Notification {

	CONST META_KEY = 'rb_duplicate_post__user_notification';
    CONST EXPIRATION_DAYS = 7;

	public function set_notification(  array $data, $user_id=null ) {

        if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$user_id = (int) $user_id;

		if ( $user_id <= 0 ) {
			return false; 
		}

		$payload = [
			'created_at'      => time(),
			'expiration_days' =>  self::EXPIRATION_DAYS,
			'data'            => $data,
		];

		$json = wp_json_encode( $payload, JSON_UNESCAPED_UNICODE );
		if ( false === $json ) {
			return false;
		}

		return (bool) update_user_meta( $user_id, self::META_KEY, $json );
	}

	public function get_notification_once( $user_id=null ) {

        if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return null;
		}

		$json = get_user_meta( $user_id, self::META_KEY, true );
		if ( empty( $json ) ) {
			return null;
		}

		delete_user_meta( $user_id, self::META_KEY );

		$payload = json_decode( $json, true );
		return ( is_array( $payload ) && isset( $payload['data'] ) ) ? $payload['data'] : null;
	}

	public function has_notification( $user_id = null ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
        $user_id = (int) $user_id;
		return ! empty( get_user_meta( $user_id, self::META_KEY, true ) );
	}

    /**
     * Clear notification.
     *
     * @param mixed $user_id
     * @return boolean
     */
	public function clear_notification( $user_id= null ) {
        if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}
		$user_id = (int) $user_id;
		if ( $user_id <= 0 ) {
			return false;
		}
		return (bool) delete_user_meta( $user_id, self::META_KEY );
	}

    /**
     * Check if notification is expired.
     *
     * @param  mixed $json
     * @return boolean
     */
	public function is_expired( $json ) {
		$data = json_decode( $json, true );
		if ( ! is_array( $data ) || empty( $data['created_at'] ) ) {
			return true;
		}

		$created_at = (int) $data['created_at'];
		$days = isset( $data['expiration_days'] ) ? (int) $data['expiration_days'] : self::EXPIRATION_DAYS;

		return ( time() - $created_at ) > ( $days * DAY_IN_SECONDS );
	}

	/**
	 * Cleanup expired notifications.
	 */
	public function cleanup_expired() {
		// only the users with a notice waiting, of the whole network (user meta is shared by its sites):
		// a shop with thousands of customers is not read user by user every day
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- the query finds the few users who have this key
		$users = get_users( array( 'meta_key' => self::META_KEY, 'fields' => 'ID', 'blog_id' => 0 ) );

		foreach ( $users as $user_id ) {
			$json = get_user_meta( (int) $user_id, self::META_KEY, true );
			if ( empty( $json ) || $this->is_expired( $json ) ) {
				delete_user_meta( (int) $user_id, self::META_KEY );
			}
		}
	}
}
