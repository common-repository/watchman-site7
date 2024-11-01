<?php
/**
 * Description: Deletes the tables of the plugin and plugin settings from the database of the website.
 * PHP version 8.0.1
 * @category uninstall.php
 * @package  WatchMan-Site7
 * @author   Oleg Klenitsky <klenitskiy.oleg@mail.ru>
 * @version  4.2.0
 * @license  GPLv2 or later
 * @filesource
 */

if ( ! defined( "ABSPATH" ) ) {
	exit();
}
if ( ! defined( "WP_UNINSTALL_PLUGIN" ) ) {
	exit();
}

global $wpdb;

// Delete options.
delete_option( "wms7_visitors_per_page" );
delete_option( "wms7_main_settings" );
delete_option( "wms7_screen_settings" );
delete_option( "wms7_current_url" );
delete_option( "wms7_console" );

delete_option( "wms7_backend" );
delete_option( "wms7_frontend" );
delete_option( "wms7_cron" );

delete_option( "wms7_search" );

// Delete table watchman_site.
$sql = "DROP TABLE IF EXISTS {$wpdb->prefix}wms7_visitors";
$wpdb->query( $sql );

// Delete table watchman_site_countries.
$sql = "DROP TABLE IF EXISTS {$wpdb->prefix}wms7_countries";
$wpdb->query( $sql );

// Delete table watchman_site_cross_table.
$sql = "DROP TABLE IF EXISTS {$wpdb->prefix}wms7_cross_table";
$wpdb->query( $sql );
