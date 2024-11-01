<?php
/**
 * Description: Creates 2 tables in the database of the website for the plugin.
 * PHP version 8.0.1
 * @category wms7-create-tables.php
 * @package  WatchMan-Site7
 * @author   Oleg Klenitsky <klenitskiy.oleg@mail.ru>
 * @version  4.2.0
 * @license  GPLv2 or later
 * @filesource
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * Description: For use dbDelta.
 */
require_once ABSPATH . 'wp-admin/includes/upgrade.php';

/**
 * Description: Create tables: wms7_visitors, wms7_countries.
 * @return boolean.
 */
function wms7_create_tables() {
	// create tables.
	global $wpdb;

	$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wms7_visitors
	(
	id INT( 11 ) NOT NULL AUTO_INCREMENT ,
	uid INT( 11 ) NOT NULL ,
	user_login VARCHAR( 60 ) NOT NULL ,
	user_role VARCHAR( 30 ) NOT NULL ,
	time_visit DATETIME NOT NULL ,
	user_ip VARCHAR( 100 ) NOT NULL ,
	internal_ip LONGTEXT NOT NULL ,
	user_ip_info LONGTEXT NOT NULL ,
	black_list LONGTEXT NOT NULL ,
	whois_service VARCHAR( 30 ) NOT NULL ,
	country LONGTEXT NOT NULL ,
	provider LONGTEXT NOT NULL ,
	login_result VARCHAR( 1 ) NOT NULL ,
	robot VARCHAR( 100 ) NOT NULL ,
	page_visit LONGTEXT NOT NULL ,
	page_from LONGTEXT NOT NULL ,
	info LONGTEXT NOT NULL ,
	PRIMARY KEY ( id ) ,
	INDEX ( uid, user_ip, login_result )
	);";
	$wpdb->query( $sql );

	$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wms7_countries
	(
	cid int(4) unsigned NOT NULL AUTO_INCREMENT,
	code char(2) NOT NULL,
	name varchar(150) NOT NULL,
	latitude float NOT NULL,
	longitude float NOT NULL,
	PRIMARY KEY (`cid`)
	);";
	$wpdb->query( $sql );

	$sql        = "SELECT count(*) FROM {$wpdb->prefix}wms7_countries";
	$count_rows = $wpdb->get_var( $sql );
	// count rows.
	if ( 249 !== (int) $count_rows ) {
		$sql = "INSERT INTO {$wpdb->prefix}wms7_countries (`cid`, `code`, `name`, `latitude`, `longitude`) VALUES ";
		$sql = $sql . wms7_sql_countries();

		$wpdb->query( $sql );
	}

	return true;
}
