<form id="contacts_addcardform">
	<?php if(count($_['addressbooks'])==1): ?>
		<input type="hidden" name="id" value="<?php echo $_['addressbooks'][0]['id']; ?>">
	<?php else: ?>
		<label for="id"><?php echo $l->t('Group'); ?></label>
		<select name="id" size="1">
			<?php foreach($_['addressbooks'] as $addressbook): ?>
				<option value="<?php echo $addressbook['id']; ?>"><?php echo $addressbook['displayname']; ?></option>
			<?php endforeach; ?>
		</select>
	<?php endif; ?>
	<label for="fn"><?php echo $l->t('Name'); ?></label>
	<input type="text" name="fn" value=""><br>
	<input type="submit" name="submit" value="<?php echo $l->t('Create Contact'); ?>">
</form>
