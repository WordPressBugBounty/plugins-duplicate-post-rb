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

    namespace rbDuplicatePost\AdminUI;

    defined( 'WPINC' ) || exit;

    use rbDuplicatePost\GeneralOptions;
    use rbDuplicatePost\User;
    use rbDuplicatePost\Utils;
    use rbDuplicatePost\Profile\Profile;

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

        public static function hooks(){
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
            #adminmenu .toplevel_page_rb_plugins_settings > .wp-menu-image.dashicons-rest-api:before{ color: #99d000; } </style>';
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

        /**
         * Add Page to Admin Menu
         */
        private static function addToRootMenu() {

            $menu_exits = menu_page_url( 'rb_plugins_settings', false );

            if ( ! $menu_exits ) {
                $title_plugins = __( 'RB Plugins', 'duplicate-post-rb' );
                add_menu_page( $title_plugins, $title_plugins, '', 'rb_plugins_settings', array(self::class, 'pluginsListPage' ), 'dashicons-rest-api', 20 );
                add_action( 'admin_head', array( self::class, 'addAdminCss' ) );
            }

            $title = __( 'Duplicate Post', 'duplicate-post-rb' );
            add_submenu_page( 'rb_plugins_settings', $title, $title, 'manage_options', self::PAGE_NAME, array( self::class, 'renderPage' ) );
            self::redirectFromOldMenu('toRootMenu');
        }

        /**
         * Add Page to Admin Menu
         */
        private static function addToToolsMenu() {
            $title = __( 'RB Duplicate Post', 'duplicate-post-rb' );
            add_submenu_page( 'tools.php', $title, $title, 'manage_options', self::PAGE_NAME, array( self::class, 'renderPage' ) );
            self::redirectFromOldMenu('toToolsMenu');
        }
        
        

        /**
         * Redirect from old menu
         */
        private static function redirectFromOldMenu( $redirectTarget = '' ) {
            if(!$redirectTarget) {
                return;
            }
            $screen = Utils::getCurrentScreen();

            if(!$screen) {
                return;
            }
            if($redirectTarget === 'toRootMenu'){
                if( $screen==='tools.php' && isset($_GET['page']) &&  $_GET['page'] === self::PAGE_NAME ) {
                    wp_redirect(admin_url('admin.php?page='.self::PAGE_NAME));
                    exit;
                }
            }
            if($redirectTarget === 'toToolsMenu'){
                if( $screen==='admin.php' && isset($_GET['page']) &&  $_GET['page'] === self::PAGE_NAME ) {
                    wp_redirect(admin_url('tools.php?page='.self::PAGE_NAME));
                    exit;
                }
            }

            return ;
        }

       

        /**
         * Enqueue Scripts
         */
        public static function enqueueScripts( $hook ) {
            if ( 'rb-plugins_page_rb_duplicate_post_settings' !== $hook &&  'tools_page_rb_duplicate_post_settings' !== $hook ) {
                return;
            }

            $handle = RB_DUPLICATE_POST_ASSETS_PREFIX . 'options';

            wp_enqueue_script(
                $handle,
                RB_DUPLICATE_POST_URL . 'assets/js/main.js',
                array(),
                RB_DUPLICATE_POST_VERSION,
                true
            );
        }

        /**
         * Render Page
         */
        public static function renderPage() {
            $profile_id = Profile::getLastViewedProfile();
            $blockPro = true;
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Duplicate Post RB', 'duplicate-post-rb' ); ?></h1>
        </div>
        <div style="background-color:#fff; margin-right:20px;" class="rbDuplicatePostOptions" rbDuplicatePost_id="<?php echo  $profile_id ; ?>"></div>
        <script>
            window.rb_duplicate_post_options_url = "<?php echo esc_js( RB_DUPLICATE_POST_URL . 'assets/js/' ); ?>";
            window.rb_duplicate_post = {
                imagesUrl: "<?php echo esc_js( RB_DUPLICATE_POST_URL . 'assets/js/' ); ?>",
                restUrl: "<?php echo esc_js( get_rest_url() ); ?>",
                wp_rest: "<?php echo esc_js( wp_create_nonce( 'wp_rest' ) ); ?>",
                blockPro: <?php echo $blockPro ? 'true' : 'false'; ?>,
                debug: true
            };
        </script>
        <?php
    }
}
