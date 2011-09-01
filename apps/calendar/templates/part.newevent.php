<div id="newevent" title="<?php echo $l -> t("Create a new event");?>">
	<form id="newevent_form">
	<table id="newevent_table" width="100%">
		<tr>
			<td width="75px"><?php echo $l -> t("Title");?>:</td>
			<td>
			<input type="text" style="width:350px;" size="100" placeholder="<?php echo $l -> t("Title of the Event");?>"  maxlength="100" id="newevent_title"/>
			</td>
		</tr>
		<tr>
			<td width="75px"><?php echo $l -> t("Location");?>:</td>
			<td>
			<input type="text" style="width:350px;" size="100" placeholder="<?php echo $l -> t("Location of the Event");?>" maxlength="100"  id="newevent_location" />
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<td width="75px"><?php echo $l -> t("Category");?>:</td>
			<td>
			<select class="formselect" id="formcategorie_select" style="width:140px;" id="newevent_cat">
				<option>Coming soon</option>
				<option>Work</option>
				<option>Call</option>
			</select></td>
			<td width="75px">&nbsp;&nbsp;&nbsp;<?php echo $l -> t("Calendar");?>:</td>
			<td>
			<select class="formselect" id="formcalendar_select" style="width:140px;" id="newevent_cal">
				<?php
				$option_calendars = OC_Calendar_Calendar::allCalendars(OC_User::getUser());
				for($i = 0; $i < count($option_calendars); $i++){
					echo "<option id=\"option_" . $option_calendars[$i]["id"] . "\">" . $option_calendars[$i]["displayname"] . "</option>";
				}
				?>
			</select></td>
		</tr>
	</table>
	<hr>
				<?php $day = substr($_GET["d"], 0, 2);
			$month = substr($_GET["d"], 2, 2);
			$year = substr($_GET["d"], 4, 4);
			$time = $_GET["t"];
			if($time != "undefined" && !is_nan($_GET["t"]) && $_GET["t"] != "allday"){
				$time = $_GET["t"];
				$minutes = "00";
			}elseif($_GET["t"] == "allday"){
				$time = "0";
				$minutes = "00";
				$allday = true;
			}else{
				$time = date("H");
				$minutes = date("i");
			}
			?>
	<table>
		<tr>
			<td width="75px"></td>
			<td>
			<input onclick="lock_time();" type="checkbox"<?php if($allday == true){echo "checked=\"checked\"";}  ?> id="newcalendar_allday_checkbox">
			<?php if($allday == true){echo "<script type=\"text/javascript\">document.getElementById(\"fromtime\").disabled = true;document.getElementById(\"totime\").disabled = true;document.getElementById(\"fromtime\").style.color = \"#A9A9A9\";document.getElementById(\"totime\").style.color = \"#A9A9A9\";</script>";}?>
			<label for="newcalendar_allday_checkbox"><?php echo $l -> t("All Day Event");?></label></td>
		</tr>
		<tr>

			<td width="75px"><?php echo $l -> t("From");?>:</td>
			<td>
			<input type="text" value="<?php echo $day . "-" . $month . "-" . $year;?>" id="from">
			&nbsp;&nbsp;
			<input type="time" value="<?php echo $time . ":" . $minutes;?>" id="fromtime">
			</td><!--use jquery-->
		</tr>
		<tr>
			<?php
			if(date("H") == 23) {
				$day++;
				$time = 0;
			} else {
				$time++;
			}
			?>
			<td width="75px"><?php echo $l -> t("To");?>:</td>
			<td>
			<input type="text" value="<?php echo $day . "-" . $month . "-" . $year;?>" id="to">
			&nbsp;&nbsp;
			<input type="time" value="<?php echo $time . ":" . $minutes;?>" id="totime">
			</td><!--use jquery-->
		</tr><!--
		<tr>
			<td width="75px"><?php echo $l -> t("Repeat");?>:</td>
			<td>
			<select class="formselect" id="formrepeat_select" style="width:350px;">
				<option id="repeat_doesnotrepeat" selected="selected"><?php echo $l->t("Does not repeat");?></option>
				<option id="repeat_daily"><?php echo $l->t("Daily");?></option>
				<option id="repeat_weekly"><?php echo $l->t("Weekly");?></option>
				<option id="repeat_weekday"><?php echo $l->t("Every Weekday");?></option>
				<option id="repeat_biweekly"><?php echo $l->t("Bi-Weekly");?></option>
				<option id="repeat_monthly"><?php echo $l->t("Monthly");?></option>
				<option id="repeat_yearly"><?php echo $l->t("Yearly");?></option>
			</select></td>
		</tr>-->
	</table>
	<hr>
	<table><!--
		<tr>
			<td width="75px"><?php echo $l -> t("Attendees");?>:</td>
			<td style="height: 50px;"></td>
		</tr>
	</table>
	<hr>-->
	<table>
		<tr>
			<td width="75px" style="vertical-align: top;"><?php echo $l -> t("Description");?>:</td>
			<td><textarea style="width:350px;height: 150px;"placeholder="<?php echo $l->t("Description of the Event");?>" id="description"></textarea></td>
		</tr>
	</table>
	<div style="width: 100%;text-align: center;color: #FF1D1D;" id="errorbox"></div>
	<span id="newcalendar_actions">
		<input type="button" class="submit" style="float: left;" value="<?php echo $l -> t("Submit");?>" onclick="validate_newevent_form();">
	</span>
	</form>
</div>
<script type="text/javascript">
	$("#newevent").dialog({
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
	function validate_newevent_form(){
		var newevent_title = document.getElementById("newevent_title").value;
		var newevent_location = document.getElementById("newevent_location").value;
		var newevent_cat = document.getElementById("formcategorie_select").options[document.getElementById("formcategorie_select").selectedIndex].value;
		var newevent_cal = document.getElementById("formcalendar_select").options[document.getElementById("formcalendar_select").selectedIndex].id;
		var newevent_allday = document.getElementById("newcalendar_allday_checkbox").checked;
		var newevent_from = document.getElementById("from").value;
		var newevent_fromtime = document.getElementById("fromtime").value;
		var newevent_to = document.getElementById("to").value;
		var newevent_totime = document.getElementById("totime").value;
		var newevent_description = document.getElementById("description").value;
		$.post("ajax/newevent.php", { title: newevent_title, location: newevent_location, cat: newevent_cat, cal: newevent_cal, allday: newevent_allday, from: newevent_from, fromtime: newevent_fromtime, to: newevent_to, totime: newevent_totime, description: newevent_description},
			function(data){
				if(data.error == "true"){
					document.getElementById("errorbox").innerHTML = "";
					var output = "Missing fields: <br />";
					if(data.title == "true"){
						output = output + "Title<br />";
					}
					if(data.cal == "true"){
						output = output + "Calendar<br />";
					}
					if(data.from == "true"){
						output = output + "From Date<br />";
					}
					if(data.fromtime == "true"){
						output = output + "From Time<br />";
					}
					if(data.to == "true"){
						output = output + "To Date<br />";
					}
					if(data.totime == "true"){
						output = output + "To Time<br />";
					}
					if(data.endbeforestart == "true"){
						output = "The event ends before it starts!";
					}
					if(data.dberror == "true"){
						output = "There was a database fail!";
					}
					document.getElementById("errorbox").innerHTML = output;
				}else{
					window.location.reload();
				}
				if(data.success == true){
					location.reload();
				}
			},"json");
	}
</script>