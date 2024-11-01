<?php
/**
 * Description: Plugin core. Registers site visits. Other service functions.
 *
 * PHP version 8.0.1
 * @category Wms7_Core
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
 * Description: Plugin core. Registers site visits. Other service functions.
 * @category Class
 * @package  WatchMan-Site7
 * @author   Oleg Klenitskiy <klenitskiy.oleg@mail.ru>
 * @version  4.2.0
 * @license  GPLv2 or later
 */
class Wms7_Core {
	/**
	 * Type of site visit. ( 0=Failed, 1=Success, 2=Unlogged, 3=Robot ).
	 *
	 * @var integer
	 */
	private $login_result;
	/**
	 * Site visitor IP address.
	 *
	 * @var string
	 */
	private $user_ip;
	/**
	 * Description: Class constructor Wms7_Core.
	 */
	public function __construct() {
		global $wpdb;

		add_action( "plugins_loaded", array( $this, "wms7_load_locale" ) );
		add_action( "init", array( $this, "wms7_init_visit_actions" ) );
		add_action( "admin_init", array( $this, "wms7_main_settings" ) );
		add_action( "admin_menu", array( $this, "wms7_admin_menu" ) );
		add_action( "admin_head", array( $this, "wms7_screen_options" ) );
		add_action( "wms7_truncate", array( $this, "wms7_truncate_log" ) );
		add_action( "wms7_htaccess", array( $this, "wms7_ctrl_htaccess" ) );
		add_action( "preprocess_comment", array( $this, "wms7_trackbacks_check" ), 0, 1 );

		add_filter( "wp_authenticate_user", array( $this, "wms7_authenticate_user" ) );
		add_filter( "screen_settings", array( $this, "wms7_screen_settings_add" ), 10, 2 );
		add_filter( "set-screen-option", array( $this, "wms7_screen_settings_save" ), 11, 3 );

		// disable users listing, posts listing.
		add_filter("rest_endpoints", array( $this, "wms7_disable_list_users_posts" ), 12, 1);

		// disable XML-RPC methods.
		add_filter( "xmlrpc_methods", array( $this, "wms7_remove_xmlrpc_methods" ) );

		// collapse admin menu.
		add_filter("admin_body_class", array( $this, "wms7_folded_menu" ), 10, 1);

		if ( ! wp_next_scheduled( "wms7_truncate" ) ) {
			wp_schedule_event( time(), "daily", "wms7_truncate" );
		}
		if ( ! wp_next_scheduled( "wms7_htaccess" ) ) {
			wp_schedule_event( time(), "hourly", "wms7_htaccess" );
		}
	}
	/**
	 * Collapse admin menu.
	 * @param  string Classes of backend.
	 */
	public function wms7_folded_menu( $classes ) {
		$_page = filter_input( INPUT_GET, "page", FILTER_DEFAULT );
		if ( "wms7_visitors" === $_page ) {
			return $classes . " folded";
		} else {
			return $classes;
		}
	}
	/**
	 * Description: Protect against the Brute Force Amplification Attack.
	 * @param  array $endpoints The available endpoints.
	 * @return array
	 */
	public function wms7_disable_list_users_posts( $endpoints ) {
		unset( $endpoints["/wp/v2/users"] );
		unset( $endpoints["/wp/v2/posts"] );

		return $endpoints;
	}
	/**
	 * Description: Protect against the Brute Force Amplification Attack.
	 * @param  array $methods Methods system.multicall of XMLRPC.
	 * @return array
	 */
	public function wms7_remove_xmlrpc_methods( $methods ) {
		return array();
	}
	/**
	 * Description: Checking the link to this site when processing trackbacks (pings).
	 * @param array $commentdata Data of comment.
	 */
	public function wms7_trackbacks_check( $commentdata ) {
		if ( in_array( $commentdata["comment_type"], array("trackback","pingback") ) ){
			$external_html = wp_remote_retrieve_body( wp_remote_get( $commentdata["comment_author_url"] ) );

			// stop if there is no link to this site.
			if ( ! preg_match( '~<a[^>]+href=[\'"](https?:)?//'. preg_quote( parse_url( home_url(), PHP_URL_HOST ) ) .'~si', $external_html) )
				die("no backlink.");
		}
	}
	/**
	 * Description: Return error if user account is blocked.
	 * @param  string $user Authenticate user.
	 * @return object $user
	 */
	public function wms7_authenticate_user( $user ) {
		$blocked = ( !empty( $user->ID ) ) ? Wms7_List_Table::wms7_login_compromising( $user->ID ) : false;
		if ( $blocked ) {
			$this->login_result = 0;
			return new WP_Error( "broke", __( "<strong>ERROR</strong>: Access denied for: ", "wms7" ) . $user->user_login );
		} else {
			return $user;
		}
	}
	/**
	 * Description: Insert/delete - Deny from IP.
	 */
	public function wms7_ctrl_htaccess() {
		global $wpdb;

		$black_list = $wpdb->get_results(
			$wpdb->prepare(
				"
        SELECT MAX(id) as id, user_ip, black_list, info
        FROM {$wpdb->prefix}wms7_visitors
        WHERE TRIM(`black_list`) <> %s
        GROUP BY user_ip, black_list, info
        ORDER BY id ASC
        ",
				""
			)
		);

		foreach ( $black_list as $key => $item ) {
			$black_list_item = json_decode( $item->black_list, true );
			$info_item       = json_decode( $item->info, true );

			$days_start = getdate(strtotime($black_list_item["ban_start_date"]))["yday"];
			$days_end   = getdate(strtotime($black_list_item["ban_end_date"]))["yday"];

			if ( getdate()["yday"] >= $days_start && getdate()["yday"] <= $days_end ) {
				wms7_ip_ins_to_file( $item->user_ip );
				if ( isset( $black_list_item["ban_user_agent"] ) && true === $black_list_item["ban_user_agent"] ) {
					wms7_agent_ins_to_file( $info_item["User Agent"] );
				}
			} else {
				wms7_ip_del_from_file( $item->user_ip );
				wms7_agent_del_from_file( $info_item["User Agent"] );
				$this->wms7_login_unbaned( $item->id );
			}
		}
	}
	/**
	 * Description: Cancels user login blocking in the black_list field.
	 * @param string $id Record id of visit.
	 */
	private function wms7_login_unbaned( $id ) {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT `black_list`
				FROM {$wpdb->prefix}wms7_visitors
				WHERE `id` = %s
				",
				$id
			)
		);
		$results = $results[0];
		$results = json_decode( $results->black_list, true );
		// unbaned user login.
		$results["ban_login"] = false;
		// save result into field black_list.
		$results = wp_json_encode( $results );
		$wpdb->update(
			$wpdb->prefix . "wms7_visitors",
			array( "black_list" => $results ),
			array( "ID" => $id )
		);
	}
	/**
	 * Description: Removes an entry from the visit table when the retention period expires.
	 */
	public function wms7_truncate_log() {
		global $wpdb;

		$opt          = get_option( "wms7_main_settings" );
		$log_duration = ( isset($val["log_duration"]) && "" !== $val["log_duration"] ) ? $val["log_duration"] : "0";

		if ( "0" !== $log_duration ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"
	        DELETE
	        FROM {$wpdb->prefix}wms7_visitors
	        WHERE `black_list` = ''
	        AND `time_visit` < DATE_SUB(CURDATE(),INTERVAL %d DAY)
	        ",
					$log_duration
				)
			);
		}
	}
	/**
	 * Description: Loading a command translation file.
	 */
	public function wms7_load_locale() {
		load_plugin_textdomain( "wms7", false, basename( dirname( __FILE__ ) ) . "/languages/" );
	}
	/**
	 * Description: Determines the type and nature of site visits.
	 */
	public function wms7_init_visit_actions() {

		// Action on successful login.
		add_action( "wp_login", array( $this, "wms7_login_success" ), 9 );

		// Action on failed login.
		add_action( "wp_login_failed", array( $this, "wms7_login_failed" ), 9 );

		// Action visit unlogged to site.
		$this->wms7_visit_site();
	}
	/**
	 * Description: Registers a visit to the site of a visitor without a login.
	 */
	public function wms7_visit_site() {
		$this->login_result = 2;
		$this->wms7_login_action();
	}
	/**
	 * Description: Registers a visit to the site of a visitor with a login.
	 */
	public function wms7_login_success() {
		$this->login_result = 1;
		$this->wms7_login_action();
	}
	/**
	 * Description: Registers a visit to the site of a visitor with an failed login.
	 */
	public function wms7_login_failed() {
		$this->login_result = 0;
		$this->wms7_login_action();
	}
	/**
	 * Creates an array of visitor blocking data for subsequent transfer to the DB.
	 *
	 * @param  string  $$log      Log name.
	 * @return array   In format json.
	 */
	function wms7_block_visitor( $log ) {
		if ( "" === $log ) return "";

		$user_data = get_user_by( "login", substr($log, 4) );

		if ( isset($user_data->roles) ) {
			$ban_message = "password for " . $user_data->roles[0];
		} else {
			$ban_message = "password for unknown login";
		}

		// block the address user ip.
		$date = new DateTime( current_time( "Y-m-d" ) );
		$arr = array(
			"ban_start_date" => current_time( "Y-m-d" ),
			"ban_end_date"   => $date->modify("+1 day")->format("Y-m-d"),
			"ban_message"    => $ban_message,
			"ban_notes"      => "Brute force",
			"ban_login"      => false,
			"ban_user_agent" => true,
		);
		$black_list = wp_json_encode( $arr );

		return $black_list;
	}
	/**
	 * Description: Collects visitor IP data from global variables.
	 * @return string Data of IP visitor.
	 */
	private function wms7_get_user_ip() {
		$list = "";
		// We get the headers or use the global SERVER.
		if ( function_exists( "apache_request_headers" ) ) {
			$headers = apache_request_headers();
			$list    = $list . "---Apache request headers-------------&#010;";
			foreach ( $headers as $header => $value ) {
				$list = $list . $header . ": " . $value . "&#010;";
			}
		} else {
			$headers = filter_input_array( INPUT_SERVER, FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
			$list    = $list . "---Server request headers-------------&#010;";
			foreach ( $headers as $header => $value ) {
				if ( substr( $header, 0, 5 ) === "HTTP_" ) {

					$header = substr( $header, 5 );
					$header = str_replace( "_", " ", $header );
					$header = strtolower( $header );
					$header = ucwords( $header );
					$header = str_replace( " ", "-", $header );

					$list = $list . $header . ": " . $value . "&#010;";
				}
			}
		}
		$the_ip = "";
		// We get the redirected IP-address, if it exists.
		$_x_forwarded_for = filter_input( INPUT_SERVER, "X-Forwarded-For", FILTER_DEFAULT );
		if ( $_x_forwarded_for ) {
			$the_ip .= "X-Forwarded-For = " . $_x_forwarded_for . "&#010;";
		}
		$_http_x_forwarded_for = filter_input( INPUT_SERVER, "HTTP_X_FORWARDED_FOR", FILTER_DEFAULT );
		if ( $_http_x_forwarded_for ) {
			$the_ip .= "HTTP_X_FORWARDED_FOR = " . $_http_x_forwarded_for . "&#010;";
		}
		$_http_x_forwarded = filter_input( INPUT_SERVER, "HTTP_X_FORWARDED", FILTER_DEFAULT );
		if ( $_http_x_forwarded ) {
			$the_ip .= "HTTP_X_FORWARDED = " . $_http_x_forwarded . "&#010;";
		}
		$http_x_cluster_client_ip = filter_input( INPUT_SERVER, "HTTP_X_CLUSTER_CLIENT_IP", FILTER_DEFAULT );
		if ( $http_x_cluster_client_ip ) {
			$the_ip .= "HTTP_X_CLUSTER_CLIENT_IP = " . $http_x_cluster_client_ip . "&#010;";
		}
		$_http_forwarded_for = filter_input( INPUT_SERVER, "HTTP_FORWARDED_FOR", FILTER_DEFAULT );
		if ( $_http_forwarded_for ) {
			$the_ip .= "HTTP_FORWARDED_FOR = " . $_http_forwarded_for . "&#010;";
		}
		$_http_forwarded = filter_input( INPUT_SERVER, "HTTP_FORWARDED", FILTER_DEFAULT );
		if ( $_http_forwarded ) {
			$the_ip .= "HTTP_FORWARDED = " . $_http_forwarded . "&#010;";
		}
		$_http_client_ip = filter_input( INPUT_SERVER, "HTTP_CLIENT_IP", FILTER_DEFAULT );
		if ( $_http_client_ip ) {
			$the_ip .= "HTTP_CLIENT_IP = " . $_http_client_ip . "&#010;";
		}
		$_remote_addr = filter_input( INPUT_SERVER, "REMOTE_ADDR", FILTER_DEFAULT );
		if ( $_remote_addr ) {
			$the_ip .= "REMOTE_ADDR = " . $_remote_addr;
		}
		return $list . "---" . $the_ip . "---";
	}
	/**
	 * Description: Collects all data about the visitor.
	 */
	private function wms7_login_action() {
		global $current_user;

		$_forward_for     = filter_input( INPUT_SERVER, "HTTP_X_FORWARDED_FOR", FILTER_DEFAULT );
		$_remote_addr     = filter_input( INPUT_SERVER, "REMOTE_ADDR", FILTER_DEFAULT );
		$_request_uri     = filter_input( INPUT_SERVER, "REQUEST_URI", FILTER_DEFAULT );
		$_server_addr     = filter_input( INPUT_SERVER, "SERVER_ADDR", FILTER_DEFAULT );
		$_server_name     = filter_input( INPUT_SERVER, "SERVER_NAME", FILTER_DEFAULT );
		$_server_software = filter_input( INPUT_SERVER, "SERVER_SOFTWARE", FILTER_DEFAULT );
		$_http_referer    = filter_input( INPUT_SERVER, "HTTP_REFERER", FILTER_DEFAULT );
		$_http_user_agent = filter_input( INPUT_SERVER, "HTTP_USER_AGENT", FILTER_DEFAULT );
		$_arr_cookie      = filter_var_array( $_COOKIE );

		// get user cookie.
		$user_cookie = "";
		foreach ( $_arr_cookie  as $key => $value ) {
			$user_cookie = $user_cookie . $key . "=" . $value . "&#010;";
		}
		// get user IP.
		$this->user_ip = ( $_forward_for ) ? $_forward_for : $_remote_addr;
		// get user info.
		$info_add     = $this->wms7_get_user_ip();
		$user_ip_info = $info_add . "&#010;" .
						"---Visit page information-------------&#010;" .
						"REQUEST_URI = " . $_request_uri . "&#010;" .
						"HTTP_REFERER = " . $_http_referer . "&#010;" .
						"SERVER_ADDR = " . $_server_addr . "&#010;" .
						"SERVER_NAME = " . $_server_name . "&#010;" .
						"SERVER_SOFTWARE = " . $_server_software . "&#010;" .
						"---Cookies of visitor-----------------&#010;" .
						$user_cookie;
		// get page_visit.
		$page_visit = ( $_request_uri ) ? $_request_uri : "";

		// get page_from.
		$page_from = ( $_http_referer ) ? $_http_referer : "";

		// Check $user_ip is excluded from the protocol visits.
		if ( $this->wms7_ip_excluded( $this->user_ip ) ) {
			return;
		}
		$userdata = wp_get_current_user();

		$uid = ( $userdata->ID ) ? $userdata->ID : 0;
		if ( 0 !== $uid ) {
			$this->login_result = 1;
		}
		$_log = filter_input( INPUT_POST, "log", FILTER_DEFAULT );
		$_pwd = filter_input( INPUT_POST, "pwd", FILTER_DEFAULT );
		$_rmb = filter_input( INPUT_POST, "rememberme", FILTER_DEFAULT );

		$user_login = ( $userdata->user_login ) ? $userdata->user_login : "";
		$log        = ( $_log ) ? ( "log: " . $_log ) : "";
		$pwd        = ( $_pwd ) ? ( "pwd: " . $_pwd ) : "";
		$rmbr       = ( $_rmb ) ? ( "rmbr: " . $_rmb ) : "";

		$user = ( $_log || $_pwd ) ? $log . "<br>" . $pwd . "<br>" . $rmbr : $user_login;
		// get user role.
		$user_roles = $current_user->roles;
		$user_role  = array_shift( $user_roles );
		if ( is_null( $user_role ) ) {
			$user_role = "";
		}
		if ( ( 2 === $this->login_result ) && ( $_log || $_pwd ) ) {
			return;
		}
		if ( stristr( $page_visit, "wp-admin" ) ) {
			return;
		}
		if ( stristr( $page_from, "wp-admin" ) ) {
			return;
		}
		if ( stristr( $page_visit, "wp-cron.php" ) ) {
			return;
		}
		if ( stristr( $page_visit, "login=failed" ) ) {
			return;
		}
		if ( stristr( $page_visit, "admin-ajax.php" ) ) {
			return;
		}
		// check if is robot.
		$val        = get_option( "wms7_main_settings" );
		$robots_reg = isset( $val["robots_reg"] ) ? esc_attr( $val["robots_reg"] ) : false;
		$robot      = $this->wms7_robots( $_http_user_agent );
		if ( "" !== $robot ) {
			if ( $robots_reg ) {
				$this->login_result = 3;
			} else {
				return;
			}
		}
		// get whois_service.
		$val           = get_option( "wms7_main_settings" );
		$whois_service = isset( $val["whois_service"] ) ? esc_attr( $val["whois_service"] ) : "none";

		$arr = wms7_who_is( $this->user_ip, $whois_service );

		$country  = isset( $arr["country"] ) ? ( $arr["country"] ) : "none";
		$provider = isset( $arr["provider"] ) ? $arr["provider"] : "none";

		// get add info.
		$login = "<span>" . __( "Undefined", "wms7" ) . "</span>";

		if ( 0 == $this->login_result ) {
			$login = "<span class='failed'>" . __( "Failed", "wms7" ) . "</span>";
		}
		if ( 1 == $this->login_result ) {
			$login = "<span class='successful'>" . __( "Success", "wms7" ) . "</span>";
		}
		if ( 2 == $this->login_result ) {
			$login = "<span class='unlogged'>" . __( "Unlogged", "wms7" ) . "</span>";
		}
		if ( 3 == $this->login_result ) {
			$login = "<span class='robot'>" . __( "Robot", "wms7" ) . "</span>";
		}
		$data["Login"] = $login;

		$data["User Agent"] = $_http_user_agent;
		$serialized_data    = wp_json_encode( $data );

		$val = get_option( "wms7_main_settings" );
		$val = isset( $val["attack_analyzer"] ) ? true : false;
		if ( 0 === $this->login_result && true == $val ) {
			$black_list = $this->wms7_block_visitor( $log );
			wms7_ip_ins_to_file( $this->user_ip );
			wms7_agent_ins_to_file( $_http_user_agent );
		} else {
			$black_list = "";
		}

		$values = array(
			"uid"           => $uid,
			"user_login"    => $user,
			"user_role"     => $user_role,
			"time_visit"    => current_time( "mysql" ),
			"user_ip"       => $this->user_ip,
			"user_ip_info"  => $user_ip_info,
			"black_list"    => $black_list,
			"whois_service" => $whois_service,
			"country"       => $country,
			"provider"      => $provider,
			"login_result"  => $this->login_result,
			"robot"         => $robot,
			"page_visit"    => $page_visit,
			"page_from"     => $page_from,
			"info"          => $serialized_data,
		);

		$format = array( "%d", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s", "%s" );
		// Saves all data about the visitor.
		$this->wms7_save_data( $values, $format );
	}
	/**
	 * Description: Saves all data about the visitor.
	 * @param array  $values Data of visit.
	 * @param string $format Format data of visit.
	 */
	private function wms7_save_data( $values, $format ) {
		global $wpdb;

		$wpdb->insert( $wpdb->prefix . "wms7_visitors", $values, $format );

		$new_count_rows = wms7_count_rows();

		// For backend. Current data storage: total number of visits.
		update_option( "wms7_backend", $new_count_rows );
		// For frontend. Current data storage: total number of visits to different categories of visitors and time.
		$content = wms7_widget_counter();
		update_option( "wms7_frontend", $content );
	}
	/**
	 * Description: Does not register a visit with this IP.
	 * It is recommended to exclude the registration of the site administrator's visit.
	 * @param  string  $user_ip IP of visitor.
	 * @return boolean IP of visitor exluded or not.
	 */
	private function wms7_ip_excluded( $user_ip ) {

		$val = get_option( "wms7_main_settings" );
		$val = ( isset( $val["ip_excluded"] ) ) ? esc_attr( $val["ip_excluded"] ) : "";

		if ( "" !== $val ) {
			if ( stristr( $val, strval( $user_ip ) ) ) {
				return true;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}
	/**
	 * Description: Identifies the visitor as a robot or false.
	 * @param  string  $_http_user_agent Contains the name of the robot.
	 * @return boolean Visitor is robot or not.
	 */
	private function wms7_robots( $_http_user_agent ) {

		$val   = get_option( "wms7_main_settings" );
		$val   = isset( $val["robots"] ) ? esc_attr( $val["robots"] ) : "";
		$robot = "";
		if ( "" !== $val ) {
			$result = explode( "|", $val );
			foreach ( $result as $item ) {
				if ( stristr( $_http_user_agent, strval( $item ) ) ) {
					$robot = $item;
					break;
				}
			}
		}
		return $robot;
	}
	/**
	 * Description: Adds menu pages for admin panel.
	 */
	public function wms7_admin_menu() {
		if ( is_admin() || current_user_can("manage_options") ) {
			add_menu_page( __( "Visitors", "wms7" ), __( "Visitors", "wms7" ), "manage_options", "wms7_visitors", array( $this, "wms7_visit_manager" ), "dashicons-shield", "71" );

			add_submenu_page( "wms7_visitors", __( "Settings", "wms7" ), __( "Settings", "wms7" ), "manage_options", "wms7_settings", array( $this, "wms7_settings" ) );

			add_submenu_page( "NULL", __( "Black list", "wms7" ), __( "Black list", "wms7" ), "manage_options", "wms7_black_list", array( $this, "wms7_black_list" ) );
		}
	}
	/**
	 * Description: Adds help tabs of plugin.
	 * @return object For role administrator.
	 */
	public function wms7_screen_options() {
		$url_api_doc  = "https://www.adminkov.bcr.by/doc/watchman-site7/api_doc/index.html";
		$url_user_doc = "https://www.adminkov.bcr.by/doc/watchman-site7/user_doc/index.htm";
		// execute only on wms7 pages, othewise return null.
		$_page = filter_input( INPUT_GET, "page", FILTER_DEFAULT );
		if ( "wms7_visitors" !== $_page && "wms7_settings" !== $_page && "wms7_black_list" !== $_page ) {
			return;
		}
		if ( "wms7_visitors" === $_page ) {

			$img1            = plugins_url( "/images/filters_1level.png", __FILE__ );
			$img2            = plugins_url( "/images/filters_2level.png", __FILE__ );
			$img3            = plugins_url( "/images/panel_info.png", __FILE__ );
			$img4            = plugins_url( "/images/bulk_actions.png", __FILE__ );
			$img5            = plugins_url( "/images/screen_options.png", __FILE__ );
			$img6            = plugins_url( "/images/other_functions.png", __FILE__ );
			$img7            = plugins_url( "/images/map1.png", __FILE__ );
			$img8            = plugins_url( "/images/map2.png", __FILE__ );
			$img9            = plugins_url( "/images/sse.png", __FILE__ );
			$url             = site_url();

			// if per page option is not set, use default.
			$args = array(
				"label"   => __( "The number of elements on the page:", "wms7" ),
				"default" => get_option( "wms7_visitors_per_page", 10 ),
			);
			// display options.
			add_screen_option( "per_page", $args );

			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-1",
					"title"   => __( "1.Description", "wms7" ),
					"content" => "<p>" . __( "This plugin is written for administrators of sites created on Wordpress. The main functions of the plugin are:", "wms7" ) . "<br>" . __( "1. Record the date and time of visit to the site by people, robots.", "wms7" ) . "<br>" . __( "2. The entry registration site visit: successful, unsuccessful, no registration.", "wms7" ) . "<br>" . __( "3. The entry address of the visitor: country of the visitor, the address of a provider.", "wms7" ) . "<br>" . __( "4. Record information about the browser, OS of the visitor.", "wms7" ) . "<br>" . __( "5. A visitor record in the category of unwelcome and a ban on visiting the site in a set period of time.", "wms7" ) . "<br>" . __( "For convenience the administrator of the site plugin used:", "wms7" ) . "<br>" . __( "1. Filters 1 level.", "wms7" ) . "<br>" . __( "2. Filters 2 level.", "wms7" ) . "<br>" . __( "3. The deletion of unnecessary records on the visit in automatic and manual modes.", "wms7" ) . "<br>" . __( "4. Report of visits to the site in an external file for later analysis.", "wms7" ) . "</p>",
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-2",
					"title"   => __( "2.Filters level I", "wms7" ),
					"content" => __( "The first level filters are filters located in the upper part of the main page of the plugin:", "wms7" ) . "<br>" . __( "- (group 1) ", "wms7" ) . "<a href='https://wordpress.org/support/article/roles-and-capabilities/' target='_blank'>role</a>" . __( " of visitors.", "wms7" ) . "<br>" . __( "- (group 1) date (month/year) visiting the site.", "wms7" ) . "<br>" . __( "- (group 1) country of visitors to the site.", "wms7" ) . "<br>" . __( "- (group 2), the username or IP of the visitor of the website.", "wms7" ) . "<br>" . __( "Filters of the first level are major and affect the operation of filters of the second level. At the first level of filters in groups I and II are mutually exclusive and can simultaneously work with only one group of filters of the first level. The range of values in the drop-down filter list level I group 1 is based on actual visits to the site visitors.", "wms7" ) . "<br><br>" . __( "Filter level I (groups 1 and 2)", "wms7" ) . "<br><img src=" . $img1 . " style='float: left;'>",
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-3",
					"title"   => __( "3.Filters level II", "wms7" ),
					"content" => __( "The second level filters are filters located in the upper part of the main page of the plugin under the colour panel:", "wms7" ) . "<br>" . __( "- All visits (number of visits).", "wms7" ) . "<br>" . __( "- Visit without registering on the website (number of visits).", "wms7" ) . "<br>" . __( "- Visits to successful registered users of the website (number of visits).", "wms7" ) . "<br>" . __( "- Unsuccessful attempts to register on the website website visitors (number of attempts).", "wms7" ) . "<br>" . __( "- list of the robots visiting the website (number of visits).", "wms7" ) . "<br>" . __( "- Visitors to the website listed in the black list (the number).", "wms7" ) . "<br>" . __( "Filter level II, working within the rules set by the filters level I.", "wms7" ) . "<br><br>" . __( "Filter level II (6 pieces)", "wms7" ) . "<br><img src=" . $img2 . " style='float: left;'>",
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-4",
					"title"   => __( "4.Panel info", "wms7" ),
					"content" => __( "Dashboard (panel - info) consists of four information blocks:", "wms7" ) . "<br>" . __( "- Block « Settings » it displays the settings of the plugin installed on the Settings page.", "wms7" ) . "<br>" . __( "- Block « History of visits » to the site it displays the types of site visits (A-all visits, U-unregistered visit, S was visiting, F-unsuccessful registration attempts, R-robots). Then - the number of visits.", "wms7" ) . "<br>" . __( "- Block « Robots List » it displays the date, the time of the last visit robots entered in the list of robots on the Settings page.", "wms7" ) . "<br>" . __( "- Block  « Black List » it shows ip of the site visitors who were blocking access to the site. Display format: date of commencement of lock-access website, end date start blocking access to the site, the ip block address of the visitor.", "wms7" ) . "<br><br>" . __( "Panel info", "wms7" ) . "<br><img src=" . $img3 . " style='float: left;'>",
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-5",
					"title"   => __( "5.Bulk actions", "wms7" ),
					"content" => __( "In the category of mass actions are included:", "wms7" ) . "<br>" . __( "- clear. This action clears the Blacklist field of the selected record.", "wms7"). "<br>". __( "- delete. This action allows you to delete the selected entry in the main table - site visits. If any entry is marked for deletion and is blacklisted, the entry is not deleted until the administrator executes the Clear command.", "wms7" ) . "<br>" . __( " - report. This action creates a report of site visits from selected records in html format.", "wms7" ) . "<br><br>" . __( "Bulk actions", "wms7" ) . "<br><img src=" . $img4 . " style='float: left;'>",
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-6",
					"title"   => __( "6.Settings screen", "wms7" ),
					"content" => __( "Group screen settings: « column » and « pagination » are the standard settings of the Wordpress screen and no additional comments need.", "wms7" ) . "<br>" . __( "Group screen settings: « Display panel-info » used to display or hide the 4 dashboard: setting list, history list, robots list, black list.", "wms7" ) . "<br>" . __( "Group screen settings: « Display filters level II » are used to display or hide filters of the 2nd level, which obey to filters of the 1st level. Are located under the info-panel and executed in the form of radio buttons.", "wms7" ) . "<br>" . __( "Group screen settings: « Display buttons add functions of bottom screen » used to display or hide the call buttons of various service functions available only to the administrator.", "wms7" ) . "<br><br>" . __( "screen Settings", "wms7" ) . "<br><img src=" . $img5 . " style='float: left;'>",
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-7",
					"title"   => __( "7.Other functions", "wms7" ),
					"content" => __( "Additional features of the plugin are in the form of buttons located at the bottom of the main table, visit the website:", "wms7" ) . "<br>" . __( "- « index » feature edit and save in a modal window file index.php", "wms7" ) . "<br>" . __( "- « robots » feature edit and save in a modal window file rorots.txt", "wms7" ) . "<br>" . __( "- « htaccess » edit function and save in a modal window file .htaccess", "wms7" ) . "<br>" . __( "- « wp-config » function to edit and save it in a modal window file wp-config.php", "wms7" ) . "<br>" . __( "- « wp-cron » output function and removal of task wp-cron in a modal window", "wms7" ) . "<br>" . __( "- « statistic » statistic of visits to the site", "wms7" ) . "<br>" . __("- « console » creates a console for executing PHP commands and WordPress functions", "wms7") . "<br>" . __("- « debug.log » Shows current PHP and WordPress error messages", "wms7") ."<br><br>" . __( "Additional features", "wms7" ) . "<br><img src=" . $img6 . " style='float: left;'>",
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-8",
					"title"   => __( "8.SSE", "wms7" ),
					"content" => __( "The SSE function (Server Send Events) is made in the form of a button located at the top of the plugin main screen. The function is designed to automatically update the screen when new visitors to the site. If you are actively working with the plug - in, it is recommended to disable SSE mode, and after the work-re-enable SSE mode", "wms7" ) . "<br><br><img src=" . $img9 . ">",
				)
			);
			// Help sidebars are optional.
			get_current_screen()->set_help_sidebar(
				"<p><strong>" . __( "Additional information:", "wms7" ) . "</strong></p>" .
				"<p><a href='https://wordpress.org/plugins/watchman-site7/' target='_blank'>" . __( "page the WordPress repository", "wms7" ) . "</a></p>" .
				"<p><a href='$url_api_doc' target='_blank'>" . __( "API Documentation", "wms7" ) . "</a></p>" .
				"<p><a href='$url_user_doc' target='_blank'>" . __( "User Documentation", "wms7" ) . "</a></p>" .
				"<p><a href='https://www.adminkov.bcr.by/category/watchman-site7/' target='_blank'>" . __( "home page support plugin", "wms7" ) . "</a></p>" .
				"<p><a href='https://www.youtube.com/watch?v=iB-7anPcUxU&list=PLe_4Q0gv64g3WgA1Mo_S3arSrK3htZ1Nt' target='_blank'>" . __( "training video", "wms7" ) . "</a></p>"
			);
		}
		if ( "wms7_settings" === $_page ) {
			$img1  = plugins_url( "/images/options.png", __FILE__ );
			$img3  = plugins_url( "/images/ip_excluded.png", __FILE__ );
			$img4  = plugins_url( "/images/whois_service.png", __FILE__ );
			$img5  = plugins_url( "/images/robots.png", __FILE__ );
			$img7  = plugins_url( "/images/robots_banned.png", __FILE__ );
			$img8  = plugins_url( "/images/google_map_api.png", __FILE__ );
			$img9  = plugins_url( "/images/export_fields_csv.png", __FILE__ );
			$img10 = plugins_url( "/images/yahoo_com.png", __FILE__ );

			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-0",
					"title"   => __( "General settings", "wms7" ),
					"content" => __( "On this page, the basic plugin settings are formed, which are stored in the table: prefix_options in the site database. The main settings are grouped in option: wms7_main_settings. Screen settings are stored in the same table in the wms7_screen_settings option. When uninstalling the plugin, the above parameters will be removed.  Also tables of plugin: prefix_wms7_visitors and prefix_wms7_countries from the site database will be deleted.", "wms7" ) . "<br><br>" . __( "Fragment table prefix_options", "wms7" ) . "<br><img src=" . $img1 . " style='float: left;'>",
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-1",
					"title"   => __( "1.Duration log entries", "wms7" ),
					"content" => __( "The value of this field determines how long information about website visits should be stored. If the value of this parameter is 0, then all data on site visits will be stored permanently. The deletion of information about visits that have expired is automatically performed by the cron event 'wms7_truncate' once a day. However, this event will fire every time the Save button is clicked on the page General settings of this plugin.", "wms7" ),
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-2",
					"title"   => __( "2.Do not save visits for", "wms7" ),
					"content" => __( "List of IP-addresses whose visits will not be displayed in the table of site visits. This can be useful for the ip address of the site administrator, which does not make sense to store in the table of site visits. Separate the list of IP addresses with - '|'. All site visits from the listed IP addresses will be ignored for entry in the site visits table.", "wms7" ) . "<br><img src=" . $img3 . " style='float: left;'>",
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-3",
					"title"   => __( "3.Robots", "wms7" ),
					"content" => __( "List of names whose visits will be displayed in the table of site visits as robots. This list can be supplemented with other names of robots. Separate names in the list with - '|'. Robot names must not contain spaces or special characters.", "wms7" ) . "<br><img src=" . $img5 . " style='float: left;'>",
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-4",
					"title"   => __( "4.Visits of robots", "wms7" ),
					"content" => __( "In the case of setting the flag, all visits by robots will be recorded in the database. The names of the robots are taken from section 4 of the Robots", "wms7" ),
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-5",
					"title"   => __( "5.SSE sound", "wms7" ),
					"content" => __( "Designed to beep when the screen refreshes when new site visitors appear. The screen is refreshed when the SSE button on the plugin main page is enabled.", "wms7"),
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-6",
					"title"   => __( "6.WP DEBUG", "wms7" ),
					"content" => __( "Enabling WP_DEBUG will cause all PHP errors, notices and warnings to be displayed. This is likely to modify the default behavior of PHP which only displays fatal errors and/or shows a white screen of death when errors are reached.Showing all PHP notices and warnings often results in error messages for things that don’t seem broken, but do not follow proper data validation conventions inside PHP. These warnings are easy to fix once the relevant code has been identified, and the resulting code is almost always more bug-resistant and easier to maintain.<br>Enabling WP_DEBUG will also cause notices about deprecated functions and arguments within WordPress that are being used on your site. These are functions or function arguments that have not been removed from the core code yet but are slated for deletion in the near future. Deprecation notices often indicate the new function that should be used instead. ", "wms7"),
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-7",
					"title"   => __( "7.Attack analyzer", "wms7" ),
					"content" => __( "Attack analyzer Brute force", "wms7"),
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-8",
					"title"   => __( "8.WHO-IS service", "wms7" ),
					"content" => __( "Choice of one of 4 WHO-IS providers. Information about the site visitor is provided in the form: the visitor's country code, the name of the visitor's country, the visitor's city. The quality and reliability of the information provided varies from region to region. The information provided by the service WHO-IS provider is displayed on the plugin's main page in the visitor's IP address column.", "wms7" ) . "<br><img src=" . $img4 . " style='float: left;'>",
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-9",
					"title"   => __( "9.STUN server", "wms7" ),
					"content" => __( "Select a STUN server to get additional information: internal IP address of site visitors. ", "wms7"),
				)
			);
			// Help sidebars are optional.
			get_current_screen()->set_help_sidebar(
				"<p><strong>" . __( "Additional information:", "wms7" ) . "</strong></p>" .
				"<p><a href='https://wordpress.org/plugins/watchman-site7/' target='_blank'>" . __( "page the WordPress repository", "wms7" ) . "</a></p>" .
				"<p><a href='$url_api_doc' target='_blank'>" . __( "API Documentation", "wms7" ) . "</a></p>" .
				"<p><a href='$url_user_doc' target='_blank'>" . __( "User Documentation", "wms7" ) . "</a></p>" .
				"<p><a href='https://www.adminkov.bcr.by/category/watchman-site7/' target='_blank'>" . __( "home page support plugin", "wms7" ) . "</a></p>" .
				"<p><a href='https://www.youtube.com/watch?v=iB-7anPcUxU&list=PLe_4Q0gv64g3WgA1Mo_S3arSrK3htZ1Nt' target='_blank'>" . __( "training video", "wms7" ) . "</a></p>"
			);
			return current_user_can( "manage_options" );
		}
		if ( "wms7_black_list" === $_page ) {
			$img1 = plugins_url( "/images/black_list.png", __FILE__ );
			$img2 = plugins_url( "/images/ban_start_date.png", __FILE__ );
			$img3 = plugins_url( "/images/ban_end_date.png", __FILE__ );

			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-1",
					"title"   => __( "1. Black list", "wms7" ),
					"content" => __( "On this page information is generated to block access to the IP of the visitor to the site visit. Information to lock is stored in the file .htaccess in a string (for example): Deny from 104.223.44.213", "wms7" ) . "<br><br>" . __( "Information about blocking the IP of the visitor is stored in the form of:", "wms7" ) . "<br><img src='" . $img1 . "' style='float: left;'>",
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-2",
					"title"   => __( "2.field: Ban start date", "wms7" ),
					"content" => __( "This field indicates the start date of blocking the IP address of the visitor. An example of selecting the date of blocking the IP of the visitor:", "wms7" ) . "<br><br><img src='" . $img2 . "' style='float: left;'>",
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-3",
					"title"   => __( "3.field: Ban end date", "wms7" ),
					"content" => __( "On this page information is generated about the end of the lock IP of the visitor to the site. The reservation is removed from the file .htaccess end IP block the visitor:", "wms7" ) . "<br><br><img src='" . $img3 . "' style='float: left;''>",
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-4",
					"title"   => __( "4.field: Ban message", "wms7" ),
					"content" => __( "This field is used to store information as to why the decision of the administrator about the IP blocking the website visitor.", "wms7" ),
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-5",
					"title"   => __( "5.field: Ban notes", "wms7" ),
					"content" => __( "Additional, redundant field. Is used for convenience by the site administrator.", "wms7" ),
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-6",
					"title"   => __( "6.field: Ban user login", "wms7" ),
					"content" => __( "By selecting this option, the user's login will be blocked and he will not be able to log in under any IP address.", "wms7" ),
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-7",
					"title"   => __( "7.field: Ban user agent", "wms7" ),
					"content" => __( "By selecting this option, any user will not be able to visit this site with this User Agent. It is advisable to use this option for a short period of time, since this User Agent can be used by other users.", "wms7" ),
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-8",
					"title"   => __( "8.field: IP info1", "wms7" ),
					"content" => __( "This option displays devices on the local network from which the user accessed this site. For this option to work, you need to select any suitable stun server in the settings of this plugin (17.STUN server).", "wms7" ),
				)
			);
			get_current_screen()->add_help_tab(
				array(
					"id"      => "wms7-tab-9",
					"title"   => __( "9.field: IP info2", "wms7" ),
					"content" => __( "This option stores additional information about the visitor obtained from the PHP global environment variables.", "wms7" ),
				)
			);
			// Help sidebars are optional.
			get_current_screen()->set_help_sidebar(
				"<p><strong>" . __( "Additional information:", "wms7" ) . "</strong></p>" .
				"<p><a href='https://wordpress.org/plugins/watchman-site7/' target='_blank'>" . __( "page the WordPress repository", "wms7" ) . "</a></p>" .
				"<p><a href='$url_api_doc' target='_blank'>" . __( "API Documentation", "wms7" ) . "</a></p>" .
				"<p><a href='$url_user_doc' target='_blank'>" . __( "User Documentation", "wms7" ) . "</a></p>" .
				"<p><a href='https://www.adminkov.bcr.by/category/watchman-site7/' target='_blank'>" . __( "home page support plugin", "wms7" ) . "</a></p>" .
				"<p><a href='https://www.youtube.com/watch?v=iB-7anPcUxU&list=PLe_4Q0gv64g3WgA1Mo_S3arSrK3htZ1Nt' target='_blank'>" . __( "training video", "wms7" ) . "</a></p>"
			);
			return current_user_can( "manage_options" );
		}
		$table = new wms7_List_Table();
	}

	/**
	 * Description: Adds custom screen settings to the plugin main page.
	 * @param  string $status Status.
	 * @param  string $args   Options.
	 * @return object Custum screen settings.
	 */
	public function wms7_screen_settings_add( $status, $args ) {

		if ( "toplevel_page_wms7_visitors" == $args->base ) {

			$val = get_option( "wms7_screen_settings" );
			if ( ! $val ) {
				$val = [];
				$val["setting_list"]    = "1";
				$val["history_list"]    = "1";
				$val["robots_list"]     = "1";
				$val["black_list"]      = "1";
				$val["all_link"]        = "1";
				$val["unlogged_link"]   = "1";
				$val["successful_link"] = "1";
				$val["failed_link"]     = "1";
				$val["robots_link"]     = "1";
				$val["blacklist_link"]  = "1";
				$val["index_php"]       = "1";
				$val["robots_txt"]      = "1";
				$val["htaccess"]        = "1";
				$val["wp_config_php"]   = "1";
				$val["wp_cron"]         = "1";
				$val["statistic"]       = "1";
				$val["console"]         = "1";
				$val["debug_log"]       = "1";

				update_option( "wms7_screen_settings", $val );
			}
			$setting_list = isset( $val["setting_list"] ) ? "checked" : "";
			$history_list = isset( $val["history_list"] ) ? "checked" : "";
			$robots_list  = isset( $val["robots_list"] ) ? "checked" : "";
			$black_list   = isset( $val["black_list"] ) ? "checked" : "";

			$banner1      = isset( $val["banner1"] ) ? "checked" : "";
			$banner2      = isset( $val["banner2"] ) ? "checked" : "";

			$all_link     = isset( $val["all_link"] ) ? "checked" : "";
			$unlog_link   = isset( $val["unlogged_link"] ) ? "checked" : "";
			$suc_link     = isset( $val["successful_link"] ) ? "checked" : "";
			$failed_link  = isset( $val["failed_link"] ) ? "checked" : "";
			$robots_link  = isset( $val["robots_link"] ) ? "checked" : "";
			$black_link   = isset( $val["blacklist_link"] ) ? "checked" : "";

			$index_php    = isset( $val["index_php"] ) ? "checked" : "";
			$robots_txt   = isset( $val["robots_txt"] ) ? "checked" : "";
			$htaccess     = isset( $val["htaccess"] ) ? "checked" : "";
			$wp_config    = isset( $val["wp_config_php"] ) ? "checked" : "";
			$wp_cron      = isset( $val["wp_cron"] ) ? "checked" : "";
			$statistic    = isset( $val["statistic"] ) ? "checked" : "";
			$mail         = isset( $val["mail"] ) ? "checked" : "";
			$console      = isset( $val["console"] ) ? "checked" : "";
			$debug_log    = isset( $val["debug_log"] ) ? "checked" : "";

			$legend_panel_info = __( "Display panel info", "wms7" );
			$lbl_setting_list  = __( "Setting list", "wms7" );
			$lbl_history_list  = __( "History list", "wms7" );
			$lbl_robots_list   = __( "Robots list", "wms7" );
			$lbl_black_list    = __( "Black list", "wms7" );

			$legend_filters_level2 = __( "Display filters level II", "wms7" );
			$lbl_all_link          = __( "All visits", "wms7" );
			$lbl_unlogged_link     = __( "Unlogged visits", "wms7" );
			$lbl_successful_link   = __( "Success visits", "wms7" );
			$lbl_failed_link       = __( "Failed visits", "wms7" );
			$lbl_robots_link       = __( "Robots visits", "wms7" );
			$lbl_blacklist_link    = __( "Black list", "wms7" );

			$legend_button_bottom = __( "Display buttons add functions of bottom screen", "wms7" );
			$lbl_index            = __( "index", "wms7" );
			$lbl_robots           = __( "robots", "wms7" );
			$lbl_htaccess         = __( "htaccess", "wms7" );
			$lbl_wp_config        = __( "wp-config", "wms7" );
			$lbl_wp_cron          = __( "wp-cron", "wms7" );
			$lbl_statistic        = __( "statistic", "wms7" );
			$lbl_console          = __( "console", "wms7" );
			$lbl_debug_log        = __( "debug log", "wms7" );

			$custom_fld = "

			<fieldset class='panel-info-screen-setting'>
				<legend>$legend_panel_info</legend>

				<input type='checkbox' id='setting_list' name='wms7_screen_settings[setting_list]' value='1' $setting_list />
				<label for='setting_list'>$lbl_setting_list</label>

				<input type='checkbox' id='history_list' name='wms7_screen_settings[history_list]' value='1' $history_list />
				<label for='history_list'>$lbl_history_list</label>

				<input type='checkbox' id='robots_list' name='wms7_screen_settings[robots_list]' value='1' $robots_list />
				<label for='robots_list'>$lbl_robots_list</label>

				<input type='checkbox' id='black_list' name='wms7_screen_settings[black_list]' value='1' $black_list />
				<label for='black_list'>$lbl_black_list</label>
			</fieldset>
			<fieldset style='border: 1px solid black; padding: 0 10px;'>
				<legend>$legend_filters_level2</legend>

				<input type='checkbox' id='all_link' name='wms7_screen_settings[all_link]' value='1' $all_link />
				<label for='all_link'>$lbl_all_link</label>

				<input type='checkbox' id='unlogged_link' name='wms7_screen_settings[unlogged_link]' value='1' $unlog_link />
				<label for='unlogged_link'>$lbl_unlogged_link</label>

				<input type='checkbox' id='successful_link' name='wms7_screen_settings[successful_link]' value='1' $suc_link />
				<label for='successful_link'>$lbl_successful_link</label>

				<input type='checkbox' id='failed_link' name='wms7_screen_settings[failed_link]' value='1' $failed_link />
				<label for='failed_link'>$lbl_failed_link</label>

				<input type='checkbox' id='robots_link' name='wms7_screen_settings[robots_link]' value='1' $robots_link />
				<label for='robots_link'>$lbl_robots_link</label>

				<input type='checkbox' id='blacklist_link' name='wms7_screen_settings[blacklist_link]' value='1' $black_link />
				<label for='blacklist_link'>$lbl_blacklist_link</label>
			</fieldset>
			<fieldset style='border: 1px solid black; padding: 0 10px;'>
				<legend>$legend_button_bottom</legend>

				<input type='checkbox' id='index_php' name='wms7_screen_settings[index_php]' value='1' $index_php />
				<label for='index_php'>$lbl_index</label>

				<input type='checkbox' id='robots_txt' name='wms7_screen_settings[robots_txt]' value='1' $robots_txt />
				<label for='robots_txt'>$lbl_robots</label>

				<input type='checkbox' id='htaccess' name='wms7_screen_settings[htaccess]' value='1' $htaccess />
				<label for='htaccess'>$lbl_htaccess</label>

				<input type='checkbox' id='wp_config_php' name='wms7_screen_settings[wp_config_php]' value='1' $wp_config  />
				<label for='wp_config_php'>$lbl_wp_config</label>

				<input type='checkbox' id='wp_cron' name='wms7_screen_settings[wp_cron]' value='1' $wp_cron />
				<label for='wp_cron'>$lbl_wp_cron</label>

				<input type='checkbox' id='statistic' name='wms7_screen_settings[statistic]' value='1' $statistic />
				<label for='statistic'>$lbl_statistic</label>

				<input type='checkbox' id='console' name='wms7_screen_settings[console]' value='1' $console />
				<label for='console'>$lbl_console</label>

				<input type='checkbox' id='debug_log' name='wms7_screen_settings[debug_log]' value='1' $debug_log  />
				<label for='debug_log'>$lbl_debug_log</label>
			</fieldset>
			";

			return $status . $custom_fld;
		}
	}
	/**
	 * Description: Create and save screen settings of plugin.
	 * @param string  $status Status.
	 * @param string  $option Option.
	 * @param integer $value  Value.
	 */
	public function wms7_screen_settings_save( $status, $option, $value ) {
		$_wms7_screen_settings = filter_input( INPUT_POST, "wms7_screen_settings", FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );

		if ( $_wms7_screen_settings ) {
			foreach ( $_wms7_screen_settings as $key => $wms7_value ) {
				$_wms7_screen_settings[ $key ] = sanitize_text_field( $wms7_value );
			}
			update_option( "wms7_screen_settings", $_wms7_screen_settings );
		} else {
			update_option( "wms7_screen_settings", null );
		}
		update_option( "wms7_visitors_per_page", sanitize_option( $option, $value ) );
	}
	/**
	 * Description: Filter data about visits by role or time or country.
	 * @param boolean $filter_or_stat Build items for filter 1 level or for statistics.
	 */
	public function wms7_role_time_country_filter( $filter_or_stat ) {
		global $wpdb;

		$_filter_role    = filter_input( INPUT_GET, "filter_role", FILTER_DEFAULT );
		$_filter_time    = filter_input( INPUT_GET, "filter_time", FILTER_DEFAULT );
		$_filter_country = filter_input( INPUT_GET, "filter_country", FILTER_DEFAULT );

		$_stat_role    = filter_input( INPUT_POST, "filter_role", FILTER_DEFAULT );
		$_stat_time    = filter_input( INPUT_POST, "filter_time", FILTER_DEFAULT );
		$_stat_country = filter_input( INPUT_POST, "filter_country", FILTER_DEFAULT );

		// create $option_role.
		$results1 = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT DISTINCT user_role
				FROM {$wpdb->prefix}wms7_visitors
				WHERE user_role <> %s
				ORDER BY user_role ASC
				",
				""
			)
		);

		// create $option_date.
		$results2 = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT DISTINCT YEAR(time_visit) as %s, MONTH(time_visit) as %s
				FROM {$wpdb->prefix}wms7_visitors
				ORDER BY YEAR(time_visit), MONTH(time_visit) DESC
				",
				"year",
				"month"
			)
		);

		// create $option_country.
		$results3 = $wpdb->get_results(
			$wpdb->prepare(
				"
				SELECT DISTINCT LEFT(`country`,%d) as code_country
				FROM {$wpdb->prefix}wms7_visitors
				ORDER BY country ASC
				",
				2
			)
		);

		$title1 = __( "Select role of visitors", "wms7" );
		$value1 = __( "Role All", "wms7" );
		$title2 = __( "Select time of visits", "wms7" );
		$value2 = __( "Time All", "wms7" );
		$title3 = __( "Select country of visitors", "wms7" );
		$value3 = __( "Country All", "wms7" );
		$title4 = __( "Filter  level I, group 1", "wms7" );
		?>
		<input type="hidden" name="page" value="wms7_visitors" />
		<select name="filter_role" title="<?php echo __( $title1 ); ?>">
			<option value="" ><?php echo __( $value1 ); ?></option>
			<?php
			if ( $results1 ) {
				foreach ( $results1 as $row ) {
					if ( $filter_or_stat ) {
						// build items for filter level1.
						?>
						<option value="<?php echo __( $row->user_role ); ?>" <?php echo __( selected( $row->user_role, $_filter_role, false ) ); ?> ><?php echo __( $row->user_role ); ?></option>
						<?php
					}	else {
						// build items for win pop-up statistics.
						?>
						<option value="<?php echo __( $row->user_role ); ?>" <?php echo __( selected( $row->user_role, $_stat_role, false ) ); ?> ><?php echo __( $row->user_role ); ?></option>
						<?php
					}
				}
			}
			?>
		</select>
		<select name="filter_time" title="<?php echo __( $title2 ); ?>">
			<option value="" ><?php echo __( $value2 ); ?></option>
			<?php
			if ( $results2 ) {
				foreach ( $results2 as $row ) {
					$time_stamp = mktime( 0, 0, 0, $row->month, 1, $row->year );
					$month      = ( 1 === strlen( $row->month ) ) ? "0" . $row->month : $row->month;
					if ( $filter_or_stat ) {
						// build items for filter level1.
						?>
						<option value="<?php echo __( $row->year ) . __( $month ); ?>" <?php echo __( selected( $row->year . $month, $_filter_time, false ) ); ?> ><?php echo __( date( "F", $time_stamp ) ) . " " . __( $row->year ); ?>
						</option>
						<?php
					} else {
						// build items for win pop-up statistics.
						?>
						<option value="<?php echo __( $row->year ) . __( $month ); ?>" <?php echo __( selected( $row->year . $month, $_stat_time, false ) ); ?> ><?php echo __( date( "F", $time_stamp ) ) . " " . __( $row->year ); ?>
						</option>
						<?php
					}
				}
			}
			?>
		</select>
		<select name="filter_country" title="<?php echo __( $title3 ); ?>">
			<option value="" ><?php echo __( $value3 ); ?></option>
			<?php
			if ( $results3 ) {
				foreach ( $results3 as $row ) {
					if ( $filter_or_stat ) {
						// build items for filter level1.
						?>
						<option value="<?php echo __( $row->code_country ); ?>" <?php echo __( selected( $row->code_country, $_filter_country, false ) ); ?> ><?php echo __( $row->code_country ); ?></option>
						<?php
					} else {
						// build items for win pop-up statistics.
						?>
						<option value="<?php echo __( $row->code_country ); ?>" <?php echo __( selected( $row->code_country, $_stat_country, false ) ); ?> ><?php echo __( $row->code_country ); ?></option>
						<?php
					}
				}
			}
			?>
		</select>
		<?php
		if ( $filter_or_stat ) {
		?>
			<input type="submit" id="btn_level1_left" class="button" title="<?php echo __( $title4 ); ?>" value="Filter"/>
		<?php
		}
	}
	/**
	 * Description: Create main page of plugin.
	 */
	public function wms7_visit_manager() {
		$wms7_table = new wms7_List_Table();
		$wms7_table->prepare_items();

		$id_del = Wms7_List_Table::wms7_get( "wms7_id_del" );

		?>
		<div class="sse" onclick="wms7_sse_backend()" title="<?php echo __( "Refresh table of visits", "wms7" ); ?>">
			<input type="checkbox" id="sse">
			<label><i></i></label>
		</div>
		<?php
		// Bulk actions.
		if ((isset($_POST["action"]) && "clear" === $_POST["action"]) ||
				(isset($_POST["action2"]) && "clear" === $_POST["action2"])) {
			if (isset($_POST["id"])) {
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo __( "Black list item data cleaned successful", "wms7" ); ?> : (records=<?php echo count( $_POST["id"] ); ?>), date-time: ( <?php echo __( current_time( "mysql" ) ); ?>)
					</p>
				</div>
				<?php
			}else{
				?>
				<div class="notice notice-warning is-dismissible">
					<p><?php echo __( "No records selected for clear", "wms7" ); ?>, date-time: ( <?php echo __( current_time( "mysql" ) ); ?>)
					</p>
				</div>
				<?php
			}
		}
		if ((isset($_POST["action"]) && "delete" === $_POST["action"]) ||
				(isset($_POST["action2"]) && "delete" === $_POST["action2"])) {
			if (isset($_POST["id"])) {
				if ( count($_POST["id"]) === $id_del ){
					?>
					<div class="notice notice-success is-dismissible">
						<p><?php echo __( "Items deleted", "wms7" ); ?> : (records=<?php echo count( $_POST["id"] ); ?>), date-time: ( <?php echo __( current_time( "mysql" ) ); ?>)
						</p>
					</div>
					<?php
				} else {
					?>
					<div class="notice notice-warning is-dismissible">
						<p><?php echo __( "Not all marked records have been deleted", "wms7" ); ?> : (selected=<?php echo count( $_POST["id"] ) ?>, deleted=<?php echo $id_del; ?>), date-time: ( <?php echo __( current_time( "mysql" ) ); ?>)
						</p>
				</div>
				<?php
				}
			}else{
				?>
				<div class="notice notice-warning is-dismissible">
					<p><?php echo __( "No records selected for delete", "wms7" ); ?>, date-time: ( <?php echo __( current_time( "mysql" ) ); ?>)
					</p>
				</div>
				<?php
			}
		}
		if ((isset($_POST["action"]) && "report" === $_POST["action"]) ||
				(isset($_POST["action2"]) && "report" === $_POST["action2"])) {
			if (isset($_POST["id"])) {
				$path = plugin_dir_url( __FILE__ ) . "report/report.html";
				?>
				<div class="notice notice-success is-dismissible">
					<p><?php echo __( "Report created", "wms7" ); ?> : (records=<?php echo count( $_POST["id"] ); ?>), date-time: ( <?php echo __( current_time( "mysql" ) ); ?>) <a href="<?php echo __($path);?>" download>download: report.html</a>
					</p>
				</div>
				<?php
			}else{
				?>
				<div class="notice notice-warning is-dismissible">
					<p><?php echo __( "No records selected for create report", "wms7" ); ?>, date-time: ( <?php echo __( current_time( "mysql" ) ); ?>)
					</p>
				</div>
				<?php
			}
		}
		//
		if (isset($_POST["cron_refresh"])) {
			wms7_work_progress();
			?>
			<script type="text/javascript">
				if ( window.EventSource ) {
					let wms7_source = new EventSource( wms7_ajax_url + "?action=cron");
					wms7_source.onmessage = function(e) {
						let arr = e.data.replace(/"/g, '').split( "|" );
						let process = document.getElementById( "process" );
						if ( null !== process ) {
							process.innerHTML = arr[0] + " of " + arr[1];
						} else {
							wms7_source.close();
						}
						console.log( "Current task=" + arr[0] + " All task=" + arr[1] );
					}
				}
			</script>
			<?php
		}
		$plugine_info = get_plugin_data( __DIR__ . "/watchman-site7.php"  );
		$img2         = plugins_url( "/images/wms7_logo.png", __FILE__ );
		?>
		<div class="wrap">
			<span class="dashicons dashicons-shield" style="float: left;"></span>
			<h1><?php echo __( $plugine_info["Name"] ) . ": " . __( "visitors of site", "wms7" ) . "<span style='font-size:70%;'> (v." . __( $plugine_info["Version"] ) . ")</span>"; ?></h1>

			<div class="banners">
				<img src="<?php echo __( $img2 ); ?>" style="width:40px;height:40px;" title="WatchMan-Site7">
			</div>

			<div class="alignleft actions">
				<form id="filter_level1_left" method="GET">
					<?php $this->wms7_role_time_country_filter( true ); ?>
				</form>
			</div>

			<div class="alignright actions" title="Enter login or visitor IP">
				<form id="filter_level1_right" method="POST">
					<?php
					$wms7_table->search_box("Filter", "search_id");
					if ("" !== get_option( "wms7_search" )) {
						?>
						<script type="text/javascript">
							var search = document.getElementById("search_id-search-input");
							if (search) {
								search.value = "<?php echo get_option( 'wms7_search' );?>";
							}
						</script>
						<?php
					}
					?>
				</form>
			</div>

			<?php $this->wms7_info_panel(); ?>

			<form id="visitors_table" method="POST">
				<?php $wms7_table->display(); ?>
			</form>
		</div>
		<?php
		$this->wms7_services();
	}
	/**
	 * Description: Additional service functions.
	 */
	public function wms7_services() {
		$url = get_option( "wms7_current_url" );

		// Buttons at the bottom of the table.
		$_btns_service = filter_input( INPUT_POST, "btns_service", FILTER_DEFAULT );
		$_wms7_nonce   = filter_input( INPUT_POST, "wms7_nonce", FILTER_DEFAULT );

		if ( $_btns_service && wp_verify_nonce( $_wms7_nonce, "wms7_nonce" ) ) {
			wms7_win_popup();
		}
		// save index.php.
		$_index = filter_input( INPUT_POST, "index", FILTER_DEFAULT );
		if ( ( $_index ) && ( "Save" === $_index ) ) {
			$_content = filter_input( INPUT_POST, "content" );
			wms7_save_index_php( sanitize_post( $_content, "edit" ) );
		}
		// save robots.txt.
		$_robots = filter_input( INPUT_POST, "robots", FILTER_DEFAULT );
		if ( ( $_robots ) && ( "Save" === $_robots ) ) {
			$_content = filter_input( INPUT_POST, "content" );
			wms7_save_robots_txt( sanitize_post( $_content, "edit" ) );
		}
		// save htaccess.
		$_htaccess = filter_input( INPUT_POST, "htaccess", FILTER_DEFAULT );
		if ( ( $_htaccess ) && ( "Save" === $_htaccess ) ) {
			$_content = filter_input( INPUT_POST, "content" );
			wms7_save_htaccess( sanitize_post( $_content, "edit" ) );
		}
		// save wp-config.
		$_wp_config  = filter_input( INPUT_POST, "wp-config", FILTER_DEFAULT );
		if ( ( $_wp_config ) && ( "Save" === $_wp_config ) ) {
			$_content = filter_input( INPUT_POST, "content" );
			wms7_save_wp_config( sanitize_post( $_content, "edit" ) );
		}
		// clear debug_log.
		$_debug_log  = filter_input( INPUT_POST, "debug_log", FILTER_DEFAULT );
		if ( ( $_debug_log ) && ( "Clear" === $_debug_log ) ) {
			$_content = filter_input( INPUT_POST, "content" );
			wms7_clear_debug_log();
			wms7_debug_log( "debug.log", $url );
		}
		// refresh cron table.
		$_cron_refresh = filter_input( INPUT_POST, "cron_refresh", FILTER_DEFAULT );
		$_cron_delete  = filter_input( INPUT_POST, "cron_delete", FILTER_DEFAULT );
		if ( ( $_cron_refresh ) || ( $_cron_delete ) ) {
			update_option( "wms7_cron", "0|0" );
			$str_head = "wp-cron tasks";
			wms7_wp_cron( $str_head, $url );
		}
		// refresh stat table and graph.
		$_stat_table = filter_input( INPUT_POST, "stat_table", FILTER_DEFAULT );
		$_stat_graph = filter_input( INPUT_POST, "stat_graph", FILTER_DEFAULT );
		if ( $_stat_table || $_stat_graph ) {
			if ( $_stat_table ) {
				$str_head = "statistic of visits: table";
			}
			if ( $_stat_graph ) {
				$str_head = "statistic of visits: graph";
			}
			wms7_stat( $str_head, $url );
		}
	}
	/**
	 * Description: Create and control page Settings.
	 */
	public function wms7_settings() {
		$plugine_info  = get_plugin_data( __DIR__ . "/watchman-site7.php"  );
		$url           = get_site_url( null, "/wp-admin/index.php" );
		$val           = get_option( "wms7_main_settings" );
		?>
		<div class="wrap">
			<span class="dashicons dashicons-shield" style="float: left;"></span>
			<h1><?php echo __( $plugine_info["Name"] ) . ": " . __( "General settings", "wms7" ); ?></h1>
			<br>

			<details style="background-color: white;cursor: pointer;">
			  <summary style="background-color: #F0F0F1;"><b>About the environment:</b></summary>
			  	<p><?php echo("<b>Operating system:</b> ". php_uname()); ?></p>
			  	<p><?php echo("<b>Interface type between web server and PHP:</b> ". php_sapi_name()); ?></p>
			  	<p><?php echo("<b>Current PHP version:</b> ". phpversion()); ?></p>
			  	<p><?php echo("<b>Server software:</b> ".$_SERVER["SERVER_SOFTWARE"]); ?></p>
			</details>

			<?php
			$_settings_updated = filter_input(INPUT_GET, "settings-updated", FILTER_VALIDATE_BOOLEAN);
			if ( $_settings_updated ) {
				?>
				<div class="notice notice-success is-dismissible" ><p><strong><?php echo __( "Settings data saved successful.", "wms7" ) . ", date-time: (" . esc_html( current_time( "mysql" ) ) . ")"; ?></strong></p></div>
				<?php
			}
			?>
			<form method="POST" action="options.php">
				<table class="form-table">
					<tr>
						<td>
							<?php
								settings_fields( "wms7_option_group" );
								do_settings_sections( "wms7_settings" );
							?>
						</td>
					</tr>
				</table>
				<button type="submit" class="button-primary" name="save" >Save</button>
				<button type="button" class="button-primary" name="quit" onClick="location.href='<?php echo esc_url( $url ); ?>'">Quit</button>
			</form>
		</div>
		<?php
	}
	/**
	 * Description: Validation and sanitize fields to page Settings.
	 * @param  array $settings Items for setting.
	 * @return array $new_settings.
	 */
	public function wms7_validation_settings( $settings ) {
		$new_settings = array();
		// Duration log entries.
		if ( isset( $settings["log_duration"] ) && "" !== $settings["log_duration"] ) {
			$value = filter_var( $settings["log_duration"], FILTER_VALIDATE_INT );
			$new_settings["log_duration"] = sanitize_text_field( $value );

			// since we're on the General Settings page - update cron schedule if settings has been updated.
			wp_clear_scheduled_hook( "wms7_truncate" );
		}

		// Do not save visits for.
		if ( isset( $settings["ip_excluded"] ) && "" !== $settings["ip_excluded"] ) {
			$arr = explode( "|", $settings["ip_excluded"] );

			foreach( $arr as $k => $v ) {
				if ( false === filter_var( $v, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
					$arr[$k] = null;
				}
			}

			$arr = array_filter( $arr );
			$arr = array_unique( $arr );
			$str = implode( "|", $arr );
			$new_settings["ip_excluded"] = sanitize_text_field( $str );
		}

		// Robots.
		if ( isset( $settings["robots"] ) && "" !== $settings["robots"] ) {
			$arr = explode( "|", $settings["robots"] );

			foreach( $arr as $k => $v ) {
				if ( false === filter_var( $v, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME ) ) {
					$arr[$k] = null;
				}
			}

			$arr = array_filter( $arr );
			$arr = array_unique( $arr );
			$str = implode( "|", $arr );
			$new_settings["robots"] = sanitize_text_field( $str );
		}

		// Visits of robots.
		if ( isset( $settings["robots_reg"] ) ) {
			$value = filter_var( $settings["robots_reg"], FILTER_VALIDATE_BOOLEAN );
			$new_settings["robots_reg"] = sanitize_text_field( $value );
		}

		// WHO-IS service.
		if ( isset( $settings["whois_service"] ) && "" !== $settings["whois_service"] ) {
			$new_settings["whois_service"] = sanitize_text_field( $settings["whois_service"] );
		}

		// SSE sound.
		if ( isset( $settings["fIn"] ) && "" !== $settings["fIn"] ) {
			$new_settings["fIn"] = sanitize_text_field( $settings["fIn"] );
		}
		if ( isset( $settings["tIn"] ) && "" !== $settings["tIn"] ) {
			$new_settings["tIn"] = sanitize_text_field( $settings["tIn"] );
		}
		if ( isset( $settings["vIn"] ) && "" !== $settings["vIn"] ) {
			$new_settings["vIn"] = sanitize_text_field( $settings["vIn"] );
		}
		if ( isset( $settings["dIn"] ) && "" !== $settings["dIn"] ) {
			$new_settings["dIn"] = sanitize_text_field( $settings["dIn"] );
		}

		// WP_DEBUG.
		if ( isset( $settings["wp_debug"] ) ) {
			$value = filter_var( $settings["wp_debug"], FILTER_VALIDATE_BOOLEAN );
			$new_settings["wp_debug"] = sanitize_text_field( $value );
			wms7_wp_debug_change(true);
		} else {
			wms7_wp_debug_change(false);
		}

		// Attack analyzer.
		if ( isset( $settings["attack_analyzer"] ) ) {
			$value = filter_var( $settings["attack_analyzer"], FILTER_VALIDATE_BOOLEAN );
			$new_settings["attack_analyzer"] = sanitize_text_field( $value );
		}

		// WHO-IS service.
		if ( isset( $settings["whois_service"] ) ) {
			$new_settings["whois_service"] = sanitize_text_field( $settings["whois_service"] );
		}

		// STUN server.
		if ( isset( $settings["stun_server"] ) && "" !== $settings["stun_server"] ) {
			$new_settings["stun_server"] = sanitize_text_field( $settings["stun_server"] );
		}

		return $new_settings;
	}
	/**
	 * Description: Add fields to page Settings.
	 */
	public function wms7_main_settings() {

		register_setting( "wms7_option_group", "wms7_main_settings", array( $this, "wms7_validation_settings" ) );

		add_settings_section( "wms7_section", "", "", "wms7_settings" );

		add_settings_field(
			"field1",
			"<label style='cursor: pointer;' for='log_duration'>" . __( "1.Duration log entries", "wms7" ) . ":</label>",
			array( $this, "wms7_main_setting_field1" ),
			"wms7_settings",
			"wms7_section"
		);
		add_settings_field(
			"field2",
			"<label style='cursor: pointer;' for='ip_excluded'>" . __( "2.Do not save visits for", "wms7" ) . ":</label>",
			array( $this, "wms7_main_setting_field2" ),
			"wms7_settings",
			"wms7_section"
		);
		add_settings_field(
			"field3",
			"<label style='cursor: pointer;' for='robots'>" . __( "3.Robots", "wms7" ) . ":</label>",
			array( $this, "wms7_main_setting_field3" ),
			"wms7_settings",
			"wms7_section"
		);
		add_settings_field(
			"field4",
			"<label style='cursor: pointer;' for='robots_reg'>" . __( "4.Visits of robots", "wms7" ) . ":</label>",
			array( $this, "wms7_main_setting_field4" ),
			"wms7_settings",
			"wms7_section"
		);
		add_settings_field(
			"field5",
			"<label style='cursor: pointer;' for='sse_sound'>" . __( "5.SSE sound", "wms7" ) . ":</label>",
			array( $this, "wms7_main_setting_field5" ),
			"wms7_settings",
			"wms7_section"
		);
		add_settings_field(
			"field6",
			"<label style='cursor: pointer;' for='wp_debug'>" . __( "6.WP_DEBUG", "wms7" ) . ":</label>",
			array( $this, "wms7_main_setting_field6" ),
			"wms7_settings",
			"wms7_section"
		);
		add_settings_field(
			"field7",
			"<label style='cursor: pointer;' for='attack_analyzer'>" . __( "7.Attack analyzer", "wms7" ) . ":</label>",
			array( $this, "wms7_main_setting_field7" ),
			"wms7_settings",
			"wms7_section"
		);
		add_settings_field(
			"field8",
			"<label style='cursor: pointer;' for='who_is'>" . __( "8.WHO-IS service", "wms7" ) . ":</label>",
			array( $this, "wms7_main_setting_field8" ),
			"wms7_settings",
			"wms7_section"
		);
		add_settings_field(
			"field9",
			"<label style='cursor: pointer;' for='stun_server'>" . __( "9.STUN server", "wms7" ) . ":</label>",
			array( $this, "wms7_main_setting_field9" ),
			"wms7_settings",
			"wms7_section"
		);
	}
	/**
	 * Description: Filling option1 (Duration log entries) on page Settings.
	 */
	public function wms7_main_setting_field1() {
		$val = get_option( "wms7_main_settings" );
		$val = ( isset($val["log_duration"]) && "" !== $val["log_duration"] ) ? $val["log_duration"] : "120";
		?>
		<input id="log_duration" name="wms7_main_settings[log_duration]" type="number" step="1" min="0" max="365" value="<?php echo __( $val ); ?>" /><br>
		<label><?php echo __( "days. Leave empty or enter 0 if you not want the log to be truncated", "wms7" ); ?></label>
		<?php
	}
	/**
	 * Description: Filling option2 (Do not save visits for) on page Settings.
	 */
	public function wms7_main_setting_field2() {
		$val = get_option( "wms7_main_settings" );
		$val = isset( $val["ip_excluded"] ) ? $val["ip_excluded"] : "";
		?>
		<textarea id="ip_excluded" name="wms7_main_settings[ip_excluded]" placeholder="IP1|IP2|IP3|IP4"  style="margin: 0px; width: 320px; height: 45px;"><?php echo esc_textarea( $val ); ?></textarea><br>
		<label><?php echo __( "Visits from these IP addresses will be excluded from the protocol visits.", "wms7" ); ?></label>
		<?php
	}
	/**
	 * Description: Filling option3 (Robots) on page Settings.
	 */
	public function wms7_main_setting_field3() {
		$val = get_option( "wms7_main_settings" );
		$val = isset( $val["robots"] ) ? $val["robots"] : "Mail.RU|YandexBot|Googlebot|bingbot|Virusdie|AhrefsBot|YandexMetrika|MJ12bot|BegunAdvertising|Slurp|DotBot|YandexMobileBot|MegaIndex|Google|YandexAccessibilityBot|SemrushBot|Baiduspider|SEOkicks-Robot|BingPreview|rogerbot|Applebot|Qwantify|DuckDuckBot|Cliqzbot|NetcraftSurveyAgent|SeznamBot|CCBot|linkdexbot|Barkrowler|Wget|ltx71|Slackbot|Nimbostratus-Bot|Crawler|Thither.Direct|Moreover|LetsearchBot|Adsbot|Konturbot|PetalBot|Expanse|Linespider|Bytespider|ClaudeBot";
		?>
		<textarea id="robots" name="wms7_main_settings[robots]" placeholder="Name1|Name2|Name3|"  style="margin: 0px; width: 320px; height: 45px;"><?php echo esc_textarea( $val ); ?></textarea><br>
		<label for="robots"><?php echo __( "Visits this name will be marked - Robot", "wms7" ); ?></label>
		<?php
	}
	/**
	 * Description: Filling option4 (Visits of robots) on page Settings.
	 */
	public function wms7_main_setting_field4() {
		$val = get_option( "wms7_main_settings" );
		$val = isset( $val["robots_reg"] ) ? "checked" : "";
		?>
		<input id="robots_reg" name="wms7_main_settings[robots_reg]" type="checkbox" value="1" <?php echo $val ?>/><br>
		<label for="robots_reg"><?php echo __( "Register visits by robots.", "wms7" ); ?></label>
		<?php
	}
	/**
	 * Description: Filling option5 (SSE sound) on page Settings.
	 */
	public function wms7_main_setting_field5() {
		$val = get_option( "wms7_main_settings" );
		$fIn = isset( $val["fIn"] ) ? $val["fIn"] : "600";
		$tIn = isset( $val["tIn"] ) ? $val["tIn"] : "1";
		$vIn = isset( $val["vIn"] ) ? $val["vIn"] : "9";
		$dIn = isset( $val["dIn"] ) ? $val["dIn"] : "390";
		?>
		<table>
			<tr>
				<td style="height:20px;padding:0;margin:0;">
					<label>frequency</label>
				</td>
				<td style="height:20px;padding:0;margin:0;">
					<input type="range" id="fIn" name="wms7_main_settings[fIn]" value="<?php echo __( $fIn ); ?>" min="40" max="6000" oninput="wms7_show()" />
				</td>
				<td style="height:20px;padding:0;margin:0;">
					<span id="fOut"></span>
				</td>
			</tr>
			<tr>
				<td style="height:20px;padding:0;margin:0;">
					<label>type</label>
				</td>
				<td style="height:20px;padding:0;margin:0;">
					<input type="range" id="tIn" name="wms7_main_settings[tIn]" value="<?php echo __( $tIn ); ?>" min="0" max="3" oninput="wms7_show()" />
				</td>
				<td style="height:20px;padding:0;margin:0;">
					<span id="tOut"></span>
				</td>
			</tr>
			<tr>
				<td style="height:20px;padding:0;margin:0;">
					<label>volume</label>
				</td>
				<td style="height:20px;padding:0;margin:0;">
					<input type="range" id="vIn" name="wms7_main_settings[vIn]" value="<?php echo __( $vIn ); ?>" min="0" max="100" oninput="wms7_show()" />
				</td>
				<td style="height:20px;padding:0;margin:0;">
					<span id="vOut"></span>
				</td>
			</tr>
			<tr>
				<td style="height:20px;padding:0;margin:0;">
					<label>duration</label>
				</td>
				<td style="height:20px;padding:0;margin:0;">
					<input type="range" id="dIn" name="wms7_main_settings[dIn]" value="<?php echo __( $dIn ); ?>" min="1" max="5000" oninput="wms7_show()" />
				</td>
				<td style="height:20px;padding:0;margin:0;">
					<span id="dOut"></span>
				</td>
			</tr>
		</table>
		<br>
		<input type="button" value="Play" onclick="wms7_beep();" />
		<br>
		<label><?php echo __( "It is intended for sound maintenance of updating of the screen at receipt of new visitors of the website", "wms7" ); ?></label>
		<?php
	}
	/**
	 * Description: Filling option6 (WP_DEBUG) on page Settings.
	 */
	public function wms7_main_setting_field6() {
		$val = get_option( "wms7_main_settings" );
		$val = isset( $val["wp_debug"] ) ? "checked" : "";
		?>
		<input id="wp_debug" name="wms7_main_settings[wp_debug]" type="checkbox" value="1" <?php echo $val ?>/><br>
		<label for="wp_debug"><?php echo __( "Debug mode is enabled. WP, PHP errors will be displayed in the debug_log, which can be viewed by clicking the debug_log button on the plugin main page.", "wms7" ); ?></label>
		<?php
	}
	/**
	 * Description: Filling option7 (attack_analyzer) on page Settings.
	 */
	public function wms7_main_setting_field7() {
		$val = get_option( "wms7_main_settings" );
		$val = isset( $val["attack_analyzer"] ) ? "checked" : "";
		?>
		<input id="attack_analyzer" name="wms7_main_settings[attack_analyzer]" type="checkbox" value="1" <?php echo $val ?>/><br>
		<label for="attack_analyzer"><?php echo __( "Attack analyzer Brute force", "wms7" ); ?></label>
		<?php
	}
	/**
	 * Description: Filling option7 (WHO-IS service) on page Settings.
	 */
	public function wms7_main_setting_field8() {
		$val      = get_option( "wms7_main_settings" );

		$checked0 = ( !isset($val["whois_service"]) || $val["whois_service"] == "none" ) ? "checked" : "";
		$checked1 = ( isset($val["whois_service"]) && $val["whois_service"] == "IP-API" ) ? "checked" : "";
		$checked2 = ( isset($val["whois_service"]) && $val["whois_service"] == "IP-Info" ) ? "checked" : "";
		$checked3 = ( isset($val["whois_service"]) && $val["whois_service"] == "SxGeo" ) ? "checked" : "";
		$checked4 = ( isset($val["whois_service"]) && $val["whois_service"] == "Geobytes" ) ? "checked" : "";
		?>
		<table>
			<tr>
				<td style="padding:0;">
					<input type="radio" value="none" <?php echo __( $checked0 ); ?> id="who_0" name="wms7_main_settings[whois_service]">
					<label style="cursor: pointer;" for="who_0">None</label>
				</td>
				<td style="padding:0;">--</td><td style="padding:0;">--</td>
			</tr>
			<tr>
				<td style="padding:0;width:200px;">
					<input type="radio" value="IP-API" <?php echo __( $checked1 ); ?> id="who_1" name="wms7_main_settings[whois_service]">
					<label style="cursor: pointer;" for="who_1">IP-API (high quality)</label>
				</td>
				<td style="padding:0;width:50px;"><a href="https://ip-api.com/" target="_blank">Site</a></td>
				<td style="padding:0;">contact@ip-api.com</td>
			</tr>
			<tr>
				<td style="padding:0;">
					<input type="radio" value="IP-Info" <?php echo __( $checked2 ); ?> id="who_2" name="wms7_main_settings[whois_service]">
					<label style="cursor: pointer;" for="who_2">IP-Info (high quality)</label>
				</td>
				<td style="padding:0;"><a href="https://ipinfo.io/" target="_blank">Site</a></td>
				<td style="padding:0;">https://ipinfo.io/contact</td>
			</tr>
			<tr>
				<td style="padding:0;">
					<input type="radio" value="SxGeo" <?php echo __( $checked3 ); ?> id="who_3" name="wms7_main_settings[whois_service]">
					<label style="cursor: pointer;" for="who_3">SxGeo (medium quality)</label>
				</td>
				<td style="padding:0;"><a href="https://sypexgeo.net/" target="_blank">Site</a></td>
				<td style="padding:0;">https://sypexgeo.net/ru/contacts/</td>
			</tr>
			<tr>
				<td style="padding:0;">
					<input type="radio" value="Geobytes" <?php echo __( $checked4 ); ?> id="who_4" name="wms7_main_settings[whois_service]">
					<label style="cursor: pointer;" for="who_4">Geobytes (medium quality)</label>
				</td>
				<td style="padding:0;"><a href="https://geobytes.com/" target="_blank">Site</a></td>
				<td style="padding:0;">https://geobytes.com/</td>
			</tr>
		</table>
		<label for="who_0"><?php echo __( "Select provider information of IP address of site visitors.", "wms7" ); ?></label>
		<?php
	}
	/**
	 * Description: Filling option9 (STUN server) on page Settings.
	 */
	public function wms7_main_setting_field9() {
		$val      = get_option( "wms7_main_settings" );

		$checked0 = ( !isset($val["stun_server"]) || $val["stun_server"] == "none" ) ? "checked" : "";
		$checked1 = ( isset($val["stun_server"]) && $val["stun_server"] == "stun1.l.google.com" ) ? "checked" : "";
		$checked2 = ( isset($val["stun_server"]) && $val["stun_server"] == "stun2.l.google.com" ) ? "checked" : "";
		$checked3 = ( isset($val["stun_server"]) && $val["stun_server"] == "stun3.l.google.com" ) ? "checked" : "";
		$checked4 = ( isset($val["stun_server"]) && $val["stun_server"] == "stun4.l.google.com" ) ? "checked" : "";
		?>
		<input type="radio" value="none" <?php echo __( $checked0 ); ?> id="stun_0" name="wms7_main_settings[stun_server]">
		<label style="cursor: pointer;" for="stun_0">None</label><br>

		<input type="radio" value="stun1.l.google.com" <?php echo __( $checked1 ); ?> id="stun_1" name="wms7_main_settings[stun_server]">
		<label style="cursor: pointer;" for="stun_1">stun1.l.google.com</label><br>

		<input type="radio" value="stun2.l.google.com" <?php echo __( $checked2 ); ?> id="stun_2" name="wms7_main_settings[stun_server]">
		<label style="cursor: pointer;" for="stun_2">stun2.l.google.com</label><br>

		<input type="radio" value="stun3.l.google.com" <?php echo __( $checked3 ); ?> id="stun_3" name="wms7_main_settings[stun_server]">
		<label style="cursor: pointer;" for="stun_3">stun3.l.google.com</label><br>

		<input type="radio" value="stun4.l.google.com" <?php echo __( $checked4 ); ?> id="stun_4" name="wms7_main_settings[stun_server]">
		<label style="cursor: pointer;" for="stun_4">stun4.l.google.com</label>
		<br>
		<label for="stun_0"><?php echo __( "Select a STUN server to get additional information from local network of site visitors.", "wms7" ); ?></label>
		<?php
	}
	/**
	 * Description: Generates data for the InfoPanel.
	 */
	private function wms7_info_panel() {
		$val          = get_option( "wms7_screen_settings" );
		$setting_list = isset( $val["setting_list"] ) ? $val["setting_list"] : 0;
		$history_list = isset( $val["history_list"] ) ? $val["history_list"] : 0;
		$robots_list  = isset( $val["robots_list"] ) ? $val["robots_list"] : 0;
		$black_list   = isset( $val["black_list"] ) ? $val["black_list"] : 0;

		$val       = $setting_list + $history_list + $robots_list + $black_list;
		$width_box = "";
		switch ( $val ) {
			case "1":
				$width_box = "98%";
				break;
			case "2":
				$width_box = "49%";
				break;
			case "3":
				$width_box = "32.5%";
				break;
			case "4":
				$width_box = "24.5%";
				break;
		}

		$panel_info_hidden = ( "1" !== $setting_list && "1" !== $history_list && "1" !== $robots_list && "1" !== $black_list ) ? "hidden" : "";

		$hidden_setting_list = ( "1" === $setting_list ) ? "" : "hidden";
		$hidden_history_list = ( "1" === $history_list ) ? "" : "hidden";
		$hidden_robots_list  = ( "1" === $robots_list ) ? "" : "hidden";
		$hidden_black_list   = ( "1" === $black_list ) ? "" : "hidden";

		?>
		<fieldset class = "info_panel" title="Panel info" <?php echo __( $panel_info_hidden ); ?> >
			<fieldset class = "fldset_panel_info" title="General settings" <?php echo __( $hidden_setting_list ); ?> style="width:<?php echo __( $width_box ); ?>;">
				<legend class = "panel_title"><?php echo __( "Settings", "wms7" ); ?></legend>
				<?php echo __( $this->wms7_settings_info() ); ?>
			</fieldset>

			<fieldset class = "fldset_panel_info" title="Date All(), Unlogged(), Success(), Failed(), Robots()" <?php echo __( $hidden_history_list ); ?> style="width:<?php echo __( $width_box ); ?>;">
				<legend class = "panel_title"><?php echo __( "History list", "wms7" ); ?></legend>
				<?php echo __( $this->wms7_history_list_info() ); ?>
			</fieldset>

			<fieldset class="fldset_panel_info" title="Date Time, Robot" <?php echo __( $hidden_robots_list ); ?> style="width:<?php echo __( $width_box ); ?>;">
				<legend class = "panel_title"><?php echo __( "Robots list", "wms7" ); ?></legend>
				<?php echo __( $this->wms7_robot_visit_info() ); ?>
			</fieldset>

			<fieldset class="fldset_panel_info" title="Start_blocking, End_blocking, IP" <?php echo __( $hidden_black_list ); ?> style="width:<?php echo __( $width_box ); ?>;">
				<legend class = "panel_title"><?php echo __( "Black list", "wms7" ); ?></legend>
				<?php echo __( $this->wms7_black_list_info() ); ?>
			</fieldset>
		</fieldset>
		<?php
	}
	/**
	 * Description: Generates data for the InfoPanel, Section4 - black list.
	 */
	private function wms7_black_list_info() {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"
        SELECT `id`, `user_ip`, `black_list`, `info`
        FROM {$wpdb->prefix}wms7_visitors
        WHERE TRIM(`black_list`) <> %s
        ORDER BY `time_visit` DESC
        ",
				""
			)
		);
		?>
		<table class="blockarea_panel_info">
			<tbody style="display:block;width:100%;height:80px;overflow:auto;border:1px solid #B4AEA0;border-radius:5px;">
				<?php
				foreach ( $results as $row ) {
					$row_black_list = json_decode( $row->black_list, true );
					?>
					<tr style="display: table;width: 100%;">
						<td style="width:30%;"><?php echo($row_black_list["ban_start_date"]); ?></td>
						<td style="width:30%;"><?php echo($row_black_list["ban_end_date"]); ?></td>
						<td style="width:40%;"><?php echo($row->user_ip); ?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<?php
	}
	/**
	 * Description: Generates data for the InfoPanel, Section3 - robots.
	 */
	private function wms7_robot_visit_info() {
		global $wpdb;

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"
        SELECT MAX(`time_visit`) as `date_visit`, `robot`
        FROM {$wpdb->prefix}wms7_visitors
        WHERE `login_result`=%d
        GROUP BY (`robot`)
        ORDER BY `date_visit` DESC
        ",
				3
			)
		);
		?>
		<table class="blockarea_panel_info">
			<tbody style="display:block;width:100%;height:80px;overflow:auto;border:1px solid #B4AEA0;border-radius:5px;">
				<?php
				foreach ( $results as $row ) {
					?>
					<tr style="display: table;width: 100%;">
						<td style="width:50%;"><?php echo($row->date_visit); ?></td>
						<td style="width:50%;"><?php echo($row->robot); ?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<?php
	}
	/**
	 * Description: Generates data for the InfoPanel, Section2 - history list.
	 */
	private function wms7_history_list_info() {
		global $wpdb;

		$results = $wpdb->get_results(
			"
      SELECT left(`time_visit`,10) as `date_visit`,
      count(`login_result`) as `count_all`,
      sum(`login_result`= 0) as `count0`,
      sum(`login_result`= 1) as `count1`,
      sum(`login_result`= 2) as `count2` ,
      sum(`login_result`= 3) as `count3`
      FROM {$wpdb->prefix}wms7_visitors
      GROUP BY `date_visit`
      ORDER BY `date_visit` DESC
      "
		);
		?>
		<table class="blockarea_panel_info">
			<tbody style="display:block;width:100%;height:80px;overflow:auto;border:1px solid #B4AEA0;border-radius:5px;">
				<?php
				foreach ( $results as $row ) {
					?>
					<tr style="display: table;width: 100%;">
						<td style="width:25%;"><?php echo($row->date_visit); ?></td>
						<td style="width:15%;"><?php echo("A" . $row->count_all); ?></td>
						<td style="width:15%;"><?php echo("U" . $row->count2); ?></td>
						<td style="width:15%;"><?php echo("S" . $row->count1); ?></td>
						<td style="width:15%;"><?php echo("F" . $row->count0); ?></td>
						<td style="width:15%;"><?php echo("R" . $row->count3); ?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<?php
	}
	/**
	 * Description: Generates data for the InfoPanel, Section1 - general settings.
	 */
	private function wms7_settings_info() {
		$val             = get_option( "wms7_main_settings" );
		$log_duration    = isset( $val["log_duration"] ) ? $val["log_duration"] : "";
		$ip_excluded     = isset( $val["ip_excluded"] ) ? $val["ip_excluded"] : "";
		$robots_reg      = isset( $val["robots_reg"] ) ? "Yes" : "No";
		$whois_service   = isset( $val["whois_service"] ) ? $val["whois_service"] : "none";
		$wp_debug        = isset( $val["wp_debug"] ) ? "Yes" : "No";
		$attack_analyzer = isset( $val["attack_analyzer"] ) ? "Yes" : "No";

		$arr = array (
	    __( "WP_DEBUG", "wms7" )  => $wp_debug,
	    __( "Attack analyzer", "wms7" )  => $attack_analyzer,
	    __( "Visits of robots", "wms7" ) => $robots_reg,
	    __( "WHO-IS service", "wms7" )   => $whois_service,
	    __( "Duration log entries", "wms7" ) => $log_duration . " days",
	    __( "Do not save visits for", "wms7" )   => str_replace( "|", " ", $ip_excluded )
		);

		?>
		<table class="blockarea_panel_info">
			<tbody style="display:block;width:100%;height:80px;overflow:auto;border:1px solid #B4AEA0;border-radius:5px;">
				<?php
				foreach ( $arr as $key => $item ) {
					?>
					<tr style="display: table;width: 100%;">
						<td style="width:50%;"><?php echo($key); ?></td>
						<td style="width:50%;"><?php echo($item); ?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<?php
	}
	/**
	 * Description: Creates Black list page of plugin.
	 */
	public function wms7_black_list() {
		global $wpdb;

		$plugine_info    = get_plugin_data( WMS7_PLUGIN_DIR . "/" . WMS7_PLUGIN_NAME . ".php" );
		$_id             = filter_input( INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT );
		$_blacklist_save = filter_input( INPUT_POST, "blacklist_save", FILTER_DEFAULT );

		$_ban_start_date = filter_input( INPUT_POST, "ban_start_date", FILTER_SANITIZE_NUMBER_INT );
		$_ban_end_date   = filter_input( INPUT_POST, "ban_end_date", FILTER_SANITIZE_NUMBER_INT );
		$_ban_message    = filter_input( INPUT_POST, "ban_message", FILTER_DEFAULT );
		$_ban_notes      = filter_input( INPUT_POST, "ban_notes", FILTER_DEFAULT );
		$_ban_login      = filter_input( INPUT_POST, "ban_login", FILTER_VALIDATE_BOOLEAN );
		$_ban_user_agent = filter_input( INPUT_POST, "ban_user_agent", FILTER_VALIDATE_BOOLEAN );

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"
        SELECT `user_ip`, `info`
        FROM {$wpdb->prefix}wms7_visitors
        WHERE `id` = %s
        ",
				$_id
			),
			"ARRAY_A"
		);

		$user_ip    = array_shift( $results[0] );
		$user_agent = json_decode( array_shift( $results[0] ), true );
		$user_agent = $user_agent["User Agent"];

		// Add custom meta box.
		add_meta_box(
			"wms7_black_list_meta_box",
			"<font size='4'>" . __( "Black list data for", "wms7" )
			. ": IP = " . $user_ip . " (id=" . $_id . ")</font>",
			array( $this, "wms7_black_list_visitor" ),
			"wms7_black_list",
			"normal",
			"default"
		);
		?>
		<div class="wrap"><span class="dashicons dashicons-shield" style="float: left;"></span>
			<h1><?php echo __( $plugine_info["Name"] ) . ": " . __( "black list", "wms7" ); ?></h1>
			<?php
			if ( $_blacklist_save ) {
				$msg = __( "Black list item data saved successful.", "wms7" );
				?>
				<div class="updated notice is-dismissible" >
					<p>
						<strong>
							<?php echo ( $msg . " Date-time: (" . current_time( "mysql" ) ) ?>
						</strong>
					</p>
				</div>
				<?php
				if ( $_ban_start_date && $_ban_end_date ) {
					$arr = array(
						"ban_start_date" => ( $_ban_start_date ) ? $_ban_start_date : "",
						"ban_end_date"   => ( $_ban_end_date ) ? $_ban_end_date : "",
						"ban_message"    => ( $_ban_message ) ? $_ban_message : "",
						"ban_notes"      => ( $_ban_notes ) ? $_ban_notes : "",
						"ban_login"      => ( $_ban_login ) ? $_ban_login : false,
						"ban_user_agent" => ( $_ban_user_agent ) ? $_ban_user_agent : false,
					);

					$serialized_data = wp_json_encode( $arr );

					$wpdb->update(
						$wpdb->prefix . "wms7_visitors",
						array( "black_list" => $serialized_data ),
						array( "ID" => $_id ),
						array( "%s" )
					);
				}

				$days_start = getdate(strtotime($_ban_start_date))["yday"];
				$days_end   = getdate(strtotime($_ban_end_date))["yday"];

				if ( $_ban_user_agent &&
					( getdate()["yday"] >= $days_start && getdate()["yday"] <= $days_end ) ) {
					// Insert user_agent into .htaccess.
					wms7_agent_ins_to_file( $user_agent );
				} else {
					// Delete user_agent into .htaccess.
					wms7_agent_del_from_file( $user_agent );
				}

				if ( $user_ip &&
					( getdate()["yday"] >= $days_start && getdate()["yday"] <= $days_end ) ) {
					// Insert user_ip into .htaccess.
					wms7_ip_ins_to_file( $user_ip );
				} else {
					// Delete user_ip into .htaccess.
					wms7_ip_del_from_file( $user_ip );
				}
			}
			?>
			<form id="black_list" method="POST">
				<div class="metabox-holder">
					<?php do_meta_boxes( "wms7_black_list", "normal", "" ); ?>
					<input type="submit" value="Save" class="button-primary" name="blacklist_save">
					<input type="button" value="Quit" class="button-primary" name="blacklist_quit" onClick="location.href='<?php echo (esc_url( get_option( 'wms7_current_url' ) ) )?>'">
				</div>
			</form>
		</div>
		<?php
	}
	/**
	 * Description: Creates custom fields on the Black list page.
	 */
	public function wms7_black_list_visitor() {
		global $wpdb;
		$result = array();
		$uid    = "";
		$_id    = filter_input( INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT );

		if ( $_id ) {
			$result = $wpdb->get_results(
				$wpdb->prepare(
					"
          SELECT `uid`, `black_list`
          FROM {$wpdb->prefix}wms7_visitors
          WHERE `id` = %d
          ",
					$_id
				),
				"ARRAY_A"
			);
		}
		$result     = array_shift( $result );
		$uid        = $result["uid"];
		$black_list = json_decode( $result["black_list"], true );

		$black_list["ban_start_date"] = isset( $black_list["ban_start_date"] ) ? $black_list["ban_start_date"] : "";
		$black_list["ban_end_date"]		= isset( $black_list["ban_end_date"] ) ? $black_list["ban_end_date"] : "";
		$black_list["ban_message"]		= isset( $black_list["ban_message"] ) ? $black_list["ban_message"] : "";
		$black_list["ban_notes"]			= isset( $black_list["ban_notes"] ) ? $black_list["ban_notes"] : "";
		$black_list["ban_login"]			= isset( $black_list["ban_login"] ) ? $black_list["ban_login"] : false;
		$black_list["ban_user_agent"]	= isset( $black_list["ban_user_agent"] ) ? $black_list["ban_user_agent"] : false;

		$ip_info  = $this->wms7_ip_info();
		$ip_info1 = $ip_info[0];
		$ip_info2 = $ip_info[1];

		?>
		<table class="form-table">
			<tr>
				<th>
					<label for="ban_start_date"><?php echo __( "Ban start date", "wms7" ); ?></label>
				</th>
				<td>
					<input id="ban_start_date" name="ban_start_date" type="date" value="<?php echo __( sanitize_text_field( $black_list["ban_start_date"] ) ); ?>"  placeholder="<?php echo __( "Ban start date", "wms7" ); ?>" required>
				</td>
				<th rowspan="2" style="width: auto;">
					<label for="ip_info1"><?php echo __( "IP info1", "wms7" ); ?></label>
				</th>
				<td rowspan="2" style="width: 50%;">
					<table style="border: 1px solid black;background: #F0F0F1;width:100%;">
						<thead>
							<tr style="display: inline-table;width: 100%;">
								<th style="padding:0;width:19%;">address</th>
								<th style="padding:0;width:9%;">port</th>
								<th style="padding:0;width:9%;">type</th>
								<th style="padding:0;width:9%;">protocol</th>
								<th style="padding:0;width:20%;">device name</th>
							</tr>
						</thead>
						<tbody style="display: block;overflow-y: auto;max-height: 80px;">
						<?php
							if ( empty( $ip_info1 )) {
							?>
								<tr style="display: inline-table;width: 100%;">
									<td style="border-top: 1px solid black;width:20%;"><?php echo( "empty" ); ?></td>
									<td style="border-top: 1px solid black;width:10%;"><?php echo( "empty" ); ?></td>
									<td style="border-top: 1px solid black;width:10%;"><?php echo( "empty" ); ?></td>
									<td style="border-top: 1px solid black;width:10%;"><?php echo( "empty" ); ?></td>
									<td style="border-top: 1px solid black;width:20%;"><?php echo( "empty" ); ?></td>
								</tr>
							<?php
							} else {
								$ip_info1 = explode( ";", $ip_info1 );
								foreach ( $ip_info1 as $ip_info ) {
									if ( "" !== $ip_info ) {
										$ip_info   = explode( ",", $ip_info );

										if ( filter_var( $ip_info[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
											$comp_name = gethostbyaddr( $ip_info[0] );
											if ( $comp_name == $ip_info[0] ) {
												$comp_name = "";
											}
										} else {
											$comp_name = "";
										}

										?>
										<tr style="display: inline-table;width: 100%;">
											<td style="border-top: 1px solid black;width:20%;"><?php echo __( $ip_info[0] ); ?></td>
											<td style="border-top: 1px solid black;width:10%;"><?php echo __( $ip_info[1] ); ?></td>
											<td style="border-top: 1px solid black;width:10%;"><?php echo __( $ip_info[2] ); ?></td>
											<td style="border-top: 1px solid black;width:10%;"><?php echo __( $ip_info[3] ); ?></td>
											<td style="border-top: 1px solid black;width:20%;word-break: break-all;"><?php echo __( $comp_name ); ?></td>
										</tr>
									<?php
									}
								}
							}
						?>
						</tbody>
					</table>
				</td>
			</tr>
			<tr>
				<th>
					<label for="ban_end_date"><?php echo __( "Ban end date", "wms7" ); ?></label>
				</th>
				<td>
					<input id="ban_end_date" name="ban_end_date" type="date" value="<?php echo __( sanitize_text_field( $black_list["ban_end_date"] ) ); ?>"  placeholder="<?php echo __( "Ban end date", "wms7" ); ?>" required>
				</td>
			</tr>
			<tr>
				<th>
					<label for="ban_message"><?php echo __( "Ban message", "wms7" ); ?></label>
				</th>
				<td>
					<input id="ban_message" name="ban_message" type="text" value="<?php echo __( sanitize_text_field( $black_list["ban_message"] ) ); ?>"  placeholder="<?php echo __( "Ban message", "wms7" ); ?>" required style="width:80%;">
				</td>

				<th rowspan="2" style="width: auto;">
					<label for="ip_info2"><?php echo __( "IP info2", "wms7" ); ?></label>
				</th>
				<td rowspan="4" style="width: 50%;">
					<textarea readonly style="height: 220px;width: 100%;margin-top: 15px;" id ="ip_info2" name="ip_info2" rows="6" style="width:100%;"><?php echo __( $ip_info2 ); ?></textarea>
				</td>

			</tr>
			<tr>
				<th>
					<label for="ban_notes"><?php echo __( "Notes", "wms7" ); ?></label>
			</th>
				<td>
					<input id="ban_notes" name="ban_notes" type="text" value="<?php echo __( sanitize_text_field( $black_list["ban_notes"] ) ); ?>" placeholder="<?php echo __( "Notes", "wms7" ); ?>" required style="width:80%;">
				</td>
			</tr>
			<?php
			if ( "0" !== $uid ) {
				?>
			<tr>
				<th>
					<label for="ban_login"><?php echo __( "Ban user login", "wms7" ); ?></label>
				</th>
				<td colspan="3">
					<input id="ban_login" name="ban_login" type="checkbox" value="1" <?php checked( sanitize_text_field( $black_list["ban_login"] ) ); ?>>
				</td>
			</tr>
				<?php
			}
			?>
			<tr>
				<th>
					<label for="ban_user_agent"><?php echo __( "Ban user agent", "wms7" ); ?></label>
				</th>
				<td colspan="3">
					<input id="ban_user_agent" name="ban_user_agent" type="checkbox" value="1" <?php checked( sanitize_text_field( $black_list["ban_user_agent"] ) ); ?>>
				</td>
			</tr>
			</table>
		<?php
	}
	/**
	 * Description: Provides additional information about the ip visitor from the database.
	 * @return string Info of the IP adress of visitor from DB.
	 */
	private function wms7_ip_info() {
		global $wpdb;
		$user_ip_info = [];

		$_id = filter_input( INPUT_GET, "id", FILTER_SANITIZE_NUMBER_INT );

		if ( $_id ) {
			$results = $wpdb->get_results(
				$wpdb->prepare(
					"
          SELECT `internal_ip`, `user_ip_info`
          FROM {$wpdb->prefix}wms7_visitors
          WHERE `id` = %d
          ",
					$_id
				),
				"ARRAY_A"
			);

			if ( ! empty( $results ) ) {
				$user_ip_info[0] = $results[0]["internal_ip"];
				$user_ip_info[1] = $results[0]["user_ip_info"];
			}
		}
		return $user_ip_info;
	}
}
