<script type='text/javascript'>
var eventSources = <?php echo json_encode($_['eventSources']) ?>;
</script>
				<div id="sysbox"></div>
				<div id="controls">
					<div>
						<form>
							<div id="view">
								<input type="button" value="<?php echo $l->t('Week');?>" id="oneweekview_radio"/>
								<input type="button" value="<?php echo $l->t('Month');?>" id="onemonthview_radio"/>
								<input type="button" value="<?php echo $l->t('List');?>" id="listview_radio"/>
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
				<div id="calendar_holder">
				</div>
				<!-- Dialogs -->
				<div id="dialog_holder"></div>
				<div id="parsingfail_dialog" title="Parsing Fail">
					<?php echo $l->t("There was a fail, while parsing the file."); ?>
				</div>
				<!-- End of Dialogs -->
