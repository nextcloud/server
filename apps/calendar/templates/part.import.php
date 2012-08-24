<?php
//Prerendering for iCalendar file
$file = OC_Filesystem::file_get_contents($_['path'] . '/' . $_['filename']);
if(!$file){
	OCP\JSON::error(array('error'=>'404'));
}
$import = new OC_Calendar_Import($file);
$import->setUserID(OCP\User::getUser());
$newcalendarname = strip_tags($import->createCalendarName());
$guessedcalendarname = strip_tags($import->guessCalendarName());
$calendarcolor = strip_tags($import->createCalendarColor());
//loading calendars for select box
$calendar_options = OC_Calendar_Calendar::allCalendars(OCP\USER::getUser());
$calendar_options[] = array('id'=>'newcal', 'displayname'=>$l->t('create a new calendar'));
$defaultcolors = OC_Calendar_Calendar::getCalendarColorOptions();
?>
<div id="calendar_import_dialog" title="<?php echo $l->t("Import a calendar file");?>">
<div id="calendar_import_form">
	<form>
		<input type="hidden" id="calendar_import_filename" value="<?php echo $_['filename'];?>">
		<input type="hidden" id="calendar_import_path" value="<?php echo $_['path'];?>">
		<input type="hidden" id="calendar_import_progresskey" value="<?php echo rand() ?>">
		<input type="hidden" id="calendar_import_availablename" value="<?php echo $newcalendarname ?>">
		<div id="calendar_import_form_message"><?php echo $l->t('Please choose a calendar'); ?></div>
		<select style="width:100%;" id="calendar_import_calendar" name="calendar_import_calendar">
		<?php
		for($i = 0;$i<count($calendar_options);$i++){
			$calendar_options[$i]['displayname'] = $calendar_options[$i]['displayname'];
		}
		echo OCP\html_select_options($calendar_options, $calendar_options[0]['id'], array('value'=>'id', 'label'=>'displayname'));
		?>
		</select>
		<br><br>
		<div id="calendar_import_newcalform">
			<input id="calendar_import_newcalendar_color" class="color-picker" type="hidden" size="6" value="<?php echo substr($calendarcolor,1); ?>">
			<input id="calendar_import_newcalendar"  class="" type="text" placeholder="<?php echo $l->t('Name of new calendar'); ?>" value="<?php echo $guessedcalendarname ?>"><br>
			<div id="calendar_import_defaultcolors">
				<?php
				foreach($defaultcolors as $color){
					echo '<span class="calendar-colorpicker-color" rel="' . $color . '" style="background-color: ' . $color .  ';"></span>';
				}
				?>
			</div>
			<!--<input id="calendar_import_generatename" type="button" class="button" value="<?php echo $l->t('Take an available name!'); ?>"><br>-->
			<div  id="calendar_import_mergewarning" class="hint"><?php echo $l->t('A Calendar with this name already exists. If you continue anyhow, these calendars will be merged.'); ?></div>
		</div>
		<input id="calendar_import_submit" type="button" class="button" value="&raquo; <?php echo $l->t('Import'); ?> &raquo;" id="startimport">
	<form>
</div>
<div id="calendar_import_process">
	<div id="calendar_import_process_message"></div>
	<div  id="calendar_import_progressbar"></div>
	<br>
	<div id="calendar_import_status" class="hint"></div>
	<br>
	<input id="calendar_import_done" type="button" value="<?php echo $l->t('Close Dialog'); ?>">
</div>
</div>