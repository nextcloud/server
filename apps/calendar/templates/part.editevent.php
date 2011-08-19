<div id="editevent" title="<?php echo $l -> t("Edit an event");?>">
	<table id="editevent_table" width="100%">
		<tr>
			<td width="75px"><?php echo $l -> t("Title");?>:</td>
			<td>
			<input type="text" style="width:350px;" size="100" placeholder="Title of the Event"  maxlength="100" />
			</td>
		</tr>
		<tr>
			<td width="75px"><?php echo $l -> t("Location");?>:</td>
			<td>
			<input type="text" style="width:350px;" size="100" placeholder="Location of the Event"  maxlength="100" />
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<td width="75px"><?php echo $l -> t("Category");?>:</td>
			<td>
			<select class="formselect" id="formcategorie_select" style="width:140px;">
				<option>Coming soon</option><!--
				<option>Work</option>
				<option>Call</option>-->
			</select></td>
			<td width="75px">&nbsp;&nbsp;&nbsp;<?php echo $l -> t("Calendar");?>:</td>
			<td>
			<select class="formselect" id="formcalendar_select" style="width:140px;">
				<option>Coming soon</option><!--
				<option>Calendar 1</option>
				<option>Calendar 2</option>
				<option>Calendar 3</option>-->
			</select></td>
		</tr>
	</table>
	<hr>
	<table>
		<tr>
			<td width="75px"></td>
			<td>
			<input onclick="lock_time();" type="checkbox" id="newcalendar_allday_checkbox">
			<label for="newcalendar_allday_checkbox"><?php echo $l -> t("All Day Event");?></label></td>
		</tr>
		<tr>
			<?php $day = substr($_GET["d"], 0, 2);
			$month = substr($_GET["d"], 2, 2);
			$year = substr($_GET["d"], 4, 4);
			?>
			<td width="75px"><?php echo $l -> t("From");?>:</td>
			<td>
			<input type="text" value="<?php echo $day . "-" . $month . "-" . $year;?>" id="from">
			&nbsp;&nbsp;
			<input type="time" value="<?php echo date("H:i");?>" id="fromtime">
			</td><!--use jquery-->
		</tr>
		<tr>
			<?php
			if(date("H") == 23) {$day++;
				$time = 0;
			} else {$time = date("H") + 1;
			}
			?>
			<td width="75px"><?php echo $l -> t("To");?>:</td>
			<td>
			<input type="text" value="<?php echo $day . "-" . $month . "-" . $year;?>" id="to">
			&nbsp;&nbsp;
			<input type="time" value="<?php echo $time . date(":i");?>" id="totime">
			</td><!--use jquery-->
		</tr>
		<tr>
			<td width="75px"><?php echo $l -> t("Repeat");?>:</td>
			<td>
			<select class="formselect" id="formrepeat_select" style="width:350px;">
				<option id="doesnotrepeat" selected="selected">Does not repeat</option>
				<option>Daily</option>
				<option>Weekly</option>
				<option>Every Weekday</option>
				<option>Bi-Weekly</option>
				<option>Monthly</option>
				<option>Yearly</option>
			</select></td>
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
			<td>			<textarea style="width:350px;height: 150px;"placeholder="Description of the Event"></textarea></td>
		</tr>
	</table>
	<span id="editevent_actions">
		<input type="button" style="float: left;" value="<?php echo $l -> t("Submit");?>">
		<input type="button" style="float: right;" value="<?php echo $l -> t("Reset");?>">
	</span>
</div>
<script type="text/javascript">
	$( "#editevent" ).dialog({
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
	function lock_time() {
		if(document.getElementById("totime").disabled == true) {
			document.getElementById("fromtime").disabled = false;
			document.getElementById("totime").disabled = false;
			document.getElementById("fromtime").style.color = "#333";
			document.getElementById("totime").style.color = "#333";
		} else {
			document.getElementById("fromtime").disabled = true;
			document.getElementById("totime").disabled = true;
			document.getElementById("fromtime").style.color = "#A9A9A9";
			document.getElementById("totime").style.color = "#A9A9A9";
		}
	}
</script>