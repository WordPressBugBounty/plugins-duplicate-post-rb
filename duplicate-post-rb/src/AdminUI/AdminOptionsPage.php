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

    namespace rbDuplicatePost\AdminUI;

    defined( 'WPINC' ) || exit;

    use rbDuplicatePost\GeneralOptions;
    use rbDuplicatePost\User;
    use rbDuplicatePost\Utils;
    use rbDuplicatePost\ProCheck;
    use rbDuplicatePost\Profile\Profile;
    use rbDuplicatePost\Helpers\PostTypes;
    use rbDuplicatePost\Helpers\Multisite;

    class AdminOptionsPage {

        CONST PAGE_NAME = 'rb_duplicate_post_settings';

        /**
         * Constructor  
         */
        public function __construct() {
            if ( !is_admin() ) {
                return ;    
            }
            add_action('init', array( self::class, 'hooks' ));
        }

        /**
         * Hooks
         */  
        public static function hooks() {
            if ( !User::canManageOptions() ) {
                return ;
            }
            
            Profile::initDefaultProfile();

            add_action( 'admin_enqueue_scripts', array( self::class, 'enqueueScripts' ) );
            add_action( 'admin_menu', array( self::class, 'addAdminMenu' ) );
        }

        /**
         * Add Admin CSS
         */
        public static function addAdminCss() {
            echo '<style>
             #wpadminbar >  #wp-toolbar > #wp-admin-bar-root-default #wp-admin-bar-rb-duplicate-post-copy-button  .ab-icon:before {
                    content: "\f105";
                    top: 3px;
            }
            #adminmenu .toplevel_page_rb_plugins_settings > .wp-menu-image.dashicons-rest-api:before{ color: #99d000; }
            </style>';
        }

        /**
         * Plugins List Page
         */
        public static function pluginsListPage() {}

        /**
         * Add Page to Admin Menu
         */
        public static function addAdminMenu() {
            $pluginMenuInToolsMenu = GeneralOptions::getOption('pluginMenuInToolsMenu');

            if($pluginMenuInToolsMenu['value']) {
                self::addToToolsMenu();
                return;
            }
            self::addToRootMenu();
        }

        private static function addToRootMenu() {

            $menu_exits = menu_page_url( 'rb_plugins_settings', false );

            if ( ! $menu_exits ) {
                $title_plugins = __( 'RB Plugins', 'duplicate-post-rb' );
                add_menu_page( $title_plugins, $title_plugins, '', 'rb_plugins_settings', array(self::class, 'pluginsListPage' ), 'dashicons-rest-api', 20 );
                add_action( 'admin_head', array( self::class, 'addAdminCss' ) );
            }

            $title = __( 'Duplicate Post', 'duplicate-post-rb' );
            add_submenu_page( 'rb_plugins_settings', $title, $title, 'manage_options', self::PAGE_NAME, array( self::class, 'renderPage' ) );
            add_action( 'admin_menu', array( self::class, 'removePlaceholderItem' ), 999 );
            self::redirectFromOldMenu('toRootMenu');
        }

        /**
         * The "RB Plugins" menu's own page is an empty placeholder (no
         * capability). WordPress repeats it as the menu's first item, hidden
         * from everyone but a network's super admin, who passes any capability
         * check: removed once every RB plugin has added its page.
         */
        public static function removePlaceholderItem() {
            remove_submenu_page( 'rb_plugins_settings', 'rb_plugins_settings' );
        }

        private static function addToToolsMenu() {
            $title = __( 'RB Duplicate Post', 'duplicate-post-rb' );
            add_submenu_page( 'tools.php', $title, $title, 'manage_options', self::PAGE_NAME, array( self::class, 'renderPage' ) );
            self::redirectFromOldMenu('toToolsMenu');
        }

        private static function redirectFromOldMenu( $redirectTarget = '' ) {
            if(!$redirectTarget) {
                return;
            }
            $screen = Utils::getCurrentScreen();

            if(!$screen) {
                return;
            }
            if($redirectTarget === 'toRootMenu'){
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reads which admin page is open, changes nothing
                if( $screen==='tools.php' && isset($_GET['page']) && sanitize_key( wp_unslash( $_GET['page'] ) ) === self::PAGE_NAME ) {
                    wp_safe_redirect(admin_url('admin.php?page='.self::PAGE_NAME));
                    exit;
                }
            }
            if($redirectTarget === 'toToolsMenu'){
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- reads which admin page is open, changes nothing
                if( $screen==='admin.php' && isset($_GET['page']) && sanitize_key( wp_unslash( $_GET['page'] ) ) === self::PAGE_NAME ) {
                    wp_safe_redirect(admin_url('tools.php?page='.self::PAGE_NAME));
                    exit;
                }
            }

            return ;
        }

        public static function enqueueScripts( $hook ) {
            if ( 'rb-plugins_page_rb_duplicate_post_settings' !== $hook &&  'tools_page_rb_duplicate_post_settings' !== $hook ) {
                return;
            }

            $handle = RB_DUPLICATE_POST_ASSETS_PREFIX . 'settings';

            wp_enqueue_script(
                $handle,
                RB_DUPLICATE_POST_URL . 'assets/admin/settings.js',
                array( 'wp-i18n' ),
                RB_DUPLICATE_POST_VERSION,
                true
            );
            wp_set_script_translations( $handle, 'duplicate-post-rb', RB_DUPLICATE_POST_PATH . 'languages' );

            // custom post types the "never copy" list offers (post and page have switches of their own)
            $custom_types = PostTypes::get_all_types_with_labels();
            unset( $custom_types['post'], $custom_types['page'] );

            wp_localize_script( $handle, 'RBDuplicatePostAdmin', array(
                'restUrl'     => esc_url_raw( rest_url( 'rb_duplicate_post/v1/' ) ),
                'nonce'       => wp_create_nonce( 'wp_rest' ),
                'pro'         => ProCheck::isActive(),
                'version'     => RB_DUPLICATE_POST_VERSION,
                'profileId'   => (int) Profile::getLastViewedProfile(),
                'isMultisite' => is_multisite(),
                'customTypes' => $custom_types,
                // for the Auto copy rules: "posts in this category"
                'categories'  => wp_list_pluck( get_categories( array( 'hide_empty' => false, 'number' => 200 ) ), 'name', 'term_id' ),
                // taxonomies the Assistant's term steps can use
                'taxonomies'  => wp_list_pluck( array_filter( get_taxonomies( array( 'show_ui' => true ), 'objects' ), function ( $t ) { return $t->name !== 'link_category'; } ), 'label', 'name' ),
                'icons'       => RowActionCopyButton::ICONS,
                'logActions'  => \rbDuplicatePost\Log\Actions::all(),
                // written by the PRO key's scheduled cleanup: {time, removed}
                'autoCleanupLast' => get_option( 'rb_duplicate_post_auto_cleanup_last', null ),
                // RB Links Redirection, our plugin that makes the cleanup's redirects
                'redirection' => array(
                    'active'   => function_exists( 'rb_redirection_add' ),
                    'restUrl'  => esc_url_raw( rest_url( 'rb_redirection/v1/' ) ),
                    'adminUrl' => function_exists( 'rb_redirection_admin_url' ) ? rb_redirection_admin_url() : '',
                    'pageUrl'  => 'https://rbplugins.com/wordpress-plugins/rb-links-redirection/',
                ),
                'buyUrl'      => 'https://rbplugins.com/wordpress-plugins/duplicate-post-rb/#pricing',
                'docsUrl'     => 'https://rbplugins.com/docs/duplicate-post-rb/',
            ) );

            add_action( 'admin_head', array( self::class, 'addAdminCss' ) );
        }

        /**
         * Render Page: the mount point of assets/admin/settings.js
         */
        public static function renderPage() {
            ?>
            <div class="wrap rb-duplicate-post-wrap">
                <h1 class="screen-reader-text"><?php esc_html_e( 'Duplicate Post RB', 'duplicate-post-rb' ); ?></h1>
                <?php RoboSoftBanner::render(); ?>
                <div id="rb-duplicate-post-root">
                    <noscript><?php esc_html_e( 'The Duplicate Post RB settings need JavaScript.', 'duplicate-post-rb' ); ?></noscript>
                </div>
            </div>
            <?php
        }
    }
