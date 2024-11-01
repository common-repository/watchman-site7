<?php
/**
 * Description: Create statistics table of visits.
 * PHP version 8.0.1
 * @category wms7-statistic.php
 * @package  WatchMan-Site7
 * @author   Oleg Klenitsky <klenitskiy.oleg@mail.ru>
 * @version  4.2.0
 * @license  GPLv2 or later
 * @filesource
 */

if ( ! defined( "ABSPATH" ) ) {
	exit();
}

/**
 * Description: Parsing User Agent to extract data: name browser, name platform, name operating system.
 * @param  string $user_agent User Agent of visitor.
 * @return array
 */
function wms7_parse_user_agent( $user_agent ) {
	$browser  = "unknown";
	$platform = "unknown";
	$device   = "unknown";

	if ( ! $user_agent ) {
		return $empty;
	}

	$browser_info = new Wms7_Browser();
	$browser_info->wms7_browser_data( $user_agent );

	$browser = $browser_info->wms7_get_browser();

	if ( $browser_info->wms7_is_robot() ) {
		return array( "browser" => $browser );
	} else {
		$platform = $browser_info->wms7_get_platform();
		if ( $browser_info->wms7_is_mobile() ) {
			$device = "mobile";
		} else {
			$device = "desktop";
		}
		return array(
			"browser"  => $browser,
			"platform" => $platform,
			"device"   => $device,
		);
	}
}
/**
 * Description: Creates graph statistic of visits.
 * @param  string $where Login result (0, 1, 2, 3).
 * @return array
 */
function wms7_create_graph_stat( $where ) {
	global $wpdb;

	$_radio_stat = filter_input( INPUT_POST, "radio_stat", FILTER_DEFAULT );
	$_graph_type = filter_input( INPUT_POST, "graph_type", FILTER_DEFAULT );

	switch ( $_radio_stat ) {
		case "":
		case "visits":
			$where = ( $where ) ? $where : "";
			break;
		case "unlogged":
			$where = ( $where ) ? $where . " AND `login_result` = 2" : "WHERE `login_result` = 2";
			break;
		case "success":
			$where = ( $where ) ? $where . " AND `login_result` = 1" : "WHERE `login_result` = 1";
			break;
		case "failed":
			$where = ( $where ) ? $where . " AND `login_result` = 0" : "WHERE `login_result` = 0";
			break;
		case "robots":
			$where = ( $where ) ? $where . " AND `login_result` = 3" : "WHERE `login_result` = 3";
			break;
		case "blacklist":
			$where = ( $where ) ? $where . " AND `black_list` <> ''" : "WHERE `black_list` <> ''";
			break;
	}

	$results = $wpdb->get_results(
		"
		SELECT `info`
		FROM {$wpdb->prefix}wms7_visitors
		$where
		",
		"ARRAY_A"
	);

	$data_graph = array();
	foreach ( $results as $part ) {
		$part = array_shift( $part );
		$part = stripcslashes( $part );
		if ( ! strpos( $part, "null" ) ) {
			$part1  = substr( $part, strpos( $part, "User Agent" ) + 13, -2 );
			$result = wms7_parse_user_agent( $part1 );
			switch ( $_graph_type ) {
				case "browser":
					if ( isset( $result["browser"] ) ) {
						$data_graph["browser"][] = $result["browser"];
					}
					break;
				case "device":
					if ( isset( $result["device"] ) ) {
						$data_graph["device"][] = $result["device"];
					}
					break;
				case "platform":
					if ( isset( $result["platform"] ) ) {
						$data_graph["platform"][] = $result["platform"];
					}
					break;
			}
		}
	}
	if ( ! empty( $data_graph ) ) {
		$data_graph = array_count_values( array_shift( $data_graph ) );
	}

	return $data_graph;
}
/**
 * Description: Create table statistic of visits.
 * @param  string $where Login result (0, 1, 2, 3).
 * @return array
 */
function wms7_create_table_stat( $where ) {
	global $wpdb;

	$_radio_stat = filter_input( INPUT_POST, "radio_stat", FILTER_DEFAULT );

	switch ( $_radio_stat ) {
		case "":
		case "visits":
			$where = ( $where ) ? $where : "";
			break;
		case "unlogged":
			$where = ( $where ) ? $where . " AND `login_result` = 2" : "WHERE `login_result` = 2";
			break;
		case "success":
			$where = ( $where ) ? $where . " AND `login_result` = 1" : "WHERE `login_result` = 1";
			break;
		case "failed":
			$where = ( $where ) ? $where . " AND `login_result` = 0" : "WHERE `login_result` = 0";
			break;
		case "robots":
			$where = ( $where ) ? $where . " AND `login_result` = 3" : "WHERE `login_result` = 3";
			break;
		case "blacklist":
			$where = ( $where ) ? $where . " AND `black_list` <> ''" : "WHERE `black_list` <> ''";
			break;
	}

	$sql = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wms7_visitors_cross_table (`date_country` longtext NOT NULL,`tbl_country` longtext NOT NULL,`tbl_result` longtext NOT NULL)";
	$wpdb->query( $sql );
	$sql = "TRUNCATE TABLE {$wpdb->prefix}wms7_visitors_cross_table";
	$wpdb->query( $sql );

	$sql = "INSERT INTO {$wpdb->prefix}wms7_visitors_cross_table (`date_country`, `tbl_country`, `tbl_result`)
	SELECT DATE_FORMAT(`time_visit`,'%Y %m') as `date_country`, LEFT(`country`,4) as `tbl_country`, COUNT(`user_ip`) as `tbl_result` FROM {$wpdb->prefix}wms7_visitors $where GROUP BY `date_country`, `tbl_country` ORDER BY `tbl_country`";
	$wpdb->query( $sql );

	$sql        = "SELECT DISTINCT `tbl_country` FROM {$wpdb->prefix}wms7_visitors_cross_table";
	$data_array = $wpdb->get_results( $sql, "ARRAY_A" );

	$sql = "SELECT `date_country`, ";
	foreach ( $data_array as $values ) {
		$sql = $sql . "group_concat(IF(`tbl_country`='" . $values["tbl_country"] . "', tbl_result, NULL)) as `" . $values["tbl_country"] . "`, ";
	}
	$sql     = substr( $sql, 0, -2 );
	$sql     = $sql . " FROM {$wpdb->prefix}wms7_visitors_cross_table GROUP BY `date_country`";
	$records = $wpdb->get_results( $sql, "ARRAY_A" );

	return ( $records );
}
