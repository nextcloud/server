<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
?>
<td id="<?php echo $_['new'] ? 'new' : 'edit' ?>calendar_dialog" title="<?php echo $_['new'] ? $l->t("New calendar") : $l->t("Edit calendar"); ?>" colspan="6">
<table width="100%" style="border: 0;">
<tr>
	<th><?php echo $l->t('Displayname') ?></th>
	<td>
		<input id="displayname_<?php echo $_['calendar']['id'] ?>" type="text" value="<?php echo $_['calendar']['displayname'] ?>">
	</td>
</tr>
<?php if (!$_['new']): ?>
<tr>
	<td></td>
	<td>
		<input id="edit_active_<?php echo $_['calendar']['id'] ?>" type="checkbox"<?php echo $_['calendar']['active'] ? ' checked="checked"' : '' ?>>
		<label for="edit_active_<?php echo $_['calendar']['id'] ?>">
			<?php echo $l->t('Active') ?>
		</label>
	</td>
</tr>
<?php endif; ?>
<tr>
	<th><?php echo $l->t('Calendar color') ?></th>
	<td>
		<select id="calendarcolor_<?php echo $_['calendar']['id'] ?>" class="colorpicker">
			<?php
			if (!isset($_['calendar']['calendarcolor'])) {$_['calendar']['calendarcolor'] = false;}
			foreach($_['calendarcolor_options'] as $color){
				echo '<option value="' . $color . '"' . ($_['calendar']['calendarcolor'] == $color ? ' selected="selected"' : '') . '>' . $color . '</option>';
			}
			?>
		</select>
	</td>
</tr>
</table>
<input style="float: left;" type="button" onclick="Calendar.UI.Calendar.submit(this, <?php echo $_['new'] ? "'new'" : $_['calendar']['id'] ?>);" value="<?php echo $_['new'] ? $l->t("Save") : $l->t("Submit"); ?>">
<input style="float: left;" type="button" onclick="Calendar.UI.Calendar.cancel(this, <?php echo $_['new'] ? "'new'" : $_['calendar']['id'] ?>);" value="<?php echo $l->t("Cancel"); ?>">
</td>
