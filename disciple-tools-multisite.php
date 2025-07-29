<?php
/**
 *Plugin Name: Disciple.Tools - Multisite
 * Plugin URI: https://github.com/DiscipleTools/disciple-tools-multisite
 * Description: An essential plugin for configuring and managing Disciple.Tools instances on a Multisite from the Network Admin.
 * Version:  1.14.1
 * Author URI: https://github.com/DiscipleTools
 * GitHub Plugin URI: https://github.com/DiscipleTools/disciple-tools-multisite
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 5.6
 *
 * @package Disciple_Tools
 * @link    https://github.com/DiscipleTools
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 *
 * @version 1.2 Added support for the Network Dashboard and the Network Dashboard Remote plugins.
 * @version 1.3 Disciple Tools 1.0 support
 * @version 1.4 Changed version control. Added mapbox key bulk utility.
 * @version 1.7 Added enumerator to the sites table
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**********************************************************************************************************************
 * MAKE DISCIPLE TOOLS DEFAULT THEME
 */
if ( ! defined( 'WP_DEFAULT_THEME' ) ) {
    if ( file_exists( ABSPATH . 'wp-content/themes/disciple-tools-theme/functions.php' ) ) {
        define( 'WP_DEFAULT_THEME', 'disciple-tools-theme' );
    } elseif ( file_exists( ABSPATH . 'wp-content/themes/disciple-tools-theme-master/functions.php' ) ){
        define( 'WP_DEFAULT_THEME', 'disciple-tools-theme-master' );
    }
}

global $wp_version;
if ( version_compare( $wp_version, '5.1', '<' ) ) {
    add_action( 'wpmu_new_blog', 'dt_new_blog_force_dt_theme', 10, 1 );
}
else {
    add_action( 'wp_initialize_site', function ( WP_Site $new_site ){
        dt_new_blog_force_dt_theme( $new_site->id );
    }, 10, 1 );
}
function dt_new_blog_force_dt_theme( $blog_id ){
    update_blog_option( $blog_id, 'template', 'disciple-tools-theme' );
    update_blog_option( $blog_id, 'stylesheet', 'disciple-tools-theme' );
    update_blog_option( $blog_id, 'current_theme', 'Disciple Tools' );

    if ( get_network_option( 1, 'dt_mapbox_api_key' ) ) {
        $key = get_network_option( 1, 'dt_mapbox_api_key' );
        update_blog_option( $blog_id, 'dt_mapbox_api_key', $key );
    }

    // make sure blog administrators can add users or the add new users feature will not be available.
    $add_users_enabled = get_site_option( 'add_new_users' );
    if ( !$add_users_enabled ) {
        update_site_option( 'add_new_users', 1 );
    }
}
/** End */

/**
 * Gets the instance of the `DT_Multisite` class.
 *
 * @since  0.1
 * @access public
 * @return object|bool
 */
function dt_multisite() {
    if ( is_multisite() && is_network_admin() ) {
        return DT_Multisite::instance();
    }
    return false;
}
add_action( 'init', 'dt_multisite' );
require_once( 'includes/wp-migrate-pro.php' );

class DT_Multisite {

    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * Constructor method.
     *
     * @since  0.1
     * @access private
     * @return void
     */
    private function __construct() {
        if ( is_multisite() && is_network_admin() ) {
            require_once( 'includes/tab-overview.php' );
            require_once( 'includes/tab-network-dashboard.php' );
            require_once( 'includes/tab-mapbox-keys.php' );
            require_once( 'includes/tab-multisite-migration.php' );
            require_once( 'includes/tab-movement-maps-stats-plugin.php' );
            require_once( 'includes/tab-ipstack.php' );
            require_once( 'includes/tab-google.php' );
            require_once( 'includes/admin-page.php' );
            require_once( 'includes/add-colum-to-sites-list.php' );
            require_once( 'includes/tab-sso-login.php' );
            require_once( 'includes/tab-cloudflare.php' );
            require_once( 'includes/tab-ai.php' );
            require_once( 'includes/tab-storage.php' );
        }

        if ( is_admin() || is_network_admin() ) {
            // adds links to the plugin description area in the plugin admin list.
            add_filter( 'plugin_row_meta', [ $this, 'plugin_description_links' ], 10, 4 );
        }
        require_once( 'includes/hook-functions.php' );
    }

    /**
     * Filters the array of row meta for each/specific plugin in the Plugins list table.
     * Appends additional links below each/specific plugin on the plugins page.
     *
     * @access  public
     * @param   array       $links_array            An array of the plugin's metadata
     * @param   string      $plugin_file_name       Path to the plugin file
     * @param   array       $plugin_data            An array of plugin data
     * @param   string      $status                 Status of the plugin
     * @return  array       $links_array
     */
    public function plugin_description_links( $links_array, $plugin_file_name, $plugin_data, $status ) {
        if ( strpos( $plugin_file_name, basename( __FILE__ ) ) ) {
            // You can still use `array_unshift()` to add links at the beginning.

            $links_array[] = '<a href="https://disciple.tools/plugins/multisite/">Plugin Webpage</a>';
            $links_array[] = '<a href="https://github.com/DiscipleTools/disciple-tools-multisite">Github Project</a>';
            $links_array[] = '<a href="https://disciple.tools">Disciple.Tools Community</a>';

        }

        return $links_array;
    }

    /**
     * Method that runs only when the plugin is activated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function activation() {
    }

    /**
     * Method that runs only when the plugin is deactivated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function deactivation() {
        delete_option( 'dismissed-dt-multisite' );
    }

    /**
     * Magic method to output a string if trying to use the object as a string.
     *
     * @since  0.1
     * @access public
     * @return string
     */
    public function __toString() {
        return 'dt_multisite';
    }

    /**
     * Magic method to keep the object from being cloned.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to keep the object from being unserialized.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, 'Whoah, partner!', '0.1' );
    }

    /**
     * Magic method to prevent a fatal error when calling a method that doesn't exist.
     *
     * @param string $method
     * @param array $args
     * @return null
     * @since  0.1
     * @access public
     */
    public function __call( $method = '', $args = array() ) {
        _doing_it_wrong( 'dt_multisite::' . esc_html( $method ), 'Method does not exist.', '0.1' );
        unset( $method, $args );
        return null;
    }
}
// end main plugin class

// Register activation hook.
register_activation_hook( __FILE__, [ 'DT_Multisite', 'activation' ] );
register_deactivation_hook( __FILE__, [ 'DT_Multisite', 'deactivation' ] );

/**
 * Make the update checker available on multisites when the default theme is not Disciple.Tools
 */
require( 'includes/admin/plugin-update-checker/plugin-update-checker.php' );

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;
add_action( 'plugins_loaded', function (){
    $is_updating_plugin = isset( $_POST['action'] ) && $_POST['action'] === 'update-plugin'; // phpcs:ignore WordPress.Security.NonceVerification.Missing
    $disable = isset( $_POST['wppusher'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
    if ( !$disable && is_multisite() && ( is_network_admin() || wp_doing_cron() || $is_updating_plugin ) && is_main_site() ){
        // find the Disciple.Tools theme and load the plugin update checker.
        $current_theme = wp_get_theme();
        foreach ( wp_get_themes() as $theme ){
            if ( $theme->get( 'TextDomain' ) === 'disciple_tools' && file_exists( $theme->get_stylesheet_directory() . '/dt-core/libraries/plugin-update-checker/plugin-update-checker.php' ) ){
                PucFactory::buildUpdateChecker(
                    'https://raw.githubusercontent.com/DiscipleTools/disciple-tools-version-control/master/disciple-tools-theme-version-control.json',
                    $theme->get_stylesheet_directory(),
                    basename( $theme->get_stylesheet_directory() )
                );
            }
        }
        if ( !function_exists( 'get_plugins' ) ) {
            include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        }
        $plugins = get_plugins();
        $dont_update = get_option( 'dt_multisite_dont_update_list', [] );
        foreach ( $plugins as $plugin_key => $plugin ){
            $plugin_folder_name = explode( '/', $plugin_key )[0];
            $plugin_path = trailingslashit( WP_PLUGIN_DIR ) . $plugin_folder_name;
            if ( file_exists( $plugin_path . '/version-control.json' ) && isset( $plugin['PluginURI'] ) && !empty( $plugin['PluginURI'] ) ){
                $hosted_json = str_replace( 'github.com', 'raw.githubusercontent.com', $plugin['PluginURI'] ) . '/master/version-control.json';
                //don't keep retrying failed updates
                if ( isset( $dont_update[$hosted_json] ) && $dont_update[$hosted_json] > time() - DAY_IN_SECONDS * 30 ){
                    continue;
                }
                //don't set if already being set by a plugin
                $slug_check_filter = 'puc_is_slug_in_use-' . $plugin_folder_name;
                $slug_used_by = apply_filters( $slug_check_filter, false );
                if ( empty( $slug_used_by ) ){
                    PucFactory::buildUpdateChecker(
                        $hosted_json,
                        trailingslashit( WP_PLUGIN_DIR ) . $plugin_key,
                        'multi' . $plugin_folder_name,
                        24
                    );
                }
            }
        }
    }
    //catch plugin update errors and save url that fail
    add_action('puc_api_error', function ( $result = null, $url = null, $slug = null ){
        if ( empty( $result ) || empty( $url ) || empty( $slug ) ){
            dt_write_log( 'puc_api_error: missing params' );
            dt_write_log( $result );
            return;
        }
        $dont_update = get_option( 'dt_multisite_dont_update_list', [] );
        $slug = strtok( $slug ?: '', '?' );
        $dont_update[$slug] = time();
        update_option( 'dt_multisite_dont_update_list', $dont_update );
    }, 10, 3);


    if ( !is_main_site() ){
        $cron_jobs = get_option( 'cron', [] );
        foreach ( $cron_jobs as $timestamp => $cron ){
            if ( is_array( $cron ) ){
                foreach ( $cron as $hook => $data ){
                    if ( strpos( $hook, 'puc' ) !== false ){
                        wp_unschedule_event( $timestamp, $hook );
                    }
                }
            }
        }
    }
} );
