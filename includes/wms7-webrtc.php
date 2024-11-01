<?php
/**
 * Description: Send data of IP internal of visitor.
 * PHP version 8.0.1
 * @category Wms7-webrtc.php
 * @package  WatchMan-Site7
 * @author   Oleg Klenitsky <klenitskiy.oleg@mail.ru>
 * @version  4.2.0
 * @license  GPLv2 or later
 * @filesource
 */

/**
 * Description: Check to save data of IP internal of visitors to site.
 */
function wms7_ip_internal_visitor() {
  $_wms7_ip_internal_visitor = filter_input( INPUT_POST, "ip_internal_visitor", FILTER_DEFAULT );

  if ( isset($_wms7_ip_internal_visitor) ) {
    wms7_save_internal_ip( $_wms7_ip_internal_visitor );
  }
}
/**
 * Description: Saves data of IP internal of visitors to site to the database.
 * @param string $data Data of data of IP internal.
 */
function wms7_save_internal_ip( $data ) {
  global $wpdb;

  $_wms7_id = filter_input( INPUT_POST, "wms7_id", FILTER_DEFAULT );

  $wpdb->update(
    $wpdb->prefix . "wms7_visitors",
    array( "internal_ip" => $data ),
    array( "ID" => $_wms7_id )
  );
}
