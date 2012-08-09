				<script type='text/javascript'>
				var defaultView = '<?php echo OCP\Config::getUserValue(OCP\USER::getUser(), 'calendar', 'currentview', 'month') ?>';
				var eventSources = <?php echo json_encode($_['eventSources']) ?>;
				var categories = <?php echo json_encode($_['categories']); ?>;
				var dayNames = new Array("<?php echo $l -> t("Sunday");?>", "<?php echo $l -> t("Monday");?>", "<?php echo $l -> t("Tuesday");?>", "<?php echo $l -> t("Wednesday");?>", "<?php echo $l -> t("Thursday");?>", "<?php echo $l -> t("Friday");?>", "<?php echo $l -> t("Saturday");?>");
				var dayNamesShort = new Array("<?php echo $l -> t("Sun.");?>", "<?php echo $l -> t("Mon.");?>", "<?php echo $l -> t("Tue.");?>", "<?php echo $l -> t("Wed.");?>", "<?php echo $l -> t("Thu.");?>", "<?php echo $l -> t("Fri.");?>", "<?php echo $l -> t("Sat.");?>");
				var monthNames = new Array("<?php echo $l -> t("January");?>", "<?php echo $l -> t("February");?>", "<?php echo $l -> t("March");?>", "<?php echo $l -> t("April");?>", "<?php echo $l -> t("May");?>", "<?php echo $l -> t("June");?>", "<?php echo $l -> t("July");?>", "<?php echo $l -> t("August");?>", "<?php echo $l -> t("September");?>", "<?php echo $l -> t("October");?>", "<?php echo $l -> t("November");?>", "<?php echo $l -> t("December");?>");
				var monthNamesShort = new Array("<?php echo $l -> t("Jan.");?>", "<?php echo $l -> t("Feb.");?>", "<?php echo $l -> t("Mar.");?>", "<?php echo $l -> t("Apr.");?>", "<?php echo $l -> t("May.");?>", "<?php echo $l -> t("Jun.");?>", "<?php echo $l -> t("Jul.");?>", "<?php echo $l -> t("Aug.");?>", "<?php echo $l -> t("Sep.");?>", "<?php echo $l -> t("Oct.");?>", "<?php echo $l -> t("Nov.");?>", "<?php echo $l -> t("Dec.");?>");
				var agendatime = '<?php echo ((int) OCP\Config::getUserValue(OCP\USER::getUser(), 'calendar', 'timeformat', '24') == 24 ? 'HH:mm' : 'hh:mm tt'); ?>{ - <?php echo ((int) OCP\Config::getUserValue(OCP\USER::getUser(), 'calendar', 'timeformat', '24') == 24 ? 'HH:mm' : 'hh:mm tt'); ?>}';
				var defaulttime = '<?php echo ((int) OCP\Config::getUserValue(OCP\USER::getUser(), 'calendar', 'timeformat', '24') == 24 ? 'HH:mm' : 'hh:mm tt'); ?>';
				var allDayText = '<?php echo addslashes($l->t('All day')) ?>';
				var newcalendar = '<?php echo addslashes($l->t('New Calendar')) ?>';
				var missing_field = '<?php echo addslashes($l->t('Missing fields')) ?>';
				var missing_field_title = '<?php echo addslashes($l->t('Title')) ?>';
				var missing_field_calendar = '<?php echo addslashes($l->t('Calendar')) ?>';
				var missing_field_fromdate = '<?php echo addslashes($l->t('From Date')) ?>';
				var missing_field_fromtime = '<?php echo addslashes($l->t('From Time')) ?>';
				var missing_field_todate = '<?php echo addslashes($l->t('To Date')) ?>';
				var missing_field_totime = '<?php echo addslashes($l->t('To Time')) ?>';
				var missing_field_startsbeforeends = '<?php echo addslashes($l->t('The event ends before it starts')) ?>';
				var missing_field_dberror = '<?php echo addslashes($l->t('There was a database fail')) ?>';
				var totalurl = '<?php echo OCP\Util::linkToRemote('caldav'); ?>calendars';
				var firstDay = '<?php echo (OCP\Config::getUserValue(OCP\USER::getUser(), 'calendar', 'firstday', 'mo') == 'mo' ? '1' : '0'); ?>';
				$(document).ready(function() {
				<?php
				if(array_key_exists('showevent', $_)){
					$data = OC_Calendar_App::getEventObject($_['showevent']);
					$date = substr($data['startdate'], 0, 10);
					list($year, $month, $day) = explode('-', $date);
					echo '$(\'#calendar_holder\').fullCalendar(\'gotoDate\', ' . $year . ', ' . --$month . ', ' . $day . ');';
					echo '$(\'#dialog_holder\').load(OC.filePath(\'calendar\', \'ajax\', \'editeventform.php\') + \'?id=\' +  ' . $_['showevent'] . ' , Calendar.UI.startEventDialog);';
				}
				?>
				});
				</script>
				<div id="notification" style="display:none;"></div>
				<div id="controls">
					<form id="view">
						<input type="button" value="<?php echo $l->t('Week');?>" id="oneweekview_radio"/>
						<input type="button" value="<?php echo $l->t('Month');?>" id="onemonthview_radio"/>
						<input type="button" value="<?php echo $l->t('List');?>" id="listview_radio"/>&nbsp;&nbsp;
						<img id="loading" src="<?php echo OCP\Util::imagePath('core', 'loading.gif'); ?>" />
					</form>
					<form id="choosecalendar">
						<!--<input type="button" id="today_input" value="<?php echo $l->t("Today");?>"/>-->
						<a class="settings calendarsettings" title="<?php echo $l->t('Settings'); ?>"><img class="svg" src="<?php echo OCP\Util::imagePath('calendar', 'icon.svg'); ?>" alt="<?php echo $l->t('Settings'); ?>" /></a>
						<a class="settings generalsettings" title="<?php echo $l->t('Settings'); ?>"><img class="svg" src="core/img/actions/settings.svg" alt="<?php echo $l->t('Settings'); ?>" /></a>
					</form>
					<form id="datecontrol">
						<input type="button" value="&nbsp;&lt;&nbsp;" id="datecontrol_left"/>
						<input type="button" value="" id="datecontrol_date"/>
						<input type="button" value="&nbsp;&gt;&nbsp;" id="datecontrol_right"/>
					</form>
				</div>
				<div id="fullcalendar"></div>
				<div id="dialog_holder"></div>
				<div id="appsettings" class="popup topright hidden"></div>