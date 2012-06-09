<div id="calendar_import_dialog" title="<?php echo $l->t("Import a calendar file"); ?>">
<div id="form_container">
<input type="hidden" id="filename" value="<?php echo $_['filename'];?>">
<input type="hidden" id="path" value="<?php echo $_['path'];?>">
<input type="hidden" id="progressfile" value="<?php echo md5(session_id()) . '.txt';?>">
<p style="text-align:center;"><b><?php echo $l->t('Please choose the calendar'); ?></b></p>
<select style="width:100%;" id="calendar" name="calendar">
<?php
$calendar_options = OC_Calendar_Calendar::allCalendars(OCP\USER::getUser());
$calendar_options[] = array('id'=>'newcal', 'displayname'=>$l->t('create a new calendar'));
for($i = 0;$i<count($calendar_options);$i++){
	$calendar_options[$i]['displayname'] = htmlspecialchars($calendar_options[$i]['displayname']);
}
echo OCP\html_select_options($calendar_options, $calendar_options[0]['id'], array('value'=>'id', 'label'=>'displayname'));
?>
</select>
<div id="newcalform" style="display: none;">
	<input type="text" style="width: 97%;" placeholder="<?php echo $l->t('Name of new calendar'); ?>" id="newcalendar" name="newcalendar">
</div>
<input type="button" value="<?php echo $l->t("Import");?>!" id="startimport">
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