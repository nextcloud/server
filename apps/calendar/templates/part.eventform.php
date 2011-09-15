	<table width="100%">
		<tr>
			<th width="75px"><?php echo $l->t("Title");?>:</th>
			<td>
			<input type="text" style="width:350px;" size="100" placeholder="<?php echo $l->t("Title of the Event");?>" value="<?php echo $_['title'] ?>" maxlength="100" name="title"/>
			</td>
		</tr>
		<tr>
			<th width="75px"><?php echo $l->t("Location");?>:</th>
			<td>
			<input type="text" style="width:350px;" size="100" placeholder="<?php echo $l->t("Location of the Event");?>" value="<?php echo $_['location'] ?>" maxlength="100"  name="location" />
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<th width="75px"><?php echo $l->t("Category");?>:</th>
			<td>
			<select style="width:140px;" name="category">
				<?php
				foreach($_['categories'] as $category){
					echo '<option value="' . $category . '"' . ($_['category'] == $category ? ' selected="selected"' : '') . '>' . $category . '</option>';
				}
				?>
			</select></td>
			<th width="75px">&nbsp;&nbsp;&nbsp;<?php echo $l->t("Calendar");?>:</th>
			<td>
			<select style="width:140px;" name="calendar">
				<?php
				foreach($_['calendars'] as $calendar){
					echo '<option value="' . $calendar['id'] . '"' . ($_['calendar'] == $calendar['id'] ? ' selected="selected"' : '') . '>' . $calendar['displayname'] . '</option>';
				}
				?>
			</select></td>
		</tr>
	</table>
	<hr>
	<table>
		<tr>
			<th width="75px"></th>
			<td>
			<input onclick="lock_time();" type="checkbox"<?php if($_['allday']){echo 'checked="checked"';} ?> id="allday_checkbox" name="allday">
			<?php if($_['allday']){echo '<script type="text/javascript">document.getElementById("fromtime").disabled = true;document.getElementById("totime").disabled = true;document.getElementById("fromtime").style.color = "#A9A9A9";document.getElementById("totime").style.color = "#A9A9A9";</script>';}?>
			<label for="allday_checkbox"><?php echo $l->t("All Day Event");?></label></td>
		</tr>
		<tr>

			<th width="75px"><?php echo $l->t("From");?>:</th>
			<td>
			<input type="text" value="<?php echo $_['startdate'];?>" name="from" id="from">
			&nbsp;&nbsp;
			<input type="time" value="<?php echo $_['starttime'];?>" name="fromtime" id="fromtime">
			</td><!--use jquery-->
		</tr>
		<tr>
			<th width="75px"><?php echo $l->t("To");?>:</th>
			<td>
			<input type="text" value="<?php echo $_['enddate'];?>" name="to" id="to">
			&nbsp;&nbsp;
			<input type="time" value="<?php echo $_['endtime'];?>" name="totime" id="totime">
			</td><!--use jquery-->
		</tr><!--
		<tr>
			<th width="75px"><?php echo $l->t("Repeat");?>:</th>
			<td>
			<select name="repeat" style="width:350px;">
				<?php
				foreach($_['repeat_options'] as $id => $label){
					echo '<option value="' . $id . '"' . ($_['repeat'] == $id ? ' selected="selected"' : '') . '>' . $label . '</option>';
				}
				?>
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
			<td><textarea style="width:350px;height: 150px;" placeholder="<?php echo $l->t("Description of the Event");?>" name="description"><?php echo $_['description'] ?></textarea></td>
		</tr>
	</table>
