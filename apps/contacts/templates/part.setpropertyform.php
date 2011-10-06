<form id="contacts_setpropertyform">
	<input type="hidden" name="checksum" value="<?php echo $_['property']['checksum']; ?>">
	<input type="hidden" name="id" value="<?php echo $_['id']; ?>">
	<?php if($_['property']['name']=='ADR'): ?>
		<label><?php echo $l->t('PO Box'); ?></label> <input type="text" name="value[0]" value="<?php echo $_['property']['value'][0]; ?>"><br>
		<label><?php echo $l->t('Extended'); ?></label> <input type="text" name="value[1]" value="<?php echo $_['property']['value'][1]; ?>"><br>
		<label><?php echo $l->t('Street'); ?></label> <input type="text" name="value[2]" value="<?php echo $_['property']['value'][2]; ?>"><br>
		<label><?php echo $l->t('City'); ?></label> <input type="text" name="value[3]" value="<?php echo $_['property']['value'][3]; ?>"><br>
		<label><?php echo $l->t('Region'); ?></label> <input type="text" name="value[4]" value="<?php echo $_['property']['value'][4]; ?>"><br>
		<label><?php echo $l->t('Zipcode'); ?></label> <input type="text" name="value[5]" value="<?php echo $_['property']['value'][5]; ?>"><br>
		<label><?php echo $l->t('Country'); ?></label> <input type="text" name="value[6]" value="<?php echo $_['property']['value'][6]; ?>"><br>
	<?php elseif($_['property']['name']=='TEL'): ?>
		<input type="text" name="value" value="<?php echo $_['property']['value']; ?>">
	<?php else: ?>
		<input type="text" name="value" value="<?php echo $_['property']['value']; ?>">
	<?php endif; ?>
	<input type="submit" value="<?php echo $l->t('Edit'); ?>">
</form>
