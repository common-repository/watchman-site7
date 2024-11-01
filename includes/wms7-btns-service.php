<?php
/**
 * Description: Используется для вызовов сервисных функций плагина.
 * PHP version 8.0.1
 * @category Wms7-btns-service.php
 * @package  WatchMan-Site7
 * @author   Oleg Klenitsky <klenitskiy.oleg@mail.ru>
 * @version  4.2.0
 * @license  GPLv2 or later
 * @filesource
 */

/**
 * Description: A pop-up window starts on the main page of the plugin with an indication of the purpose of the action:
 * File editor "index.php".<br>
 * File editor "robots.txt".<br>
 * File editor ".htaccess".<br>
 * File editor "wp-config.php".<br>
 * Control wp-cron tasks.<br>
 * Displays statistics of site visits in various presentation formats.<br>
 * Commands Management Console PHP and WordPress.<br>
 * Window error viewer PHP and WordPress on site.
 */
function wms7_win_popup() {
	$_btns_service = filter_input( INPUT_POST, "btns_service", FILTER_DEFAULT );
	$url           = get_option( "wms7_current_url" );

	switch ( $_btns_service ) {
		case "index":
			$str_head = "index.php";
			wms7_file_editor( $str_head, $url );
			break;
		case "robots":
			$str_head = "robots.txt";
			wms7_file_editor( $str_head, $url );
			break;
		case "htaccess":
			$str_head = ".htaccess";
			wms7_file_editor( $str_head, $url );
			break;
		case "wp-config":
			$str_head = "wp-config.php";
			wms7_file_editor( $str_head, $url );
			break;
		case "wp-cron":
			$str_head = "wp-cron tasks";
			wms7_wp_cron( $str_head, $url );
			break;
		case "statistic":
			$str_head = "statistic of visits";
			wms7_stat( $str_head, $url );
			break;
		case "console":
			$str_head = "WordPress console";
			wms7_console( $str_head, $url );
			break;
		case "debug.log":
			$str_head = "debug.log";
			wms7_debug_log( $str_head, $url );
			break;
	}
}
/**
 * Description: File editor for: index.php robots.txt .htaccess wp-config.php
 * @param string $str_head Head of pop-up window.
 * @param string $url      URL of current page.
 */
function wms7_file_editor( $str_head, $url ) {
	WP_Filesystem();
	global $wp_filesystem;

	$img1          = plugins_url( "../images/screw.png", __FILE__ );
	$img2          = plugins_url( "../images/screw_l.png", __FILE__ );
	$img3          = plugins_url( "../images/screw_r.png", __FILE__ );
	$_btns_service = filter_input( INPUT_POST, "btns_service", FILTER_DEFAULT );

	if ( ! file_exists( ABSPATH . $str_head ) ) {
		$str_body = "File not found: " . ABSPATH . $str_head;
	} else {
		$filename = ABSPATH . $str_head;
		$str_body = $wp_filesystem->get_contents( $filename );
	}
	?>
	<div class="win-popup">
		<label class="btn"></label>
		<input type="checkbox" style="display: none;" checked>
		<div class="popup-content">
			<div class="popup-header">
				<h2><?php echo __( $str_head ); ?></h2>
				<img src="<?php echo __( $img1 ); ?>" style="position: absolute;top: 10px;left: 12px;">
				<label class="btn-close" title="close" onClick="location.href='<?php echo esc_url( $url ); ?>'"></label>
			</div>
			<form id="popup_win_file_editor" method="POST">
				<div class="popup-body">
					<div style="height: 300px; padding: 5px 0 0 0; margin:0 0 0 10px; background-color: #D4D0C8;">
						<img src="<?php echo __( $img2 ); ?>" style="position: absolute;top: 50px;left: 12px;z-index:2;">
						<img src="<?php echo __( $img3 ); ?>" style="position: absolute;top: 335px;left: 12px;z-index:2;">
						<img src="<?php echo __( $img3 ); ?>" style="position: absolute;top: 50px;left: 96%;z-index:2;">
						<img src="<?php echo __( $img2 ); ?>" style="position: absolute;top: 335px;left: 96%;z-index:2;">
						<textarea name="content"><?php echo esc_textarea( $str_body ); ?></textarea>
					</div>
				</div>
				<div class="popup-footer">
					<input type="submit" class="button-primary" name="<?php echo __( $_btns_service ); ?>" value="Save" onClick="wms7_button_sound()">
					<input type="hidden" name="wms7_nonce" value="<?php echo __( wp_create_nonce( "wms7_nonce" ) ); ?>">
					<label style="padding: 0;"><?php echo __( ABSPATH ); ?> </label>
				</div>
			</form>
		</div>
	</div>
	<?php
}
/**
 * Description: Control wp-cron tasks.
 * @param string $str_head Head of pop-up window.
 * @param string $url      URL of current page.
 */
function wms7_wp_cron( $str_head, $url ) {
	$img1       = plugins_url( "../images/screw.png", __FILE__ );
	$img2       = plugins_url( "../images/screw_l.png", __FILE__ );
	$img3       = plugins_url( "../images/screw_r.png", __FILE__ );
	$wms7_cron  = new wms7_cron();
	$cron_table = $wms7_cron->wms7_create_cron_table();
	?>
	<div class="win-popup">
		<label class="btn"></label>
		<input type="checkbox" style="display: none;" checked>
		<div class="popup-content">
			<div class="popup-header">
				<h2><?php echo __( $str_head ); ?></h2>
				<img src="<?php echo __( $img1 ); ?>" style="position: absolute;top: 10px;left: 12px;">
				<label class="btn-close" title="close" onClick="location.href='<?php echo esc_url( $url ); ?>'"></label>
			</div>
			<form id="popup_win_cron" method="POST">
				<div class="popup-body">
					<div style="height: 300px; padding: 5px 0 0 0; margin:0 0 0 10px; background-color: #D4D0C8;">
						<img src="<?php echo __( $img2 ); ?>" style="position: absolute;top: 50px;left: 12px;z-index:2;">
						<img src="<?php echo __( $img3 ); ?>" style="position: absolute;top: 335px;left: 12px;z-index:2;">
						<img src="<?php echo __( $img3 ); ?>" style="position: absolute;top: 50px;left: 96%;z-index:2;">
						<img src="<?php echo __( $img2 ); ?>" style="position: absolute;top: 335px;left: 96%;z-index:2;">
						<ul class="tasks" style="margin-left: 5%; width: 90%;">
						<li class = "tasks" style="color: red;font-weight:bold;"><?php echo __( "Not found", "wms7" ); ?> : <?php echo __( $wms7_cron->orphan_count ); ?></li>
						<li class = "tasks" style="color: blue;font-weight:bold;"><?php echo __( "Plugin task", "wms7" ); ?> : <?php echo __( $wms7_cron->plugin_count ); ?></li>
						<li class = "tasks" style="color: green;font-weight:bold;"><?php echo __( "Themes task", "wms7" ); ?> : <?php echo __( $wms7_cron->themes_count ); ?></li>
						<li class = "tasks" style="color: brown;font-weight:bold;"><?php echo __( "WP task", "wms7" ); ?> : <?php echo __( $wms7_cron->wp_count ); ?></li>
						</ul>
						<table class="table_cron">
							<thead class="thead_cron">
								<tr class="tr_cron">
									<th class="th_cron" width="9%">id</th>
									<th class="th_cron" width="35%"><?php echo __( "Task name", "wms7" ); ?></th>
									<th class="th_cron" width="15%"><?php echo __( "Recurrence", "wms7" ); ?></th>
									<th class="th_cron" width="20%"><?php echo __( "Next run", "wms7" ); ?></th>
									<th class="th_cron" width="21%"><?php echo __( "Source task", "wms7" ); ?></th>
								</tr>
							</thead>
							<tbody class="tbody_cron">
							<?php
							$i = 0;
							foreach ( $cron_table as $item ) {
								$i++;
								$val = explode( "|", $item );
								?>
								<tr class="tr_cron">
									<td class="td_cron" width="8%" style="padding-left:5px;">
										<input type="checkbox" name="<?php echo __( $val[0] ); ?>" value="cron<?php echo __( $i ); ?>" > <?php echo __( $i ); ?>
									</td>
									<td class="td_cron" width="36%">
										<?php echo __( $val[0] ); ?>
									</td>
									<td class="td_cron" width="15%">
										<?php echo __( $val[1] ); ?>
									</td>
									<td class="td_cron" width="20%">
										<?php echo __( $val[2] ); ?>
									</td>
									<td class="td_cron" width="21%" title="<?php echo __( $val[3] ); ?>" >
									<?php
									switch ( $val[5] ) {
										case "":
											?>
											<?php echo __( $val[4] ); ?></td>
											<?php
											break;
										case "step1":
											?>
										<span style="color: brown;"><?php echo __( $val[4] ); ?></span></td>
											<?php
											break;
										case "step2":
											?>
										<span style="color: brown;"><?php echo __( $val[4] ); ?></span></td>
											<?php
											break;
										case "step3":
											?>
										<span style="color: green;"><?php echo __( $val[4] ); ?></span></td>
											<?php
											break;
										case "step4":
											?>
										<span style="color: blue;"><?php echo __( $val[4] ); ?></span></td>
											<?php
											break;
										case "step5":
											?>
										<span style="color: red;"><?php echo __( $val[4] ); ?></span></td>
											<?php
											break;
									}
									?>
								</tr>
								<?php
							}
							?>
							</tbody>
							<tfoot class="tfoot_cron">
								<tr class="tr_cron"><th class="th" width="9%">id</th>
									<th class="th_cron" width="35%"><?php echo __( "Task name", "wms7" ); ?></th>
									<th class="th_cron" width="15%"><?php echo __( "Recurrence", "wms7" ); ?></th>
									<th class="th_cron" width="20%"><?php echo __( "Next run", "wms7" ); ?></th>
									<th class="th_cron" width="21%"><?php echo __( "Source task", "wms7" ); ?></th>
								</tr>
							</tfoot>
						</table>
					</div>
				</div>

				<div class="popup-footer">
					<input type="submit" class="button-primary" name="cron_delete" value="Delete" onClick="wms7_button_sound()">
					<input type="submit" class="button-primary" name="cron_refresh" value="Refresh" onClick="wms7_button_sound()">
					<label><?php echo __( " Note: click Refresh and move your cursor over the Source task cell to see the access path.", "wms7" ); ?></label>
				</div>
				<input type="hidden" name="wms7_nonce" value="<?php echo __( wp_create_nonce( "wms7_nonce" ) ); ?>">
			</form>
		</div>
	</div>
	<script type="text/javascript">
		var work_progress = document.getElementById("work_progress");
		if (work_progress) {
			work_progress.remove();
		}
	</script>
	<?php
}
/**
 * Description: Displays statistics of site visits in various presentation formats.
 * @param string $str_head Head of pop-up window.
 * @param string $url      URL of current page.
 */
function wms7_stat( $str_head, $url ) {
	$img1            = plugins_url( "../images/screw.png", __FILE__ );
	$img2            = plugins_url( "../images/screw_l.png", __FILE__ );
	$img3            = plugins_url( "../images/screw_r.png", __FILE__ );
	$all_total       = Wms7_List_Table::wms7_get( "allTotal_stat" );
	$visits_total    = Wms7_List_Table::wms7_get( "visitsTotal_stat" );
	$success_total   = Wms7_List_Table::wms7_get( "successTotal_stat" );
	$failed_total    = Wms7_List_Table::wms7_get( "failedTotal_stat" );
	$robots_total    = Wms7_List_Table::wms7_get( "robotsTotal_stat" );
	$blacklist_total = Wms7_List_Table::wms7_get( "blacklistTotal_stat" );
	$where_stat      = Wms7_List_Table::wms7_get( "where_stat" );
	$_stat_table     = filter_input( INPUT_POST, "stat_table", FILTER_DEFAULT );
	$_stat_graph     = filter_input( INPUT_POST, "stat_graph", FILTER_DEFAULT );
	$_graph_type     = filter_input( INPUT_POST, "graph_type", FILTER_DEFAULT );

	$records_table_stat = wms7_create_table_stat( $where_stat );
	$records_graph_stat = wms7_create_graph_stat( $where_stat );

	?>
	<div class='win-popup'>
		<label class='btn' for='win-popup'></label>
		<input type='checkbox' style='display: none;' checked>
		<div class='popup-content'>
			<img src="<?php echo __( $img2 ); ?>" style="position: absolute;top: 68px;left: 12px;z-index:2;">
			<img src="<?php echo __( $img3 ); ?>" style="position: absolute;top: 342px;left: 12px;z-index:2;">
			<img src="<?php echo __( $img3 ); ?>" style="position: absolute;top: 68px;left: 655px;z-index:2;">
			<img src="<?php echo __( $img2 ); ?>" style="position: absolute;top: 342px;left: 655px;z-index:2;">
			<div class='popup-header'>
				<h2><?php echo esc_html( $str_head ); ?></h2>
				<img src="<?php echo esc_html( $img1 ); ?>" style="position: absolute;top: 10px;left: 12px;">
				<label class='btn-close' title='close' for='win-popup' onClick="location.href='<?php echo esc_url( $url ); ?>'"></label>
			</div>
			<form id='popup_win' method='POST'>
				<div style='margin: -5px 0 5px 10px;'>
					<input class='radio' type='radio' id='visits' name='radio_stat' value='visits' onClick='wms7_stat_btn()'/>
					<label for='visits' style='color:black;'><?php echo esc_html( 'Visits All', 'watchman-site7' ); ?>(<?php echo esc_html( $all_total ); ?>)</label>
					<input class='radio' type='radio' id='unlogged' name='radio_stat' value='unlogged' onClick='wms7_stat_btn()'/>
					<label for='unlogged' style='color:black;'><?php echo esc_html( 'Unlogged', 'watchman-site7' ); ?>(<?php echo esc_html( $visits_total ); ?>)</label>
					<input class='radio' type='radio' id='success' name='radio_stat' value='success' onClick='wms7_stat_btn()'/>
					<label for='success' style='color:black;'><?php echo esc_html( 'Success', 'watchman-site7' ); ?>(<?php echo esc_html( $success_total ); ?>)</label>
					<input class='radio' type='radio' id='failed' name='radio_stat' value='failed' onClick='wms7_stat_btn()'/>
					<label for='failed' style='color:black;'><?php echo esc_html( 'Failed', 'watchman-site7' ); ?>(<?php echo esc_html( $failed_total ); ?>)</label>
					<input class='radio' type='radio' id='robots' name='radio_stat' value='robots' onClick='wms7_stat_btn()'/>
					<label for='robots' style='color:black;'><?php echo esc_html( 'Robots', 'watchman-site7' ); ?>(<?php echo esc_html( $robots_total ); ?>)</label>
					<input class='radio' type='radio' id='blacklist' name='radio_stat' value='blacklist' onClick='wms7_stat_btn()'/>
					<label for='blacklist' style='color:black;'><?php echo esc_html( 'Black List', 'watchman-site7' ); ?>(<?php echo esc_html( $blacklist_total ); ?>)</label>
				</div>
				<div style="margin-left: 10px;background:#D4D0C8;width: 660px;height:275px;padding-top: 15px;">
					<div style="overflow:auto;margin-left:10px;margin-right:10px;width: 635px;height:260px;">
						<?php
						if ( $_stat_table && ! empty( $records_table_stat ) ) {
							?>
							<table class="table_stat" style="margin-left:5px;max-width:640px;border-collapse: collapse;">
								<thead>
									<tr>
									<?php
									foreach ( $records_table_stat[0] as $key => $value ) {
										?>
										<th class="stat_th"><?php echo __( $key ); ?></th>
										<?php
									}
									?>
									</tr>
								</thead>
								<tbody>
									<?php
									foreach ( $records_table_stat as $record ) {
										$i = 0;
										?>
										<tr>
										<?php
										foreach ( $record as $key => $value ) {
											if ( 0 === $i ) {
												?>
													<td class="stat_td" style="width:90px;"><?php echo __( $value ); ?></td>
													<?php
											} else {
												?>
												<td class="stat_td" style="width:30px;"><?php echo __( $value ); ?></td>
													<?php
											}
											$i++;
										}
										?>
										</tr>
										<?php
									}
									?>
								</tbody>
								<tfoot>
									<tr>
										<?php
										foreach ( $records_table_stat[0] as $key => $value ) {
										?>
										<th class="stat_th"><?php echo __( $key ); ?></th>
										<?php
									}
										?>
									</tr>
								</tfoot>
							</table>
							<?php
						}
						if ( $_stat_graph && ! empty( $records_graph_stat ) ) {
							?>
							<div id="dashboard_chart">
								<div style="text-align:center;"><div id="filter_chart"></div></div>
								<div id="piechart" style="margin: 0;padding:0;width: 640px;height: 238px;position:absolute;">
									<?php
									$records_graph_stat = wp_json_encode( $records_graph_stat );
									?>
									<script src="https://www.gstatic.com/charts/loader.js" type="text/javascript">
									</script>
									<script>
										wms7_graph_statistic('<?php echo __( $records_graph_stat ); ?>');
									</script>
									<?php
									?>
								</div>
							</div>
							<?php
						}
						?>
					</div>
				</div>
				<div class="popup-footer">
					<input type="submit" class="button-primary" name="stat_table" id="btn_stat_table" value="Table">
					<input type="submit" class="button-primary" name="stat_graph" id="btn_stat_graph" value="Graph" style="float:right;margin-left:5px;">
					<select class="alignright actions" id="graph_type" name="graph_type">
						<option value="browser" <?php echo __( selected( "browser", $_graph_type, false ) ); ?>><?php echo __( "browser", "wms7" ); ?></option>
						<option value="device" <?php echo __( selected( "device", $_graph_type, false ) ); ?>><?php echo __( "device", "wms7" ); ?></option>
						<option value="platform" <?php echo __( selected( "platform", $_graph_type, false ) ); ?>><?php echo __( "platform", "wms7" ); ?></option>
					</select>
				</div>
				<input type="hidden" name="wms7_nonce" value="<?php echo __( wp_create_nonce( "wms7_nonce" ) ); ?>">
			</form>
		</div>
	</div>
	<?php

}
/**
 * Description: Commands Management Console PHP and WordPress.
 * @param string $str_head Head of pop-up window.
 * @param string $url      URL of current page.
 */
function wms7_console( $str_head, $url ) {
	$img1 = plugins_url( "../images/screw.png", __FILE__ );
	$img2 = plugins_url( "../images/screw_l.png", __FILE__ );
	$img3 = plugins_url( "../images/screw_r.png", __FILE__ );
	?>
	<div class="win-popup">
		<label class="btn"></label>
		<input type="checkbox" style="display: none;" checked>
		<div class="popup-content">
			<div class="popup-header">
				<h2><?php echo __( $str_head ); ?></h2>
				<img src="<?php echo __( $img1 ); ?>" style="position: absolute;top: 10px;left: 12px;">
				<label class="btn-close" title="close" onClick="location.href='<?php echo esc_url( $url ); ?>'"></label>
			</div>
			<form id="popup_win_console" method="POST">
				<div class="popup-body">
					<div style="height: 330px; padding: 5px 0 0 0; margin:0 0 0 10px; background-color: #D4D0C8;">
						<img src="<?php echo __( $img2 ); ?>" style="position: absolute;top: 50px;left: 12px;z-index:2;">
						<img src="<?php echo __( $img3 ); ?>" style="position: absolute;top: 365px;left: 12px;z-index:2;">
						<img src="<?php echo __( $img3 ); ?>" style="position: absolute;top: 50px;left: 96%;z-index:2;">
						<img src="<?php echo __( $img2 ); ?>" style="position: absolute;top: 365px;left: 96%;z-index:2;">
						<div id="wms7_console">
							<script language="javascript">window.onload = wms7_console()</script>
						</div>
					</div>
				</div>
				<div class="popup-footer" style="margin: 0 0 0 10px;">
					<label style="font-weight: bold;">Use: "?" for help menu</label>
				</div>
			</form>
		</div>
	</div>
	<?php
}
/**
 * Description: Window error viewer PHP and WordPress on site.
 * @param string $str_head Head of pop-up window.
 * @param string $url      URL of current page.
 */
function wms7_debug_log( $str_head, $url ) {
	WP_Filesystem();
	global $wp_filesystem;

	$img1          = plugins_url( "../images/screw.png", __FILE__ );
	$img2          = plugins_url( "../images/screw_l.png", __FILE__ );
	$img3          = plugins_url( "../images/screw_r.png", __FILE__ );
	$_btns_service = filter_input( INPUT_POST, "btns_service", FILTER_DEFAULT );

	$filename = ABSPATH . "wp-content/debug.log";
	if ( ! file_exists( $filename ) ) {
		$str_body = "File not found: " . $filename . "\nTo create debug.log - you need to select the item in the plugin settings: 7.WP_DEBUG.\nThe debug.log file will appear automatically if there are PHP errors in WP.";
	} else {
		$str_body = $wp_filesystem->get_contents( $filename );
	}
	?>
	<div class="win-popup">
		<label class="btn"></label>
		<input type="checkbox" style="display: none;" checked>
		<div class="popup-content">
			<div class="popup-header">
				<h2><?php echo __( "WordPress ".$str_head ); ?></h2>
				<img src="<?php echo __( $img1 ); ?>" style="position: absolute;top: 10px;left: 12px;">
				<label class="btn-close" title="close" onClick="location.href='<?php echo esc_url( $url ); ?>'"></label>
			</div>
			<form id="popup_win_debug_log" method="POST">
				<div class="popup-body">
					<div style="height: 300px; padding: 5px 0 0 0; margin:0 0 0 10px; background-color: #D4D0C8;">
						<img src="<?php echo __( $img2 ); ?>" style="position: absolute;top: 50px;left: 12px;z-index:2;">
						<img src="<?php echo __( $img3 ); ?>" style="position: absolute;top: 335px;left: 12px;z-index:2;">
						<img src="<?php echo __( $img3 ); ?>" style="position: absolute;top: 50px;left: 96%;z-index:2;">
						<img src="<?php echo __( $img2 ); ?>" style="position: absolute;top: 335px;left: 96%;z-index:2;">
						<textarea name="content"><?php echo esc_textarea( $str_body ); ?></textarea>
					</div>
				</div>
				<div class="popup-footer">
					<input type="submit" class="button-primary" name="<?php echo __( $_btns_service ); ?>" value="Clear" onClick="wms7_button_sound()">
					<input type="hidden" name="wms7_nonce" value="<?php echo __( wp_create_nonce( "wms7_nonce" ) ); ?>">
					<label style="padding: 0;"><?php echo __( $filename ); ?> </label>
				</div>
			</form>
		</div>
	</div>
	<?php
}
/**
 * Builds progress for wp-cron popup during Refresh process.
 */
function wms7_work_progress() {
	?>
  <div id="work_progress" class="work_progress">
    <div class="ldio-c25u3t2knio">
    	<div></div>
    </div>
    <span class="preloader__value">
        <span id="process">wait...</span>
    </span>
  </div>
	<style type="text/css">
		@keyframes ldio-c25u3t2knio {
		  0% { transform: translate(-50%,-50%) rotate(0deg); }
		  100% { transform: translate(-50%,-50%) rotate(360deg); }
		}
		.ldio-c25u3t2knio div {
		  position: absolute;
		  width: 80px;
		  height: 80px;
		  border: 10px solid #85a2b6;
		  border-top-color: transparent;
		  border-radius: 50%;
		}
		.ldio-c25u3t2knio div {
		  animation: ldio-c25u3t2knio 1s linear infinite;
		  top: 50px;
		  left: 50px;
		}
		.work_progress, .preloader__value {
			width:100px;
			height:100px;
			overflow:hidden;
			position:fixed;
			top:50%;
			left:48%;

		  z-index: 3;
		}
		.work_progress {
			border-radius: 50px;
    	background-color: white;
		}
		.preloader__value {
			display: flex;
			justify-content: center;
			align-items: center;
		  font-family: Arial, sans-serif;
		  font-size: 16px;
		  font-weight: bold;
		}
		#process {
			height: 20px;
		}
		.ldio-c25u3t2knio div {
			box-sizing: content-box;
		}
	</style>
	<?php
}
