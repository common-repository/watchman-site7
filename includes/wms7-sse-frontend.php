<?php
/**
 * Description: Transfer data about site visits to a widget - counter of visits.
 * PHP version 8.0.1
 * @category Wms7-sse-frontend.php
 * @package  WatchMan-Site7
 * @author   Oleg Klenitsky <klenitskiy.oleg@mail.ru>
 * @version  4.2.0
 * @license  GPLv2 or later
 * @filesource
 */

/**
 * Description: Serves to send information to the client browser (frontend of site).
 *
 * @param string $data Number of visits records.
 */
function wms7_send_frontend( $data ) {
	if ( ! headers_sent() ) {
		header( "Content-Type: text/event-stream" );
		header( "Cache-Control: no-cache" );
		header( "Connection: keep-alive" );
		header( "Access-Control-Expose-Headers: *" );

		echo "data: " . json_encode( $data );
		echo ";" . json_encode( current_time( "mysql" ) );
		echo "\n\n";
	}
	// check for output_buffering activation.
	if ( 0 !== count( ob_get_status() ) ) {
		ob_flush();
	}
	flush();
}
/**
 * Description: Check to send information to the client browser (frontend of site).
 */
function wms7_sse_frontend() {

	wms7_send_frontend( get_option("wms7_frontend","0,0,0,0,0,0,0,0,0") );

	wp_die();
}
/**
 * Description: Creates the number of visits to different categories of visitors and different time.
 * @return string.
 */
function wms7_widget_counter() {
	global $wpdb;

	$data_month_visits = $wpdb->get_var(
		$wpdb->prepare(
			"
      SELECT count(%s) FROM {$wpdb->prefix}wms7_visitors
      WHERE login_result <> %d AND MONTH(time_visit) = MONTH(now()) AND YEAR(time_visit) = YEAR(now())
      ",
			"*",
			3
		)
	);

	$data_month_visitors = $wpdb->get_var(
		$wpdb->prepare(
			"
			SELECT count(DISTINCT user_ip) FROM {$wpdb->prefix}wms7_visitors
			WHERE login_result <> %d AND MONTH(time_visit) = MONTH(now()) AND YEAR(time_visit) = YEAR(now())
			",
			3
		)
	);

	$data_month_robots = $wpdb->get_var(
		$wpdb->prepare(
			"
			SELECT count(DISTINCT robot) FROM {$wpdb->prefix}wms7_visitors
			WHERE login_result = %d AND MONTH(time_visit) = MONTH(now()) AND YEAR(time_visit) = YEAR(now())
			",
			3
		)
	);

	$data_week_visits = $wpdb->get_var(
		$wpdb->prepare(
			"
			SELECT count(%s) FROM {$wpdb->prefix}wms7_visitors
			WHERE login_result <> %d AND WEEK(time_visit) = WEEK(now()) AND YEAR(time_visit) = YEAR(now())
			",
			"*",
			3
		)
	);

	$data_week_visitors = $wpdb->get_var(
		$wpdb->prepare(
			"
			SELECT count(DISTINCT user_ip) FROM {$wpdb->prefix}wms7_visitors
			WHERE login_result <> %d AND WEEK(time_visit) = WEEK(now()) AND YEAR(time_visit) = YEAR(now())
			",
			3
		)
	);

	$data_week_robots = $wpdb->get_var(
		$wpdb->prepare(
			"
			SELECT count(DISTINCT robot) FROM {$wpdb->prefix}wms7_visitors
			WHERE login_result = %d AND WEEK(time_visit) = WEEK(now()) AND YEAR(time_visit) = YEAR(now())
			",
			3
		)
	);

	$data_today_visits = $wpdb->get_var(
		$wpdb->prepare(
			"
			SELECT count(%s) FROM {$wpdb->prefix}wms7_visitors
			WHERE login_result <> %d AND DATE(time_visit) = CURDATE()
			",
			"*",
			3
		)
	);

	$data_today_visitors = $wpdb->get_var(
		$wpdb->prepare(
			"
			SELECT count(DISTINCT user_ip) FROM {$wpdb->prefix}wms7_visitors
			WHERE login_result <> %d AND DATE(time_visit) = CURDATE()
			",
			3
		)
	);

	$data_today_robots = $wpdb->get_var(
		$wpdb->prepare(
			"
			SELECT count(DISTINCT robot) FROM {$wpdb->prefix}wms7_visitors
			WHERE login_result = %d AND DATE(time_visit) = CURDATE()
			",
			3
		)
	);

	$result = intval( $data_month_visits ).",".intval( $data_month_visitors ).",".intval( $data_month_robots ).",".
		intval( $data_week_visits ).",".intval( $data_week_visitors ).",".intval( $data_week_robots ).",".
		intval( $data_today_visits ).",".intval( $data_today_visitors ).",".intval( $data_today_robots );

	return $result;
}
