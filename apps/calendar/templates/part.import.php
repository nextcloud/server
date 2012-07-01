<?php
//Prerendering for iCalendar file
$file = OC_Filesystem::file_get_contents($_['path'] . '/' . $_['filename']);
if(!$file){
	OCP\JSON::error(array('error'=>'404'));
}
$import = new OC_Calendar_Import($file);
$newcalendarname = strip_tags($import->createCalendarName());
$guessedcalendarname = $import->guessCalendarName();
?>
<div id="calendar_import_dialog" title="<?php echo $l->t("Import a calendar file");?>">
<div id="form_container">
<input type="hidden" id="filename" value="<?php echo $_['filename'];?>">
<input type="hidden" id="path" value="<?php echo $_['path'];?>">
<input type="hidden" id="progresskey" value="<?php echo rand() ?>">
<p style="text-align:center;"><b><?php echo $l->t('Please choose a calendar'); ?></b></p>
<select style="width:100%;" id="calendar" name="calendar">
<?php
$calendar_options = OC_Calendar_Calendar::allCalendars(OCP\USER::getUser());
$calendar_options[] = array('id'=>'newcal', 'displayname'=>$l->t('create a new calendar'));
for($i = 0;$i<count($calendar_options);$i++){
	$calendar_options[$i]['displayname'] = $calendar_options[$i]['displayname'];
}
echo OCP\html_select_options($calendar_options, $calendar_options[0]['id'], array('value'=>'id', 'label'=>'displayname'));
?>
</select>
<div id="newcalform" style="display: none;">
	<input type="text" style="width: 97%;" placeholder="<?php echo $l->t('Name of new calendar'); ?>" id="newcalendar" name="newcalendar" value="<?php echo $newcalendarname ?>">
</div>
<div id="namealreadyused" style="display: none;text-align:center;">
	<span class="hint"><?php echo $l->t('A Calendar with this name already exists. If you continue anyhow, these calendars will be merged.'); ?></span>
</div>
<input type="button" value="<?php echo $l->t("Import") . ' ' . $_['filename']; ?>!" id="startimport">
</div>
<div id="progressbar_container" style="display: none">
<p style="text-align:center;"><b><?php echo $l->t('Importing calendar'); ?></b></p>
<div id="progressbar"></div>
<div id="import_done" style="display: none;">
<p style="text-align:center;"><b><?php echo $l->t('Calendar imported successfully'); ?></b></p>
<input type="button" value="<?php echo $l->t('Close Dialog'); ?>" id="import_done_button">
</div>
</div>
</div>