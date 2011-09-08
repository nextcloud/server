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
	<td colspan="4">
		<a href="#" onclick="oc_cal_newcalendar(this);"><?php echo $l->t('New Calendar') ?></a>
	</td>
</tr>
</table>
<script type="text/javascript">
	$( "#choosecalendar_dialog" ).dialog({
		width : 500,
		close : function() {
					oc_cal_opendialog = 0;
					var lastchild = document.getElementById("body-user").lastChild
					while(lastchild.id != "lightbox"){
						document.getElementById("body-user").removeChild(lastchild);
						lastchild = document.getElementById("body-user").lastChild;
					}
			}
	});
</script> 
