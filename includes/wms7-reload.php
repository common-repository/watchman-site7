<?php
/**
 * Description: Reload environment console of the plugin.
 * PHP version 8.0.1
 * @category   wms7-reload.php
 * @package    WatchMan-Site7
 * @author     Oleg Klenitsky <klenitskiy.oleg@mail.ru>
 * @version    4.2.0
 * @license    GPLv2 or later
 * @filesource
 */

/**
 * Description: Reload environment of console.
 */
function wms7_reload() {
	$wms7_console = get_option( "wms7_console", array() );
	if ( isset( $wms7_console["console_vars"] ) ) {
		unset( $wms7_console["console_vars"] );
	}
	if ( isset( $wms7_console["partial"] ) ) {
		unset( $wms7_console["partial"] );
	}
	update_option( "wms7_console", $wms7_console );

	echo json_encode( array( "output" => "Success reload!" ) );

	wp_die();
}
