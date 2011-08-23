<div id="choosecalendar_dialog" title="<?php echo $l->t("Choose active calendars"); ?>">
<?php
$option_calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser());
for($i = 0; $i < count($option_calendars); $i++){
	 echo "<input type=\"button\" id=\"button_" . $option_calendars[$i]["id"] . "\" value=\"" . $option_calendars[$i]["displayname"] . "\">";
}
?>
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
	function highlight_button(id){
		document.getElementById("button_" + id).style.color = "#000000";
	}
</script> 
