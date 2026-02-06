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

defined('WPINC') || exit;

use rbDuplicatePost\Notification;

/**
 * Class Cron
 * 
 * Manages scheduling and running of notification cleanup.
 */
class Cron {

	CONST HOOK_NAME = 'notification_cleanup_event';

	public function __construct() {
		add_action( self::HOOK_NAME, array( self::class, 'run_cleanup' ) );
	}

	/**
	 * Schedule cron job (once a day)
	 */
	public static function schedule() {
		if ( ! wp_next_scheduled( self::HOOK_NAME ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HOOK_NAME );
		}
	}

	/**
	 * Unschedule cron job when plugin is deactivated
	 */
	public static function unschedule() {
		$timestamp = wp_next_scheduled( self::HOOK_NAME );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::HOOK_NAME );
		}
	}

	/**
	 * Cron handler
	 */
	public static function run_cleanup() {
		$notification = new Notification();
		$notification->cleanup_expired();
	}
}
