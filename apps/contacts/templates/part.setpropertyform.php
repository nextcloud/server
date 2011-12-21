	<form id="contacts_setpropertyform">
		<input type="hidden" name="checksum" value="<?php echo $_['property']['checksum']; ?>">
		<input type="hidden" name="id" value="<?php echo $_['id']; ?>">
		<?php if($_['property']['name']=='FN'): ?>
			<p class="contacts_property_data"><input id="fn" type="text" name="value" value="<?php echo $_['property']['value']; ?>"></p>
		<?php elseif($_['property']['name']=='ADR'): ?>
			<p class="contacts_property_name"><label for="adr_pobox"><?php echo $l->t('Address'); ?></label></p>
			<ol class="contacts_property_data" id="contacts_addresspart">
				<li class="input">
					<label class="label" for="adr_type"><?php echo $l->t('Type'); ?></label>
					<select id="adr_type" name="parameters[TYPE]" size="1">
						<?php echo html_select_options($_['adr_types'], strtoupper($_['property']['parameters']['TYPE'])) ?>
					</select>
				</li>
				<li>
					<label for="adr_pobox"><?php echo $l->t('PO Box'); ?></label>
					<input id="adr_pobox" type="text" name="value[0]" value="<?php echo $_['property']['value'][0] ?>">
				</li>
				<li>
					<label for="adr_extended"><?php echo $l->t('Extended'); ?></label>
					<input id="adr_extended" type="text" name="value[1]" value="<?php echo $_['property']['value'][1] ?>">
				</li>
				<li>
					<label for="adr_street"><?php echo $l->t('Street'); ?></label>
					<input id="adr_street" type="text" name="value[2]" value="<?php echo $_['property']['value'][2] ?>">
				</li>
				<li>
					<label for="adr_city"><?php echo $l->t('City'); ?></label>
					<input id="adr_city" type="text" name="value[3]" value="<?php echo $_['property']['value'][3] ?>">
				</li>
				<li>
					<label for="adr_region"><?php echo $l->t('Region'); ?></label>
					<input id="adr_region" type="text" name="value[4]" value="<?php echo $_['property']['value'][4] ?>">
				</li>
				<li>
					<label for="adr_zipcode"><?php echo $l->t('Zipcode'); ?></label>
					<input id="adr_zipcode" type="text" name="value[5]" value="<?php echo $_['property']['value'][5] ?>">
				</li>
				<li>
					<label for="adr_country"><?php echo $l->t('Country'); ?></label>
					<input id="adr_country" type="text" name="value[6]" value="<?php echo $_['property']['value'][6] ?>">
				</li>
			</ol>
		<?php elseif($_['property']['name']=='TEL'): ?>
			<p class="contacts_property_name"><label for="tel"><?php echo $l->t('Phone'); ?></label></p>
			<p class="contacts_property_data"><input id="tel" type="phone" name="value" value="<?php echo $_['property']['value'] ?>">
				<select id="tel_type<?php echo $_['property']['checksum'] ?>" name="parameters[TYPE][]" multiple="multiple" data-placeholder="<?php echo $l->t('Type') ?>">
					<?php echo html_select_options($_['phone_types'], isset($_['property']['parameters']['TYPE'])?$_['property']['parameters']['TYPE']:'') ?>
				</select></p>
		<?php elseif($_['property']['name']=='EMAIL'): ?>
			<p class="contacts_property_name"><label for="email"><?php echo $l->t('Email'); ?></label></p>
			<p class="contacts_property_data"><input id="email" type="text" name="value" value="<?php echo $_['property']['value']; ?>"></p>
		<?php elseif($_['property']['name']=='ORG'): ?>
			<p class="contacts_property_name"><label for="org"><?php echo $l->t('Organization'); ?></label></p>
			<p class="contacts_property_data"><input id="org" type="text" name="value" value="<?php echo $_['property']['value']; ?>"></p>
		<?php endif; ?>
		<input id="contacts_setproperty_button" type="submit" value="<?php echo $l->t('Update'); ?>">
	</form>
