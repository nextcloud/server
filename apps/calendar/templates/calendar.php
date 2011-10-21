<script type='text/javascript'>
var defaultView = '<?php echo OC_Preferences::getValue(OC_USER::getUser(), 'calendar', 'currentview', 'month') ?>';
var eventSources = <?php echo json_encode($_['eventSources']) ?>;
var dayNames = <?php echo json_encode($l->tA(array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'))) ?>;
var dayNamesShort = <?php echo json_encode($l->tA(array('Sun.', 'Mon.', 'Tue.', 'Wed.', 'Thu.', 'Fri.', 'Sat.'))) ?>;
var monthNames = <?php echo json_encode($l->tA(array('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'))) ?>;
var monthNamesShort = <?php echo json_encode($l->tA(array('Jan.', 'Feb.', 'Mar.', 'Apr.', 'May.', 'Jun.', 'Jul.', 'Aug.', 'Sep.', 'Oct.', 'Nov.', 'Dec.'))) ?>;
var allDayText = '<?php echo $l->t('All day') ?>';
var neweventtitle = '<?php echo $l->t('Title') ?>';
$(document).ready(function() {
	var date = new Date();
	var d = date.getDate();
	var m = date.getMonth();
	var y = date.getFullYear();
		
	var calendar = $('#calendar').fullCalendar({
		selectable: true,
		selectHelper: true,
		select: function(start, end, allDay) {
			var title = prompt(neweventtitle + ':');
			if (title) {
				calendar.fullCalendar('renderEvent',
					{
						title: title,
						start: start,
						end: end,
						allDay: allDay
					},
					true // make the event "stick"
				);
			}
			calendar.fullCalendar('unselect');
		},
		editable: true,
		events: [
			{
				title: 'All Day Event',
				start: new Date(y, m, 1)
			},
			{
				title: 'Long Event',
				start: new Date(y, m, d-5),
				end: new Date(y, m, d-2)
			},
			{
				id: 999,
				title: 'Repeating Event',
				start: new Date(y, m, d-3, 16, 0),
				allDay: false
			},
			{
				id: 999,
				title: 'Repeating Event',
				start: new Date(y, m, d+4, 16, 0),
				allDay: false
			},
			{
				title: 'Meeting',
				start: new Date(y, m, d, 10, 30),
				allDay: false
			},
			{
				title: 'Lunch',
				start: new Date(y, m, d, 12, 0),
				end: new Date(y, m, d, 14, 0),
				allDay: false
			},
			{
				title: 'Birthday Party',
				start: new Date(y, m, d+1, 19, 0),
				end: new Date(y, m, d+1, 22, 30),
				allDay: false
			},
			{
				title: 'Click for Google',
				start: new Date(y, m, 28),
				end: new Date(y, m, 29),
				url: 'http://google.com/'
			}
		]
	});
	var date = $('#calendar').fullCalendar('getDate');$('#datecontrol_date').html(date.getDate() + "." + date.getMonth() + "." + date.getFullYear());
	$('#oneweekview_radio').click(function(){$('#calendar').fullCalendar( 'changeView', 'agendaWeek');});
	$('#onemonthview_radio').click(function(){$('#calendar').fullCalendar( 'changeView', 'month');});
	$('#today_input').click(function(){$('#calendar').fullCalendar( 'today' );var date = $('#calendar').fullCalendar('getDate');$('#datecontrol_date').html(date.getDate() + "." + date.getMonth() + "." + date.getFullYear());});
	$('#datecontrol_left').click(function(){$('#calendar').fullCalendar( 'prev' );var date = $('#calendar').fullCalendar('getDate');$('#datecontrol_date').html(date.getDate() + "." + date.getMonth() + "." + date.getFullYear());});
	$('#datecontrol_right').click(function(){$('#calendar').fullCalendar( 'next' );var date = $('#calendar').fullCalendar('getDate');$('#datecontrol_date').html(date.getDate() + "." + date.getMonth() + "." + date.getFullYear());});
});
</script>
				<div id="controls">
					<div>
						<form>
							<div id="view">
								<input type="button" value="<?php echo $l->t('Week');?>" id="oneweekview_radio"/>
								<input type="button" value="<?php echo $l->t('Month');?>" id="onemonthview_radio"/>
								<!--<input type="button" value="<?php echo $l->t('List');?>" id="listview_radio"/>-->
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
								<span id="datecontrol_date"></span>
								<input type="button" value="&nbsp;&gt;&nbsp;" id="datecontrol_right"/>
							</div>
						</form>
					</div>
				</div>
				<div id="calendar">
				</div>
				<!-- Dialogs -->
				<div id="dialog_holder"></div>
				<div id="parsingfail_dialog" title="Parsing Fail">
					<?php echo $l->t("There was a fail, while parsing the file."); ?>
				</div>
				<!-- End of Dialogs -->
