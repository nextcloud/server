<div id="importdialog" title="<?php echo $l->t("Import Ical File"); ?>">
<input type="hidden" id="filename" value="<?php echo $_GET["filename"];?>">
<input type="hidden" id="path" value="<?php echo $_GET["path"];?>">
<div id="first"><strong style="text-align: center;margin: 0 auto;"><?php echo $l->t("How to import the new calendar?");?></strong>
<br><br>
<input style="float: left;" type="button" value="<?php echo $l->t("Import into an existing calendar"); ?>" onclick="$('#first').css('display', 'none');$('#existingcal').css('display', 'block');">
<input style="float: right;" type="button" value="<?php echo $l->t("Import into a new calendar");?>" onclick="$('#first').css('display', 'none');$('#newcal').css('display', 'block');">
</div>
<div id="existingcal" style="display: none;">
<strong><?php echo $l->t("Please choose the calendar"); ?></strong><br><br>
<form id="inputradioform">
<?php
$calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser());
foreach($calendars as $calendar){
	echo '<input type="radio" style="width: 20px;" name="calendar" id="radio_' . $calendar["id"] . '" value="' . $calendar["id"] . '">' . $calendar["displayname"] . '<br>';
}
?>
</form>
<br><br>
<input type="button" value="<?php echo $l->t("Import");?>!" onclick="importcal('existing');">
<br><br>
<input type="button" value="<?php echo $l->t("Back");?>" onclick="$('#existingcal').css('display', 'none');$('#first').css('display', 'block');">
</div>
<div id="newcal" style="display: none;">
<strong><?php echo $l->t("Please fill out the form"); ?></strong>
<!-- modified part of part.editcalendar.php -->
<table width="100%" style="border: 0;">
<tr>
<th><?php echo $l->t('Displayname') ?></th>
<td>
<input id="displayname" type="text" value="">
</td>
</tr>
</table>
<!-- end of modified part -->
<br><br>
<input type="button" value="<?php echo $l->t("Import");?>!" onclick="importcal('new');">
<br><br>
<input type="button" value="<?php echo $l->t("Back");?>" onclick="$('#newcal').css('display', 'none');$('#first').css('display', 'block');">
</div>
</div>
<script type="text/javascript">
$("input:radio[name='calendar']:first").attr("checked","checked");
$("#importdialog").dialog({
	width : 500,
	close : function(event, ui) {
		$(this).dialog('destroy').remove();
		$("#importdialogholder").remove();
	}
});
function importcal(importtype){
	var path = $("#path").val();
	var file = $("#filename").val();
	if(importtype == "existing"){
		var calid = $("input:radio[name='calendar']:checked").val();
		$.getJSON(OC.filePath('calendar', '', 'import.php') + "?import=existing&calid=" + calid + "&path=" + path + "&file=" + file, function(){
			$("#importdialog").dialog('destroy').remove();
			$("#importdialogholder").remove();
		});
	}
	if(importtype == "new"){
		var calname = $("#displayname").val();
		$.post(OC.filePath('calendar', '', 'import.php'), {'import':'new', 'calname':calname, 'path':path, 'file':file}, function(){
			$("#importdialog").dialog('destroy').remove();
			$("#importdialogholder").remove();
		});
	}
}
</script>