<?php
/**
 * Description: Designed to work with the file system.
 * PHP version 8.0.1
 * @category wms7-io-interface.php
 * @package  WatchMan-Site7
 * @author   Oleg Klenitsky <klenitskiy.oleg@mail.ru>
 * @version  4.2.0
 * @license  GPLv2 or later
 * @filesource
 */

if ( ! defined( "ABSPATH" ) ) {
	exit();
}
if ( ! defined( "FS_CHMOD_FILE" ) ) {
	define( "FS_CHMOD_FILE", ( 0644 & ~ umask() ) );
}

/**
 * Description: Save file index.php.
 * @param string $file_content File content.
 */
function wms7_save_index_php( $file_content ) {
	WP_Filesystem();
	global $wp_filesystem;

	if ( current_user_can( "manage_options" ) ) {
		// file name.
		$filename = ABSPATH . "index.php";
		if ( ! file_exists( $filename ) ) {
			return;
		}
		chmod($filename, 0755);
		// save current version file.
		$arr      = $wp_filesystem->get_contents( $filename );
		$filename_old = ABSPATH . "index_old.php";
		$wp_filesystem->put_contents( $filename_old, $arr, FS_CHMOD_FILE );
		// remove the shielding.
		$file_content = stripslashes( $file_content );
		// Write content to a file.
		$wp_filesystem->put_contents( $filename, $file_content, FS_CHMOD_FILE );
	}
}
/**
 * Description: Save file robots.txt.
 * @param string $file_content File content.
 */
function wms7_save_robots_txt( $file_content ) {
	WP_Filesystem();
	global $wp_filesystem;

	if ( current_user_can( "manage_options" ) ) {
		// file name.
		$filename = ABSPATH . "robots.txt";
		if ( ! file_exists( $filename ) ) {
			return;
		}
		chmod($filename, 0755);
		// save current version file.
		$arr      = $wp_filesystem->get_contents( $filename );
		$filename_old = ABSPATH . "robots_old.txt";
		$wp_filesystem->put_contents( $filename_old, $arr, FS_CHMOD_FILE );
		// remove the shielding.
		$file_content = stripslashes( $file_content );
		// Write content to a file.
		$wp_filesystem->put_contents( $filename, $file_content, FS_CHMOD_FILE );
	}
}
/**
 * Description: Save file htaccess.
 * @param string $file_content File content.
 */
function wms7_save_htaccess( $file_content ) {
	WP_Filesystem();
	global $wp_filesystem;

	if ( current_user_can( "manage_options" ) ) {
		// file name.
		$filename = ABSPATH . ".htaccess";
		if ( ! file_exists( $filename ) ) {
			return;
		}
		chmod($filename, 0755);
		// save current version file.
		$arr      = $wp_filesystem->get_contents( $filename );
		$filename_old = ABSPATH . ".htaccess_old";
		$wp_filesystem->put_contents( $filename_old, $arr, FS_CHMOD_FILE );
		// remove the shielding.
		$file_content = stripslashes( $file_content );
		// Write content to a file.
		$wp_filesystem->put_contents( $filename, $file_content, FS_CHMOD_FILE );
	}
}
/**
 * Description: Save file wp_config.php.
 * @param string $file_content File content.
 */
function wms7_save_wp_config( $file_content ) {
	WP_Filesystem();
	global $wp_filesystem;

	if ( current_user_can( "manage_options" ) ) {
		// file name.
		$filename = ABSPATH . "wp-config.php";
		if ( ! file_exists( $filename ) ) {
			return;
		}
		chmod($filename, 0755);
		// save current version file.
		$arr      = $wp_filesystem->get_contents( $filename );
		$filename_old = ABSPATH . "wp-config_old.php";
		$wp_filesystem->put_contents( $filename_old, $arr, FS_CHMOD_FILE );
		// remove the shielding.
		$file_content = stripslashes( $file_content );
		// Write content to a file.
		$wp_filesystem->put_contents( $filename, $file_content, FS_CHMOD_FILE );
	}
}
/**
 * Description: Change settings debug_log in wp_config.php.
 * @param boolean $wp_debug True or False.
 */
function wms7_wp_debug_change( $wp_debug ) {
	WP_Filesystem();
	global $wp_filesystem;
	$item = 0;

	if ( current_user_can( "manage_options" ) ) {
		// file name.
		$filename = ABSPATH . "wp-config.php";
		if ( ! file_exists( $filename ) ) {
			return;
		}
		chmod($filename, 0755);
		// Open the file to get existing content.
		$arr = $wp_filesystem->get_contents_array( $filename );
		if ( $wp_debug ) {
			foreach ( $arr as $key => $value ) {
				if ( false  !== strpos( $value, "'WP_DEBUG', false" ) ) {
					$item1 = "define( 'WP_DEBUG_LOG', true );\n";
					$item2 = "define( 'WP_DEBUG_DISPLAY', false );\n";
					$item3 = "@ini_set( 'display_errors', 0 );\n";
					$arr[ $key ] = "define( 'WP_DEBUG', true);\n". $item1.$item2.$item3;
					break;
				}
			}
		} else {
			foreach ( $arr as $key => $value ) {
				if ( false  !== strpos( $value, "WP_DEBUG', true" ) ) {
					$arr[ $key ] = "define( 'WP_DEBUG', false);\n";
				}
				if ( false  !== strpos( $value, "WP_DEBUG_LOG'" ) ) {
					$arr[ $key ] = null;
				}
				if ( false  !== strpos( $value, "WP_DEBUG_DISPLAY'" ) ) {
					$arr[ $key ] = null;
				}
				if ( false  !== strpos( $value, "display_errors'" ) ) {
					$arr[ $key ] = null;
				}
			}
		}
		$arr = array_filter( $arr );
		// Write contents back to file.
		$wp_filesystem->put_contents( $filename, implode( "", $arr ), FS_CHMOD_FILE );
	}
}
/**
 * Description: Check file debug.log - empty or not. If not empty, the button debug_log lights up.
 */
function wms7_check_debug_log() {
	WP_Filesystem();
	global $wp_filesystem;

	if ( current_user_can( "manage_options" ) ) {
		// file name.
		$filename = ABSPATH . "wp-content/debug.log";
		if ( ! file_exists( $filename ) ) {
			return false;
		}
		chmod($filename, 0755);
		// content current file.
		$content = $wp_filesystem->get_contents( $filename );
		if ( "" !== $content ) {
			return true;
		} else {
			return false;
		}
	}
}
/**
 * Description: Clear file debug.log
 */
function wms7_clear_debug_log() {
	WP_Filesystem();
	global $wp_filesystem;

	if ( current_user_can( "manage_options" ) ) {
		// file name.
		$filename = ABSPATH . "wp-content/debug.log";
		if ( ! file_exists( $filename ) ) {
			// Clear content to a file.
			$wp_filesystem->put_contents( $filename, "", FS_CHMOD_FILE );
			return;
		}
		chmod($filename, 0755);
		// save current version file.
		$arr      = $wp_filesystem->get_contents( $filename );
		$filename_old = ABSPATH . "wp-content/debug_old.log";
		$wp_filesystem->put_contents( $filename_old, $arr, FS_CHMOD_FILE );
		// Clear content to a file.
		$wp_filesystem->put_contents( $filename, "", FS_CHMOD_FILE );
	}
}
/**
 * Description: Delete IP from file htaccess.
 * @param string $user_ip User ip.
 */
function wms7_ip_del_from_file( $user_ip ) {

	if ( ! $user_ip ) exit();

	WP_Filesystem();
	global $wp_filesystem;

	// file name.
	$filename = ABSPATH . ".htaccess";
	if ( ! file_exists( $filename ) ) {
		return;
	}
	chmod($filename, 0755);
	// Open the file to get existing content.
	$arr = $wp_filesystem->get_contents_array( $filename );

	foreach ( $arr as $key => $value ) {
		if ( ! empty( $value ) ) {
			if ( false  !== strpos( $value, $user_ip ) ) {
				$arr[ $key ] = null;
				break;
			}
		}
	}

	$arr = array_filter( $arr );
	$arr = array_unique( $arr );
	// Write contents back to file.
	$wp_filesystem->put_contents( $filename, implode( "", $arr ), FS_CHMOD_FILE );
}
/**
 * Description: Insert IP into file htaccess.
 * @param string $user_ip User ip.
 */
function wms7_ip_ins_to_file( $user_ip ) {
	WP_Filesystem();
	global $wp_filesystem;

	// file name.
	$filename = ABSPATH . ".htaccess";
	if ( ! file_exists( $filename ) ) {
		return;
	}
	chmod($filename, 0755);
	// Open the file to get existing content.
	$arr = $wp_filesystem->get_contents( $filename );

	// search string in file.
	if ( false  !== strpos( $arr, $user_ip ) ) {
		return;
	}
	// Add a new line to the file.
	$arr .= "\n" . "Deny from " . $user_ip;

	// Write contents back to file.
	$wp_filesystem->put_contents( $filename, $arr, FS_CHMOD_FILE );
}
/**
 * Description: Insert user_agent into file htaccess.
 * @param string $user_agent Robot name.
 */
function wms7_agent_ins_to_file( $user_agent ) {
	WP_Filesystem();
	global $wp_filesystem;

	// file name.
	$filename = ABSPATH . ".htaccess";
	if ( ! file_exists( $filename ) ) {
		return;
	}
	chmod($filename, 0755);
	// Open the file to get existing content.
	$arr = $wp_filesystem->get_contents( $filename );

	// insert Deny from env = wms7_bad_bot.
	if ( false  === strpos( $arr, "wms7_bad_bot" ) ) {
		$arr = "Deny from env=wms7_bad_bot" . "\n" . $arr;
		// Write contents back to file.
		$wp_filesystem->put_contents( $filename, $arr, FS_CHMOD_FILE );
	}
	$user_agent = str_replace(array( "(", ")" ), ".", $user_agent);
	// search string in file.
	if ( "" !== trim( $user_agent ) && false  !== strpos( $arr, $user_agent ) ) {
		return;
	}
	if ( "" == trim( $user_agent ) ) {
		$user_agent = "^$";
	}
	// Add a new line to the file.
	$arr = "SetEnvIfNoCase User-Agent '"
		. $user_agent
		. "' wms7_bad_bot"
		. "\n"
		. $arr;
	// Write contents back to file.
	$wp_filesystem->put_contents( $filename, $arr, FS_CHMOD_FILE );
}
/**
 * Description: Delete user_agent in file htaccess.
 * @param string $user_agent User agent of browser.
 */
function wms7_agent_del_from_file( $user_agent ) {
	WP_Filesystem();
	global $wp_filesystem;

	// file name.
	$filename = ABSPATH . ".htaccess";
	if ( ! file_exists( $filename ) ) {
		return;
	}
	chmod($filename, 0755);
	// Open the file to get existing content.
	$arr  = $wp_filesystem->get_contents_array( $filename );
	if ( ! empty( $user_agent ) ) {
		$user_agent = str_replace(array( "(", ")" ), ".", $user_agent);
	} else {
		$user_agent = "^$";
	}

	foreach ( $arr as $key => $value ) {
		if ( ! empty( $value ) ) {
			if ( false !== strpos( $value, $user_agent ) ) {
				$arr[ $key ] = null;
				break;
			}
		} else {
			$arr[ $key ] = null;
		}
	}
	$arr = array_filter( $arr );
	$arr = array_unique( $arr );
	// Write contents back to file.
	$wp_filesystem->put_contents( $filename, implode( "", $arr ), FS_CHMOD_FILE );
}
