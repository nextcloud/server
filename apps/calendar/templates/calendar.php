<?php
$hours24 = array(
	'allday' => $l->t('All day'),
	 0 => '0',
	 1 => '1',
	 2 => '2',
	 3 => '3',
	 4 => '4',
	 5 => '5',
	 6 => '6',
	 7 => '7',
	 8 => '8',
	 9 => '9',
	10 => '10',
	11 => '11',
	12 => '12',
	13 => '13',
	14 => '14',
	15 => '15',
	16 => '16',
	17 => '17',
	18 => '18',
	19 => '19',
	20 => '20',
	21 => '21',
	22 => '22',
	23 => '23',
);
$hoursampm = array(
	'allday' => $l->t('All day'),
	 0 => '12 a.m.',
	 1 => '1 a.m.',
	 2 => '2 a.m.',
	 3 => '3 a.m.',
	 4 => '4 a.m.',
	 5 => '5 a.m.',
	 6 => '6 a.m.',
	 7 => '7 a.m.',
	 8 => '8 a.m.',
	 9 => '9 a.m.',
	10 => '10 a.m.',
	11 => '11 a.m.',
	12 => '12 p.m.',
	13 => '1 p.m.',
	14 => '2 p.m.',
	15 => '3 p.m.',
	16 => '4 p.m.',
	17 => '5 p.m.',
	18 => '6 p.m.',
	19 => '7 p.m.',
	20 => '8 p.m.',
	21 => '9 p.m.',
	22 => '10 p.m.',
	23 => '11 p.m.',
);
if(OC_Preferences::getValue( OC_User::getUser(), 'calendar', 'timeformat', "24") == "24"){
	$hours = $hours24;
}else{
	$hours = $hoursampm;
}
$weekdaynames = array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday');
$dayforgenerator = OC_Preferences::getValue( OC_User::getUser(), 'calendar', 'firstdayofweek', "1");
$weekdays = array();
for($i = 0;$i <= 6; $i++){
	$weekdays[$i] = $weekdaynames[$dayforgenerator];
	if($dayforgenerator == 6){
		$dayforgenerator = 0;
	}else{
		$dayforgenerator++;
	}
}
$weekendjson = OC_Preferences::getValue( OC_User::getUser(), 'calendar', 'weekend', '{"Monday":"false","Tuesday":"false","Wednesday":"false","Thursday":"false","Friday":"false","Saturday":"true","Sunday":"true"}');
$weekend = json_decode($weekendjson, true);
$weekenddays = array("sunday"=>$weekend["Sunday"], "monday"=>$weekend["Monday"], "tuesday"=>$weekend["Tuesday"], "wednesday"=>$weekend["Wednesday"], "thursday"=>$weekend["Thursday"], "friday"=>$weekend["Friday"], "saturday"=>$weekend["Saturday"]);
?>
				<script type="text/javascript">
				<?php
				echo "var weekdays = new Array('".$weekdays[0]."','".$weekdays[1]."','".$weekdays[2]."','".$weekdays[3]."','".$weekdays[4]."','".$weekdays[5]."','".$weekdays[6]."');\n";
				?>
				Calendar.UI.weekdays = weekdays;
				Calendar.UI.daylong = new Array("<?php echo $l -> t("Sunday");?>", "<?php echo $l -> t("Monday");?>", "<?php echo $l -> t("Tuesday");?>", "<?php echo $l -> t("Wednesday");?>", "<?php echo $l -> t("Thursday");?>", "<?php echo $l -> t("Friday");?>", "<?php echo $l -> t("Saturday");?>");
				Calendar.UI.dayshort = new Array("<?php echo $l -> t("Sun.");?>", "<?php echo $l -> t("Mon.");?>", "<?php echo $l -> t("Tue.");?>", "<?php echo $l -> t("Wed.");?>", "<?php echo $l -> t("Thu.");?>", "<?php echo $l -> t("Fri.");?>", "<?php echo $l -> t("Sat.");?>");
				Calendar.UI.monthlong = new Array("<?php echo $l -> t("January");?>", "<?php echo $l -> t("February");?>", "<?php echo $l -> t("March");?>", "<?php echo $l -> t("April");?>", "<?php echo $l -> t("May");?>", "<?php echo $l -> t("June");?>", "<?php echo $l -> t("July");?>", "<?php echo $l -> t("August");?>", "<?php echo $l -> t("September");?>", "<?php echo $l -> t("October");?>", "<?php echo $l -> t("November");?>", "<?php echo $l -> t("December");?>");
				Calendar.UI.monthshort = new Array("<?php echo $l -> t("Jan.");?>", "<?php echo $l -> t("Feb.");?>", "<?php echo $l -> t("Mar.");?>", "<?php echo $l -> t("Apr.");?>", "<?php echo $l -> t("May.");?>", "<?php echo $l -> t("Jun.");?>", "<?php echo $l -> t("Jul.");?>", "<?php echo $l -> t("Aug.");?>", "<?php echo $l -> t("Sep.");?>", "<?php echo $l -> t("Oct.");?>", "<?php echo $l -> t("Nov.");?>", "<?php echo $l -> t("Dec.");?>");
				Calendar.UI.cw_label = "<?php echo $l->t("Week");?>";
				Calendar.UI.cws_label = "<?php echo $l->t("Weeks");?>";
				Calendar.UI.more_before = String('<?php echo $l->t('More before {startdate}') ?>');
				Calendar.UI.more_after = String('<?php echo $l->t('More after {enddate}') ?>');
				Calendar.firstdayofweek = parseInt("<?php echo OC_Preferences::getValue( OC_User::getUser(), 'calendar', 'firstdayofweek', "1"); ?>");
				//use last view as default on the next
				Calendar.UI.setCurrentView("<?php echo OC_Preferences::getValue(OC_USER::getUser(), "calendar", "currentview", "onemonthview") ?>");
				var totalurl = "<?php echo OC_Helper::linkTo('calendar', 'caldav.php', null, true) . '/calendars'; ?>";
				</script>
				<div id="sysbox"></div>
				<div id="controls">
					<div>
						<form>
							<div id="view">
								<!-- <input type="button" value="1 <?php echo $l->t('Day');?>" id="onedayview_radio" onclick="Calendar.UI.setCurrentView('onedayview');"/> -->
								<input type="button" value="<?php echo $l->t('Week');?>" id="oneweekview_radio" onclick="Calendar.UI.setCurrentView('oneweekview');"/>
								<!-- <input type="button" value="4 <?php echo $l->t('Weeks');?>" id="fourweeksview_radio" onclick="Calendar.UI.setCurrentView('fourweeksview');"/> -->
								<input type="button" value="<?php echo $l->t('Month');?>" id="onemonthview_radio" onclick="Calendar.UI.setCurrentView('onemonthview');"/>
								<input type="button" value="<?php echo $l->t('List');?>" id="listview_radio" onclick="Calendar.UI.setCurrentView('listview');"/>
							</div>
						</form>
						<form>
							<div id="choosecalendar">
								<input type="button" id="today_input" value="<?php echo $l->t("Today");?>" onclick="Calendar.UI.switch2Today();"/>
								<input type="button" id="choosecalendar_input" value="<?php echo $l->t("Calendars");?>" onclick="Calendar.UI.Calendar.overview();" />
							</div>
						</form>
						<form>
							<div id="datecontrol">
								<input type="button" value="&nbsp;&lt;&nbsp;" id="datecontrol_left" onclick="Calendar.UI.updateDate('backward');"/>
								<input id="datecontrol_date" type="button" value=""/>
								<input type="button" value="&nbsp;&gt;&nbsp;" id="datecontrol_right" onclick="Calendar.UI.updateDate('forward');"/>
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
									<th id="onedayview_today" class="calendar_row" onclick="Calendar.UI.newEvent('#onedayview_today');"></th>
								</tr>
							</thead>
							<tbody>
<?php foreach($hours as $time => $time_label): ?>
								<tr>
									<td class="calendar_time"><?php echo $time_label ?></td>
									<td class="calendar_row <?php echo $time ?>" onclick="Calendar.UI.newEvent('#onedayview_today', '<?php echo $time ?>');"></td>
								</tr>
<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<div id="oneweekview">
						<table>
							<thead>
								<tr>
									<th class="calendar_time"><?php echo $l->t("Time");?></th>
<?php foreach($weekdays as $weekdaynr => $weekday): ?>
									<th class="calendar_row <?php echo $weekday ?> <?php echo $weekenddays[$weekday] == "true" ? 'weekend_thead' : '' ?>" onclick="Calendar.UI.newEvent('#oneweekview th.<?php echo $weekday ?>');"></th>
<?php endforeach; ?>
								</tr>
							</thead>
							<tbody>
<?php foreach($hours as $time => $time_label): ?>
								<tr>
									<td class="calendar_time"><?php echo $time_label?></td>
<?php foreach($weekdays as $weekdaynr => $weekday): ?>
									<td class="<?php echo $weekday ?> <?php echo $time ?> calendar_row <?php echo $weekenddays[$weekday] == "true" ? 'weekend_row' : '' ?>" onclick="Calendar.UI.newEvent('#oneweekview th.<?php echo $weekday ?>', '<?php echo $time ?>');"></td>
<?php endforeach; ?>
								</tr>
<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<div id="fourweeksview">
						<table>
							<thead>
								<tr>
									<th class="calendar_row calw"><?php echo $l -> t("Week");?></th>
<?php foreach($weekdays as $weekdaynr => $weekday): ?>
									<th class="calendar_row <?php echo $weekdaynr > 4 ? 'weekend_thead' : '' ?>"><?php echo $l->t(ucfirst($weekday)) ?></th>
<?php endforeach; ?>
								</tr>
							</thead>
							<tbody>
<?php foreach(range(1, 4) as $week): ?>
								<tr class="week_<?php echo $week ?>">
									<td class="calw"></td>
<?php foreach($weekdays as $weekdaynr => $weekday): ?>
									<td class="day <?php echo $weekday ?> <?php echo $weekdaynr > 4 ? 'weekend' : '' ?>" onclick="Calendar.UI.newEvent('#fourweeksview .week_<?php echo $week ?> .<?php echo $weekday ?>')">
									<div class="dateinfo"></div>
									<div class="events"></div>
									</td>
<?php endforeach; ?>
								</tr>
<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<div id="onemonthview">
						<table>
							<thead>
								<tr>
<?php foreach($weekdays as $weekdaynr => $weekday): ?>
									<th class="calendar_row <?php echo $weekenddays[$weekday] == "true" ? 'weekend_thead' : '' ?> <?php echo $weekday ?>"><?php echo $l->t(ucfirst($weekday));?></th>
<?php endforeach; ?>
								</tr>
							</thead>
							<tbody>
<?php foreach(range(1, 6) as $week): ?>
								<tr class="week_<?php echo $week ?>">
<?php foreach($weekdays as $weekdaynr => $weekday): ?>
									<td class="day <?php echo $weekday ?> <?php echo $weekenddays[$weekday] == "true" ? 'weekend' : '' ?>" onclick="Calendar.UI.newEvent('#onemonthview .week_<?php echo $week ?> .<?php echo $weekday ?>')">
									<div class="dateinfo"></div>
									<div class="events"></div>
									</td>
<?php endforeach; ?>
								</tr>
<?php endforeach; ?>
							</tbody>
						</table>
					</div>
					<div id="listview">
						<div id="more_before"></div>
						<div id="events"></div>
						<div id="more_after"></div>
					</div>
				</div>
				<!-- Dialogs -->
				<div id="dialog_holder"></div>
				<div id="parsingfail_dialog" title="Parsing Fail">
					<?php echo $l->t("There was a fail, while parsing the file."); ?>
				</div>
				<!-- End of Dialogs -->
