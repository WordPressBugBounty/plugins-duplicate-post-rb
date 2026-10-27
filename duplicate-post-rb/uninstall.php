<?php
/*
*      RB Duplicate Post
*      By RbPlugin
*
*      Contact: https://rbplugins.com
*      Created: 2025
*      Licensed under the GPLv3 license - http://www.gnu.org/licenses/gpl-3.0.html
 */

/*
 * Deleting the plugin (Plugins → Delete) removes, on every site of a network, only what is
 * of no use without it: the copy log, the notices waiting for users and its cron event.
 * Profiles, settings and what it noted on posts (where a copy came from, rewrites in
 * progress) stay: a plugin deleted by mistake and installed again works as it did.
 * The plugin itself is not loaded here: names are written out.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange -- removing the plugin's own log table

/** the plugin's log and cron event on the current site */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound -- rb_duplicate_post_ is this plugin's prefix; Plugin Check only guesses rbDuplicatePost from the namespace
function rb_duplicate_post_uninstall_site() {
	global $wpdb;

	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}rb_duplicate_post_log" );

	// the installed version: without it the plugin installed again makes its log table anew
	// on every site (PluginActivator), not only where it is activated
	delete_option( 'rb-duplicate-post-version' );
	delete_option( 'rb_duplicate_post_after_install' );
	// installed again, the plugin welcomes again
	delete_option( 'rb_duplicate_post_welcome_pending' );
	delete_option( 'rb_duplicate_post_welcome_seen' );

	wp_unschedule_hook( 'rb_duplicate_post_log_clean' );
}

if ( is_multisite() ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- rb_duplicate_post_ is this plugin's prefix; Plugin Check only guesses rbDuplicatePost from the namespace
	foreach ( get_sites( array( 'fields' => 'ids', 'number' => 0 ) ) as $rb_duplicate_post_site_id ) {
		switch_to_blog( (int) $rb_duplicate_post_site_id );
		rb_duplicate_post_uninstall_site();
		restore_current_blog();
	}
} else {
	rb_duplicate_post_uninstall_site();
}

// the notices waiting for users after a copy (user meta: one table for the whole network)
delete_metadata( 'user', 0, 'rb_duplicate_post__user_notification', '', true );
