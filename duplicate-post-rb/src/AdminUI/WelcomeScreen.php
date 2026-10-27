<?php
/*
*      RB Duplicate Post
*      By RbPlugin
*
*      Contact: https://rbplugins.com
*      Created: 2025
*      Licensed under the GPLv3 license - http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace rbDuplicatePost\AdminUI;

defined( 'WPINC' ) || exit;

use rbDuplicatePost\ProCheck;

/**
 * The welcome screen: once after the plugin is installed, and once after an
 * update that brings an announcement (ANNOUNCEMENT), the next admin page an
 * administrator opens shows what the plugin can do now, offers RB Links
 * Redirection (installed and activated from here, in one click) and leads to
 * the settings. A full-screen layer of assets/admin/welcome.js, closed with its
 * cross, Esc or a click on a button; it is not shown again for this announcement.
 */
class WelcomeScreen {

	/** set on activation and on an update: the screen is due on the next admin page */
	const PENDING_OPTION = 'rb_duplicate_post_welcome_pending';

	/** the announcement last shown on this site */
	const SEEN_OPTION = 'rb_duplicate_post_welcome_seen';

	/** raise it with a release worth announcing: the screen shows once more after that update */
	const ANNOUNCEMENT = '2.5';

	const REDIRECTION_SLUG = 'rb-links-redirection';
	const REDIRECTION_FILE = 'rb-links-redirection.php';

	public function __construct() {
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue' ) );
	}

	/** the screen is due: called on activation and when a new version is first loaded */
	public static function mark_pending() {
		update_option( self::PENDING_OPTION, 1, false );
	}

	private static function is_due(): bool {
		if ( wp_doing_ajax() || is_network_admin() || ( defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST ) ) {
			return false;
		}
		if ( ! current_user_can( 'manage_options' ) || ! get_option( self::PENDING_OPTION ) ) {
			return false;
		}
		// shown once per announcement: activated again, or updated without news, it stays away
		return version_compare( (string) get_option( self::SEEN_OPTION, '0' ), self::ANNOUNCEMENT, '<' );
	}

	public static function enqueue() {
		if ( ! self::is_due() ) {
			return;
		}
		// shown now: not again for this announcement, whether it is closed or the page is left
		update_option( self::SEEN_OPTION, self::ANNOUNCEMENT, false );
		delete_option( self::PENDING_OPTION );

		$handle = RB_DUPLICATE_POST_ASSETS_PREFIX . 'welcome';
		wp_enqueue_script( $handle, RB_DUPLICATE_POST_URL . 'assets/admin/welcome.js', array( 'wp-i18n' ), RB_DUPLICATE_POST_VERSION, true );
		wp_set_script_translations( $handle, 'duplicate-post-rb', RB_DUPLICATE_POST_PATH . 'languages' );
		wp_localize_script( $handle, 'RBDuplicatePostWelcome', array(
			'version'     => RB_DUPLICATE_POST_VERSION,
			'pro'         => ProCheck::isActive(),
			'isMultisite' => is_multisite(),
			'settingsUrl' => admin_url( 'admin.php?page=' . AdminOptionsPage::PAGE_NAME ),
			'buyUrl'      => 'https://rbplugins.com/wordpress-plugins/duplicate-post-rb/?pk_campaign=wp_dp_welcome#pricing',
			'docsUrl'     => 'https://rbplugins.com/docs/duplicate-post-rb/',
			'restUrl'     => esc_url_raw( rest_url() ),
			'nonce'       => wp_create_nonce( 'wp_rest' ),
			'redirection' => self::redirection(),
		) );
	}

	/**
	 * RB Links Redirection on this site: active, installed (and its plugin path for
	 * the REST API) or missing; what this user may do about it.
	 */
	private static function redirection(): array {
		$out = array(
			'state'       => 'missing',
			'plugin'      => self::REDIRECTION_SLUG . '/' . substr( self::REDIRECTION_FILE, 0, -4 ),
			'adminUrl'    => admin_url( 'admin.php?page=rb_redirection' ),
			'wporgUrl'    => 'https://wordpress.org/plugins/' . self::REDIRECTION_SLUG . '/',
			'canInstall'  => current_user_can( 'install_plugins' ) && current_user_can( 'activate_plugins' ) && ! ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ),
			'canActivate' => current_user_can( 'activate_plugins' ),
		);
		if ( function_exists( 'rb_redirection_add' ) ) {
			$out['state'] = 'active';
			if ( function_exists( 'rb_redirection_admin_url' ) ) {
				$out['adminUrl'] = rb_redirection_admin_url();
			}
			return $out;
		}
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		// installed under any folder name (a zip uploaded by hand keeps its own)
		foreach ( array_keys( get_plugins() ) as $file ) {
			if ( basename( $file ) === self::REDIRECTION_FILE ) {
				$out['state']  = 'installed';
				$out['plugin'] = substr( $file, 0, -4 );
				break;
			}
		}
		return $out;
	}
}
