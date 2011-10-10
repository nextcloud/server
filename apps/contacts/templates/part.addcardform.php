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
	<label for="ADR"><?php echo $l->t('Address'); ?></label>
	<div id="contacts_addresspart">
		<select id="ADR" name="parameters[ADR][TYPE]" size="1">
			<option value="adr_work"><?php echo $l->t('Work'); ?></option>
			<option value="adr_home" selected="selected"><?php echo $l->t('Home'); ?></option>
		</select>
		<p><label><?php echo $l->t('PO Box'); ?></label> <input type="text" name="value[ADR][0]" value=""></p>
		<p><label><?php echo $l->t('Extended'); ?></label> <input type="text" name="value[ADR][1]" value=""></p>
		<p><label><?php echo $l->t('Street'); ?></label> <input type="text" name="value[ADR][2]" value=""></p>
		<p><label><?php echo $l->t('City'); ?></label> <input type="text" name="value[ADR][3]" value=""></p>
		<p><label><?php echo $l->t('Region'); ?></label> <input type="text" name="value[ADR][4]" value=""></p>
		<p><label><?php echo $l->t('Zipcode'); ?></label> <input type="text" name="value[ADR][5]" value=""></p>
		<p><label><?php echo $l->t('Country'); ?></label> <input type="text" name="value[ADR][6]" value=""></p>
	</div>
	<label for="TEL"><?php echo $l->t('Telephone'); ?></label>
	<div id="contacts_phonepart">
		<select id="TEL" name="parameters[TEL][TYPE]" size="1">
			<option value="home"><?php echo $l->t('Home'); ?></option>
			<option value="cell" selected="selected"><?php echo $l->t('Mobile'); ?></option>
			<option value="work"><?php echo $l->t('Work'); ?></option>
			<option value="text"><?php echo $l->t('Text'); ?></option>
			<option value="voice"><?php echo $l->t('Voice'); ?></option>
			<option value="fax"><?php echo $l->t('Fax'); ?></option>
			<option value="video"><?php echo $l->t('Video'); ?></option>
			<option value="pager"><?php echo $l->t('Pager'); ?></option>
		</select>
		<input type="text" name="value[TEL]" value="">
	</div>
	<label for="EMAIL"><?php echo $l->t('Email'); ?></label>
	<div id="contacts_email">
		<input id="EMAIL" type="text" name="value[EMAIL]" value="">
	</div>
	<label for="ORG"><?php echo $l->t('Organization'); ?></label>
	<div id="contacts_organisation">
		<input id="ORG" type="text" name="value[ORG]" value="">
	</div>
	<input type="submit" name="submit" value="<?php echo $l->t('Create Contact'); ?>">
</form>
