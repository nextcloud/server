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
								<input class="button" type="submit" id="today_input" value="Today" onclick="oc_cal_switch2today();"/>
								<input type="radio" id="choosecalendar_input" checked="checked"/>

<table data-groups="admin"> 
			<tr data-uid="root"> 
			<td class="select"><input type="checkbox"></input></td> 
			<td class="name">root</td> 
			<td class="groups"> 
				<select data-username="root" data-user-groups="admin" data-placeholder="groups" title="Gruppen" multiple="multiple"> 
											<option value="admin">admin</option> 
									</select> 
			</td> 
			<td class="remove"> 
							</td> 
		</tr> 
	</table> 

								<!--
								<label for="choosecalendar_input" onclick="oc_cal_choosecalendar_dialog();">
									Choose your Calendar
								</label>-->
							</div>
						</form>
						<form>
							<div id="datecontrol">
								<input type="button" value="&lt;" id="datecontrol_left" onclick="oc_cal_update_view('', 'backward');"/>
								<input id="datecontrol_date" type="button" value=""/>
								<input type="button" value="&gt;" id="datecontrol_left" onclick="oc_cal_update_view('', 'forward');"/>
							</div>
						</form>
					</div>
				</div>
				<div id="calendar_holder">
					<div id="onedayview">
						<table>
							<thead>
								<tr>
									<th class="calendar_time">Time</th>
									<th id="onedayview_today" class="calendar_row"></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td class="calendar_time">All day</td>
									<td id="onedayview_wholeday" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">00:00</td>
									<td id="onedayview_0" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">01:00</td>
									<td id="onedayview_1" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">02:00</td>
									<td id="onedayview_2" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">03:00</td>
									<td id="onedayview_3" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">04:00</td>
									<td id="onedayview_4" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">05:00</td>
									<td id="onedayview_5" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">06:00</td>
									<td id="onedayview_6" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">07:00</td>
									<td id="onedayview_7" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">08:00</td>
									<td id="onedayview_8" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">09:00</td>
									<td id="onedayview_9" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">10:00</td>
									<td id="onedayview_10" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">11:00</td>
									<td id="onedayview_11" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">12:00</td>
									<td id="onedayview_12" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">13:00</td>
									<td id="onedayview_13" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">14:00</td>
									<td id="onedayview_14" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">15:00</td>
									<td id="onedayview_15" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">16:00</td>
									<td id="onedayview_16" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">17:00</td>
									<td id="onedayview_17" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">18:00</td>
									<td id="onedayview_18" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">19:00</td>
									<td id="onedayview_19" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">20:00</td>
									<td id="onedayview_20" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">21:00</td>
									<td id="onedayview_21" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">22:00</td>
									<td id="onedayview_22" class="calendar_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">23:00</td>
									<td id="onedayview_23" class="calendar_row"></td>
								</tr>
							</tbody>
						</table>
					</div>
					<div id="oneweekview">
						<table>
							<thead>
								<tr>
									<th class="calendar_time">Time</th>
									<th id="oneweekview_monday" class="calendar_row"></th>
									<th id="oneweekview_tuesday" class="calendar_row"></th>
									<th id="oneweekview_wednesday" class="calendar_row"></th>
									<th id="oneweekview_thursday" class="calendar_row"></th>
									<th id="oneweekview_friday" class="calendar_row"></th>
									<th id="oneweekview_saturday" class="weekend_thead"></th>
									<th id="oneweekview_sunday" class="weekend_thead"></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td class="calendar_time">All day</td>
									<td id="oneweekview_monday_allday" class="calendar_row"></td>
									<td id="oneweekview_tuesday_allday" class="calendar_row"></td>
									<td id="oneweekview_wednesday_allday" class="calendar_row"></td>
									<td id="oneweekview_thursday_allday" class="calendar_row"></td>
									<td id="oneweekview_friday_allday" class="calendar_row"></td>
									<td id="oneweekview_saturday_allday" class="weekend_row"></td>
									<td id="oneweekview_sunday_allday" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">00:00</td>
									<td id="oneweekview_monday_0" class="calendar_row"></td>
									<td id="oneweekview_tuesday_0" class="calendar_row"></td>
									<td id="oneweekview_wednesday_0" class="calendar_row"></td>
									<td id="oneweekview_thursday_0" class="calendar_row"></td>
									<td id="oneweekview_friday_0" class="calendar_row"></td>
									<td id="oneweekview_saturday_0" class="weekend_row"></td>
									<td id="oneweekview_sunday_0" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">01:00</td>
									<td id="oneweekview_monday_1" class="calendar_row"></td>
									<td id="oneweekview_tuesday_1" class="calendar_row"></td>
									<td id="oneweekview_wednesday_1" class="calendar_row"></td>
									<td id="oneweekview_thursday_1" class="calendar_row"></td>
									<td id="oneweekview_friday_1" class="calendar_row"></td>
									<td id="oneweekview_saturday_1" class="weekend_row"></td>
									<td id="oneweekview_sunday_1" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">02:00</td>
									<td id="oneweekview_monday_2" class="calendar_row"></td>
									<td id="oneweekview_tuesday_2" class="calendar_row"></td>
									<td id="oneweekview_wednesday_2" class="calendar_row"></td>
									<td id="oneweekview_thursday_2" class="calendar_row"></td>
									<td id="oneweekview_friday_2" class="calendar_row"></td>
									<td id="oneweekview_saturday_2" class="weekend_row"></td>
									<td id="oneweekview_sunday_2" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">03:00</td>
									<td id="oneweekview_monday_3" class="calendar_row"></td>
									<td id="oneweekview_tuesday_3" class="calendar_row"></td>
									<td id="oneweekview_wednesday_3" class="calendar_row"></td>
									<td id="oneweekview_thursday_3" class="calendar_row"></td>
									<td id="oneweekview_friday_3" class="calendar_row"></td>
									<td id="oneweekview_saturday_3" class="weekend_row"></td>
									<td id="oneweekview_sunday_3" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">04:00</td>
									<td id="oneweekview_monday_4" class="calendar_row"></td>
									<td id="oneweekview_tuesday_4" class="calendar_row"></td>
									<td id="oneweekview_wednesday_4" class="calendar_row"></td>
									<td id="oneweekview_thursday_4" class="calendar_row"></td>
									<td id="oneweekview_friday_4" class="calendar_row"></td>
									<td id="oneweekview_saturday_4" class="weekend_row"></td>
									<td id="oneweekview_sunday_4" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">05:00</td>
									<td id="oneweekview_monday_5" class="calendar_row"></td>
									<td id="oneweekview_tuesday_5" class="calendar_row"></td>
									<td id="oneweekview_wednesday_5" class="calendar_row"></td>
									<td id="oneweekview_thursday_5" class="calendar_row"></td>
									<td id="oneweekview_friday_5" class="calendar_row"></td>
									<td id="oneweekview_saturday_5" class="weekend_row"></td>
									<td id="oneweekview_sunday_5" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">06:00</td>
									<td id="oneweekview_monday_6" class="calendar_row"></td>
									<td id="oneweekview_tuesday_6" class="calendar_row"></td>
									<td id="oneweekview_wednesday_6" class="calendar_row"></td>
									<td id="oneweekview_thursday_6" class="calendar_row"></td>
									<td id="oneweekview_friday_6" class="calendar_row"></td>
									<td id="oneweekview_saturday_6" class="weekend_row"></td>
									<td id="oneweekview_sunday_6" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">07:00</td>
									<td id="oneweekview_monday_7" class="calendar_row"></td>
									<td id="oneweekview_tuesday_7" class="calendar_row"></td>
									<td id="oneweekview_wednesday_7" class="calendar_row"></td>
									<td id="oneweekview_thursday_7" class="calendar_row"></td>
									<td id="oneweekview_friday_7" class="calendar_row"></td>
									<td id="oneweekview_saturday_7" class="weekend_row"></td>
									<td id="oneweekview_sunday_7" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">08:00</td>
									<td id="oneweekview_monday_8" class="calendar_row"></td>
									<td id="oneweekview_tuesday_8" class="calendar_row"></td>
									<td id="oneweekview_wednesday_8" class="calendar_row"></td>
									<td id="oneweekview_thursday_8" class="calendar_row"></td>
									<td id="oneweekview_friday_8" class="calendar_row"></td>
									<td id="oneweekview_saturday_8" class="weekend_row"></td>
									<td id="oneweekview_sunday_8" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">09:00</td>
									<td id="oneweekview_monday_9" class="calendar_row"></td>
									<td id="oneweekview_tuesday_9" class="calendar_row"></td>
									<td id="oneweekview_wednesday_9" class="calendar_row"></td>
									<td id="oneweekview_thursday_9" class="calendar_row"></td>
									<td id="oneweekview_friday_9" class="calendar_row"></td>
									<td id="oneweekview_saturday_9" class="weekend_row"></td>
									<td id="oneweekview_sunday_9" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">10:00</td>
									<td id="oneweekview_monday_10" class="calendar_row"></td>
									<td id="oneweekview_tuesday_10" class="calendar_row"></td>
									<td id="oneweekview_wednesday_10" class="calendar_row"></td>
									<td id="oneweekview_thursday_10" class="calendar_row"></td>
									<td id="oneweekview_friday_10" class="calendar_row"></td>
									<td id="oneweekview_saturday_10" class="weekend_row"></td>
									<td id="oneweekview_sunday_10" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">11:00</td>
									<td id="oneweekview_monday_11" class="calendar_row"></td>
									<td id="oneweekview_tuesday_11" class="calendar_row"></td>
									<td id="oneweekview_wednesday_11" class="calendar_row"></td>
									<td id="oneweekview_thursday_11" class="calendar_row"></td>
									<td id="oneweekview_friday_11" class="calendar_row"></td>
									<td id="oneweekview_saturday_11" class="weekend_row"></td>
									<td id="oneweekview_sunday_11" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">12:00</td>
									<td id="oneweekview_monday_12" class="calendar_row"></td>
									<td id="oneweekview_tuesday_12" class="calendar_row"></td>
									<td id="oneweekview_wednesday_12" class="calendar_row"></td>
									<td id="oneweekview_thursday_12" class="calendar_row"></td>
									<td id="oneweekview_friday_12" class="calendar_row"></td>
									<td id="oneweekview_saturday_12" class="weekend_row"></td>
									<td id="oneweekview_sunday_12" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">13:00</td>
									<td id="oneweekview_monday_13" class="calendar_row"></td>
									<td id="oneweekview_tuesday_13" class="calendar_row"></td>
									<td id="oneweekview_wednesday_13" class="calendar_row"></td>
									<td id="oneweekview_thursday_13" class="calendar_row"></td>
									<td id="oneweekview_friday_13" class="calendar_row"></td>
									<td id="oneweekview_saturday_13" class="weekend_row"></td>
									<td id="oneweekview_sunday_13" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">14:00</td>
									<td id="oneweekview_monday_14" class="calendar_row"></td>
									<td id="oneweekview_tuesday_14" class="calendar_row"></td>
									<td id="oneweekview_wednesday_14" class="calendar_row"></td>
									<td id="oneweekview_thursday_14" class="calendar_row"></td>
									<td id="oneweekview_friday_14" class="calendar_row"></td>
									<td id="oneweekview_saturday_14" class="weekend_row"></td>
									<td id="oneweekview_sunday_14" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">15:00</td>
									<td id="oneweekview_monday_15" class="calendar_row"></td>
									<td id="oneweekview_tuesday_15" class="calendar_row"></td>
									<td id="oneweekview_wednesday_15" class="calendar_row"></td>
									<td id="oneweekview_thursday_15" class="calendar_row"></td>
									<td id="oneweekview_friday_15" class="calendar_row"></td>
									<td id="oneweekview_saturday_15" class="weekend_row"></td>
									<td id="oneweekview_sunday_15" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">16:00</td>
									<td id="oneweekview_monday_16" class="calendar_row"></td>
									<td id="oneweekview_tuesday_16" class="calendar_row"></td>
									<td id="oneweekview_wednesday_16" class="calendar_row"></td>
									<td id="oneweekview_thursday_16" class="calendar_row"></td>
									<td id="oneweekview_friday_16" class="calendar_row"></td>
									<td id="oneweekview_saturday_16" class="weekend_row"></td>
									<td id="oneweekview_sunday_16" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">17:00</td>
									<td id="oneweekview_monday_17" class="calendar_row"></td>
									<td id="oneweekview_tuesday_17" class="calendar_row"></td>
									<td id="oneweekview_wednesday_17" class="calendar_row"></td>
									<td id="oneweekview_thursday_17" class="calendar_row"></td>
									<td id="oneweekview_friday_17" class="calendar_row"></td>
									<td id="oneweekview_saturday_17" class="weekend_row"></td>
									<td id="oneweekview_sunday_17" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">18:00</td>
									<td id="oneweekview_monday_18" class="calendar_row"></td>
									<td id="oneweekview_tuesday_18" class="calendar_row"></td>
									<td id="oneweekview_wednesday_18" class="calendar_row"></td>
									<td id="oneweekview_thursday_18" class="calendar_row"></td>
									<td id="oneweekview_friday_18" class="calendar_row"></td>
									<td id="oneweekview_saturday_18" class="weekend_row"></td>
									<td id="oneweekview_sunday_18" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">19:00</td>
									<td id="oneweekview_monday_19" class="calendar_row"></td>
									<td id="oneweekview_tuesday_19" class="calendar_row"></td>
									<td id="oneweekview_wednesday_19" class="calendar_row"></td>
									<td id="oneweekview_thursday_19" class="calendar_row"></td>
									<td id="oneweekview_friday_19" class="calendar_row"></td>
									<td id="oneweekview_saturday_19" class="weekend_row"></td>
									<td id="oneweekview_sunday_19" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">20:00</td>
									<td id="oneweekview_monday_20" class="calendar_row"></td>
									<td id="oneweekview_tuesday_20" class="calendar_row"></td>
									<td id="oneweekview_wednesday_20" class="calendar_row"></td>
									<td id="oneweekview_thursday_20" class="calendar_row"></td>
									<td id="oneweekview_friday_20" class="calendar_row"></td>
									<td id="oneweekview_saturday_20" class="weekend_row"></td>
									<td id="oneweekview_sunday_20" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">21:00</td>
									<td id="oneweekview_monday_21" class="calendar_row"></td>
									<td id="oneweekview_tuesday_21" class="calendar_row"></td>
									<td id="oneweekview_wednesday_21" class="calendar_row"></td>
									<td id="oneweekview_thursday_21" class="calendar_row"></td>
									<td id="oneweekview_friday_21" class="calendar_row"></td>
									<td id="oneweekview_saturday_21" class="weekend_row"></td>
									<td id="oneweekview_sunday_21" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">22:00</td>
									<td id="oneweekview_monday_22" class="calendar_row"></td>
									<td id="oneweekview_tuesday_22" class="calendar_row"></td>
									<td id="oneweekview_wednesday_22" class="calendar_row"></td>
									<td id="oneweekview_thursday_22" class="calendar_row"></td>
									<td id="oneweekview_friday_22" class="calendar_row"></td>
									<td id="oneweekview_saturday_22" class="weekend_row"></td>
									<td id="oneweekview_sunday_22" class="weekend_row"></td>
								</tr>
								<tr>
									<td class="calendar_time">23:00</td>
									<td id="oneweekview_monday_23" class="calendar_row"></td>
									<td id="oneweekview_tuesday_23" class="calendar_row"></td>
									<td id="oneweekview_wednesday_23" class="calendar_row"></td>
									<td id="oneweekview_thursday_23" class="calendar_row"></td>
									<td id="oneweekview_friday_23" class="calendar_row"></td>
									<td id="oneweekview_saturday_23" class="weekend_row"></td>
									<td id="oneweekview_sunday_23" class="weekend_row"></td>
								</tr>
							</tbody>
						</table>
					</div>
					<div id="fourweeksview">
						<table>
							<thead>
								<tr>
									<th id="fourweeksview_calw_label" class="calendar_row">CW</th>
									<th id="fourweeksview_monday" class="calendar_row">Monday</th>
									<th id="fourweeksview_tuesday" class="calendar_row">Tuesday</th>
									<th id="fourweeksview_wednesday" class="calendar_row">Wednesday</th>
									<th id="fourweeksview_thursday" class="calendar_row">Thursday</th>
									<th id="fourweeksview_friday" class="calendar_row">Friday</th>
									<th id="fourweeksview_saturday" class="weekend_thead">Saturday</th>
									<th id="fourweeksview_sunday" class="weekend_thead">Sunday</th>
								</tr>
							</thead>
							<tbody>
								<tr id="fourweeksview_week_1">
									<td id="fourweeksview_calw1"></td>
									<td id="fourweeksview_monday_1" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_monday_1">
									</div>
									<div class="events" id="events_fourweeksview_monday_1">
									</div>
									</td>
									<td id="fourweeksview_tuesday_1" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_tuesday_1">
									</div>
									<div class="events" id="events_fourweeksview_tuesday_1">
									</div>
									</td>
									<td id="fourweeksview_wednesday_1" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_wednesday_1">
									</div>
									<div class="events" id="events_fourweeksview_wednesday_1">
									</div>
									</td>
									<td id="fourweeksview_thursday_1" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_thursday_1">
									</div>
									<div class="events" id="events_fourweeksview_thursday_1">
									</div>
									</td>
									<td id="fourweeksview_friday_1" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_friday_1">
									</div>
									<div class="events" id="events_fourweeksview_friday_1">
									</div>
									</td>
									<td id="fourweeksview_saturday_1" class="weekend">
									<div class="dateinfo" id="dateinfo_fourweeksview_saturday_1">
									</div>
									<div class="events" id="events_fourweeksview_saturday_1">
									</div>
									</td>
									<td id="fourweeksview_sunday_1" class="weekend">
									<div class="dateinfo" id="dateinfo_fourweeksview_sunday_1">
									</div>
									<div class="events" id="events_fourweeksview_sunday_1">
									</div>
									</td>
								</tr>
								<tr id="fourweeksview_week_2">
									<td id="fourweeksview_calw2"></td>
									<td id="fourweeksview_monday_2" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_monday_2">
									</div>
									<div class="events" id="events_fourweeksview_monday_2">
									</div>
									</td>
									<td id="fourweeksview_tuesday_2" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_tuesday_2">
									</div>
									<div class="events" id="events_fourweeksview_tuesday_2">
									</div>
									</td>
									<td id="fourweeksview_wednesday_2" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_wednesday_2">
									</div>
									<div class="events" id="events_fourweeksview_wednesday_2">
									</div>
									</td>
									<td id="fourweeksview_thursday_2" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_thursday_2">
									</div>
									<div class="events" id="events_fourweeksview_thursday_2">
									</div>
									</td>
									<td id="fourweeksview_friday_2" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_friday_2">
									</div>
									<div class="events" id="events_fourweeksview_friday_2">
									</div>
									</td>
									<td id="fourweeksview_saturday_2" class="weekend">
									<div class="dateinfo" id="dateinfo_fourweeksview_saturday_2">
									</div>
									<div class="events" id="events_fourweeksview_saturday_2">
									</div>
									</td>
									<td id="fourweeksview_sunday_2" class="weekend">
									<div class="dateinfo" id="dateinfo_fourweeksview_sunday_2">
									</div>
									<div class="events" id="events_fourweeksview_sunday_2">
									</div>
									</td>
								</tr>
								<tr id="fourweeksview_week_3">
									<td id="fourweeksview_calw3"></td>
									<td id="fourweeksview_monday_3" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_monday_3">
									</div>
									<div class="events" id="events_fourweeksview_monday_3">
									</div>
									</td>
									<td id="fourweeksview_tuesday_3" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_tuesday_3">
									</div>
									<div class="events" id="events_fourweeksview_tuesday_3">
									</div>
									</td>
									<td id="fourweeksview_wednesday_3" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_wednesday_3">
									</div>
									<div class="events" id="events_fourweeksview_wednesday_3">
									</div>
									</td>
									<td id="fourweeksview_thursday_3" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_thursday_3">
									</div>
									<div class="events" id="events_fourweeksview_thursday_3">
									</div>
									</td>
									<td id="fourweeksview_friday_3" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_friday_3">
									</div>
									<div class="events" id="events_fourweeksview_friday_3">
									</div>
									</td>
									<td id="fourweeksview_saturday_3" class="weekend">
									<div class="dateinfo" id="dateinfo_fourweeksview_saturday_3">
									</div>
									<div class="events" id="events_fourweeksview_saturday_3">
									</div>
									</td>
									<td id="fourweeksview_sunday_3" class="weekend">
									<div class="dateinfo" id="dateinfo_fourweeksview_sunday_3">
									</div>
									<div class="events" id="events_fourweeksview_sunday_3">
									</div>
									</td>
								</tr>
								<tr id="fourweeksview_week_4">
									<td id="fourweeksview_calw4"></td>
									<td id="fourweeksview_monday_4" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_monday_4">
									</div>
									<div class="events" id="events_fourweeksview_monday_4">
									</div>
									</td>
									<td id="fourweeksview_tuesday_4" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_tuesday_4">
									</div>
									<div class="events" id="events_fourweeksview_tuesday_4">
									</div>
									</td>
									<td id="fourweeksview_wednesday_4" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_wednesday_4">
									</div>
									<div class="events" id="events_fourweeksview_wednesday_4">
									</div>
									</td>
									<td id="fourweeksview_thursday_4" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_thursday_4">
									</div>
									<div class="events" id="events_fourweeksview_thursday_4">
									</div>
									</td>
									<td id="fourweeksview_friday_4" class="fourweeksview_item">
									<div class="dateinfo" id="dateinfo_fourweeksview_friday_4">
									</div>
									<div class="events" id="events_fourweeksview_friday_4">
									</div>
									</td>
									<td id="fourweeksview_saturday_4" class="weekend">
									<div class="dateinfo" id="dateinfo_fourweeksview_saturday_4">
									</div>
									<div class="events" id="events_fourweeksview_saturday_4">
									</div>
									</td>
									<td id="fourweeksview_sunday_4" class="weekend">
									<div class="dateinfo" id="dateinfo_fourweeksview_sunday_4">
									</div>
									<div class="events" id="events_fourweeksview_sunday_4">
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
									<th id="onemonthview_monday" class="calendar_row">Monday</th>
									<th id="onemonthview_tuesday" class="calendar_row">Tuesday</th>
									<th id="onemonthview_wednesday" class="calendar_row">Wednesday</th>
									<th id="onemonthview_thursday" class="calendar_row">Thursday</th>
									<th id="onemonthview_friday" class="calendar_row">Friday</th>
									<th id="onemonthview_saturday" class="weekend_thead">Saturday</th>
									<th id="onemonthview_sunday" class="weekend_thead">Sunday</th>
								</tr>
							</thead>
							<tbody>
								<tr id="onemonthview_week_1">
									<td id="onemonthview_monday_1" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_monday_1">
									</div>
									<div class="events" id="events_onemonthview_monday_1">
									</div>
									</td>
									<td id="onemonthview_tuesday_1" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_tuesday_1">
									</div>
									<div class="events" id="events_onemonthview_tuesday_1">
									</div>
									</td>
									<td id="onemonthview_wednesday_1" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_wednesday_1">
									</div>
									<div class="events" id="events_onemonthview_wednesday_1">
									</div>
									</td>
									<td id="onemonthview_thursday_1" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_thursday_1">
									</div>
									<div class="events" id="events_onemonthview_thursday_1">
									</div>
									</td>
									<td id="onemonthview_friday_1" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_friday_1">
									</div>
									<div class="events" id="events_onemonthview_friday_1">
									</div>
									</td>
									<td id="onemonthview_saturday_1" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_saturday_1">
									</div>
									<div class="events" id="events_onemonthview_saturday_1">
									</div>
									</td>
									<td id="onemonthview_sunday_1" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_sunday_1">
									</div>
									<div class="events" id="events_onemonthview_sunday_1">
									</div>
									</td>
								</tr>
								<tr id="onemonthview_week_2">
									<td id="onemonthview_monday_2" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_monday_2">
									</div>
									<div class="events" id="events_onemonthview_monday_2">
									</div>
									</td>
									<td id="onemonthview_tuesday_2" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_tuesday_2">
									</div>
									<div class="events" id="events_onemonthview_tuesday_2">
									</div>
									</td>
									<td id="onemonthview_wednesday_2" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_wednesday_2">
									</div>
									<div class="events" id="events_onemonthview_wednesday_2">
									</div>
									</td>
									<td id="onemonthview_thursday_2" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_thursday_2">
									</div>
									<div class="events" id="events_onemonthview_thursday_2">
									</div>
									</td>
									<td id="onemonthview_friday_2" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_friday_2">
									</div>
									<div class="events" id="events_onemonthview_friday_2">
									</div>
									</td>
									<td id="onemonthview_saturday_2" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_saturday_2">
									</div>
									<div class="events" id="events_onemonthview_saturday_2">
									</div>
									</td>
									<td id="onemonthview_sunday_2" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_sunday_2">
									</div>
									<div class="events" id="events_onemonthview_sunday_2">
									</div>
									</td>
								</tr>
								<tr id="onemonthview_week_3">
									<td id="onemonthview_monday_3" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_monday_3">
									</div>
									<div class="events" id="events_onemonthview_monday_3">
									</div>
									</td>
									<td id="onemonthview_tuesday_3" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_tuesday_3">
									</div>
									<div class="events" id="events_onemonthview_tuesday_3">
									</div>
									</td>
									<td id="onemonthview_wednesday_3" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_wednesday_3">
									</div>
									<div class="events" id="events_onemonthview_wednesday_3">
									</div>
									</td>
									<td id="onemonthview_thursday_3" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_thursday_3">
									</div>
									<div class="events" id="events_onemonthview_thursday_3">
									</div>
									</td>
									<td id="onemonthview_friday_3" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_friday_3">
									</div>
									<div class="events" id="events_onemonthview_friday_3">
									</div>
									</td>
									<td id="onemonthview_saturday_3" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_saturday_3">
									</div>
									<div class="events" id="events_onemonthview_saturday_3">
									</div>
									</td>
									<td id="onemonthview_sunday_3" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_sunday_3">
									</div>
									<div class="events" id="events_onemonthview_sunday_3">
									</div>
									</td>
								</tr>
								<tr id="onemonthview_week_4">
									<td id="onemonthview_monday_4" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_monday_4">
									</div>
									<div class="events" id="events_onemonthview_monday_4">
									</div>
									</td>
									<td id="onemonthview_tuesday_4" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_tuesday_4">
									</div>
									<div class="events" id="events_onemonthview_tuesday_4">
									</div>
									</td>
									<td id="onemonthview_wednesday_4" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_wednesday_4">
									</div>
									<div class="events" id="events_onemonthview_wednesday_4">
									</div>
									</td>
									<td id="onemonthview_thursday_4" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_thursday_4">
									</div>
									<div class="events" id="events_onemonthview_thursday_4">
									</div>
									</td>
									<td id="onemonthview_friday_4" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_friday_4">
									</div>
									<div class="events" id="events_onemonthview_friday_4">
									</div>
									</td>
									<td id="onemonthview_saturday_4" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_saturday_4">
									</div>
									<div class="events" id="events_onemonthview_saturday_4">
									</div>
									</td>
									<td id="onemonthview_sunday_4" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_sunday_4">
									</div>
									<div class="events" id="events_onemonthview_sunday_4">
									</div>
									</td>
								</tr>
								<tr id="onemonthview_week_5">
									<td id="onemonthview_monday_5" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_monday_5">
									</div>
									<div class="events" id="events_onemonthview_monday_5">
									</div>
									</td>
									<td id="onemonthview_tuesday_5" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_tuesday_5">
									</div>
									<div class="events" id="events_onemonthview_tuesday_5">
									</div>
									</td>
									<td id="onemonthview_wednesday_5" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_wednesday_5">
									</div>
									<div class="events" id="events_onemonthview_wednesday_5">
									</div>
									</td>
									<td id="onemonthview_thursday_5" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_thursday_5">
									</div>
									<div class="events" id="events_onemonthview_thursday_5">
									</div>
									</td>
									<td id="onemonthview_friday_5" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_friday_5">
									</div>
									<div class="events" id="events_onemonthview_friday_5">
									</div>
									</td>
									<td id="onemonthview_saturday_5" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_saturday_5">
									</div>
									<div class="events" id="events_onemonthview_saturday_5">
									</div>
									</td>
									<td id="onemonthview_sunday_5" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_sunday_5">
									</div>
									<div class="events" id="events_onemonthview_sunday_5">
									</div>
									</td>
								</tr>
								<tr id="onemonthview_week_6">
									<td id="onemonthview_monday_6" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_monday_6">
									</div>
									<div class="events" id="events_onemonthview_monday_6">
									</div>
									</td>
									<td id="onemonthview_tuesday_6" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_tuesday_6">
									</div>
									<div class="events" id="events_onemonthview_tuesday_6">
									</div>
									</td>
									<td id="onemonthview_wednesday_6" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_wednesday_6">
									</div>
									<div class="events" id="events_onemonthview_wednesday_6">
									</div>
									</td>
									<td id="onemonthview_thursday_6" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_thursday_6">
									</div>
									<div class="events" id="events_onemonthview_thursday_6">
									</div>
									</td>
									<td id="onemonthview_friday_6" class="onemonthview_item">
									<div class="dateinfo" id="dateinfo_onemonthview_friday_6">
									</div>
									<div class="events" id="events_onemonthview_friday_6">
									</div>
									</td>
									<td id="onemonthview_saturday_6" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_saturday_6">
									</div>
									<div class="events" id="events_onemonthview_saturday_6">
									</div>
									</td>
									<td id="onemonthview_sunday_6" class="weekend">
									<div class="dateinfo" id="dateinfo_onemonthview_sunday_6">
									</div>
									<div class="events" id="events_onemonthview_sunday_6">
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
				<div id="choosecalendar_dialog" title="Please choose visible calendars.">
					<br/>
					<input type="checkbox" class="choosecalendar_check" id="choosecalendar_cal1" />
					<label for="choosecalendar_cal1">
						Calendar 1
					</label>
					<br/>
					<br/>
					<input type="checkbox" class="choosecalendar_check" id="choosecalendar_cal2" />
					<label for="choosecalendar_cal2">
						Calendar 2
					</label>
					<br/>
					<br/>
					<input type="checkbox" class="choosecalendar_check" id="choosecalendar_cal3" />
					<label for="choosecalendar_cal3">
						Calendar 3
					</label>
					<br/>
					<br/>
					<input type="checkbox" class="choosecalendar_check" id="choosecalendar_cal4" />
					<label for="choosecalendar_cal4">
						Calendar 4
					</label>
					<br/>
					<br />
					<input type="submit" class="choosecalendar_check" id="choosecalendar_submit" onclick="choosecalendar_dialog_submit();" value="Save"/>
				</div>
				<div id="newevent" title="Create a new event">
				</div>
				<div id="editevent" title="Edit a event">
				</div>
				<div id="parsingfail_dialog" title="Parsing Fail">
					There was a fail, while parsing the file.
				</div>
				<!-- End of Dialogs -->
				<script type="text/javascript">
				//sending ajax request on every change view and use last view as default on the next
				<?php
				if(OC_Preferences::getValue(OC_USER::getUser(), "calendar", "currentview") == ""){
					echo "var oc_cal_currentview = \"onemonthview\";";
				}else{
					echo "var oc_cal_currentview = \"" . OC_Preferences::getValue(OC_USER::getUser(), "calendar", "currentview") . "\";";
				}
				 
				?>
				document.getElementById(oc_cal_currentview).style.display = "block";
				document.getElementById(oc_cal_currentview + "_radio").style.color = "#0098E4";
				oc_cal_update_view(oc_cal_currentview);
				function oc_cal_change_view(view, task){
					document.getElementById(oc_cal_currentview).style.display = "none";
					document.getElementById(oc_cal_currentview + "_radio").style.color = "#000000";
					document.getElementById(view).style.display = "block";
					oc_cal_currentview = view;
					document.getElementById(oc_cal_currentview + "_radio").style.color = "#0098E4";
					oc_cal_update_view(view, task);
				}
				</script>
				<script type="text/javascript" id="js_events"></script>
