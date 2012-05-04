<div id="firstrun">
	<?php echo $l->t('You have no contacts in your addressbook.') ?>
	<div id="selections">
		<input type="button" value="<?php echo $l->t('Add contact') ?>" onclick="Contacts.UI.Card.editNew()" />
		<input type="button" value="<?php echo $l->t('Configure addressbooks') ?>" onclick="Contacts.UI.Addressbooks.overview()" />
	</div>
</div>
