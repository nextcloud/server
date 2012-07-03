<?php
//Prerendering for iCalendar file
$file = OC_Filesystem::file_get_contents($_['path'] . '/' . $_['filename']);
if(!$file){
	OCP\JSON::error(array('error'=>'404'));
}
$import = new OC_Calendar_Import($file);
$newcalendarname = strip_tags($import->createCalendarName());
$guessedcalendarname = $import->guessCalendarName();
//loading calendars for select box
$calendar_options = OC_Calendar_Calendar::allCalendars(OCP\USER::getUser());
$calendar_options[] = array('id'=>'newcal', 'displayname'=>$l->t('create a new calendar'));
?>
<div id="calendar_import_dialog" title="<?php echo $l->t("Import a calendar file");?>">
<div id="calendar_import_form">
	<form>
		<input type="hidden" id="calendar_import_filename" value="<?php echo $_['filename'];?>">
		<input type="hidden" id="calendar_import_path" value="<?php echo $_['path'];?>">
		<input type="hidden" id="calendar_import_progresskey" value="<?php echo rand() ?>">
		<span id="calendar_import_form_message"><?php echo $l->t('Please choose a calendar'); ?></span>
		<select style="width:100%;" id="calendar_import_calendar" name="calendar_import_calendar">
		<?php
		for($i = 0;$i<count($calendar_options);$i++){
			$calendar_options[$i]['displayname'] = $calendar_options[$i]['displayname'];
		}
		echo OCP\html_select_options($calendar_options, $calendar_options[0]['id'], array('value'=>'id', 'label'=>'displayname'));
		?>
		</select>
		<div id="calendar_import_newcalform">
			<input id="calendar_import_newcalendar"  type="text" placeholder="<?php echo $l->t('Name of new calendar'); ?>" value="<?php echo $newcalendarname ?>"><br>
			<!--<input id="calendar_import_generatename" type="button" class="button" value="<?php echo $l->t('Take an available name!'); ?>"><br>-->
			<span  id="calendar_import_mergewarning" class="hint"><?php echo $l->t('A Calendar with this name already exists. If you continue anyhow, these calendars will be merged.'); ?></span>
		</div>
		<input id="calendar_import_submit" type="button" class="button" value="&raquo; <?php echo $l->t('Import'); ?> &raquo;" id="startimport">
	<form>
</div>
<div id="calendar_import_process">
	<span id="calendar_import_process_message"></span>
	<div  id="calendar_import_progressbar"></div>
	<br>
	<div id="calendar_import_status" class="hint"></div>
	<br>
	<input id="calendar_import_done" type="button" value="<?php echo $l->t('Close Dialog'); ?>">
</div>
</div>