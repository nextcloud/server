<div id="calendar_import_dialog" title="<?php echo $l->t("Import a calendar file"); ?>">
<input type="hidden" id="filename" value="<?php echo $_GET["filename"];?>">
<input type="hidden" id="path" value="<?php echo $_GET["path"];?>">
<p style="text-align:center;"><b><?php echo $l->t('Please choose the calendar'); ?></b></h2>
<select style="width:100%;" id="calendar" name="calendar">
<?php
$calendar_options = OC_Calendar_Calendar::allCalendars(OC_User::getUser());
$calendar_options[] = array('id'=>'newcal', 'displayname'=>$l->t('create a new calendar'));
echo html_select_options($calendar_options, $calendar_options[0]['id'], array('value'=>'id', 'label'=>'displayname'));
?>
</select>
<div id="newcalform" style="display: none;">
	<input type="text" style="width: 97%;" placeholder="<?php echo $l->t('Name of new calendar'); ?>" id="newcalendar" name="newcalendar">
</div>
<input type="button" value="<?php echo $l->t("Import");?>!" id="startimport">
<br><br>
<div id="progressbar" style="display: none;"></div>
</div>