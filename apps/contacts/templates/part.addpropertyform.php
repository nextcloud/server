<li class="contacts_property_add">
	<form id="contacts_addpropertyform">
		<input type="hidden" name="id" value="<?php echo $_['id']; ?>">
		<p class="contacts_property_name">
			<select name="name" size="1">
				<option value="ADR"><?php echo $l->t('Address'); ?></option>
				<option value="TEL"><?php echo $l->t('Telephone'); ?></option>
				<option value="EMAIL" selected="selected"><?php echo $l->t('Email'); ?></option>
				<option value="ORG"><?php echo $l->t('Organization'); ?></option>
			</select>
		</p>
		<p class="contacts_property_data" id="contacts_generic">
			<input type="text" name="value" value="">
		</p><br>
		<input id="contacts_addproperty_button" type="submit">
	</form>
	<div id="contacts_addcontactsparts" style="display:none;">
		<ul class="contacts_property_data" id="contacts_addresspart">
			<li>
				<label for="adr_type"><?php echo $l->t('Type'); ?></label>
				<select id="adr_type" name="parameters[TYPE]" size="1">
					<option value="work"><?php echo $l->t('Work'); ?></option>
					<option value="home" selected="selected"><?php echo $l->t('Home'); ?></option>
				</select>
			</li>
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
		</ul>
		<p class="contacts_property_data" id="contacts_phonepart">
			<input type="text" name="value" value="">
			<select name="parameters[TYPE]" size="1">
				<option value="home"><?php echo $l->t('Home'); ?></option>
				<option value="cell" selected="selected"><?php echo $l->t('Mobile'); ?></option>
				<option value="work"><?php echo $l->t('Work'); ?></option>
				<option value="text"><?php echo $l->t('Text'); ?></option>
				<option value="voice"><?php echo $l->t('Voice'); ?></option>
				<option value="fax"><?php echo $l->t('Fax'); ?></option>
				<option value="video"><?php echo $l->t('Video'); ?></option>
				<option value="pager"><?php echo $l->t('Pager'); ?></option>
			</select>
		</p>
		<p class="contacts_property_data" id="contacts_generic">
			<input type="text" name="value" value="">
		</p>
	</div>
</li>
