<form id="contacts_addpropertyform">
	<input type="hidden" name="id" value="<?php echo $_['id']; ?>">
	<select name="name" size="1">
		<option value="ADR"><?php echo $l->t('Address'); ?></option>
		<option value="TEL"><?php echo $l->t('Telephone'); ?></option>
		<option value="EMAIL" selected="selected"><?php echo $l->t('Email'); ?></option>
		<option value="ORG"><?php echo $l->t('Organization'); ?></option>
	</select>
	<div id="contacts_generic">
		<input type="text" name="value" value="">
	</div>
	<input type="submit">
</form>
<div id="contacts_addcontactsparts" style="display:none;">
	<div id="contacts_addresspart">
		<select name="parameters[TYPE]" size="1">
			<option value="adr_work"><?php echo $l->t('Work'); ?></option>
			<option value="adr_home" selected="selected"><?php echo $l->t('Home'); ?></option>
		</select>
		<?php echo $l->t('PO Box'); ?> <input type="text" name="value[0]" value="">
		<?php echo $l->t('Extended Address'); ?> <input type="text" name="value[1]" value="">
		<?php echo $l->t('Street Name'); ?> <input type="text" name="value[2]" value="">
		<?php echo $l->t('City'); ?> <input type="text" name="value[3]" value="">
		<?php echo $l->t('Region'); ?> <input type="text" name="value[4]" value="">
		<?php echo $l->t('Postal Code'); ?> <input type="text" name="value[5]" value="">
		<?php echo $l->t('Country'); ?> <input type="text" name="value[6]" value="">
	</div>
	<div id="contacts_phonepart">
		<select name="parameters[TYPE]" size="1">
			<option value="home"><?php echo $l->t('tel_home'); ?></option>
			<option value="cell" selected="selected"><?php echo $l->t('tel_cell'); ?></option>
			<option value="work"><?php echo $l->t('tel_work'); ?></option>
			<option value="text"><?php echo $l->t('tel_text'); ?></option>
			<option value="voice"><?php echo $l->t('tel_voice'); ?></option>
			<option value="fax"><?php echo $l->t('tel_fax'); ?></option>
			<option value="video"><?php echo $l->t('tel_video'); ?></option>
			<option value="pager"><?php echo $l->t('tel_pager'); ?></option>
		</select>
		<input type="text" name="value" value="">
	</div>
	<div id="contacts_generic">
		<input type="text" name="value" value="">
	</div>
</div>
