	<table width="100%">
		<tr>
			<th width="75px"><?php echo $l->t("Title");?>:</th>
			<td>
			<input type="text" style="width:350px;" size="100" placeholder="<?php echo $l->t("Title of the Event");?>" value="<?php echo isset($_['title']) ? $_['title'] : '' ?>" maxlength="100" name="title"/>
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<th width="75px"><?php echo $l->t("Category");?>:</th>
			<td>
			<select id="category" name="categories[]" multiple="multiple" title="<?php echo $l->t("Select category") ?>">
				<?php
				foreach($_['category_options'] as $category){
					if (!isset($_['categories'])) {$_['categories'] = array();}
					echo '<option value="' . $category . '"' . (in_array($category, $_['categories']) ? ' selected="selected"' : '') . '>' . $category . '</option>';
				}
				?>
			</select></td>
			<th width="75px">&nbsp;&nbsp;&nbsp;<?php echo $l->t("Calendar");?>:</th>
			<td>
			<select style="width:140px;" name="calendar">
				<?php
				foreach($_['calendar_options'] as $calendar){
					if (!isset($_['calendar'])) {$_['calendar'] = false;}
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
			<input onclick="Calendar.UI.lockTime();" type="checkbox"<?php if($_['allday']){echo 'checked="checked"';} ?> id="allday_checkbox" name="allday">
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
		</tr>
	</table>
	<input type="button" class="submit" value="<?php echo $l->t("Advanced options"); ?>" onclick="Calendar.UI.showadvancedoptions();" id="advanced_options_button">
	<div id="advanced_options" style="display: none;">
	<!--
	<table>
		<tr>
			<th width="75px"><?php echo $l->t("Repeat");?>:</th>
			<td>
			<select name="repeat" style="width:350px;">
				<?php
				if (isset($_['repeat_options'])) {
					foreach($_['repeat_options'] as $id => $label){
						echo '<option value="' . $id . '"' . ($_['repeat'] == $id ? ' selected="selected"' : '') . '>' . $label . '</option>';
					}
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
			<th width="85px"><?php echo $l->t("Location");?>:</th>
			<td>
			<input type="text" style="width:350px;" size="100" placeholder="<?php echo $l->t("Location of the Event");?>" value="<?php echo isset($_['location']) ? $_['location'] : '' ?>" maxlength="100"  name="location" />
			</td>
		</tr>
	</table>
	<table>
		<tr>
			<th width="85px" style="vertical-align: top;"><?php echo $l->t("Description");?>:</th>
			<td><textarea style="width:350px;height: 150px;" placeholder="<?php echo $l->t("Description of the Event");?>" name="description"><?php echo isset($_['description']) ? $_['description'] : '' ?></textarea></td>
		</tr>
	</table>
	</div>
