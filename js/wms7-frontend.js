/**
 * Description: Transfer data about site visits to a widget - counter of visits.
 * Manage Black List table.
 * @category    Wms7_frontend.js
 * @package     WatchMan-Site7
 * @author      Oleg Klenitsky <klenitskiy.oleg@mail.ru>
 * @version     4.2.0
 * @license     GPLv2 or later
 */

"use strict";

var item_curnt = 0;
var item_shift = 0;

if (window.attachEvent) {
	window.attachEvent("onload", wms7_frontend_onload);
} else if (window.addEventListener) {
	window.addEventListener("load", wms7_frontend_onload, false);
} else {
	document.addEventListener("load", wms7_frontend_onload, false);
}

/**
 * Description: Main function onload.
 */
function wms7_frontend_onload() {
	wms7_sse_frontend();
}
/**
 * Description: Process Control Server Sent Events on the client side (frontend).
 */
function wms7_sse_frontend() {
	// start SSE frontend.
	if ( window.EventSource ) {
		let counter = document.getElementById( "counter" );

		if ( !counter ) return;

		let wms7_source = new EventSource( wms7_ajax_url + "?action=frontend");
		wms7_source.onmessage = function(e) {
			let arr     = e.data.replace(/"/g, "").split( ";" );
			let arr_add = arr[0].split( "," );
			console.log( "Counter=" + arr_add[0]+","+arr_add[1]+","+arr_add[2]+","+arr_add[3]+
						","+arr_add[4]+","+arr_add[5]+","+arr_add[6]+","+arr_add[7]+","+arr_add[8]+
						" Server time=" + arr[1] + " Origin=" + e.origin );
			// Redraw the widget - counter of visits.
			let counter_month_visits   = document.getElementById( "counter_month_visits" );
			let counter_month_visitors = document.getElementById( "counter_month_visitors" );
			let counter_month_robots   = document.getElementById( "counter_month_robots" );
			let counter_week_visits    = document.getElementById( "counter_week_visits" );
			let counter_week_visitors  = document.getElementById( "counter_week_visitors" );
			let counter_week_robots    = document.getElementById( "counter_week_robots" );
			let counter_today_visits   = document.getElementById( "counter_today_visits" );
			let counter_today_visitors = document.getElementById( "counter_today_visitors" );
			let counter_today_robots   = document.getElementById( "counter_today_robots" );

			counter_month_visits.innerHTML   = arr_add[0];
			counter_month_visitors.innerHTML = arr_add[1];
			counter_month_robots.innerHTML   = arr_add[2];
			counter_week_visits.innerHTML    = arr_add[3];
			counter_week_visitors.innerHTML  = arr_add[4];
			counter_week_robots.innerHTML    = arr_add[5];
			counter_today_visits.innerHTML   = arr_add[6];
			counter_today_visitors.innerHTML = arr_add[7];
			counter_today_robots.innerHTML   = arr_add[8];
		}
	} else {
		alert( "Your browser does not support Server-Sent Events. Execution stopped." );
		return;
	}
}
