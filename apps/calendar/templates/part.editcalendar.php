<td id="editcalendar_dialog" title="<?php echo $l->t("Edit calendar"); ?>" colspan="4">
<table width="100%" style="border: 0;">
<tr>
	<th><?php echo $l->t('Displayname') ?></th>
	<td>
		<input id="displayname_<?php echo $_['calendar']['id'] ?>" type="text" value="<?php echo $_['calendar']['displayname'] ?>">
	</td>
</tr>
<tr>
	<td></td>
	<td>
		<input id="active_<?php echo $_['calendar']['id'] ?>" type="checkbox"<?php echo ($_['calendar']['active'] ? ' checked="checked"' : '' ) ?>>
		<label for="active_<?php echo $_['calendar']['id'] ?>">
			<?php echo $l->t('Active') ?>
		</label>
	</td>
</tr>
<tr>
	<th><?php echo $l->t('Description') ?></th>
	<td>
		<textarea id="description_<?php echo $_['calendar']['id'] ?>"><?php echo $_['calendar']['description'] ?></textarea>
	</td>
</tr>
<tr>
	<th><?php echo $l->t('Calendar color') ?></th>
	<td>
		<input id="calendarcolor_<?php echo $_['calendar']['id'] ?>" type="text" value="<?php echo $_['calendar']['calendarcolor'] ?>">
	</td>
</tr>
</table>
<input style="float: left;" type="button" onclick="oc_cal_editcalendar_submit(this, <?php echo $_['calendar']['id'] ?>);" value="<?php echo $l->t("Submit"); ?>">
</td>
