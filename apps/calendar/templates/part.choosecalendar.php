<div id="choosecalendar_dialog" title="<?php echo $l->t("Choose active calendars"); ?>">
<table width="100%" style="border: 0;">
<?php
$option_calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser());
for($i = 0; $i < count($option_calendars); $i++){
	echo "<tr>";
	$tmpl = new OC_Template('calendar', 'part.choosecalendar.rowfields');
	$tmpl->assign('calendar', $option_calendars[$i]);
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
		<p style="margin: 0 auto;width: 90%;"><input style="display:none;width: 90%;float: left;" type="text" id="caldav_url" onmouseover="$('#caldav_url').select();" title="<?php echo $l->t("CalDav Link"); ?>"><img id="caldav_url_close" style="height: 20px;vertical-align: middle;display: none;" src="../../core/img/actions/delete.svg" alt="close" onclick="$('#caldav_url').hide();$('#caldav_url_close').hide();"/></p>
	</td>
</tr>
</table>
