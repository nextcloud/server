<div id="selectaddressbook_dialog" title="<?php echo $l->t("Select Address Books"); ?>">
<form>
<select id="adr_type" name="parameters[ADR][TYPE]" size="1">
	<?php foreach($_['addressbooks'] as $addressbook) { ?>
	<option value="<?php echo $addressbook['id']; ?>"><?php echo $addressbook['name']; ?></option>
	<?php } ?>
</select>
</form>
</div>

