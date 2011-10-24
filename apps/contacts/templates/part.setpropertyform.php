<li class="contacts_property_edit" data-checksum="<?php echo $_['property']['checksum']; ?>">
	<form id="contacts_setpropertyform">
		<input type="hidden" name="checksum" value="<?php echo $_['property']['checksum']; ?>">
		<input type="hidden" name="id" value="<?php echo $_['id']; ?>">
		<?php if($_['property']['name']=='ADR'): ?>
			<p class="contacts_property_name"><label for="adr_pobox"><?php echo $l->t('Address'); ?></label></p>
			<ol class="contacts_property_data" id="contacts_addresspart">
				<li>
					<label for="adr_pobox"><?php echo $l->t('PO Box'); ?></label>
					<input id="adr_pobox" type="text" name="value[0]" value="">
				</li>
				<li>
					<label for="adr_extended"><?php echo $l->t('Extended'); ?></label>
					<input id="adr_extended" type="text" name="value[1]" value="">
				</li>
				<li>
					<label for="adr_street"><?php echo $l->t('Street'); ?></label>
					<input id="adr_street" type="text" name="value[2]" value="">
				</li>
				<li>
					<label for="adr_city"><?php echo $l->t('City'); ?></label>
					<input id="adr_city" type="text" name="value[3]" value="">
				</li>
				<li>
					<label for="adr_region"><?php echo $l->t('Region'); ?></label>
					<input id="adr_region" type="text" name="value[4]" value="">
				</li>
				<li>
					<label for="adr_zipcode"><?php echo $l->t('Zipcode'); ?></label>
					<input id="adr_zipcode" type="text" name="value[5]" value="">
				</li>
				<li>
					<label for="adr_country"><?php echo $l->t('Country'); ?></label>
					<input id="adr_country" type="text" name="value[6]" value="">
				</li>
			</ol>
		<?php elseif($_['property']['name']=='TEL'): ?>
			<p class="contacts_property_name"><label for="tel"><?php echo $l->t('Address'); ?></label></p>
			<p class="contacts_property_data"><input id="tel" type="phone" name="value" value="<?php echo $_['property']['value']; ?>"></p>
		<?php elseif($_['property']['name']=='EMAIL'): ?>
			<p class="contacts_property_name"><label for="email"><?php echo $l->t('Email'); ?></label></p>
			<p class="contacts_property_data"><input id="email" type="text" name="value" value="<?php echo $_['property']['value']; ?>"></p>
		<?php elseif($_['property']['name']=='EMAIL'): ?>
			<p class="contacts_property_name"><label for="org"><?php echo $l->t('Organization'); ?></label></p>
			<p class="contacts_property_data"><input id="org" type="text" name="value" value="<?php echo $_['property']['value']; ?>"></p>
		<?php endif; ?>
		<input id="contacts_setproperty_button" type="submit" value="<?php echo $l->t('Edit'); ?>">
	</form>
</li>
