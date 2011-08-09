<form id="contacts_setpropertyform">
	<input type="hidden" name="checksum" value="<?php echo $_['property']['checksum']; ?>">
	<input type="hidden" name="line" value="<?php echo $_['property']['line']; ?>">
	<input type="hidden" name="id" value="<?php echo $_['id']; ?>">
	<?php if($_['property']['name']=='ADR'): ?>
		<?php echo $l->t('PO Box'); ?> <input type="text" name="value[0]" value="<?php echo $_['property']['value'][0]; ?>">
		<?php echo $l->t('Extended Address'); ?> <input type="text" name="value[1]" value="<?php echo $_['property']['value'][1]; ?>">
		<?php echo $l->t('Street Name'); ?> <input type="text" name="value[2]" value="<?php echo $_['property']['value'][2]; ?>">
		<?php echo $l->t('City'); ?> <input type="text" name="value[3]" value="<?php echo $_['property']['value'][3]; ?>">
		<?php echo $l->t('Region'); ?> <input type="text" name="value[4]" value="<?php echo $_['property']['value'][4]; ?>">
		<?php echo $l->t('Postal Code'); ?> <input type="text" name="value[5]" value="<?php echo $_['property']['value'][5]; ?>">
		<?php echo $l->t('Country'); ?> <input type="text" name="value[6]" value="<?php echo $_['property']['value'][6]; ?>">
	<?php elseif($_['property']['name']=='TEL'): ?>
		<input type="text" name="value" value="<?php echo $_['property']['value']; ?>">
	<?php elseif($_['property']['name']=='NOTE'): ?>
		<textarea type="text" name="value"><?php echo $_['property']['value']; ?></textarea>
	<?php else: ?>
		<input type="text" name="value" value="<?php echo $_['property']['value']; ?>">
	<?php endif; ?>
	<input type="submit">
</form>
