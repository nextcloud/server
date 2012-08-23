<div id="appsettings" class="popup bottomleft hidden"></div>
<div id="firstrun">
	<?php echo $l->t('You have no contacts in your addressbook.') ?>
	<div id="selections">
		<input type="button" value="<?php echo $l->t('Add contact') ?>" onclick="OC.Contacts.Card.editNew()" />
	</div>
</div>
