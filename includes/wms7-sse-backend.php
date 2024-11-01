<?php
/**
 * Description: Send a count of records of visitor.
 * PHP version 8.0.1
 * @category Wms7-sse-backend.php
 * @package  WatchMan-Site7
 * @author   Oleg Klenitsky <klenitskiy.oleg@mail.ru>
 * @version  4.2.0
 * @license  GPLv2 or later
 * @filesource
 */

/**
 * Description: Send information to the client browser (backend of site).
 * @param string $data Number of visits records.
 */
function wms7_send_backend( $data ) {

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
 * Description: Check to send information to the client browser (backend of site).
 */
function wms7_sse_backend() {
	wms7_send_backend( get_option("wms7_backend", "0") );
	wp_die();
}
/**
 * Description: Obtain the number of all visits records.
 * @return number.
 */
function wms7_count_rows() {
	global $wpdb;

	$results = $wpdb->get_var(
		$wpdb->prepare(
			"
      SELECT count(%s) FROM {$wpdb->prefix}wms7_visitors
      ",
			"*"
		)
	);
	return $results;
}
