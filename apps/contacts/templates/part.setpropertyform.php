	<form id="contacts_setpropertyform">
		<input type="hidden" name="checksum" value="<?php echo $_['property']['checksum']; ?>">
		<input type="hidden" name="id" value="<?php echo $_['id']; ?>">
		<?php if($_['property']['name']=='N'): ?>
			<p class="contacts_property_name">
			<dl class="contacts_property_data form">
				<dt><label for="n1"><?php echo $l->t('Given name'); ?></label></dt>
				<dd><input id="n1" type="text" name="value[1]" value="<?php echo $_['property']['value'][1]; ?>"></dd>
				<dt><label for="n0"><?php echo $l->t('Family name'); ?></dt>
				<dd><input id="n0" type="text" name="value[0]" value="<?php echo $_['property']['value'][0]; ?>"></dd>
				<dt><label for="n2"><?php echo $l->t('Additional names'); ?></dt>
				<dd><input id="n2" type="text" name="value[2]" value="<?php echo $_['property']['value'][2]; ?>">
				<input id="n3" type="hidden" name="value[3]" value="<?php echo $_['property']['value'][3]; ?>">
				<input id="n4" type="hidden" name="value[4]" value="<?php echo $_['property']['value'][4]; ?>">
				</dd>
			</dl>
			</p>
		<?php elseif($_['property']['name']=='FN'): ?>
			<p class="contacts_property_data"><input id="fn" type="text" name="value" value="<?php echo $_['property']['value']; ?>"></p>
		<?php elseif($_['property']['name']=='ADR'): ?>
			<p class="contacts_property_name"><label for="adr_pobox"><?php echo $l->t('Address'); ?></label></p>
			<dl class="contacts_property_data form" id="contacts_addresspart">
				<dt>
					<label class="label" for="adr_type"><?php echo $l->t('Type'); ?></label>
				</dt>
				<dd>
					<select id="adr_type" name="parameters[TYPE]" size="1">
						<?php echo html_select_options($_['adr_types'], strtoupper($_['property']['parameters']['TYPE'])) ?>
					</select>
				</dd>
				<dt>
					<label for="adr_pobox"><?php echo $l->t('PO Box'); ?></label>
				</dt>
				<dd>
					<input id="adr_pobox" type="text" name="value[0]" value="<?php echo $_['property']['value'][0] ?>">
				</dd>
				<!-- dt>
					<label for="adr_extended"><?php echo $l->t('Extended'); ?></label>
				</dt>
				<dd>
					<input style="width: 7em;" id="adr_extended" type="text" name="value[1]" value="<?php echo $_['property']['value'][1] ?>">
				</dd -->
				<dt>
					<label for="adr_street"><?php echo $l->t('Street'); ?></label>
				</dt>
				<dd>
					<input style="width: 12em;" id="adr_street" type="text" name="value[2]" value="<?php echo $_['property']['value'][2] ?>">
					<label for="adr_extended"><?php echo $l->t('Extended'); ?></label><input style="width: 7em;" id="adr_extended" type="text" name="value[1]" value="<?php echo $_['property']['value'][1] ?>">
				</dd>
				<dt>
					<label for="adr_city"><?php echo $l->t('City'); ?></label>
				</dt>
				<dd>
					<input style="width: 12em;" id="adr_city" type="text" name="value[3]" value="<?php echo $_['property']['value'][3] ?>">
					<label for="adr_zipcode"><?php echo $l->t('Zipcode'); ?></label>
					<input style="width: 5em;" id="adr_zipcode" type="text" name="value[5]" value="<?php echo $_['property']['value'][5] ?>">
				</dd>
				<dt>
					<label for="adr_region"><?php echo $l->t('Region'); ?></label>
				</dt>
				<dd>
					<input id="adr_region" type="text" name="value[4]" value="<?php echo $_['property']['value'][4] ?>">
				</dd>
				<!-- dt>
					<label for="adr_zipcode"><?php echo $l->t('Zipcode'); ?></label>
				</dt>
				<dd>
					<input style="width: 7em;" id="adr_zipcode" type="text" name="value[5]" value="<?php echo $_['property']['value'][5] ?>">
				</dd -->
				<dt>
					<label for="adr_country"><?php echo $l->t('Country'); ?></label>
				</dt>
				<dd>
					<input style="width: 25em;" id="adr_country" type="text" name="value[6]" value="<?php echo $_['property']['value'][6] ?>">
				</dd>
			</dl>
		<?php elseif($_['property']['name']=='TEL'): ?>
			<p class="contacts_property_name"><label for="tel"><?php echo $l->t('Phone'); ?></label></p>
			<p class="contacts_property_data"><input id="tel" type="phone" name="value" value="<?php echo $_['property']['value'] ?>">
				<select id="tel_type<?php echo $_['property']['checksum'] ?>" name="parameters[TYPE][]" multiple="multiple" data-placeholder="<?php echo $l->t('Type') ?>">
					<?php echo html_select_options($_['phone_types'], isset($_['property']['parameters']['TYPE'])?$_['property']['parameters']['TYPE']:array()) ?>
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
