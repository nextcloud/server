<div id="chooseaddressbook_dialog" title="<?php echo $l->t("Configure Address Books"); ?>">
<table width="100%" style="border: 0;">
<?php
$option_addressbooks = OC_Contacts_Addressbook::all(OCP\USER::getUser());
for($i = 0; $i < count($option_addressbooks); $i++){
	echo "<tr>";
	$tmpl = new OCP\Template('contacts', 'part.chooseaddressbook.rowfields');
	$tmpl->assign('addressbook', $option_addressbooks[$i]);
	$tmpl->assign('active', OC_Contacts_Addressbook::isActive($option_addressbooks[$i]['id']));
	$tmpl->printpage();
	echo "</tr>";
}
?>
<tr>
	<td colspan="5" style="padding: 0.5em;">
		<a class="button" href="#" onclick="Contacts.UI.Addressbooks.newAddressbook(this);"><?php echo $l->t('New Address Book') ?></a>
		<a class="button" href="#" onclick="Contacts.UI.Addressbooks.importAddressbook(this);"><?php echo $l->t('Import from VCF') ?></a>
	</td>
</tr>
<tr>
	<td colspan="5">
		<p style="margin: 0 auto;width: 90%;"><input style="display:none;width: 90%;float: left;" type="text" id="carddav_url" onmouseover="$('#carddav_url').select();" title="<?php echo $l->t("CardDav Link"); ?>"><a class="action delete" id="carddav_url_close" style="height: 20px;vertical-align: middle;display: none;" title="close" onclick="$('#carddav_url').hide();$('#carddav_url_close').hide();"/></a></p>
	</td>
</tr>
</table>
