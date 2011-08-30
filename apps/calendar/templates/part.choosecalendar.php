<div id="choosecalendar_dialog" title="<?php echo $l->t("Choose active calendars"); ?>">
<table width="100%" style="border: 0;">
<?php
$option_calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser());
for($i = 0; $i < count($option_calendars); $i++){
	echo "<tr>";
	echo "<td width=\"20px\"><input id=\"active_" . $option_calendars[$i]["id"] . "\" type=\"checkbox\" onClick=\"oc_cal_calender_activation(this, " . $option_calendars[$i]["id"] . ")\"" . ($option_calendars[$i]["active"] ? ' checked="checked"' : '') . "></td>";
	echo "<td><label for=\"active_" . $option_calendars[$i]["id"] . "\">" . $option_calendars[$i]["displayname"] . "</label></td>";
	echo "<td width=\"20px\"><a style=\"display: block; opacity: 0.214133;\" href=\"#\" title=\"" . $l->t("Download") . "\" class=\"action\"><img src=\"/owncloud/core/img/actions/download.svg\"></a></td><td width=\"20px\"><a style=\"display: block; opacity: 0.214133;\" href=\"#\" title=\"" . $l->t("Rename") . "\" class=\"action\"><img src=\"/owncloud/core/img/actions/rename.svg\"></a></td>";
	echo "</tr>";
}
?>
</table>
<br /><br /><br />
<input style="float: left;" type="button" onclick="oc_cal_choosecalendar_submit();" value="<?php echo $l->t("Submit"); ?>">
</div>
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
