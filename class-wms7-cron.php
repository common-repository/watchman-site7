<?php
/**
 * Description:  Controles the cron events of the site.
 *
 * PHP version 8.0.1
 * @category Wms7_Cron
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
 * Description:  Controles the cron events of the site.
 * @category Class
 * @package  WatchMan-Site7
 * @author   Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version  4.1.0
 * @license  GPLv2 or later
 */
class Wms7_Cron {
	/**
	 * Description: Class constructor Wms7_Cron.
	 */
	public function __construct() {
		WP_Filesystem();
		/**
		 * Used to control the cron events of the site.
		 *
		 * @var object
		 */
		global $wp_filesystem;

		$this->dirname_wp      = $wp_filesystem->abspath() . "wp-admin";
		$this->dirname_wp_add  = $wp_filesystem->abspath() . "wp-includes";
		$this->dirname_themes  = $wp_filesystem->wp_themes_dir();
		$this->dirname_plugins = $wp_filesystem->wp_plugins_dir();

		$this->file_name    = [];
		$this->orphan_count = 0;
		$this->plugin_count = 0;
		$this->themes_count = 0;
		$this->wp_count     = 0;
	}
	/**
	 * Description: Delete item cron event.
	 */
	private function wms7_delete_item_cron() {
		$_cron = filter_input_array( INPUT_POST );
		foreach ( $_cron as $key => $value ) {
			$pos = strpos( $value, "cron" );
			if ( 0 === $pos ) {
				$timestamp = wp_next_scheduled( $key );
				wp_unschedule_event( $timestamp, $key );
			}
		}
	}
	/**
	 * Description: Create cron table.
	 * @return array Table rows for pop-up window wp-cron tasks
	 */
	public function wms7_create_cron_table() {
		$this->wms7_delete_item_cron();

		$list_crons    = $this->wms7_cron_view();
		$table_row     = explode( ";", $list_crons );
		$_cron_refresh = filter_input( INPUT_POST, "cron_refresh", FILTER_DEFAULT );
		$new_table_row = array();
		$i = 0;
		foreach ( $table_row as $item ) {
			$i++;
			$val = explode( "|", $item );
			if ( "" !== $val[0] ) {
				if ( $_cron_refresh ) {
					$source_task     = $this->wms7_search_into_directory( $val[0] );
					$new_table_row[] = $item . "|" . $source_task[0] . "|" . $source_task[1] . "|" . $source_task[2];
					update_option( "wms7_cron", $i . "|" . count($table_row) );
				} else {
					$source_task[0]  = __( "Source task", "wms7" );
					$source_task[1]  = "";
					$new_table_row[] = $item . "|" . $source_task[0] . "|" . $source_task[1] . "|";
				}
			}
		}
		return $new_table_row;
	}
	/**
	 * Description: Collecting all the cron events on the site.
	 * @return string
	 */
	private function wms7_cron_view() {
		$val3    = get_option( "cron" );
		$wp_cron = "";
		foreach ( $val3 as $timestamp => $cron ) {
			if ( is_array( $cron ) ) {
				foreach ( $cron as $key1 => $value1 ) {
					foreach ( $value1 as $key2 => $value2 ) {
						$wp_cron = $wp_cron . $key1 . "|" . $value2 ["schedule"] . "|" . get_date_from_gmt( date( "Y-m-d H:i:s", $timestamp ), "M j, Y -> H:i:s" ) . ";";
					}
				}
			}
		}
		return $wp_cron;
	}
	/**
	 * Description: Scanning the current directory.
	 * @param  string $dirname Dyrectory name.
	 * @param  string $context Context.
	 * @return string
	 */
	private function wms7_scan_dir( $dirname, $context ) {
		global $wp_filesystem;

		$dirlist = $wp_filesystem->dirlist( $dirname );
		if ( $dirlist ) {
			foreach ( $dirlist as $filename => $dirattr ) {
				$path = str_replace( "//", "/", $dirname . "/" . $dirattr["name"] );
				if ( "f" === $dirattr["type"] ) {
					if ( ".php" === substr( $dirattr["name"], -4 ) ) {
						// If the file *.php processed content.
						$search  = strpos( $wp_filesystem->get_contents( $path ), $context );
						if ( $search ) {
							$this->file_name[0] = $dirname;
							$this->file_name[1] = $dirattr["name"];

							return $this->file_name;
						}
					}
				} elseif ( "d" === $dirattr["type"] ) {
						// If it is a directory, recursively called function wms7_scan_dir.
						$ret = $this->wms7_scan_dir( $path, $context );
					if ( $ret ) {
						return $ret;
					}
				}
			}
		}
		return false;
	}
	/**
	 * Description: Search into directory.
	 * @param  string $context Context for scan dir.
	 * @return string
	 */
	private function wms7_search_into_directory( $context ) {
		$step1 = $this->wms7_scan_dir( $this->dirname_wp, $context );
		if ( $step1 ) {
			$this->wp_count++;
			$step1[2] = "step1";
			return $step1;
		}

		$step2 = $this->wms7_scan_dir( $this->dirname_wp_add, $context );
		if ( $step2 ) {
			$this->wp_count++;
			$step2[2] = "step2";
			return $step2;
		}

		$step3 = $this->wms7_scan_dir( $this->dirname_themes, $context );
		if ( $step3 ) {
			$this->themes_count++;
			$step3[2] = "step3";
			return $step3;
		}

		$step4 = $this->wms7_scan_dir( $this->dirname_plugins, $context );
		if ( $step4 ) {
			$this->plugin_count++;
			$step4[2] = "step4";
			return $step4;
		}

		$this->orphan_count++;
		$step5[0] = __( "Source not found", "wms7" );
		$step5[1] = __( "Source not found", "wms7" );
		$step5[2] = "step5";

		return $step5;
	}
}
