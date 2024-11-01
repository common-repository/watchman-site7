<?php
/**
 * Description:  Designed for site administrators and used to ensure site security and control site visits. The plugin developer prohibits the installation of this plugin on government websites of any country. The plugin is allowed to install and use only on private and personal sites or blogs.
 *
 * PHP version 8.0.1
 * @category WatchMan-Site7
 * @package  WatchMan-Site7
 * @author   Oleg Klenitsky <klenitskiy.oleg@mail.ru>
 * @version  4.2.0
 * @license  GPLv2 or later
 * @filesource
 */

/**
 * Plugin Name:  WatchMan-Site7
 * Description:  Designed for site administrators and used to ensure site security and control site visits. The plugin developer prohibits the installation of this plugin on government websites of any country. The plugin is allowed to install and use only on private and personal sites or blogs.
 * Author:       Oleg Klenitskiy
 * Author URI:   https://www.adminkov.bcr.by/
 * Plugin URI:   https://wordpress.org/plugins/watchman-site7/
 * Contributors: adminkov, innavoronich
 * Version:      4.2.0
 * Text Domain:  watchman-site7
 * Domain Path:  /languages
 * Initiation:   Is dedicated to Inna Voronich.
 */

if ( ! defined( "ABSPATH" ) ) {
	exit();
}

/**
 * Create tables in the database:wms7_visitors and wms7_countries.
 */
require_once __DIR__ . "/settings/wms7-create-tables.php";
/**
 * Contains reference data to populate the table wms7_countries in DB.
 */
require_once __DIR__ . "/settings/wms7-countries.php";
/**
 * Generate and display statistics of site visits.
 */
require_once __DIR__ . "/includes/wms7-statistic.php";
/**
 * Obtain data from Who-Is providers about the IP addresses of visitors to the site.
 */
require_once __DIR__ . "/includes/wms7-ip-info.php";
/**
 * Work with external files.
 */
require_once __DIR__ . "/includes/wms7-io-interface.php";
/**
 * Plugin service function calls.
 */
require_once __DIR__ . "/includes/wms7-btns-service.php";

/**
 * Defined global paths
 */
if ( ! defined( "WMS7_PLUGIN_NAME" ) ) {
	/**
	 * Formed from the name of the plugin directory.
	 * @filesource watchman-site7.php (line 83)
	 */
	define( "WMS7_PLUGIN_NAME", trim( dirname( plugin_basename( __FILE__ ) ), "/" ) );
}
if ( ! defined( "WMS7_PLUGIN_DIR" ) ) {
	/**
	 * Contains the path to the main plugin file.
	 * Serves for receiving plugin data by function get_plugin_data().
	 */
	define( "WMS7_PLUGIN_DIR", WP_PLUGIN_DIR . "/" . WMS7_PLUGIN_NAME );
}
if ( ! defined( "WMS7_PLUGIN_URL" ) ) {
	/**
	 * Contains the url to the main plugin file.
	 * Serves to work on the client side in module wms7-backend.js
	 */
	define( "WMS7_PLUGIN_URL", WP_PLUGIN_URL . "/" . WMS7_PLUGIN_NAME );
}

/**
 * Localization of plugin.
 */
function wms7_languages() {
	load_plugin_textdomain( "wms7", false, WMS7_PLUGIN_NAME . "/languages/" );
}
add_action( "plugins_loaded", "wms7_languages" );
/**
 * Includes script and style files for the frontend.
 */
function wms7_enqueue_scripts_frontend() {
	global $wpdb;

	// get whois_service.
	$val           = get_option( "wms7_main_settings" );

	$stun_server   = isset( $val["stun_server"] ) ? esc_attr( $val["stun_server"] ) : "none";
	$wms7_ajax_url = admin_url( "admin-ajax.php" );
	$wms7_id       = $wpdb->insert_id;

	if ( !is_admin() ) {
		wp_enqueue_script( "wms7-frontend", plugins_url( "/js/wms7-frontend.js", __FILE__ ), array(), "v.4.2.0", false );
		wp_enqueue_script( "wms7-webrtc", plugins_url( "/js/wms7_webrtc.js", __FILE__ ), array(), "v.4.2.0", false );
		?>
		<script>
			var wms7_ajax_url    = "<?php echo esc_html( $wms7_ajax_url ); ?>";
			var wms7_stun_server = "<?php echo esc_html( $stun_server ); ?>";
			var wms7_id          = "<?php echo esc_html( $wms7_id ); ?>";
		</script>
		<?php
	}
}
add_action( "wp_enqueue_scripts", "wms7_enqueue_scripts_frontend" );
/**
 * Includes script and style files for the backend.
 */
function wms7_enqueue_scripts_backend() {
	$_request_uri = filter_input( INPUT_SERVER, "REQUEST_URI", FILTER_DEFAULT );
	// for all pages of backend: wms7_settings, wms7_visitors
	wp_enqueue_script( "wms7-backend", plugins_url( "/js/wms7-backend.js", __FILE__ ), array(), "v.4.2.0", false );

	if( stristr( $_request_uri, "wms7_visitors" ) ||
		stristr( $_request_uri, "wms7_settings" ) ||
		stristr( $_request_uri, "wms7_black_list" ) ) {

		wp_enqueue_script( "wms7-console", plugins_url( "/js/wms7-console.js", __FILE__ ), array(), "v.4.2.0", false );
		wp_enqueue_style( "wms7-backend", plugins_url( "/css/wms7-backend-style.css", __FILE__ ), false, "v.4.2.0", "all" );

		$wms7_ajax_url = admin_url( "admin-ajax.php" );
		$wms7_url      = WMS7_PLUGIN_URL;
		?>
		<script>
			var wms7_ajax_url = "<?php echo esc_html( $wms7_ajax_url ); ?>";
			var wms7_url      = "<?php echo esc_html( $wms7_url ); ?>";
		</script>
		<?php

		if ( session_id() ) {
			session_destroy();
		}
	}
}
add_action( "admin_enqueue_scripts", "wms7_enqueue_scripts_backend" );

if ( ! class_exists( "wms7_List_Table" ) ) {
	/**
	 * Create table for plugin in the admin panel.
	 */
	require_once __DIR__ . "/class-wms7-list-table.php";
}
if ( ! class_exists( "Wms7_Core" ) ) {
	/**
	 * Used to receive and process requests when visiting site.
	 */
	require_once __DIR__ . "/class-wms7-core.php";

	if ( class_exists( "Wms7_Core" ) ) {
		$wms7 = new Wms7_core();
		// Activation hook.
		register_activation_hook( __FILE__, "wms7_activation" );
		/**
		 * Performed when the plugin is activation.
		 * During activation, creates two tables in the database:
		 * {$wpdb->prefix}wms7_visitors
		 * {$wpdb->prefix}wms7_countries
		 */
		function wms7_activation() {
			// Create custom tables for plugin.
			wms7_create_tables();
		}
		// Deactivation hook.
		register_deactivation_hook( __FILE__, "wms7_deactivation" );
		/**
		 * Performed when the plugin is deactivation.
		 *
		 * Delete cron events: wms7_truncate, wms7_htaccess
		 */
		function wms7_deactivation() {
			// clean up old cron jobs that no longer exist.
			wp_clear_scheduled_hook( "wms7_truncate" );
			wp_clear_scheduled_hook( "wms7_htaccess" );
		}
	}
}
if ( ! class_exists( "Wms7_Cron" ) ) {
	/**
	 * Control the cron events of the site.
	 */
	require_once __DIR__ . "/class-wms7-cron.php";
}
if ( ! class_exists( "Wms7_Widget" ) ) {
	/**
	 * Create a widget - counter site visits.
	 */
	require_once __DIR__ . "/class-wms7-widget.php";
	/**
	 * Register widget - counter site visits.
	 */
	function wms7_load_widget() {
		register_widget( "Wms7_Widget" );
	}
	add_action( "widgets_init", "wms7_load_widget" );
}
if ( ! class_exists( "Wms7_Browser" ) ) {
	/**
	 * Parses user-agent to get the names: browser, platform, device.
	 */
	require_once __DIR__ . "/class-wms7-browser.php";
}
/**
 * Executes commands WP, PHP.
 */
require_once __DIR__ . "/includes/wms7-query.php";
add_action("wp_ajax_query", "wms7_query");
/**
 * Return constant or function or variable environment WP, PHP.
 */
require_once __DIR__ . "/includes/wms7-complete.php";
add_action("wp_ajax_complete", "wms7_complete");
/**
 * Reload environment of console PHP.
 */
require_once __DIR__ . "/includes/wms7-reload.php";
add_action("wp_ajax_reload", "wms7_reload");
/**
 * Helper Console PHP Build Feature.
 */
require_once __DIR__ . "/includes/wms7-common.php";
/**
 * Transfer data of cron tasks.
 */
require_once __DIR__ . "/includes/wms7-sse-cron.php";
add_action( "wp_ajax_cron", "wms7_sse_cron" );
/**
 * Transfer a count of records of visitor.
 */
require_once __DIR__ . "/includes/wms7-sse-backend.php";
add_action( "wp_ajax_backend", "wms7_sse_backend" );
/**
 * Transfer data about site visits to a widget - counter of visits.
 */
require_once __DIR__ . "/includes/wms7-sse-frontend.php";
add_action( "wp_ajax_frontend", "wms7_sse_frontend" );
add_action( "wp_ajax_nopriv_frontend", "wms7_sse_frontend" );
/**
 * Transfer data of IP internal of visitors to site.
 */
require_once __DIR__ . "/includes/wms7-webrtc.php";
add_action( "wp_ajax_ip_internal", "wms7_ip_internal_visitor" );
add_action( "wp_ajax_nopriv_ip_internal", "wms7_ip_internal_visitor" );
