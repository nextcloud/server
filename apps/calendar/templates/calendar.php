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
<script type='text/javascript'>
	$(document).ready(function() {
		$('#calendar_holder').fullCalendar({
			editable: true,
			eventSources: <?php echo json_encode($_['eventSources']) ?>
		});

	});

</script>
				<div id="calendar_holder">
				</div>
				<!-- Dialogs -->
				<div id="dialog_holder"></div>
				<div id="parsingfail_dialog" title="Parsing Fail">
					<?php echo $l->t("There was a fail, while parsing the file."); ?>
				</div>
				<!-- End of Dialogs -->
