/**
 * Description: Used to manage the plug-in on admin panel.
 *
 * @category    Wms7_backend.js
 * @package     WatchMan-Site7
 * @author      Oleg Klenitsky <klenitskiy.oleg@mail.ru>
 * @version     4.2.0
 * @license     GPLv2 or later
 */

"use strict";

if (window.attachEvent) {
	window.attachEvent("onload", wms7_backend_onload);
} else if (window.addEventListener) {
	window.addEventListener("load", wms7_backend_onload, false);
} else {
	document.addEventListener("load", wms7_backend_onload, false);
}
/**
 * Description: Main function onload.
 */
function wms7_backend_onload() {
	var arr    = [];
	var page   = wms7_getUrlVars()["page"];
	var result = (wms7_getUrlVars()["result"]);
	var paged  = (wms7_getUrlVars()["paged"]) ? "&paged=" + wms7_getUrlVars()["paged"] : "";
	var action = (wms7_getUrlVars()["action"]) ? "&action=" + wms7_getUrlVars()["action"] : "";

	if (page == "wms7_settings" || page == "wms7_visitors" || page == "wms7_black_list") {
		var elements1 = document.getElementsByTagName("select");
			for (var i = 0; i < elements1.length; i++) {
				if ( elements1[i].type == "select-one" ) {
					elements1[i].onclick = function() {
						wms7_ctrl_sound("select");
					};
				}
			}
		var elements2 = document.querySelectorAll("input[type='submit']");
			for (var i = 0; i < elements2.length; i++) {
				if (elements2[i].type == "submit" ) {
					elements2[i].onclick = function() {
						wms7_ctrl_sound("button");
					};
				}
			}
	}
	if (page == "wms7_settings") {

		wms7_show();
	}
	if (page == "wms7_visitors") {
		wms7_page_onload();
		wms7_link_focus( page, result );
		wms7_stat_focus();

		let sse = document.getElementById( "sse" );
		if (localStorage.getItem( "wms7_sse_backend" ) == "on") {
			sse.checked = true;
			// start SSE.
			wms7_sse_backend();
		}else {
			// stop SSE.
			sse.checked = false;
			localStorage.setItem("wms7_sse_backend", "off" );
		}
	}
}
/**
 * @description Page onload.
 * Fixed a bug in the standard module WP_List_Table,
 * when the admin enters a value in the "current-page-selector field"
 * and in the address bar, the "paged" field did not change.
 */
function wms7_page_onload() {
  let current_page_selector = document.getElementById("current-page-selector");

  if (current_page_selector) {
    const queryString = new URLSearchParams(window.location.search);

    if (queryString.get("paged") !== current_page_selector.value) {
      let url = new URL(window.location);
      url.searchParams.set("paged", current_page_selector.value);
      history.pushState(null, null, url.href);
    }
  }
}
/**
 * Description: Process Control Server Sent Events on the client side (backend).
 */
function wms7_sse_backend() {
	let sse = document.getElementById( "sse" );

	if (sse.checked) {
		wms7_ctrl_sound("sse_on");
		// start SSE backend.
		localStorage.setItem("wms7_sse_backend", "on" );

		if ( window.EventSource ) {
			let wms7_source = new EventSource( wms7_ajax_url + "?action=backend");
			wms7_source.onmessage = function(e) {
				let arr           = e.data.replace(/"/g, '').split( ";" );
				let arr_add       = arr[0].split( "," );
				console.log( "All visits=" + arr_add[0] + " Server time=" + arr[1] + " Origin=" + e.origin );
				if (localStorage.getItem( "wms7_records_count" ) !== arr_add[0] ) {

					localStorage.setItem("wms7_records_count", arr_add[0] );

					wms7_beep();
					location.replace( window.location.href );
				}
			}
		} else {
			alert( "Your browser does not support Server-Sent Events. Execution stopped." );
			return;
		}
	} else {
		wms7_ctrl_sound("sse_off");
		// stop SSE backend.
		localStorage.setItem("wms7_sse_backend", "off" );
		location.replace( window.location.href );
	}
	wms7_ctrl_btn_href();
}

/**
 * Description: Blocks the controls (buttons) on the plug-in screen if SSE mode is enabled.
 */
function wms7_ctrl_btn_href() {
	let sse = document.getElementById( "sse" );
	let action1 = document.getElementById( "doaction" );
	let action2 = document.getElementById( "doaction2" );
	if (sse.checked) {
		// disable bulk action up.
		if ( action1 ) {document.getElementById( "doaction" ).disabled = true;}
		// disable bulk action down.
		if ( action2 ) {document.getElementById( "doaction2" ).disabled = true;}
		// disable filters level1.
		document.getElementById( "btn_level1_left" ).disabled  = true;
		document.getElementById( "search-submit" ).disabled = true;
		// disable all controls.
		document.getElementById( "btn_service1" ).disabled = true;
		document.getElementById( "btn_service2" ).disabled = true;
		document.getElementById( "btn_service3" ).disabled = true;
		document.getElementById( "btn_service4" ).disabled = true;
		document.getElementById( "btn_service5" ).disabled = true;
		document.getElementById( "btn_service6" ).disabled = true;
		document.getElementById( "btn_service8" ).disabled = true;
		document.getElementById( "btn_service9" ).disabled = true;

		// create a new style sheet.
		var styleTag = document.createElement( "style" );
		var a        = document.getElementsByTagName( "a" )[0];
		a.appendChild( styleTag );

		var sheet = styleTag.sheet ? styleTag.sheet : styleTag.styleSheet;

		// add a new rule to the style sheet.
		if (sheet.insertRule) {
			sheet.insertRule( "a {pointer-events: none;}", 0 );
		} else {
			sheet.addRule( "a", "pointer-events: none;", 0 );
		}
	} else {
		// enable bulk action up.
		if ( action1 ) {document.getElementById( "doaction" ).disabled = false;}
		// enable bulk action down.
		if ( action2 ) {document.getElementById( "doaction2" ).disabled = false;}
		// enable filters level1.
		document.getElementById( "btn_level1_left" ).disabled  = false;
		document.getElementById( "search-submit" ).disabled = false;
		// enable all controls.
		document.getElementById( "btn_service1" ).disabled = false;
		document.getElementById( "btn_service2" ).disabled = false;
		document.getElementById( "btn_service3" ).disabled = false;
		document.getElementById( "btn_service4" ).disabled = false;
		document.getElementById( "btn_service5" ).disabled = false;
		document.getElementById( "btn_service6" ).disabled = false;
		document.getElementById( "btn_service8" ).disabled = false;
		document.getElementById( "btn_service9" ).disabled = false;
	}
}

/**
 * Description: Get vars from URL address.
 * @return array.
 */
function wms7_getUrlVars() {
	var vars  = {};
	var url   = decodeURIComponent( window.location.href );
	var parts = url.replace(
		/[?&]+([^=&]+)=([^&]*)/gi,
		function(m,key,value) {
			vars[key] = value;
		}
	);
	return vars;
}

/**
 * Description: Setting radio buttons in the modal Statistics window.
 */
function wms7_stat_focus() {
	var btn;
	var myElement;
	btn = localStorage.getItem("wms7_stat_btn");
	if (document.getElementsByName( "radio_stat" )) {
		switch (btn) {
			case "visits" : {myElement = document.getElementById( "visits" ); break;}
			case "unlogged" : {myElement = document.getElementById( "unlogged" ); break;}
			case "success" : {myElement = document.getElementById( "success" ); break;}
			case "failed" : {myElement = document.getElementById( "failed" ); break;}
			case "robots" : {myElement = document.getElementById( "robots" ); break;}
			case "blacklist" : {myElement = document.getElementById( "blacklist" ); break;}
		}
		if (myElement) {
			myElement.checked = true;
		}
	}
}

/**
 * Description: Setting radio buttons visits in the main window of plugin.
 * @param string page Plugin page name.
 * @param string result Item of visit.
 */
function wms7_link_focus(page, result) {
	var myElement;
	if (page == "wms7_visitors") {
		switch (result) {
			case "0" : {myElement = document.getElementById( "radio-4" ); break;}
			case "1" : {myElement = document.getElementById( "radio-3" ); break;}
			case "2" : {myElement = document.getElementById( "radio-2" ); break;}
			case "3" : {myElement = document.getElementById( "radio-5" ); break;}
			case "4" : {myElement = document.getElementById( "radio-6" ); break;}
			default : {myElement = document.getElementById( "radio-1" );}
		}
		myElement.checked = true;
	}
}

/**
 * Description: Refresh the main plugin screen.
 * @param string visit Item of visit.
 */
function wms7_visit(visit) {
	var url = window.location.href;
	wms7_ctrl_sound("button");
	switch (visit) {
		case "radio-1": {url = url + "&result="; break;}
		case "radio-2": {url = url + "&result=2"; break;}
		case "radio-3": {url = url + "&result=1"; break;}
		case "radio-4": {url = url + "&result=0"; break;}
		case "radio-5": {url = url + "&result=3"; break;}
		case "radio-6": {url = url + "&result=4"; break;}
		default : {url = url;}
	}
	location.replace( url );
}

/**
 * Description: Saving the current value of the radio button Statistics to cookies.
 */
function wms7_stat_btn(){
	var myElement;
	var btn;
	wms7_ctrl_sound("button");

	myElement = document.getElementsByName( "radio_stat" );
	for (var i = 0; i < myElement.length; i++) {
		if (myElement[i].checked) {
			break;
		}
	}
	btn = myElement[i].value;

	localStorage.setItem("wms7_stat_btn", btn );
}

/**
 * Description: Settings for the sound notification object when visit the site.
 */
function wms7_show(){
	var fIn_frequency = document.getElementById( "fIn" ).value;
	var tIn_type ="";
	document.getElementById( "fOut" ).innerHTML = fIn_frequency + " Hz";

	switch ( document.getElementById( "tIn" ).value ) {
		case "0": tIn_type = "sine"; break;
		case "1": tIn_type = "square"; break;
		case "2": tIn_type = "sawtooth"; break;
		case "3": tIn_type = "triangle"; break;
	}
	document.getElementById( "tOut" ).innerHTML = tIn_type;

	var vIn_volume = document.getElementById( "vIn" ).value / 100;
	document.getElementById( "vOut" ).innerHTML = vIn_volume;

	var dIn_duration = document.getElementById( "dIn" ).value;
	document.getElementById( "dOut" ).innerHTML = dIn_duration + " ms";
}

/**
 * Description: Start of sound notification when visiting the site.
 */
function wms7_beep() {
	var fIn_frequency = document.getElementById( "fIn" );
	var vIn_volume    = document.getElementById( "vIn" );
	var dIn_duration  = document.getElementById( "dIn" );
	var tIn_type      = document.getElementById( "tIn" );

	var AudioContext = window.AudioContext || window.webkitAudioContext;
	var audioCtx     = new AudioContext();

	var oscillator = audioCtx.createOscillator();
	var gainNode   = audioCtx.createGain();

	if( fIn_frequency ) {
		fIn_frequency = document.getElementById( "fIn" ).value;
		localStorage.setItem( "fIn", fIn_frequency );
	}else{
		fIn_frequency = (localStorage.getItem( "fIn" )) ? localStorage.getItem( "fIn" ) : "600";
	}
	if( vIn_volume ) {
		vIn_volume = document.getElementById( "vIn" ).value;
		localStorage.setItem( "vIn", vIn_volume );
	}else{
		vIn_volume = (localStorage.getItem( "vIn" )) ? localStorage.getItem( "vIn" ) : "9";
	}
	if( dIn_duration ) {
		dIn_duration = document.getElementById( "dIn" ).value;
		localStorage.setItem( "dIn", dIn_duration );
	}else{
		dIn_duration = (localStorage.getItem( "dIn" )) ? localStorage.getItem( "dIn" ) : "390";
	}
	if( tIn_type ) {
		tIn_type = document.getElementById( "tIn" ).value;
		localStorage.setItem( "tIn", tIn_type );
	}else{
		tIn_type = (localStorage.getItem( "tIn" )) ? localStorage.getItem( "tIn" ) : "square";
	}

	oscillator.connect( gainNode );
	gainNode.connect( audioCtx.destination );
	switch ( tIn_type ) {
		case "0": tIn_type = "sine"; break;
		case "1": tIn_type = "square"; break;
		case "2": tIn_type = "sawtooth"; break;
		case "3": tIn_type = "triangle"; break;
	}
	gainNode.gain.value        = vIn_volume / 100;
	oscillator.frequency.value = fIn_frequency;
	oscillator.type            = tIn_type;

	oscillator.start();

	setTimeout(
		function(){
			oscillator.stop();
		},
		dIn_duration
	);
}

var dataChart = [];
/**
 * Description: Data preparation for chart statistic.
 * @param array data Used to pass data into a chart.
 */
function wms7_graph_statistic(data){
	var arr = [];
	var i   = 0;

	data = data.replace( /&quot;/g, '"' );
	data = JSON.parse( data );

	for ( var key in data ) {
		arr[i] = [key,data[key]];
		i++;
	}
	dataChart = arr;
	// Load the Visualization API and the piechart package.
	google.charts.load( "current", {"packages":["corechart", "controls"]} );
	// Set a callback to run when the Google Visualization API is loaded.
	google.charts.setOnLoadCallback( wms7_drawChart );
}

/**
 * Description: Callback that creates and populates a data table, instantiates the pie chart,
 * passes in the data and draws it.
 */
function wms7_drawChart() {
	var sel = document.getElementById( "graph_type" );
		sel = sel.options[sel.selectedIndex].value;

	// Create the data table.
	var data = new google.visualization.DataTable();
		data.addColumn( "string", "Items" );
		data.addColumn( "number", "Count" );
		data.addRows( dataChart );

	// Create a dashboard.
	var dashboard = new google.visualization.Dashboard(
		document.getElementById( "dashboard_chart" )
	);

	// Create a range slider, passing some options.
	var chartRangeSlider = new google.visualization.ControlWrapper(
		{
			"controlType": "NumberRangeFilter",
			"containerId": "filter_chart",
			"options": {
				"filterColumnLabel": "Count",
				"ui": {
					"format": {"pattern":"#"}
				}
			}
		}
	);

	// Create a pie chart, passing some options.
	var pieChart = new google.visualization.ChartWrapper(
		{
			"chartType": "PieChart",
			"containerId": "piechart",
			"options": {
				"pieSliceText": "value",
				"title": sel + " visitors to the site:",
				"legend": "left"
			}
		}
	);

	// Establish dependencies, declaring that "filter" drives "pieChart",
	// so that the pie chart will only display entries that are let through
	// given the chosen slider range.
	dashboard.bind( chartRangeSlider, pieChart );

	// Draw the dashboard.
	dashboard.draw( data );
}

/**
 * Description: Sound of control items.
 * @param string item Item control.
 */
function wms7_ctrl_sound(item) {
	var snd = new Audio;
	switch (item) {
		case "button"  : {snd.src = wms7_url + "/sound/button.wav"; break;}
		case "select"  : {snd.src = wms7_url + "/sound/select.wav"; break;}
		case "sse_on"  : {snd.src = wms7_url + "/sound/sse_on.wav"; break;}
		case "sse_off" : {snd.src = wms7_url + "/sound/sse_off.wav"; break;}
	}
	snd.play();
}
