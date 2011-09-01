 <div id="eventinfo" title="<?php echo $l -> t("Edit an event");?>">
	<table id="eventinfo_table" width="100%">
		<tr>
			<td width="75px"><?php echo $l -> t("Title");?>:</td>
			<td>
			</td>
		</tr>
		<tr>
			<td width="75px"><?php echo $l -> t("Location");?>:</td>
			<td>
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<td width="75px"><?php echo $l -> t("Category");?>:</td>
			<td></td>
			<td width="75px">&nbsp;&nbsp;&nbsp;<?php echo $l -> t("Calendar");?>:</td>
			<td></td>
		</tr>
	</table>
	<hr>
	<table>
		<tr>
			<td width="75px"></td>
			<td>
			<input type="checkbox" id="newcalendar_allday_checkbox"  disabled="true">
			<label for="newcalendar_allday_checkbox"><?php echo $l -> t("All Day Event");?></label></td>
		</tr>
		<tr>
			<td width="75px"><?php echo $l -> t("From");?>:</td>
			<td>
			&nbsp;&nbsp;
			</td><!--use jquery-->
		</tr>
		<tr>

			<td width="75px"><?php echo $l -> t("To");?>:</td>
			<td>
			&nbsp;&nbsp;
			</td><!--use jquery-->
		</tr>
		<tr>
			<td width="75px"><?php echo $l -> t("Repeat");?>:</td>
			<td></td>
		</tr>
	</table>
	<hr>
	<table>
		<tr>
			<td width="75px"><?php echo $l -> t("Attendees");?>:</td>
			<td style="height: 50px;"></td>
		</tr>
	</table>
	<hr>
	<table>
		<tr>
			<td width="75px" style="vertical-align: top;"><?php echo $l -> t("Description");?>:</td>
			<td></td>
		</tr>
	</table>
	<span id="editevent_actions">
		<input type="button" style="float: left;" value="<?php echo $l -> t("Close");?>">
		<input type="button" style="float: right;" value="<?php echo $l -> t("Edit");?>">
	</span>
</div>
<script type="text/javascript">
	$( "#eventinfo" ).dialog({
		width : 500,
		close : function() {
					oc_cal_opendialog = 0;
					var lastchild = document.getElementById("body-user").lastChild
					while(lastchild.id != "lightbox"){
						document.getElementById("body-user").removeChild(lastchild);
						lastchild = document.getElementById("body-user").lastChild;
					}
			},
		open : function(){alert("Doesn't work yet.");}
	});
	$( "#from" ).datepicker({
		dateFormat : 'dd-mm-yy'
	});
	$( "#to" ).datepicker({
		dateFormat : 'dd-mm-yy'
	});
	}
</script>