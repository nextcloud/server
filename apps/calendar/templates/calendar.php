				<script type='text/javascript'>
				var defaultView = '<?php echo OCP\Config::getUserValue(OCP\USER::getUser(), 'calendar', 'currentview', 'month') ?>';
				var eventSources = <?php echo json_encode($_['eventSources']) ?>;
				var categories = <?php echo json_encode($_['categories']); ?>;
				var dayNames = <?php echo json_encode($l->tA(array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'))) ?>;
				var dayNamesShort = <?php echo json_encode($l->tA(array('Sun.', 'Mon.', 'Tue.', 'Wed.', 'Thu.', 'Fri.', 'Sat.'))) ?>;
				var monthNames = <?php echo json_encode($l->tA(array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'))) ?>;
				var monthNamesShort = <?php echo json_encode($l->tA(array('Jan.', 'Feb.', 'Mar.', 'Apr.', 'May.', 'Jun.', 'Jul.', 'Aug.', 'Sep.', 'Oct.', 'Nov.', 'Dec.'))) ?>;
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
				<div id="controls">
					<div>
						<form>
							<div id="view">
								<input type="button" value="<?php echo $l->t('Week');?>" id="oneweekview_radio"/>
								<input type="button" value="<?php echo $l->t('Month');?>" id="onemonthview_radio"/>
								<input type="button" value="<?php echo $l->t('List');?>" id="listview_radio"/>&nbsp;&nbsp;
								<img id="loading" src="<?php echo OCP\Util::imagePath('core', 'loading.gif'); ?>" />
							</div>
						</form>
						<form>
							<div id="choosecalendar">
								<input type="button" id="today_input" value="<?php echo $l->t("Today");?>"/>
								<input type="button" id="choosecalendar_input" value="<?php echo $l->t("Calendars");?>" onclick="Calendar.UI.Calendar.overview();" />
							</div>
						</form>
						<form>
							<div id="datecontrol">
								<input type="button" value="&nbsp;&lt;&nbsp;" id="datecontrol_left"/>
								<span class="button" id="datecontrol_date"></span>
								<input type="button" value="&nbsp;&gt;&nbsp;" id="datecontrol_right"/>
							</div>
						</form>
					</div>
				</div>
				<div id="notification" style="display:none;"></div>
				<div id="calendar_holder">
				</div>
				<!-- Dialogs -->
				<div id="dialog_holder"></div>
				<div id="parsingfail_dialog" title="Parsing Fail">
					<?php echo $l->t("There was a fail, while parsing the file."); ?>
				</div>
				<!-- End of Dialogs -->
