<?php
/*************************************************
 * ownCloud - Calendar Plugin                     *
 *                                                *
 * (c) Copyright 2011 Bart Visscher               *
 * License: GNU AFFERO GENERAL PUBLIC LICENSE     *
 *                                                *
 * If you are not able to view the License,       *
 * <http://www.gnu.org/licenses/>                 *
 * please write to the Free Software Foundation.  *
 * Address:                                       *
 * 59 Temple Place, Suite 330, Boston,            *
 * MA 02111-1307  USA                             *
 *************************************************/
?>
<td id="<?php echo $_['new'] ? 'new' : 'edit' ?>calendar_dialog" title="<?php echo $_['new'] ? $l->t("New calendar") : $l->t("Edit calendar"); ?>" colspan="4">
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
		<input id="active_<?php echo $_['calendar']['id'] ?>" type="checkbox"<?php echo $_['calendar']['active'] ? ' checked="checked"' : '' ?>>
		<label for="active_<?php echo $_['calendar']['id'] ?>">
			<?php echo $l->t('Active') ?>
		</label>
	</td>
</tr>
<?php endif; ?>
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
<input style="float: left;" type="button" onclick="oc_cal_calendar_submit(this, <?php echo $_['new'] ? "'new'" : $_['calendar']['id'] ?>);" value="<?php echo $_['new'] ? $l->t("Save") : $l->t("Submit"); ?>">
<input style="float: left;" type="button" onclick="oc_cal_calendar_cancel(this, <?php echo $_['new'] ? "'new'" : $_['calendar']['id'] ?>);" value="<?php echo $l->t("Cancel"); ?>">
</td>
