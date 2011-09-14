	<table width="100%">
		<tr>
			<th width="75px"><?php echo $l->t("Title");?>:</th>
			<td>
			<input type="text" style="width:350px;" size="100" placeholder="<?php echo $l->t("Title of the Event");?>"  maxlength="100" id="newevent_title"/>
			</td>
		</tr>
		<tr>
			<th width="75px"><?php echo $l->t("Location");?>:</th>
			<td>
			<input type="text" style="width:350px;" size="100" placeholder="<?php echo $l->t("Location of the Event");?>" maxlength="100"  id="newevent_location" />
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<th width="75px"><?php echo $l->t("Category");?>:</th>
			<td>
			<select class="formselect" id="formcategorie_select" style="width:140px;" id="newevent_cat">
				<option><?php echo $l->t("None"); ?></option>
				<option><?php echo $l->t("Birthday"); ?></option>
				<option><?php echo $l->t("Business"); ?></option>
				<option><?php echo $l->t("Call"); ?></option>
				<option><?php echo $l->t("Clients"); ?></option>
				<option><?php echo $l->t("Deliverer"); ?></option>
				<option><?php echo $l->t("Holidays"); ?></option>
				<option><?php echo $l->t("Ideas"); ?></option>
				<option><?php echo $l->t("Journey"); ?></option>
				<option><?php echo $l->t("Jubilee"); ?></option>
				<option><?php echo $l->t("Meeting"); ?></option>
				<option><?php echo $l->t("Other"); ?></option>
				<option><?php echo $l->t("Personal"); ?></option>
				<option><?php echo $l->t("Projects"); ?></option>
				<option><?php echo $l->t("Questions"); ?></option>
				<option><?php echo $l->t("Work"); ?></option>
			</select></td>
			<th width="75px">&nbsp;&nbsp;&nbsp;<?php echo $l->t("Calendar");?>:</th>
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
			<th width="75px"></th>
			<td>
			<input onclick="lock_time();" type="checkbox"<?php if($allday == true){echo "checked=\"checked\"";}  ?> id="newcalendar_allday_checkbox">
			<?php if($allday == true){echo "<script type=\"text/javascript\">document.getElementById(\"fromtime\").disabled = true;document.getElementById(\"totime\").disabled = true;document.getElementById(\"fromtime\").style.color = \"#A9A9A9\";document.getElementById(\"totime\").style.color = \"#A9A9A9\";</script>";}?>
			<label for="newcalendar_allday_checkbox"><?php echo $l->t("All Day Event");?></label></td>
		</tr>
		<tr>

			<th width="75px"><?php echo $l->t("From");?>:</th>
			<td>
			<input type="text" value="<?php echo $day . "-" . $month . "-" . $year;?>" id="from">
			&nbsp;&nbsp;
			<input type="time" value="<?php echo date("H:i");?>" id="fromtime">
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
			<th width="75px"><?php echo $l->t("To");?>:</th>
			<td>
			<input type="text" value="<?php echo $day . "-" . $month . "-" . $year;?>" id="to">
			&nbsp;&nbsp;
			<input type="time" value="<?php echo $time . ":" . $minutes;?>" id="totime">
			</td><!--use jquery-->
		</tr><!--
		<tr>
			<th width="75px"><?php echo $l->t("Repeat");?>:</th>
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
			<th width="75px"><?php echo $l->t("Attendees");?>:</th>
			<td style="height: 50px;"></td>
		</tr>
	</table>
	<hr>-->
	<table>
		<tr>
			<th width="75px" style="vertical-align: top;"><?php echo $l->t("Description");?>:</th>
			<td><textarea style="width:350px;height: 150px;"placeholder="<?php echo $l->t("Description of the Event");?>" id="description"></textarea></td>
		</tr>
	</table>
