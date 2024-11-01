<?php
/**
 * Description: Creates a site visit custom table.
 *
 * PHP version 8.0.1
 * @category Wms7_List_Table
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
 * Description: Creates a site visit custom table.
 * @category Class
 * @package  WatchMan-Site7
 * @author   Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version  4.1.0
 * @license  GPLv2 or later
 */
class Wms7_List_Table extends WP_List_Table {

	/**
	 * Description: Saves the current values variables in the array for all modules of plugin.
	 *
	 * @var array
	 */
	private static $wms7_data;

	/**
	 * Description: Class constructor Wms7_List_Table.
	 */
	public function __construct() {
		parent::__construct(
			array(
				"plural"   => "",
				"singular" => "",
				"ajax"     => false,
			)
		);
	}
	public function no_items() {
		$prm_search = get_option( 'wms7_search' );
		if ( "" === $prm_search ) {
			_e( "No items found." );
		} else {
			_e( "No results were found for this filter value: " . "<b>" .$prm_search  . "</b>");
			update_option( "wms7_search", "" );
		}
	}
	/**
	 * Description: Saves variables for future use.
	 * @param string $name  Set name variable.
	 * @param string $value Set value variable.
	 */
	public static function wms7_set( $name, $value ) {
		self::$wms7_data[ $name ] = $value;
	}
	/**
	 * Description: Gets variables for future use.
	 * @param  string $name The name of the variable to get the value.
	 * @return array|false
	 */
	public static function wms7_get( $name ) {
		return ( isset( self::$wms7_data[ $name ] ) ) ? self::$wms7_data[ $name ] : false;
	}
	/**
	 * Description: Save the current url of the plugin in wp_options.
	 * @return string Current url
	 */
	public function wms7_save_current_url() {
		$_request_uri = filter_input( INPUT_SERVER, "REQUEST_URI", FILTER_DEFAULT );
		$param        = array();
		// get current args from the URL.
		$query = wp_parse_url( $_request_uri );
		$args  = wp_parse_args( $query["query"] );

		if ( isset( $args["filter_country"] ) && "" !== $args["filter_country"] ) {
			$param["filter_country"] = $args["filter_country"];
		}

		if ( isset( $args["filter_role"] ) && "" !== $args["filter_role"] ) {
			$param["filter_role"] = $args["filter_role"];
		}

		if ( isset( $args["filter_time"] ) && "" !== $args["filter_time"] ) {
			$param["filter_time"] = $args["filter_time"];
		}

		if ( isset( $args["result"] ) && "" !== $args["result"] ) {
			$param["result"] = $args["result"];
		}

		if ( isset( $args["orderby"] ) && "" !== $args["orderby"] ) {
			$param["orderby"] = $args["orderby"];
		}

		if ( isset( $args["order"] ) && "" !== $args["order"] ) {
			$param["order"] = $args["order"];
		}

		if ( isset( $args["paged"] ) && "" !== $args["paged"] ) {
			$param["paged"] = $args["paged"];
		}

		if ( isset( $args["page"] ) && "" !== $args["page"] ) {
			$param["page"] = $args["page"];
		}

		if ( isset( $args["mode"] ) && "" !== $args["mode"] ) {
			$param["mode"] = $args["mode"];
		}

		$menu_page_url                                     = menu_page_url( "wms7_visitors", false );
		( is_array( $param ) && ! empty( $param ) ) ? $url = add_query_arg( $param, $menu_page_url ) : $url = $menu_page_url;
		// save the current url of the plugin in wp_options.
		update_option( "wms7_current_url", $url );
	}
	/**
	 * Description: Creates additional controls (Filters 2 level) to be displayed between bulk activities and pagination.
	 * @param string $which Displayed top or bottom.
	 */
	public function extra_tablenav( $which ) {
		$val = get_option( "wms7_screen_settings" );

		if ( "top" == $which ) {

			$all_link        = isset( $val["all_link"] ) ? $val["all_link"] : 0;
			$unlogged_link   = isset( $val["unlogged_link"] ) ? $val["unlogged_link"] : 0;
			$successful_link = isset( $val["successful_link"] ) ? $val["successful_link"] : 0;
			$failed_link     = isset( $val["failed_link"] ) ? $val["failed_link"] : 0;
			$robots_link     = isset( $val["robots_link"] ) ? $val["robots_link"] : 0;
			$blacklist_link  = isset( $val["blacklist_link"] ) ? $val["blacklist_link"] : 0;

			$hidden_all_link        = ( "1" == $all_link ) ? "" : "hidden=true";
			$hidden_unlogged_link   = ( "1" == $unlogged_link ) ? "" : "hidden=true";
			$hidden_successful_link = ( "1" == $successful_link ) ? "" : "hidden=true";
			$hidden_failed_link     = ( "1" == $failed_link ) ? "" : "hidden=true";
			$hidden_robots_link     = ( "1" == $robots_link ) ? "" : "hidden=true";
			$hidden_blacklist_link  = ( "1" == $blacklist_link ) ? "" : "hidden=true";
			?>
			<label class="visits" title="<?php echo __( "Filter 2 level", "wms7" ); ?>"><?php echo __( "Visits", "wms7" ); ?> : </label>

			<input class="radio" id="radio-1" name="result" type="radio" value="1" <?php echo __( $hidden_all_link ); ?>onclick="wms7_visit(id)">
			<label for="radio-1" <?php echo __( $hidden_all_link ); ?> ><?php echo __( "All", "wms7" ); ?>(<?php echo __( $this->wms7_get( "allTotal" ) ); ?>)</label>

			<input class="radio" id="radio-2" name="result" type="radio" value="1" <?php echo __( $hidden_unlogged_link ); ?>onclick="wms7_visit(id)">
			<label for="radio-2" <?php echo __( $hidden_unlogged_link ); ?> ><?php echo __( "Unlogged", "wms7" ); ?>(<?php echo __( $this->wms7_get( "visitsTotal" ) ); ?>)</label>

			<input class="radio" id="radio-3" name="result" type="radio" value="1" <?php echo __( $hidden_successful_link ); ?>onclick="wms7_visit(id)">
			<label for="radio-3" <?php echo __( $hidden_successful_link ); ?> ><?php echo __( "Success", "wms7" ); ?>(<?php echo __( $this->wms7_get( "successTotal" ) ); ?>)</label>

			<input class="radio" id="radio-4" name="result" type="radio" value="1" <?php echo __( $hidden_failed_link ); ?>onclick="wms7_visit(id)">
			<label for="radio-4" <?php echo __( $hidden_failed_link ); ?> ><?php echo __( "Failed", "wms7" ); ?>(<?php echo __( $this->wms7_get( "failedTotal" ) ); ?>)</label>

			<input class="radio" id="radio-5" name="result" type="radio" value="1" <?php echo __( $hidden_robots_link ); ?>onclick="wms7_visit(id)">
			<label for="radio-5" <?php echo __( $hidden_robots_link ); ?> ><?php echo __( "Robots", "wms7" ); ?>(<?php echo __( $this->wms7_get( "robotsTotal" ) ); ?>)</label>

			<input class="radio" id="radio-6" name="result" type="radio" value="1" <?php echo __( $hidden_blacklist_link ); ?>onclick="wms7_visit(id)">
			<label for="radio-6" <?php echo __( $hidden_blacklist_link ); ?> ><?php echo __( "Black list", "wms7" ); ?>(<?php echo __( $this->wms7_get( "blacklistTotal" ) ); ?>)</label>

			<?php
		}
		// switcher top & bottom.
		$_mode = filter_input( INPUT_GET, "mode", FILTER_DEFAULT );
		$mode  = ( $_mode ) ? $_mode : "list";
		$table = new wms7_List_Table();
		$table->view_switcher( $mode );

		if ( "bottom" == $which ) {

			$index_php     = isset( $val["index_php"] ) ? $val["index_php"] : 0;
			$robots_txt    = isset( $val["robots_txt"] ) ? $val["robots_txt"] : 0;
			$htaccess      = isset( $val["htaccess"] ) ? $val["htaccess"] : 0;
			$wp_config_php = isset( $val["wp_config_php"] ) ? $val["wp_config_php"] : 0;
			$wp_cron       = isset( $val["wp_cron"] ) ? $val["wp_cron"] : 0;
			$statistic     = isset( $val["statistic"] ) ? $val["statistic"] : 0;
			$console       = isset( $val["console"] ) ? $val["console"] : 0;
			$debug_log     = isset( $val["debug_log"] ) ? $val["debug_log"] : 0;

			$hidden_index_php     = ( "1" == $index_php ) ? "" : "display:none;";
			$hidden_robots_txt    = ( "1" == $robots_txt ) ? "" : "display:none;";
			$hidden_htaccess      = ( "1" == $htaccess ) ? "" : "display:none;";
			$hidden_wp_config_php = ( "1" == $wp_config_php ) ? "" : "display:none;";
			$hidden_wp_cron       = ( "1" == $wp_cron ) ? "" : "display:none;";
			$hidden_statistic     = ( "1" == $statistic ) ? "" : "display:none;";
			$hidden_console       = ( "1" == $console ) ? "" : "display:none;";
			$hidden_debug_log     = ( "1" == $debug_log ) ? "" : "display:none;";

			$class_button = ( wms7_check_debug_log() ) ? "button blinking" : "button";

			// The code adds the buttons after the table.
			?>
			<form id="btns_service" method="POST">
				<input type="submit" value="index" id="btn_service1" class="button" name="btns_service" title="<?php echo __( "index.php of site", "wms7" ); ?>"  style="width:75px;<?php echo __( $hidden_index_php ); ?>">

				<input type="submit" value="robots" id="btn_service2" class="button" name="btns_service" title="<?php echo __( "robots.txt of site", "wms7" ); ?>" style="width:75px;<?php echo __( $hidden_robots_txt ); ?>">

				<input type="submit" value="htaccess" id="btn_service3" class="button" name="btns_service" title="<?php echo __( ".htaccess of site", "wms7" ); ?>" style="width:75px;<?php echo __( $hidden_htaccess ); ?>">

				<input type="submit" value="wp-config" id="btn_service4" class="button" name="btns_service" title="<?php echo __( "wp-config.php of site", "wms7" ); ?>" style="width:75px;<?php echo __( $hidden_wp_config_php ); ?>">

				<input type="submit" value="wp-cron" id="btn_service5" class="button" name="btns_service" title=" <?php echo __( "wp-cron events of site", "wms7" ); ?>" style="width:75px;<?php echo __( $hidden_wp_cron ); ?>">

				<input type="submit" value="statistic" id="btn_service6" class="button" name="btns_service" title="<?php echo __( "statistic of visits to site", "wms7" ); ?>" style="width:75px;<?php echo __( $hidden_statistic ); ?>">

				<input type="submit" value="console" id="btn_service8" class="button"  name="btns_service" title="<?php echo __( "console", "wms7" ); ?>" style="width:75px;<?php echo __( $hidden_console ); ?>">

				<input type="submit" value="debug.log" id="btn_service9" class="<?php echo __( $class_button ); ?>"  name="btns_service" title="<?php echo __( "error log", "wms7" ); ?>" style="width:75px;<?php echo __( $hidden_debug_log ); ?>">

				<input type="hidden" name="wms7_nonce" value="<?php echo __( wp_create_nonce( "wms7_nonce" ) ); ?>">
			</form>
			<?php
		}
	}
	/**
	 * Description: Checks the user login is compromised or not.
	 * @param  string $uid User id.
	 * @return boolean
	 */
	public static function wms7_login_compromising( $uid ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT DISTINCT `uid`
				FROM {$wpdb->prefix}wms7_visitors
				WHERE `black_list` LIKE %s
				",
				"%ban_login_:true%"
			),
			"ARRAY_A"
		);
		$compromising = false;
		foreach ( $results as $item ) {
			if ( intval( $item["uid"] ) == intval( $uid ) ) {
				$compromising = true;
				break;
			}
		}
		return $compromising;
	}
	/**
	 * Description: Checks the user IP is compromised or not.
	 * @param  string $user_ip User ip.
	 * @return boolean
	 */
	public function wms7_ip_compromising( $user_ip ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT DISTINCT `user_ip`
				FROM {$wpdb->prefix}wms7_visitors
				WHERE TRIM(`black_list`) <> %s
				",
				""
			),
			"ARRAY_A"
		);

		$compromising = false;
		foreach ( $results as $item ) {
			$item = array_shift( $item );
			if ( $item == $user_ip ) {
				$compromising = true;
				break;
			}
		}
		return $compromising;
	}
	/**
	 * Description: Checks the user agent is compromised or not.
	 * @param  string $user_agent User agent.
	 * @return boolean
	 */
	public function wms7_user_agent_compromising( $user_agent ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT DISTINCT `info`
				FROM {$wpdb->prefix}wms7_visitors
				WHERE `black_list` LIKE %s
				",
				"%ban_user_agent_:true%"
			),
			"ARRAY_A"
		);
		$compromising = false;
		foreach ( $results as $item ) {
			$item = array_shift( $item );
			$arr  = json_decode( $item, true );
			if ( $arr["User Agent"] == $user_agent ) {
				$compromising = true;
				break;
			}
		}
		return $compromising;
	}
	/**
	 * Description: Method used to render a column when no other specific method exists for that column.
	 * When WP_List_Tables attempts to render columns, it first checks for a column-specific method.
	 * If none exists, it defaults to this method instead.
	 * @param  array  $item Content cell of table.
	 * @param  string $column_name Column name.
	 * @return string
	 */
	public function column_default( $item, $column_name ) {
		$_mode = filter_input( INPUT_GET, "mode", FILTER_DEFAULT );

		switch ( $column_name ) {
			case "id":
			case "uid":
			case "time_visit":
			case "user_login":
			case "user_role":
				return $item[ $column_name ];
			case "page_visit":
				$output = $item[ $column_name ];
				$output = ( isset( $_mode ) && "excerpt" == $_mode ) ? $output : substr( $output, 0, 130 ) . "...";
				return $output;
			case "page_from":
				$output = $item[ $column_name ];
				$output = ( isset( $_mode ) && "excerpt" == $_mode ) ? $output : substr( $output, 0, 130 ) . "...";
				return $output;
			case "info":
				$data = json_decode( $item[ $column_name ], true );
				if ( is_array( $data ) ) {
					$output = "";
					foreach ( $data as $k => $v ) {
						if ( "User Agent" == $k ) {
							$agent_compromising = $this->wms7_user_agent_compromising( $v );
							if ( $agent_compromising ) {
								$output .= "<span class='failed'>" . $k . "</span>: " . $v . "<br>";
							} else {
								$output .= $k . ": " . $v . "<br>";
							}
						} else {
							$output .= $k . ": " . $v . "<br>";
						}
					}
					$output = ( isset( $_mode ) && "excerpt" == $_mode ) ? $output : substr( $output, 0, 130 ) . "...";

					return $output;
				}
				break;
			default:
				return $item[ $column_name ];
		}
	}
	/**
	 * Description: Fills table cells with data in column cb (column 0).
	 * @param  array  Content cell of table.
	 * @return string sprintf(...)
	 */
	public function column_cb( $item ) {
		return sprintf(
			"<input type='checkbox' name='id[]' value='%s' />",
			$item["id"]
		);
	}
	/**
	 * Description: Fills table cells with data in column user_login (Login).
	 * @param  array $item Content cell of table.
	 * @return string sprintf(...) or item
	 */
	public function column_user_login( $item ) {

		if ( $item["uid"] ) {
			$avatar = get_avatar( $item["uid"], 30 );
			if ( isset( $avatar ) ) {
					$user_login = $avatar . "<br>" . $item["user_login"];
			} else {
				$user_login = $item["user_login"];
			}
			if ( $this->wms7_login_compromising( $item["uid"] ) ) {
				$user_login = "<span class='failed'>" . $user_login . "</span>";
			}
			return sprintf(
				"%s",
				$user_login
			);
		} else {
			return $item["user_login"];
		}
	}
	/**
	 * Description: Fills table cells with data in column user_ip (Visitor IP).
	 * @param  array Content cell of table.
	 * @return string sprintf(...)
	 */
	public function column_user_ip( $item ) {
		WP_Filesystem();
		global $wp_filesystem;

		$plugin_dir = plugin_dir_path( __FILE__ );
  	$dir_flags  = $wp_filesystem->find_folder($plugin_dir . "images/flags/");
  	$data       = explode("<br>", $item[ "country" ]);
  	$file       = trailingslashit($dir_flags) . $data[0] . ".gif";
  	if ($wp_filesystem->exists($file)) {
  		$plugin_url = plugins_url();
			$path_img = set_url_scheme( $plugin_url."/watchman-site7/", "https" )."images/flags/".$data[0].".gif";
			$img_flag = "<image src='$path_img' >";
		}else{
			$img_flag = "";
		}
		// Checking the compromising IP.
		if ( $this->wms7_ip_compromising( $item["user_ip"] ) ) {
			$item["user_ip"] = "<span class='failed'>" . $item["user_ip"] . "</span>";
		}
		return sprintf(
			"%s",
			$item["user_ip"] . "<br>" . $img_flag . $item["country"]
		);
	}
	/**
	 * Description: Fills table cells with data in column black_list (Black list).
	 * @param  array $item Content cell of table.
	 * @return string sprintf(...)
	 */
	public function column_black_list( $item ) {
		$data = json_decode( $item["black_list"], true );
		if ( is_array( $data ) ) {
			$output = "";
			foreach ( $data as $k => $v ) {
				// echo $k . "<br>";
			}
			unset( $k );
		}

		$output = (isset($data["ban_message"])) ?  substr( $data["ban_message"], 0, 58 ) : "";

		$nonce = wp_create_nonce("wms7_nonce");
		$url   = "/wp-admin/admin.php?page=wms7_black_list";
		$url   = add_query_arg( array("uid"=>$item["uid"], "wms7_nonce"=>$nonce), $url );

		$actions = array(
			"edit"  => sprintf( "<a href='%s&id=%s&action=edit'>%s</a>", $url, $item["id"], __( "Edit", "wms7" ) ),
		);
		return sprintf(
			"%s %s",
			$output,
			$this->row_actions( $actions )
		);
	}
	/**
   * Description: Create report file of wms7_visitors table.
   * @param array $result Selected records for report.
   */
  public function wms7_create_report($result) {
    WP_Filesystem();
    global $wp_filesystem;

    $path = plugin_dir_path(__FILE__) . "/report";

    $tbody = "";
    foreach ( $result as $key => $tr_value ) {
      $tr = "";
      $td = "";
      $i  = 0;
      foreach ( $tr_value as $key => $td_value ) {
      	$out = "";
      	if ( "info" === $key ) {
      		$info = json_decode( $td_value, true );
      		foreach ( $info as $k => $v ) {
      			$out = $out . $k . ": " . $v . "<br>";
      		}
      		$td_value = $out;
      	}
        $td = $td . "<td class=td$i>" . $td_value . "</td>";
        $i++;
      }
      $i = 0;
      $tr = "<tr>" . $td . "</tr>";
      $tbody = $tbody . $tr;
    }

    $tbody = "<tbody>" . $tbody . "</tbody>";

    $table = "
    <table>
    <thead>
    <tr>
    <th class='td0'>№</th>
    <th class='td1'>UID</th>
    <th class='td2'>Login</th>
    <th class='td3'>Role</th>
    <th class='td4'>Date visit</th>
    <th class='td5'>Visitor IP</th>
    <th class='td6'>Page visit</th>
    <th class='td7'>Page from</th>
    <th class='td8'>Info</th>
    </tr>
    </thead>" .
    $tbody .
    "
    </table>
    ";

    $copyright = "Belarus, Minsk © 2019. Developer: Oleg Klenitsky";
    $foot = "<footer><img src='" . plugins_url( "images/flags/BY.gif" , __FILE__ ) ."'>" . $copyright . "</footer>";

    $template = "
    <!doctype html>
    <html lang='en'>
    <head>
      <meta charset='utf-8' />
      <meta name='viewport' content='width=device-width, initial-scale=1' />
      <title>Report of WatchMan-Site7: visitors of site</title>
      <style type='text/css'>
        table {
        	table-layout: fixed;
          width: 100%;
          border: 1px solid black;
          border-collapse: collapse;
        }
        th {
        	text-align: center;
        	padding: 5px;
          background-color: #E0E0E0;
          border: 1px solid black;
        }
        td {
        	text-align: left;
        	word-wrap:break-word;
        	padding: 5px;
          border: 1px solid black;
        }
        .td0, .td1 {
        	width: 5%;
        }
        .td2, .td3, .td4, .td5 {
        	width: 10%;
        }
        .td6, .td7 {
        	width: 15%;
        }
        .td8 {
        	width: 20%;
        }
        .successful {
        	font-weight: bold;
    			color: green;
        }
        .failed{
					font-weight:bold;
					color:red;
				}
				.robot{
					font-weight:bold;
					color:black;
				}
				.unlogged{
					font-weight:bold;
					color:blue;
				}
      </style>
    </head>
    <body>
    <h1>Report of WatchMan-Site7: visitors of site (" . current_time('mysql') . ")</h1>" .
    $table . $foot .
    "</body></html>";

    if ($wp_filesystem->exists($path)) {
      $wp_filesystem->put_contents( $path . "/report.html", $template, FS_CHMOD_FILE );
    }
  }
	/**
	 * Description: Creates column names for a table.
	 * @return array Name of columns.
	 */
	public function get_columns() {
		$columns = array(
			"cb"         => "<input type='checkbox'/>",
			"id"         => __( "ID", "wms7" ),
			"uid"        => __( "UID", "wms7" ),
			"user_login" => __( "Login", "wms7" ),
			"user_role"  => __( "Role", "wms7" ),
			"time_visit" => __( "Date visit", "wms7" ),
			"user_ip"    => __( "Visitor IP", "wms7" ),
			"black_list" => __( "Black list", "wms7" ),
			"page_visit" => __( "Page visit", "wms7" ),
			"page_from"  => __( "Page from", "wms7" ),
			"info"       => __( "Info", "wms7" ),
		);
		return $columns;
	}
	/**
	 * Description: Determines which columns of the table can be sorted.
	 * @return array Sortable columns
	 */
	public function get_sortable_columns() {
		$sortable_columns = array(
			"id"         => array( "id", true ),
			"uid"        => array( "uid", true ),
			"user_login" => array( "user_login", true ),
			"user_role"  => array( "user_role", true ),
			"time_visit" => array( "time_visit", true ),
			"user_ip"    => array( "user_ip", true ),
			"page_visit" => array( "page_visit", true ),
			"page_from"  => array( "page_from", true ),
		);
		return $sortable_columns;
	}
	/**
	 * Description: Determines list of bulk actions.
	 * @return array Bulk actions
	 */
	public function get_bulk_actions() {
		$actions = array(
			"clear"  => __( "Clear", "wms7" ),
			"delete" => __( "Delete", "wms7" ),
			"report" => __( "Report", "wms7" ),
		);
		return $actions;
	}
	/**
	 * Description: Performs bulk actions.
	 */
	private function process_bulk_action() {
		global $wpdb;

		$_id = filter_input( INPUT_POST, "id", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

    // security check.
    if ( isset( $_POST["_wpnonce"] ) && ! empty( $_POST["_wpnonce"] ) ) {

      $nonce  = filter_input( INPUT_POST, "_wpnonce", FILTER_DEFAULT );
      $action = "bulk-" . $this->_args["plural"];

      if ( ! wp_verify_nonce( $nonce, $action ) ) {
        wp_die( "Security check failed." );
      }
    }

    $action = $this->current_action();

    switch( $action ) {
      case "delete":
      	if ($_id) {
      		$_ids = implode( ",", $_id );
      		$results = $wpdb->query(
						"
						DELETE
						FROM {$wpdb->prefix}wms7_visitors
						WHERE `id` IN ($_ids)  AND LENGTH(`black_list`) = 0
						"
					);
					$this->wms7_set( "wms7_id_del", $results );
      	}
      	break;
      case "report":
      	if ($_id) {
      		$_ids = implode( ",", $_id );
          $result = $wpdb->get_results(
            "
            SELECT `id`,
            `uid`,
            `user_login`,
            `user_role`,
            `time_visit`,
            `user_ip`,
            `page_visit`,
            `page_from`,
            `info`
            FROM {$wpdb->prefix}wms7_visitors
            WHERE `id` IN ($_ids)
            ",
            "ARRAY_A"
          );
          $this->wms7_create_report($result);
      	}
      	break;
      case "clear":
      	if ($_id) {
      		$_ids = implode( ",", $_id );
      		$count_clear = $wpdb->query(
						"
						UPDATE
						{$wpdb->prefix}wms7_visitors
						SET `black_list` = NULL
						WHERE `id` IN ($_ids)
						"
					);
					if ( $count_clear ) {
						$results = $wpdb->get_results(
							"
							SELECT `user_ip`, `info`
							FROM {$wpdb->prefix}wms7_visitors
							WHERE `id` IN ($_ids)
							",
							"ARRAY_A"
						);
						foreach ( $results as $result ) {
							// Delete user_ip into .htaccess.
							wms7_ip_del_from_file( $result["user_ip"] );
							// Delete user_agent into .htaccess.
							$info = json_decode( $result["info"], true );
							$user_agent = $info["User Agent"];
							wms7_agent_del_from_file( $user_agent );
						}
					}
      	}
      	break;
    }
	}
	/**
	 * Description: Forms the string where for the main SQL query to get data in the prepare_items().
	 * @return array Contains query part SQL generated by level 1 filters of plugin
	 */
	private function wms7_make_where_query() {
		$where      = array();
		$where_stat = array();
		// for build items for win pop-up statistics.
		$_stat_role    = filter_input( INPUT_POST, "filter_role", FILTER_DEFAULT );
		$_stat_time    = filter_input( INPUT_POST, "filter_time", FILTER_DEFAULT );
		$_stat_country = filter_input( INPUT_POST, "filter_country", FILTER_DEFAULT );
		// for build items for filter level1.
		$_filter_role     = filter_input( INPUT_GET, "filter_role", FILTER_DEFAULT );
		$_filter_time     = filter_input( INPUT_GET, "filter_time", FILTER_DEFAULT );
		$_filter_country  = filter_input( INPUT_GET, "filter_country", FILTER_DEFAULT );
		$_filter_login_ip = get_option("wms7_search");

		if ( ( $_filter_login_ip ) && "" !== $_filter_login_ip ) {
			$where["filter_login_ip"] = "(user_login LIKE '%{$_filter_login_ip}%' OR user_ip LIKE '%{$_filter_login_ip}%')";
		}
		if ( ( $_filter_role ) && "" !== $_filter_role ) {
			if ( 0 === $_filter_role ) {
				$where["filter_role"] = "uid <> 0 AND user_role = '$_filter_role'";
			} else {
				$where["filter_role"] = "user_role = '$_filter_role'";
			}
		}
		if ( ( $_filter_time ) && "" !== $_filter_time ) {
			$year  = substr( $_filter_time, 0, 4 );
			$month = substr( $_filter_time, -2 );

			$where["filter_time"] = "YEAR(time_visit) = $year AND MONTH(time_visit) = $month";
		}
		if ( ( $_filter_country ) && "" !== $_filter_country ) {
			$where["filter_country"] = "LEFT(country, 2) LIKE '{$_filter_country}'";
		}

		// for build items for win pop-up statistics.
		if ( ( $_stat_role ) && "" !== $_stat_role ) {
			if ( 0 === $_stat_role ) {
				$where_stat["filter_role"] = "uid <> 0 AND user_role = '$_stat_role'";
			} else {
				$where_stat["filter_role"] = "user_role = '$_stat_role'";
			}
		}
		if ( ( $_stat_time ) && "" !== $_stat_time ) {
			$year  = substr( $_stat_time, 0, 4 );
			$month = substr( $_stat_time, -2 );

			$where_stat["filter_time"] = "YEAR(time_visit) = $year AND MONTH(time_visit) = $month";
		}
		if ( ( $_stat_country ) && "" !== $_stat_country ) {
			$where_stat["filter_country"] = "country LIKE '%{$_stat_country}%'";
		}

		$arr = array();
		$arr[0] = $where;
		$arr[1] = $where_stat;

		return $arr;
	}
	/**
	 * Description: Getting data to display in the main plugin table.
	 * @return array Data to display in the main plugin table
	 */
	private function wms7_visit_get_data( $orderby = false, $order = false, $limit = 0, $offset = 0 ) {
		$_result = filter_input( INPUT_GET, "result", FILTER_DEFAULT );
		global $wpdb;

		$where = $this->wms7_make_where_query();
		$where = $where[0];

		switch ( $_result ) {
			case "0":
				$where["login_result"] = "login_result = 0";
				break;
			case "1":
				$where["login_result"] = "login_result = 1";
				break;
			case "2":
				$where["login_result"] = "login_result = 2";
				break;
			case "3":
				$where["login_result"] = "login_result = 3";
				break;
			case "4":
				$where["login_result"] = "black_list <> ''";
				break;
		}

		$orderby = ( ! isset( $orderby ) || "" === $orderby ) ? "time_visit" : $orderby;
		$order   = ( ! isset( $order ) || "" === $order ) ? "DESC" : $order;

		if ( is_array( $where ) && ! empty( $where ) ) {
			$where = " WHERE " . implode( " AND ", $where );
		} else {
			$where = "";
		}
		$results = $wpdb->get_results(
			"
			SELECT *
			FROM {$wpdb->prefix}wms7_visitors
			$where
			ORDER BY $orderby $order
			LIMIT $limit
			OFFSET $offset
			",
			"ARRAY_A"
		);

		return $results;
	}
	/**
	 * Description: Prepares the list of visits for displaying.
	 * Used to query and filter data, handle sorting, and pagination,
	 * and any other data-manipulation required prior to rendering.<br>
	 * This method should be called explicitly after instantiating Wms7_List_Table class,
	 * and before rendering.
	 */
	public function prepare_items() {
		global $wpdb;

		$this->process_bulk_action();

		// search...
		if ( isset($_POST["s"]) && "" !== $_POST["s"]) {
			update_option("wms7_search", $_POST["s"]);
    }
    if ( isset($_POST["s"]) && "" === $_POST["s"]) {
			update_option("wms7_search", "");
    }

		$arr        = $this->wms7_make_where_query();
		$where      = $arr[0];
		$where_stat = $arr[1];

		$where6 = $where;
		$where5 = $where;
		$where4 = $where;
		$where3 = $where;
		$where2 = $where;
		$where1 = $where;

		$where2["login_result"] = "login_result = '1'"; // logged visits.
		$where3["login_result"] = "login_result = '0'"; // failed visits.
		$where4["login_result"] = "login_result = '2'"; // unlogged visits.
		$where5["login_result"] = "login_result = '3'"; // robots visits.
		$where6["login_result"] = "black_list <> ''";   // black list.

		if ( is_array( $where ) && ! empty( $where ) ) {
			$where = "WHERE " . implode( " AND ", $where );
		} else {
			$where = "";
		}
		if ( is_array( $where1 ) && ! empty( $where1 ) ) {
			$where1 = "WHERE " . implode( " AND ", $where1 );
		} else {
			$where1 = "";
		}
		if ( is_array( $where2 ) && ! empty( $where2 ) ) {
			$where2 = "WHERE " . implode( " AND ", $where2 );
		} else {
			$where2 = "";
		}
		if ( is_array( $where3 ) && ! empty( $where3 ) ) {
			$where3 = "WHERE " . implode( " AND ", $where3 );
		} else {
			$where3 = "";
		}
		if ( is_array( $where4 ) && ! empty( $where4 ) ) {
			$where4 = "WHERE " . implode( " AND ", $where4 );
		} else {
			$where4 = "";
		}
		if ( is_array( $where5 ) && ! empty( $where5 ) ) {
			$where5 = "WHERE " . implode( " AND ", $where5 );
		} else {
			$where5 = "";
		}
		if ( is_array( $where6 ) && ! empty( $where6 ) ) {
			$where6 = "WHERE " . implode( " AND ", $where6 );
		} else {
			$where6 = "";
		}

		$all_total       = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wms7_visitors {$where1}");
		$success_total   = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wms7_visitors {$where2}");
		$failed_total    = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wms7_visitors {$where3}");
		$visits_total    = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wms7_visitors {$where4}");
		$robots_total    = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wms7_visitors {$where5}");
		$blacklist_total = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wms7_visitors {$where6}");

		$_result = filter_input( INPUT_GET, "result", FILTER_DEFAULT );

		switch ( $_result ) {
			case null:
				$total_items = $all_total;
				break;
			case "1":
				$total_items = $success_total;
				break;
			case "0":
				$total_items = $failed_total;
				break;
			case "2":
				$total_items = $visits_total;
				break;
			case "3":
				$total_items = $robots_total;
				break;
			case "4":
				$total_items = $blacklist_total;
				break;
		}

		$this->wms7_set( "allTotal", $all_total );
		$this->wms7_set( "successTotal", $success_total );
		$this->wms7_set( "failedTotal", $failed_total );
		$this->wms7_set( "visitsTotal", $visits_total );
		$this->wms7_set( "robotsTotal", $robots_total );
		$this->wms7_set( "blacklistTotal", $blacklist_total );
		$this->wms7_set( "where", $where );

		$where6_stat = $where_stat;
		$where5_stat = $where_stat;
		$where4_stat = $where_stat;
		$where3_stat = $where_stat;
		$where2_stat = $where_stat;
		$where1_stat = $where_stat;

		$where2_stat["login_result"] = "login_result = '1'"; // logged visits.
		$where3_stat["login_result"] = "login_result = '0'"; // failed visits.
		$where4_stat["login_result"] = "login_result = '2'"; // unlogged visits.
		$where5_stat["login_result"] = "login_result = '3'"; // robots visits.
		$where6_stat["login_result"] = "black_list <> ''";   // black list.

		if ( is_array( $where_stat ) && ! empty( $where_stat ) ) {
			$where_stat = "WHERE " . implode( " AND ", $where_stat );
		} else {
			$where_stat = "";
		}
		if ( is_array( $where1_stat ) && ! empty( $where1_stat ) ) {
			$where1_stat = "WHERE " . implode( " AND ", $where1_stat );
		} else {
			$where1_stat = "";
		}
		if ( is_array( $where2_stat ) && ! empty( $where2_stat ) ) {
			$where2_stat = "WHERE " . implode( " AND ", $where2_stat );
		} else {
			$where2_stat = "";
		}
		if ( is_array( $where3_stat ) && ! empty( $where3_stat ) ) {
			$where3_stat = "WHERE " . implode( " AND ", $where3_stat );
		} else {
			$where3_stat = "";
		}
		if ( is_array( $where4_stat ) && ! empty( $where4_stat ) ) {
			$where4_stat = "WHERE " . implode( " AND ", $where4_stat );
		} else {
			$where4_stat = "";
		}
		if ( is_array( $where5_stat ) && ! empty( $where5_stat ) ) {
			$where5_stat = "WHERE " . implode( " AND ", $where5_stat );
		} else {
			$where5_stat = "";
		}
		if ( is_array( $where6_stat ) && ! empty( $where6_stat ) ) {
			$where6_stat = "WHERE " . implode( " AND ", $where6_stat );
		} else {
			$where6_stat = "";
		}

		$all_total_stat       = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wms7_visitors {$where1_stat}");
		$success_total_stat   = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wms7_visitors {$where2_stat}");
		$failed_total_stat    = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wms7_visitors {$where3_stat}");
		$visits_total_stat    = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wms7_visitors {$where4_stat}");
		$robots_total_stat    = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wms7_visitors {$where5_stat}");
		$blacklist_total_stat = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wms7_visitors {$where6_stat}");

		$this->wms7_set( "allTotal_stat", $all_total_stat );
		$this->wms7_set( "successTotal_stat", $success_total_stat );
		$this->wms7_set( "failedTotal_stat", $failed_total_stat );
		$this->wms7_set( "visitsTotal_stat", $visits_total_stat );
		$this->wms7_set( "robotsTotal_stat", $robots_total_stat );
		$this->wms7_set( "blacklistTotal_stat", $blacklist_total_stat );
		$this->wms7_set( "where_stat", $where_stat );

		$screen      = get_current_screen();
		$per_page    = get_option( "wms7_visitors_per_page", 10 );
		$offset      = $per_page * ( $this->get_pagenum() - 1 );

		$columns     = $this->get_columns();
		$hidden_cols = get_user_option( "manage" . $screen->id . "columnshidden" );
		$hidden      = ( $hidden_cols ) ? $hidden_cols : array();
		$sortable    = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$_orderby = filter_input( INPUT_GET, "orderby", FILTER_DEFAULT );
		$orderby  = ( $_orderby ) ? $_orderby : "id";
		$_order   = filter_input( INPUT_GET, "order", FILTER_DEFAULT );
		$order    = ( $_order ) ? $_order : "desc";

		$this->items = $this->wms7_visit_get_data( $orderby, $order, $per_page, $offset );

		$this->set_pagination_args(
			array(
				"total_items" => $total_items, // total items defined above.
				"per_page"    => $per_page, // per page constant defined at top of method.
				"total_pages" => ceil( $total_items / $per_page ), // calculate pages count.
			)
		);
		$this->wms7_save_current_url();
	}
}
