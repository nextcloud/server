				<script type="text/javascript">
				var oc_cal_daylong = new Array("<?php echo $l -> t("Sunday");?>", "<?php echo $l -> t("Monday");?>", "<?php echo $l -> t("Tuesday");?>", "<?php echo $l -> t("Wednesday");?>", "<?php echo $l -> t("Thursday");?>", "<?php echo $l -> t("Friday");?>", "<?php echo $l -> t("Saturday");?>");
				var oc_cal_dayshort = new Array("<?php echo $l -> t("Sun.");?>", "<?php echo $l -> t("Mon.");?>", "<?php echo $l -> t("Tue.");?>", "<?php echo $l -> t("Wed.");?>", "<?php echo $l -> t("Thu.");?>", "<?php echo $l -> t("Fri.");?>", "<?php echo $l -> t("Sat.");?>");
				var oc_cal_monthlong = new Array("<?php echo $l -> t("January");?>", "<?php echo $l -> t("February");?>", "<?php echo $l -> t("March");?>", "<?php echo $l -> t("April");?>", "<?php echo $l -> t("May");?>", "<?php echo $l -> t("June");?>", "<?php echo $l -> t("July");?>", "<?php echo $l -> t("August");?>", "<?php echo $l -> t("September");?>", "<?php echo $l -> t("October");?>", "<?php echo $l -> t("November");?>", "<?php echo $l -> t("December");?>");
				var oc_cal_monthshort = new Array("<?php echo $l -> t("Jan.");?>", "<?php echo $l -> t("Feb.");?>", "<?php echo $l -> t("Mar.");?>", "<?php echo $l -> t("Apr.");?>", "<?php echo $l -> t("May");?>", "<?php echo $l -> t("Jun.");?>", "<?php echo $l -> t("Jul.");?>", "<?php echo $l -> t("Aug.");?>", "<?php echo $l -> t("Sep.");?>", "<?php echo $l -> t("Oct.");?>", "<?php echo $l -> t("Nov.");?>", "<?php echo $l -> t("Dec.");?>");
				var onedayview_radio = "1 <?php echo $l->t("Day");?>";
				var oneweekview_radio = "1 <?php echo $l->t("Week");?>";
				var fourweeksview_radio = "4 <?php echo $l->t("Weeks");?>";
				var onemonthview_radio = "1 <?php echo $l->t("Month");?>";
				var listview_radio = "<?php echo $l->t("Listview");?>";
				var today_button_value = "<?php echo $l->t("Today");?>";
				var choosecalendar_value = "<?php echo $l->t("Calendars");?>";
				var cw_label = "<?php echo $l->t("CW");?>";
				var cws_label = "<?php echo $l->t("CWs");?>";
				</script>
				<div id="sysbox"></div>
				<div id="controls">
					<div>
						<form>
							<div id="view">
								<input type="button" value="1 Day" id="onedayview_radio" onclick="oc_cal_change_view('onedayview');"/>
								<input type="button" value="1 Week" id="oneweekview_radio" onclick="oc_cal_change_view('oneweekview');"/>
								<input type="button" value="4 Weeks" id="fourweeksview_radio" onclick="oc_cal_change_view('fourweeksview');"/>
								<input type="button" value="1 Month" id="onemonthview_radio" onclick="oc_cal_change_view('onemonthview');"/>
								<input type="button" value="Listview" id="listview_radio" onclick="oc_cal_change_view('listview');"/>
							</div>
						</form>
						<form>
							<div id="choosecalendar">
								<input type="button" id="today_input" value="Today" onclick="oc_cal_switch2today();"/>
								<input type="button" id="choosecalendar_input" value="Calendars" onclick="oc_cal_choosecalendar();" />
							</div>
						</form>
						<form>
							<div id="datecontrol">
								<input type="button" value="&nbsp;&lt;&nbsp;" id="datecontrol_left" onclick="Calendar.UI.updateDate('backward');Calendar.UI.updateView();"/>
								<input id="datecontrol_date" type="button" value=""/>
								<input type="button" value="&nbsp;&gt;&nbsp;" id="datecontrol_left" onclick="Calendar.UI.updateDate('forward');Calendar.UI.updateView();"/>
							</div>
						</form>
					</div>
				</div>
				<div id="calendar_holder">
					<div id="onedayview">
						<table>
							<thead>
								<tr>
									<th class="calendar_time"><?php echo $l->t("Time");?></th>
									<th id="onedayview_today" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title);"></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td class="calendar_time"><?php echo $l->t("All day");?></td>
									<td id="onedayview_wholeday" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, 'allday');"></td>
								</tr>
								<tr>
									<td class="calendar_time">00:00</td>
									<td id="onedayview_0" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '0');"></td>
								</tr>
								<tr>
									<td class="calendar_time">01:00</td>
									<td id="onedayview_1" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '1');"></td>
								</tr>
								<tr>
									<td class="calendar_time">02:00</td>
									<td id="onedayview_2" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '2');"></td>
								</tr>
								<tr>
									<td class="calendar_time">03:00</td>
									<td id="onedayview_3" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '3');"></td>
								</tr>
								<tr>
									<td class="calendar_time">04:00</td>
									<td id="onedayview_4" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '4');"></td>
								</tr>
								<tr>
									<td class="calendar_time">05:00</td>
									<td id="onedayview_5" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '5');"></td>
								</tr>
								<tr>
									<td class="calendar_time">06:00</td>
									<td id="onedayview_6" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '6');"></td>
								</tr>
								<tr>
									<td class="calendar_time">07:00</td>
									<td id="onedayview_7" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '7');"></td>
								</tr>
								<tr>
									<td class="calendar_time">08:00</td>
									<td id="onedayview_8" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '8');"></td>
								</tr>
								<tr>
									<td class="calendar_time">09:00</td>
									<td id="onedayview_9" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '9');"></td>
								</tr>
								<tr>
									<td class="calendar_time">10:00</td>
									<td id="onedayview_10" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '10');"></td>
								</tr>
								<tr>
									<td class="calendar_time">11:00</td>
									<td id="onedayview_11" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '11');"></td>
								</tr>
								<tr>
									<td class="calendar_time">12:00</td>
									<td id="onedayview_12" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '12');"></td>
								</tr>
								<tr>
									<td class="calendar_time">13:00</td>
									<td id="onedayview_13" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '13');"></td>
								</tr>
								<tr>
									<td class="calendar_time">14:00</td>
									<td id="onedayview_14" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '14');"></td>
								</tr>
								<tr>
									<td class="calendar_time">15:00</td>
									<td id="onedayview_15" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '15');"></td>
								</tr>
								<tr>
									<td class="calendar_time">16:00</td>
									<td id="onedayview_16" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '16');"></td>
								</tr>
								<tr>
									<td class="calendar_time">17:00</td>
									<td id="onedayview_17" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '17');"></td>
								</tr>
								<tr>
									<td class="calendar_time">18:00</td>
									<td id="onedayview_18" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '18');"></td>
								</tr>
								<tr>
									<td class="calendar_time">19:00</td>
									<td id="onedayview_19" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '19');"></td>
								</tr>
								<tr>
									<td class="calendar_time">20:00</td>
									<td id="onedayview_20" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '20');"></td>
								</tr>
								<tr>
									<td class="calendar_time">21:00</td>
									<td id="onedayview_21" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '21');"></td>
								</tr>
								<tr>
									<td class="calendar_time">22:00</td>
									<td id="onedayview_22" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '22');"></td>
								</tr>
								<tr>
									<td class="calendar_time">23:00</td>
									<td id="onedayview_23" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('onedayview_today').title, '23');"></td>
								</tr>
							</tbody>
						</table>
					</div>
					<div id="oneweekview">
						<table>
							<thead>
								<tr>
									<th class="calendar_time"><?php echo $l->t("Time");?></th>
									<th id="oneweekview_monday" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title);"></th>
									<th id="oneweekview_tuesday" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title);"></th>
									<th id="oneweekview_wednesday" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title);"></th>
									<th id="oneweekview_thursday" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title);"></th>
									<th id="oneweekview_friday" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title);"></th>
									<th id="oneweekview_saturday" class="weekend_thead" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title);"></th>
									<th id="oneweekview_sunday" class="weekend_thead" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title);"></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td class="calendar_time"><?php echo $l->t("All day");?></td>
									<td id="oneweekview_monday_allday" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, 'allday');"></td>
									<td id="oneweekview_tuesday_allday" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, 'allday');"></td>
									<td id="oneweekview_wednesday_allday" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, 'allday');"></td>
									<td id="oneweekview_thursday_allday" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, 'allday');"></td>
									<td id="oneweekview_friday_allday" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, 'allday');"></td>
									<td id="oneweekview_saturday_allday" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, 'allday');"></td>
									<td id="oneweekview_sunday_allday" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, 'allday');"></td>
								</tr>
								<tr>
									<td class="calendar_time">00:00</td>
									<td id="oneweekview_monday_0" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '0');"></td>
									<td id="oneweekview_tuesday_0" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '0');"></td>
									<td id="oneweekview_wednesday_0" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '0');"></td>
									<td id="oneweekview_thursday_0" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '0');"></td>
									<td id="oneweekview_friday_0" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '0');"></td>
									<td id="oneweekview_saturday_0" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '0');"></td>
									<td id="oneweekview_sunday_0" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, '0');"></td>
								</tr>
								<tr>
									<td class="calendar_time">01:00</td>
									<td id="oneweekview_monday_1" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '1');"></td>
									<td id="oneweekview_tuesday_1" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '1');"></td>
									<td id="oneweekview_wednesday_1" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '1');"></td>
									<td id="oneweekview_thursday_1" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '1');"></td>
									<td id="oneweekview_friday_1" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '1');"></td>
									<td id="oneweekview_saturday_1" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '1');"></td>
									<td id="oneweekview_sunday_1" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, '1');"></td>
								</tr>
								<tr>
									<td class="calendar_time">02:00</td>
									<td id="oneweekview_monday_2" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '2');"></td>
									<td id="oneweekview_tuesday_2" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '2');"></td>
									<td id="oneweekview_wednesday_2" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '2');"></td>
									<td id="oneweekview_thursday_2" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '2');"></td>
									<td id="oneweekview_friday_2" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '2');"></td>
									<td id="oneweekview_saturday_2" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '2');"></td>
									<td id="oneweekview_sunday_2" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, '2');"></td>
								</tr>
								<tr>
									<td class="calendar_time">03:00</td>
									<td id="oneweekview_monday_3" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '3');"></td>
									<td id="oneweekview_tuesday_3" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '3');"></td>
									<td id="oneweekview_wednesday_3" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '3');"></td>
									<td id="oneweekview_thursday_3" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '3');"></td>
									<td id="oneweekview_friday_3" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '3');"></td>
									<td id="oneweekview_saturday_3" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '3');"></td>
									<td id="oneweekview_sunday_3" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, '3');"></td>
								</tr>
								<tr>
									<td class="calendar_time">04:00</td>
									<td id="oneweekview_monday_4" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '4');"></td>
									<td id="oneweekview_tuesday_4" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '4');"></td>
									<td id="oneweekview_wednesday_4" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '4');"></td>
									<td id="oneweekview_thursday_4" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '4');"></td>
									<td id="oneweekview_friday_4" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '4');"></td>
									<td id="oneweekview_saturday_4" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '4');"></td>
									<td id="oneweekview_sunday_4" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, '4');"></td>
								</tr>
								<tr>
									<td class="calendar_time">05:00</td>
									<td id="oneweekview_monday_5" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '5');"></td>
									<td id="oneweekview_tuesday_5" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '5');"></td>
									<td id="oneweekview_wednesday_5" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '5');"></td>
									<td id="oneweekview_thursday_5" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '5');"></td>
									<td id="oneweekview_friday_5" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '5');"></td>
									<td id="oneweekview_saturday_5" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '5');"></td>
									<td id="oneweekview_sunday_5" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, '5');"></td>
								</tr>
								<tr>
									<td class="calendar_time">06:00</td>
									<td id="oneweekview_monday_6" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '6');"></td>
									<td id="oneweekview_tuesday_6" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '6');"></td>
									<td id="oneweekview_wednesday_6" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '6');"></td>
									<td id="oneweekview_thursday_6" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '6');"></td>
									<td id="oneweekview_friday_6" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '6');"></td>
									<td id="oneweekview_saturday_6" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '6');"></td>
									<td id="oneweekview_sunday_6" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, '6');"></td>
								</tr>
								<tr>
									<td class="calendar_time">07:00</td>
									<td id="oneweekview_monday_7" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '7');"></td>
									<td id="oneweekview_tuesday_7" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '7');"></td>
									<td id="oneweekview_wednesday_7" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '7');"></td>
									<td id="oneweekview_thursday_7" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '7');"></td>
									<td id="oneweekview_friday_7" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '7');"></td>
									<td id="oneweekview_saturday_7" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '7');"></td>
									<td id="oneweekview_sunday_7" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, '7');"></td>
								</tr>
								<tr>
									<td class="calendar_time">08:00</td>
									<td id="oneweekview_monday_8" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '8');"></td>
									<td id="oneweekview_tuesday_8" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '8');"></td>
									<td id="oneweekview_wednesday_8" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '8');"></td>
									<td id="oneweekview_thursday_8" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '8');"></td>
									<td id="oneweekview_friday_8" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '8');"></td>
									<td id="oneweekview_saturday_8" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '8');"></td>
									<td id="oneweekview_sunday_8" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, '8');"></td>
								</tr>
								<tr>
									<td class="calendar_time">09:00</td>
									<td id="oneweekview_monday_9" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '9');"></td>
									<td id="oneweekview_tuesday_9" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '9');"></td>
									<td id="oneweekview_wednesday_9" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '9');"></td>
									<td id="oneweekview_thursday_9" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '9');"></td>
									<td id="oneweekview_friday_9" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '9');"></td>
									<td id="oneweekview_saturday_9" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '9');"></td>
									<td id="oneweekview_sunday_9" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, '9');"></td>
								</tr>
								<tr>
									<td class="calendar_time">10:00</td>
									<td id="oneweekview_monday_10" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '10');"></td>
									<td id="oneweekview_tuesday_10" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '10');"></td>
									<td id="oneweekview_wednesday_10" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '10');"></td>
									<td id="oneweekview_thursday_10" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '10');"></td>
									<td id="oneweekview_friday_10" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '10');"></td>
									<td id="oneweekview_saturday_10" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '10');"></td>
									<td id="oneweekview_sunday_10" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, '10');"></td>
								</tr>
								<tr>
									<td class="calendar_time">11:00</td>
									<td id="oneweekview_monday_11" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '11');"></td>
									<td id="oneweekview_tuesday_11" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '11');"></td>
									<td id="oneweekview_wednesday_11" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '11');"></td>
									<td id="oneweekview_thursday_11" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '11');"></td>
									<td id="oneweekview_friday_11" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '11');"></td>
									<td id="oneweekview_saturday_11" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '11');"></td>
									<td id="oneweekview_sunday_11" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, '11');"></td>
								</tr>
								<tr>
									<td class="calendar_time">12:00</td>
									<td id="oneweekview_monday_12" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '12');"></td>
									<td id="oneweekview_tuesday_12" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '12');"></td>
									<td id="oneweekview_wednesday_12" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '12');"></td>
									<td id="oneweekview_thursday_12" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '12');"></td>
									<td id="oneweekview_friday_12" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '12');"></td>
									<td id="oneweekview_saturday_12" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '12');"></td>
									<td id="oneweekview_sunday_12" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, '12');"></td>
								</tr>
								<tr>
									<td class="calendar_time">13:00</td>
									<td id="oneweekview_monday_13" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '13');"></td>
									<td id="oneweekview_tuesday_13" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '13');"></td>
									<td id="oneweekview_wednesday_13" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '13');"></td>
									<td id="oneweekview_thursday_13" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '13');"></td>
									<td id="oneweekview_friday_13" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '13');"></td>
									<td id="oneweekview_saturday_13" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '13');"></td>
									<td id="oneweekview_sunday_13" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, '13');"></td>
								</tr>
								<tr>
									<td class="calendar_time">14:00</td>
									<td id="oneweekview_monday_14" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '14');"></td>
									<td id="oneweekview_tuesday_14" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '14');"></td>
									<td id="oneweekview_wednesday_14" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '14');"></td>
									<td id="oneweekview_thursday_14" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '14');"></td>
									<td id="oneweekview_friday_14" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '14');"></td>
									<td id="oneweekview_saturday_14" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '14');"></td>
									<td id="oneweekview_sunday_14" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, '14');"></td>
								</tr>
								<tr>
									<td class="calendar_time">15:00</td>
									<td id="oneweekview_monday_15" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '15');"></td>
									<td id="oneweekview_tuesday_15" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '15');"></td>
									<td id="oneweekview_wednesday_15" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '15');"></td>
									<td id="oneweekview_thursday_15" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '15');"></td>
									<td id="oneweekview_friday_15" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '15');"></td>
									<td id="oneweekview_saturday_15" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '15');"></td>
									<td id="oneweekview_sunday_15" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, '15');"></td>
								</tr>
								<tr>
									<td class="calendar_time">16:00</td>
									<td id="oneweekview_monday_16" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '16');"></td>
									<td id="oneweekview_tuesday_16" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '16');"></td>
									<td id="oneweekview_wednesday_16" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '16');"></td>
									<td id="oneweekview_thursday_16" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '16');"></td>
									<td id="oneweekview_friday_16" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '16');"></td>
									<td id="oneweekview_saturday_16" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '16');"></td>
									<td id="oneweekview_sunday_16" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, '16);"></td>
								</tr>
								<tr>
									<td class="calendar_time">17:00</td>
									<td id="oneweekview_monday_17" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '17');"></td>
									<td id="oneweekview_tuesday_17" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '17');"></td>
									<td id="oneweekview_wednesday_17" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '17');"></td>
									<td id="oneweekview_thursday_17" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '17');"></td>
									<td id="oneweekview_friday_17" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '17');"></td>
									<td id="oneweekview_saturday_17" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '17');"></td>
									<td id="oneweekview_sunday_17" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, '17');"></td>
								</tr>
								<tr>
									<td class="calendar_time">18:00</td>
									<td id="oneweekview_monday_18" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '18');"></td>
									<td id="oneweekview_tuesday_18" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '18');"></td>
									<td id="oneweekview_wednesday_18" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '18');"></td>
									<td id="oneweekview_thursday_18" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '18');"></td>
									<td id="oneweekview_friday_18" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '18');"></td>
									<td id="oneweekview_saturday_18" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '18');"></td>
									<td id="oneweekview_sunday_18" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, '18');"></td>
								</tr>
								<tr>
									<td class="calendar_time">19:00</td>
									<td id="oneweekview_monday_19" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '19');"></td>
									<td id="oneweekview_tuesday_19" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '19');"></td>
									<td id="oneweekview_wednesday_19" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '19');"></td>
									<td id="oneweekview_thursday_19" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '19');"></td>
									<td id="oneweekview_friday_19" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '19');"</td>
									<td id="oneweekview_saturday_19" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '19');"></td>
									<td id="oneweekview_sunday_19" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, '19');"></td>
								</tr>
								<tr>
									<td class="calendar_time">20:00</td>
									<td id="oneweekview_monday_20" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '20');"></td>
									<td id="oneweekview_tuesday_20" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '20');"></td>
									<td id="oneweekview_wednesday_20" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '20');"></td>
									<td id="oneweekview_thursday_20" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '20');"></td>
									<td id="oneweekview_friday_20" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '20');"></td>
									<td id="oneweekview_saturday_20" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '20');"></td>
									<td id="oneweekview_sunday_20" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">21:00</td>
									<td id="oneweekview_monday_21" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '21');"></td>
									<td id="oneweekview_tuesday_21" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '21');"></td>
									<td id="oneweekview_wednesday_21" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '21');"></td>
									<td id="oneweekview_thursday_21" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '21');"></td>
									<td id="oneweekview_friday_21" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '21');"></td>
									<td id="oneweekview_saturday_21" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '21');"></td>
									<td id="oneweekview_sunday_21" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, '21');"></td>
								</tr>
								<tr>
									<td class="calendar_time">22:00</td>
									<td id="oneweekview_monday_22" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '22');"></td>
									<td id="oneweekview_tuesday_22" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '22');"></td>
									<td id="oneweekview_wednesday_22" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '22');"></td>
									<td id="oneweekview_thursday_22" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '22');"></td>
									<td id="oneweekview_friday_22" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '22');"></td>
									<td id="oneweekview_saturday_22" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '22');"></td>
									<td id="oneweekview_sunday_22" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, '22');"></td>
								</tr>
								<tr>
									<td class="calendar_time">23:00</td>
									<td id="oneweekview_monday_23" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_monday').title, '23');"></td>
									<td id="oneweekview_tuesday_23" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_tuesday').title, '23');"></td>
									<td id="oneweekview_wednesday_23" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_wednesday').title, '23');"></td>
									<td id="oneweekview_thursday_23" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_thursday').title, '23');"></td>
									<td id="oneweekview_friday_23" class="calendar_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_friday').title, '23');"></td>
									<td id="oneweekview_saturday_23" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_saturday').title, '23');"></td>
									<td id="oneweekview_sunday_23" class="weekend_row" onclick="oc_cal_newevent(document.getElementById('oneweekview_sunday').title, '23');"></td>
								</tr>
							</tbody>
						</table>
					</div>
					<div id="fourweeksview">
						<table>
							<thead>
								<tr>
									<th id="fourweeksview_calw_label" class="calendar_row"><?php echo $l -> t("CW");?></th>
									<th id="fourweeksview_monday" class="calendar_row"><?php echo $l -> t("Monday");?></th>
									<th id="fourweeksview_tuesday" class="calendar_row"><?php echo $l -> t("Tuesday");?></th>
									<th id="fourweeksview_wednesday" class="calendar_row"><?php echo $l -> t("Wednesday");?></th>
									<th id="fourweeksview_thursday" class="calendar_row"><?php echo $l -> t("Thursday");?></th>
									<th id="fourweeksview_friday" class="calendar_row"><?php echo $l -> t("Friday");?></th>
									<th id="fourweeksview_saturday" class="weekend_thead"><?php echo $l -> t("Saturday");?></th>
									<th id="fourweeksview_sunday" class="weekend_thead"><?php echo $l -> t("Sunday");?></th>
								</tr>
							</thead>
							<tbody>
								<tr id="fourweeksview_week_1">
									<td id="fourweeksview_calw1"></td>
									<td id="fourweeksview_monday_1" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_monday_1">
									</div>
									<div class="events" id="events_fourweeksview_monday_1" onclick="oc_cal_newevent(document.getElementById('fourweeksview_monday_1').title)">
									</div>
									</td>
									<td id="fourweeksview_tuesday_1" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_tuesday_1">
									</div>
									<div class="events" id="events_fourweeksview_tuesday_1" onclick="oc_cal_newevent(document.getElementById('fourweeksview_tuesday_1').title)">
									</div>
									</td>
									<td id="fourweeksview_wednesday_1" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_wednesday_1">
									</div>
									<div class="events" id="events_fourweeksview_wednesday_1" onclick="oc_cal_newevent(document.getElementById('fourweeksview_wednesday_1').title)">
									</div>
									</td>
									<td id="fourweeksview_thursday_1" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_thursday_1">
									</div>
									<div class="events" id="events_fourweeksview_thursday_1" onclick="oc_cal_newevent(document.getElementById('fourweeksview_thursday_1').title)">
									</div>
									</td>
									<td id="fourweeksview_friday_1" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_friday_1">
									</div>
									<div class="events" id="events_fourweeksview_friday_1" onclick="oc_cal_newevent(document.getElementById('fourweeksview_friday_1').title)">
									</div>
									</td>
									<td id="fourweeksview_saturday_1" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_saturday_1">
									</div>
									<div class="weekend" id="events_fourweeksview_saturday_1" onclick="oc_cal_newevent(document.getElementById('fourweeksview_saturday_1').title)">
									</div>
									</td>
									<td id="fourweeksview_sunday_1" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_sunday_1">
									</div>
									<div class="weekend" id="events_fourweeksview_sunday_1" onclick="oc_cal_newevent(document.getElementById('fourweeksview_sunday_1').title)">
									</div>
									</td>
								</tr>
								<tr id="fourweeksview_week_2">
									<td id="fourweeksview_calw2"></td>
									<td id="fourweeksview_monday_2" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_monday_2">
									</div>
									<div class="events" id="events_fourweeksview_monday_2" onclick="oc_cal_newevent(document.getElementById('fourweeksview_monday_2').title)">
									</div>
									</td>
									<td id="fourweeksview_tuesday_2" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_tuesday_2">
									</div>
									<div class="events" id="events_fourweeksview_tuesday_2" onclick="oc_cal_newevent(document.getElementById('fourweeksview_tuesday_2').title)">
									</div>
									</td>
									<td id="fourweeksview_wednesday_2" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_wednesday_2">
									</div>
									<div class="events" id="events_fourweeksview_wednesday_2" onclick="oc_cal_newevent(document.getElementById('fourweeksview_wednesday_2').title)">
									</div>
									</td>
									<td id="fourweeksview_thursday_2" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_thursday_2">
									</div>
									<div class="events" id="events_fourweeksview_thursday_2" onclick="oc_cal_newevent(document.getElementById('fourweeksview_thursday_2').title)">
									</div>
									</td>
									<td id="fourweeksview_friday_2" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_friday_2">
									</div>
									<div class="events" id="events_fourweeksview_friday_2" onclick="oc_cal_newevent(document.getElementById('fourweeksview_friday_2').title)">
									</div>
									</td>
									<td id="fourweeksview_saturday_2" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_saturday_2">
									</div>
									<div class="weekend" id="events_fourweeksview_saturday_2" onclick="oc_cal_newevent(document.getElementById('fourweeksview_saturday_2').title)">
									</div>
									</td>
									<td id="fourweeksview_sunday_2" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_sunday_2">
									</div>
									<div class="weekend" id="events_fourweeksview_sunday_2" onclick="oc_cal_newevent(document.getElementById('fourweeksview_sunday_2').title)">
									</div>
									</td>
								</tr>
								<tr id="fourweeksview_week_3">
									<td id="fourweeksview_calw3"></td>
									<td id="fourweeksview_monday_3" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_monday_3">
									</div>
									<div class="events" id="events_fourweeksview_monday_3" onclick="oc_cal_newevent(document.getElementById('fourweeksview_monday_3').title)">
									</div>
									</td>
									<td id="fourweeksview_tuesday_3" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_tuesday_3">
									</div>
									<div class="events" id="events_fourweeksview_tuesday_3" onclick="oc_cal_newevent(document.getElementById('fourweeksview_tuesday_3').title)">
									</div>
									</td>
									<td id="fourweeksview_wednesday_3" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_wednesday_3">
									</div>
									<div class="events" id="events_fourweeksview_wednesday_3" onclick="oc_cal_newevent(document.getElementById('fourweeksview_wednesday_3').title)">
									</div>
									</td>
									<td id="fourweeksview_thursday_3" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_thursday_3">
									</div>
									<div class="events" id="events_fourweeksview_thursday_3" onclick="oc_cal_newevent(document.getElementById('fourweeksview_thursday_3').title)">
									</div>
									</td>
									<td id="fourweeksview_friday_3" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_friday_3">
									</div>
									<div class="events" id="events_fourweeksview_friday_3" onclick="oc_cal_newevent(document.getElementById('fourweeksview_friday_3').title)">
									</div>
									</td>
									<td id="fourweeksview_saturday_3" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_saturday_3">
									</div>
									<div class="weekend" id="events_fourweeksview_saturday_3" onclick="oc_cal_newevent(document.getElementById('fourweeksview_saturday_3').title)">
									</div>
									</td>
									<td id="fourweeksview_sunday_3" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_sunday_3">
									</div>
									<div class="weekend" id="events_fourweeksview_sunday_3" onclick="oc_cal_newevent(document.getElementById('fourweeksview_sunday_3').title)">
									</div>
									</td>
								</tr>
								<tr id="fourweeksview_week_4">
									<td id="fourweeksview_calw4"></td>
									<td id="fourweeksview_monday_4" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_monday_4">
									</div>
									<div class="events" id="events_fourweeksview_monday_4" onclick="oc_cal_newevent(document.getElementById('fourweeksview_monday_4').title)">
									</div>
									</td>
									<td id="fourweeksview_tuesday_4" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_tuesday_4">
									</div>
									<div class="events" id="events_fourweeksview_tuesday_4" onclick="oc_cal_newevent(document.getElementById('fourweeksview_tuesday_4').title)">
									</div>
									</td>
									<td id="fourweeksview_wednesday_4" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_wednesday_4">
									</div>
									<div class="events" id="events_fourweeksview_wednesday_4" onclick="oc_cal_newevent(document.getElementById('fourweeksview_wednesday_4').title)">
									</div>
									</td>
									<td id="fourweeksview_thursday_4" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_thursday_4">
									</div>
									<div class="events" id="events_fourweeksview_thursday_4" onclick="oc_cal_newevent(document.getElementById('fourweeksview_thursday_4').title)">
									</div>
									</td>
									<td id="fourweeksview_friday_4" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_friday_4">
									</div>
									<div class="events" id="events_fourweeksview_friday_4" onclick="oc_cal_newevent(document.getElementById('fourweeksview_friday_4').title)">
									</div>
									</td>
									<td id="fourweeksview_saturday_4" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_saturday_4">
									</div>
									<div class="weekend" id="events_fourweeksview_saturday_4" onclick="oc_cal_newevent(document.getElementById('fourweeksview_saturday_4').title)">
									</div>
									</td>
									<td id="fourweeksview_sunday_4" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_sunday_4">
									</div>
									<div class="weekend" id="events_fourweeksview_sunday_4"  onclick="oc_cal_newevent(document.getElementById('fourweeksview_sunday_4').title)">
									</div>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
					<div id="onemonthview">
						<table>
							<thead>
								<tr>
									<th id="onemonthview_monday" class="calendar_row"><?php echo $l -> t("Monday");?></th>
									<th id="onemonthview_tuesday" class="calendar_row"><?php echo $l -> t("Tuesday");?></th>
									<th id="onemonthview_wednesday" class="calendar_row"><?php echo $l -> t("Wednesday");?></th>
									<th id="onemonthview_thursday" class="calendar_row"><?php echo $l -> t("Thursday");?></th>
									<th id="onemonthview_friday" class="calendar_row"><?php echo $l -> t("Friday");?></th>
									<th id="onemonthview_saturday" class="weekend_thead"><?php echo $l -> t("Saturday");?></th>
									<th id="onemonthview_sunday" class="weekend_thead"><?php echo $l -> t("Sunday");?></th>
								</tr>
							</thead>
							<tbody>
								<tr id="onemonthview_week_1">
									<td id="onemonthview_monday_1" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_monday_1">
									</div>
									<div class="events" id="events_onemonthview_monday_1" onclick="oc_cal_newevent(document.getElementById('onemonthview_monday_1').title)">
									</div>
									</td>
									<td id="onemonthview_tuesday_1" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_tuesday_1">
									</div>
									<div class="events" id="events_onemonthview_tuesday_1" onclick="oc_cal_newevent(document.getElementById('onemonthview_tuesday_1').title)">
									</div>
									</td>
									<td id="onemonthview_wednesday_1" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_wednesday_1">
									</div>
									<div class="events" id="events_onemonthview_wednesday_1" onclick="oc_cal_newevent(document.getElementById('onemonthview_wednesday_1').title)">
									</div>
									</td>
									<td id="onemonthview_thursday_1" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_thursday_1">
									</div>
									<div class="events" id="events_onemonthview_thursday_1" onclick="oc_cal_newevent(document.getElementById('onemonthview_thursday_1').title)">
									</div>
									</td>
									<td id="onemonthview_friday_1" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_friday_1">
									</div>
									<div class="events" id="events_onemonthview_friday_1" onclick="oc_cal_newevent(document.getElementById('onemonthview_friday_1').title)">
									</div>
									</td>
									<td id="onemonthview_saturday_1" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_saturday_1">
									</div>
									<div class="weekend" id="events_onemonthview_saturday_1" onclick="oc_cal_newevent(document.getElementById('onemonthview_saturday_1').title)">
									</div>
									</td>
									<td id="onemonthview_sunday_1" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_sunday_1">
									</div>
									<div class="weekend" id="events_onemonthview_sunday_1" onclick="oc_cal_newevent(document.getElementById('onemonthview_sunday_1').title)">
									</div>
									</td>
								</tr>
								<tr id="onemonthview_week_2">
									<td id="onemonthview_monday_2" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_monday_2">
									</div>
									<div class="events" id="events_onemonthview_monday_2" onclick="oc_cal_newevent(document.getElementById('onemonthview_monday_2').title)">
									</div>
									</td>
									<td id="onemonthview_tuesday_2" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_tuesday_2">
									</div>
									<div class="events" id="events_onemonthview_tuesday_2" onclick="oc_cal_newevent(document.getElementById('onemonthview_tuesday_2').title)">
									</div>
									</td>
									<td id="onemonthview_wednesday_2" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_wednesday_2">
									</div>
									<div class="events" id="events_onemonthview_wednesday_2" onclick="oc_cal_newevent(document.getElementById('onemonthview_wednesday_2').title)">
									</div>
									</td>
									<td id="onemonthview_thursday_2" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_thursday_2">
									</div>
									<div class="events" id="events_onemonthview_thursday_2" onclick="oc_cal_newevent(document.getElementById('onemonthview_thursday_2').title)">
									</div>
									</td>
									<td id="onemonthview_friday_2" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_friday_2">
									</div>
									<div class="events" id="events_onemonthview_friday_2" onclick="oc_cal_newevent(document.getElementById('onemonthview_friday_2').title)">
									</div>
									</td>
									<td id="onemonthview_saturday_2" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_saturday_2">
									</div>
									<div class="weekend" id="events_onemonthview_saturday_2" onclick="oc_cal_newevent(document.getElementById('onemonthview_saturday_2').title)">
									</div>
									</td>
									<td id="onemonthview_sunday_2" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_sunday_2">
									</div>
									<div class="weekend" id="events_onemonthview_sunday_2" onclick="oc_cal_newevent(document.getElementById('onemonthview_sunday_2').title)">
									</div>
									</td>
								</tr>
								<tr id="onemonthview_week_3">
									<td id="onemonthview_monday_3" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_monday_3">
									</div>
									<div class="events" id="events_onemonthview_monday_3" onclick="oc_cal_newevent(document.getElementById('onemonthview_monday_3').title)">
									</div>
									</td>
									<td id="onemonthview_tuesday_3" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_tuesday_3">
									</div>
									<div class="events" id="events_onemonthview_tuesday_3" onclick="oc_cal_newevent(document.getElementById('onemonthview_tuesday_3').title)">
									</div>
									</td>
									<td id="onemonthview_wednesday_3" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_wednesday_3">
									</div>
									<div class="events" id="events_onemonthview_wednesday_3" onclick="oc_cal_newevent(document.getElementById('onemonthview_wednesday_3').title)">
									</div>
									</td>
									<td id="onemonthview_thursday_3" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_thursday_3">
									</div>
									<div class="events" id="events_onemonthview_thursday_3" onclick="oc_cal_newevent(document.getElementById('onemonthview_thursday_3').title)">
									</div>
									</td>
									<td id="onemonthview_friday_3" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_friday_3">
									</div>
									<div class="events" id="events_onemonthview_friday_3" onclick="oc_cal_newevent(document.getElementById('onemonthview_friday_3').title)">
									</div>
									</td>
									<td id="onemonthview_saturday_3" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_saturday_3">
									</div>
									<div class="weekend" id="events_onemonthview_saturday_3" onclick="oc_cal_newevent(document.getElementById('onemonthview_saturday_3').title)">
									</div>
									</td>
									<td id="onemonthview_sunday_3" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_sunday_3">
									</div>
									<div class="weekend" id="events_onemonthview_sunday_3" onclick="oc_cal_newevent(document.getElementById('onemonthview_sunday_3').title)">
									</div>
									</td>
								</tr>
								<tr id="onemonthview_week_4">
									<td id="onemonthview_monday_4" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_monday_4">
									</div>
									<div class="events" id="events_onemonthview_monday_4" onclick="oc_cal_newevent(document.getElementById('onemonthview_monday_4').title)">
									</div>
									</td>
									<td id="onemonthview_tuesday_4" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_tuesday_4">
									</div>
									<div class="events" id="events_onemonthview_tuesday_4" onclick="oc_cal_newevent(document.getElementById('onemonthview_tuesday_4').title)">
									</div>
									</td>
									<td id="onemonthview_wednesday_4" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_wednesday_4">
									</div>
									<div class="events" id="events_onemonthview_wednesday_4" onclick="oc_cal_newevent(document.getElementById('onemonthview_wednesday_4').title)">
									</div>
									</td>
									<td id="onemonthview_thursday_4" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_thursday_4">
									</div>
									<div class="events" id="events_onemonthview_thursday_4" onclick="oc_cal_newevent(document.getElementById('onemonthview_thursday_4').title)">
									</div>
									</td>
									<td id="onemonthview_friday_4" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_friday_4">
									</div>
									<div class="events" id="events_onemonthview_friday_4" onclick="oc_cal_newevent(document.getElementById('onemonthview_friday_4').title)">
									</div>
									</td>
									<td id="onemonthview_saturday_4" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_saturday_4">
									</div>
									<div class="weekend" id="events_onemonthview_saturday_4" onclick="oc_cal_newevent(document.getElementById('onemonthview_saturday_4').title)">
									</div>
									</td>
									<td id="onemonthview_sunday_4" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_sunday_4">
									</div>
									<div class="weekend" id="events_onemonthview_sunday_4"  onclick="oc_cal_newevent(document.getElementById('onemonthview_sunday_4').title)">
									</div>
									</td>
								</tr>
								<tr id="onemonthview_week_5">
									<td id="onemonthview_monday_5" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_monday_5">
									</div>
									<div class="events" id="events_onemonthview_monday_5" onclick="oc_cal_newevent(document.getElementById('onemonthview_monday_5').title)">
									</div>
									</td>
									<td id="onemonthview_tuesday_5" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_tuesday_5">
									</div>
									<div class="events" id="events_onemonthview_tuesday_5" onclick="oc_cal_newevent(document.getElementById('onemonthview_tuesday_5').title)">
									</div>
									</td>
									<td id="onemonthview_wednesday_5" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_wednesday_5">
									</div>
									<div class="events" id="events_onemonthview_wednesday_5" onclick="oc_cal_newevent(document.getElementById('onemonthview_wednesday_5').title)">
									</div>
									</td>
									<td id="onemonthview_thursday_5" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_thursday_5">
									</div>
									<div class="events" id="events_onemonthview_thursday_5" onclick="oc_cal_newevent(document.getElementById('onemonthview_thursday_5').title)">
									</div>
									</td>
									<td id="onemonthview_friday_5" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_friday_5">
									</div>
									<div class="events" id="events_onemonthview_friday_5" onclick="oc_cal_newevent(document.getElementById('onemonthview_friday_5').title)">
									</div>
									</td>
									<td id="onemonthview_saturday_5" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_saturday_5">
									</div>
									<div class="weekend" id="events_onemonthview_saturday_5" onclick="oc_cal_newevent(document.getElementById('onemonthview_saturday_5').title)">
									</div>
									</td>
									<td id="onemonthview_sunday_5" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_sunday_5">
									</div>
									<div class="weekend" id="events_onemonthview_sunday_5" onclick="oc_cal_newevent(document.getElementById('onemonthview_sunday_5').title)">
									</div>
									</td>
								</tr>
								<tr id="onemonthview_week_6">
									<td id="onemonthview_monday_6" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_monday_6">
									</div>
									<div class="events" id="events_onemonthview_monday_6" onclick="oc_cal_newevent(document.getElementById('onemonthview_monday_6').title)">
									</div>
									</td>
									<td id="onemonthview_tuesday_6" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_tuesday_6">
									</div>
									<div class="events" id="events_onemonthview_tuesday_6" onclick="oc_cal_newevent(document.getElementById('onemonthview_tuesday_6').title)">
									</div>
									</td>
									<td id="onemonthview_wednesday_6" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_wednesday_6">
									</div>
									<div class="events" id="events_onemonthview_wednesday_6" onclick="oc_cal_newevent(document.getElementById('onemonthview_wednesday_6').title)">
									</div>
									</td>
									<td id="onemonthview_thursday_6" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_thursday_6">
									</div>
									<div class="events" id="events_onemonthview_thursday_6" onclick="oc_cal_newevent(document.getElementById('onemonthview_thursday_6').title)">
									</div>
									</td>
									<td id="onemonthview_friday_6" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_friday_6">
									</div>
									<div class="events" id="events_onemonthview_friday_6" onclick="oc_cal_newevent(document.getElementById('onemonthview_friday_6').title)">
									</div>
									</td>
									<td id="onemonthview_saturday_6" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_saturday_6">
									</div>
									<div class="weekend" id="events_onemonthview_saturday_6" onclick="oc_cal_newevent(document.getElementById('onemonthview_saturday_6').title)">
									</div>
									</td>
									<td id="onemonthview_sunday_6" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_sunday_6">
									</div>
									<div class="weekend" id="events_onemonthview_sunday_6" onclick="oc_cal_newevent(document.getElementById('onemonthview_sunday_6').title)">
									</div>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
					<div id="listview">
						
					</div>
				</div>
				<!-- Dialogs -->
				<div id="dialog_holder"></div>
				<div id="parsingfail_dialog" title="Parsing Fail">
					<?php echo $l->t("There was a fail, while parsing the file."); ?>
				</div>
				<!-- End of Dialogs -->
				<script type="text/javascript">
				//sending ajax request on every change view and use last view as default on the next
				<?php
				echo "var view = \"" . OC_Preferences::getValue(OC_USER::getUser(), "calendar", "currentview", "onemonthview") . "\";";
				 
				?>
				Calendar.UI.setCurrentView(view);
				document.getElementById(oc_cal_currentview).style.display = "block";
				document.getElementById(oc_cal_currentview + "_radio").style.color = "#0098E4";
				function oc_cal_change_view(view){
					document.getElementById(oc_cal_currentview).style.display = "none";
					document.getElementById(oc_cal_currentview + "_radio").style.color = "#000000";
					document.getElementById(view).style.display = "block";
					Calendar.UI.setCurrentView(view);
					document.getElementById(oc_cal_currentview + "_radio").style.color = "#0098E4";
					Calendar.UI.updateView();
				}
				document.getElementById("onedayview_radio").value = onedayview_radio;
				document.getElementById("oneweekview_radio").value = oneweekview_radio;
				document.getElementById("fourweeksview_radio").value = fourweeksview_radio;
				document.getElementById("onemonthview_radio").value = onemonthview_radio;
				document.getElementById("listview_radio").value = listview_radio;
				document.getElementById("today_input").value = today_button_value;
				document.getElementById("choosecalendar_input").value = choosecalendar_value;
				//document.getElementById("download_input").src = oc_webroot + "/core/img/actions/download.svg";
				</script>
				<script type="text/javascript" id="js_events">
				<?php
				//
				
				
				
				
				
				
				?>
				</script>
