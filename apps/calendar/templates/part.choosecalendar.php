<div id="choosecalendar_dialog" title="<?php echo $l->t("Choose active calendars"); ?>">
<p><b><?php echo $l->t('Your calendars'); ?>:</b></p>
<table width="100%" style="border: 0;">
<?php
$option_calendars = OC_Calendar_Calendar::allCalendars(OCP\USER::getUser());
for($i = 0; $i < count($option_calendars); $i++){
	echo "<tr>";
	$tmpl = new OCP\Template('calendar', 'part.choosecalendar.rowfields');
	$tmpl->assign('calendar', $option_calendars[$i]);
	if(OC_Calendar_Share::allUsersSharedwith($option_calendars[$i]['id'], OC_Calendar_Share::CALENDAR) == array()){
		$shared = false;
	}else{
		$shared = true;
	}
	$tmpl->assign('shared', $shared);
	$tmpl->printpage();
	echo "</tr>";
}
?>
<tr>
	<td colspan="6">
		<a href="#" onclick="Calendar.UI.Calendar.newCalendar(this);"><input type="button" value="<?php echo $l->t('New Calendar') ?>"></a>
	</td>
</tr>
<tr>
	<td colspan="6">
		<p style="margin: 0 auto;width: 90%;"><input style="display:none;width: 90%;float: left;" type="text" id="caldav_url" onmouseover="$('#caldav_url').select();" title="<?php echo $l->t("CalDav Link"); ?>"><img id="caldav_url_close" style="height: 20px;vertical-align: middle;display: none;" src="<?php echo OCP\Util::imagePath('core', 'actions/delete.svg') ?>" alt="close" onclick="$('#caldav_url').hide();$('#caldav_url_close').hide();"/></p>
	</td>
</tr>
</table><br>
<p><b><?php echo $l->t('Shared calendars'); ?>: </b></p>
<table width="100%" style="border: 0;">
<?php
$share = OC_Calendar_Share::allSharedwithuser(OCP\USER::getUser(), OC_Calendar_Share::CALENDAR);
$count = count($share);
for($i = 0; $i < $count; $i++){
	$share[$i]['calendar'] = OC_Calendar_App::getCalendar($share[$i]['calendarid'], false, false);
	echo '<tr>';
	$tmpl = new OCP\Template('calendar', 'part.choosecalendar.rowfields.shared');
	$tmpl->assign('share', $share[$i]);
	$tmpl->printpage();
	echo '</tr>';
}
?>
</table>
<?php
if($count == 0){
	echo '<p style="text-align:center;"><b>' . $l->t('No shared calendars') . '</b></p>';
}
?>
</div>