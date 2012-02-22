<?php
/**
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
?>
<td id="<?php echo $_['new'] ? 'new' : 'edit' ?>addressbook_dialog" title="<?php echo $_['new'] ? $l->t("New Addressbook") : $l->t("Edit Addressbook"); ?>" colspan="6">
<table width="100%" style="border: 0;">
<tr>
	<th><?php echo $l->t('Displayname') ?></th>
	<td>
		<input id="displayname_<?php echo $_['addressbook']['id'] ?>" type="text" value="<?php echo htmlspecialchars($_['addressbook']['displayname']) ?>">
	</td>
</tr>
<?php if (!$_['new']): ?>
<tr>
	<td></td>
	<td>
		<input id="edit_active_<?php echo $_['addressbook']['id'] ?>" type="checkbox"<?php echo  OC_Contacts_Addressbook::isActive($_['addressbook']['id']) ? ' checked="checked"' : '' ?>>
		<label for="edit_active_<?php echo $_['addressbook']['id'] ?>">
			<?php echo $l->t('Active') ?>
		</label>
	</td>
</tr>
<?php endif; ?>
</table>
<input style="float: left;" type="button" onclick="Contacts.UI.Addressbooks.submit(this, <?php echo $_['new'] ? "'new'" : $_['addressbook']['id'] ?>);" value="<?php echo $_['new'] ? $l->t("Save") : $l->t("Submit"); ?>">
<input style="float: left;" type="button" onclick="Contacts.UI.Addressbooks.cancel(this, <?php echo $_['new'] ? "'new'" : $_['addressbook']['id'] ?>);" value="<?php echo $l->t("Cancel"); ?>">
</td>
