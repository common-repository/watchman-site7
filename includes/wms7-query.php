<?php
/**
 * Description: Main function to build console.
 * PHP version 8.0.1
 * @category   wms7-query.php
 * @package    WatchMan-Site7
 * @author     Oleg Klenitsky <klenitskiy.oleg@mail.ru>
 * @version    4.2.0
 * @license    GPLv2 or later
 * @filesource
 */

/**
 * Description: Main function to build console.
 */
function wms7_query() {
	$wms7_console = get_option( "wms7_console", array() );

	set_error_handler( 'wms7_console_error_handler' );

	$_query = filter_input( INPUT_POST, 'query', FILTER_DEFAULT );
	if ( ! isset( $_query ) || ! $_query ) {
		return;
	}
	$query = str_replace( '&#39;', "'", $_query );

	$existing_vars = get_defined_vars();

	// restore console variables if they exist.
	if ( isset( $wms7_console['console_vars'] ) ) {
		extract( eval( 'return ' . $wms7_console['console_vars'] . ';' ) );
	}

	// append query to current partial query if there is one.
	if ( isset( $wms7_console['partial'] ) ) {
		$query = $wms7_console['partial'] . $query;
	}

	try {
		if ( wms7_parse_code( $query ) === false ) {
			$wms7_console = get_option( "wms7_console", array() );
			$response     = array();
			// start output buffer (to capture prints).
			ob_start();
			$rval = ( false !== strpos($wms7_console['code'], ";") ) ? eval( $wms7_console['code'] ) : exit();
			$response['output'] = ob_get_contents();
			// quietly discard buffered output.
			ob_end_clean();

			if ( isset( $rval ) ) {
				// do it again, this time for the return value.
				ob_start();
				print_r( $rval );
				$response['rval'] = ob_get_contents();
				ob_end_clean();
			}

			// clear the code buffer.
			$wms7_console['code']    = '';
			$wms7_console['partial'] = '';
			update_option( "wms7_console", $wms7_console );

			print json_encode( $response );
		} else {
			print json_encode( array( 'output' => 'partial' ) );
		}
	} catch ( Exception $exception ) {
		wms7_console_error( $exception->getMessage() );
	}

	// store variables to wms7_console option.
	$current_vars = get_defined_vars();
	$ignore       = array( 'query', 'response', 'rval', 'existing_vars', 'current_vars', '_SESSION' );

	wms7_save_variables( $existing_vars, $current_vars, $ignore );

	wp_die();
}
