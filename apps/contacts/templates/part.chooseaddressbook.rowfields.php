<td width="20px">
	<input id="active_<?php echo $_['addressbook']["id"]; ?>" type="checkbox" onClick="Contacts.UI.Addressbooks.activation(this, <?php echo $_['addressbook']["id"]; ?>)" <?php echo (OC_Contacts_Addressbook::isActive($_['addressbook']["id"]) ? ' checked="checked"' : ''); ?>>
</td>
<td>
	<label for="active_<?php echo $_['addressbook']["id"]; ?>"><?php echo htmlspecialchars($_['addressbook']["displayname"]); ?></label>
</td>
<td width="20px">
	<a onclick="Contacts.UI.showCardDAVUrl('<?php echo OCP\USER::getUser(); ?>', '<?php echo rawurlencode($_['addressbook']["uri"]); ?>');" title="<?php echo $l->t("CardDav Link"); ?>" class="svg action globe"></a>
</td>
<td width="20px">
	<a href="<?php echo OCP\Util::linkTo('contacts', 'export.php'); ?>?bookid=<?php echo $_['addressbook']["id"]; ?>" title="<?php echo $l->t("Download"); ?>" class="svg action download"></a>
</td>
<td width="20px">
	<a title="<?php echo $l->t("Edit"); ?>" class="svg action edit" onclick="Contacts.UI.Addressbooks.editAddressbook(this, <?php echo $_['addressbook']["id"]; ?>);"></a>
</td>
<td width="20px">
	<a onclick="Contacts.UI.Addressbooks.deleteAddressbook(this, <?php echo $_['addressbook']["id"]; ?>);" title="<?php echo $l->t("Delete"); ?>" class="svg action delete"></a>
</td>
