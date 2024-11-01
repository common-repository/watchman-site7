/**
 * Description: Get internal IP address of pc visitors.
 * @category    Wms7_webrtc.js
 * @package     WatchMan-Site7
 * @author      Oleg Klenitsky <klenitskiy.oleg@mail.ru>
 * @version     4.2.0
 * @license     GPLv2 or later
 */

"use strict";

if (window.attachEvent) {
  window.attachEvent("onload", wms7_webrtc_onload);
} else if (window.addEventListener) {
  window.addEventListener("load", wms7_webrtc_onload, false);
} else {
  document.addEventListener("load", wms7_webrtc_onload, false);
}
/**
 * Description: Main function onload.
 */
function wms7_webrtc_onload() {
  if (wms7_stun_server && wms7_stun_server !== "none") {
    wms7_get_ip_internal();
  }
}
/**
 * Description: Get the internal IP address.
 */
function wms7_get_ip_internal() {
  var ip_internal_visitor = "";
  var server = {
    iceServers : [{
      urls : "stun:" + wms7_stun_server
    }]
  };

  if (RTCPeerConnection) {
    var pc = new RTCPeerConnection(server);
    pc.onicecandidate = e => {
      if (e.candidate && e.candidate.candidate.length > 10) {
        let candidate = e.candidate.candidate.substr(10);
        let [foundation, component, protocol, priority, address, port, xxx, type] = candidate.split(" ");
        candidate = address + "," + port + "," + type + "," + protocol;
        ip_internal_visitor = ip_internal_visitor + candidate + ";";
      }else{
        wms7_ip_internal_visitor(ip_internal_visitor);
      }
    }
    pc.createDataChannel("");
    pc.setLocalDescription(pc.createOffer());
  }
}
/**
 * Description: Send data of IP internal of visitors to site.
 * @param string ip_internal_visitor IP internal of visitors.
 */
function wms7_ip_internal_visitor(ip_internal_visitor) {
  var params = "ip_internal_visitor=" + ip_internal_visitor + "&wms7_id=" + wms7_id +
        "&action=ip_internal";
  var xmlhttp = new XMLHttpRequest();

  // Open an asynchronous connection.
  xmlhttp.open( 'POST', wms7_ajax_url, true );
  // Sent encoding.
  xmlhttp.setRequestHeader( 'Content-Type', 'application/x-www-form-urlencoded' );
  // Send a POST request.
  xmlhttp.send( params );
  xmlhttp.onreadystatechange = function() { // Waiting for a response from the server.
    if (xmlhttp.readyState == 4) { // The response is received.
      if (xmlhttp.status == 200) { // The server returned code 200 (which is good).
      }
    }
  };
}
