<?php
/**
 * Description: Send a count of cron tasks to pop-up window wp-cron.
 * PHP version 8.0.1
 * @category Wms7-sse-cron.php
 * @package  WatchMan-Site7
 * @author   Oleg Klenitsky <klenitskiy.oleg@mail.ru>
 * @version  4.2.0
 * @license  GPLv2 or later
 * @filesource
 */

/**
 * Description: Send information to the client browser (pop-up window wp-cron).
 * @param string $data Count of cron tasks.
 */
function wms7_send_cron( $data ) {

	if ( ! headers_sent() ) {
		header( "Content-Type: text/event-stream" );
		header( "Cache-Control: no-cache" );
		header( "Connection: keep-alive" );
		header( "Access-Control-Expose-Headers: *" );

		echo "data: " . json_encode( $data );
		echo "\n\n";
	}
	// check for output_buffering activation.
	if ( 0 !== count( ob_get_status() ) ) {
		ob_flush();
	}
	flush();
}
/**
 * Description: Prepare information to the client browser (pop-up window wp-cron).
 */
function wms7_sse_cron() {
	wms7_send_cron( get_option("wms7_cron", "0|0") );
	wp_die();
}
