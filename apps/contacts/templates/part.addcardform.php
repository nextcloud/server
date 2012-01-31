<?php
$l=new OC_L10N('contacts');
?>
<form id="contacts_addcardform">
	<?php if(count($_['addressbooks'])==1): ?>
		<input type="hidden" name="id" value="<?php echo $_['addressbooks'][0]['id']; ?>">
	<?php else: ?>
		<fieldset class="inputs">
			<dl class="form">
				<dt>
					<label for="id"><?php echo $l->t('Addressbook'); ?></label>
				</dt>
				<dd>
					<select name="id" size="1">
						<?php echo html_select_options($_['addressbooks'], null, array('value'=>'id', 'label'=>'displayname')); ?>
					</select>
				</dd>
			</dl>
		</fieldset>
	<?php endif; ?>
	<fieldset class="inputs">
		<dl class="form">
			<dt>
				<label for="n1"><?php echo $l->t('Given name'); ?></label>
			</dd>
			<dd>
				<input id="n1" type="text" name="value[N][1]" value="">
			</dd>
			<dt>
				<label for="n0"><?php echo $l->t('Family name'); ?></label>
			</dd>
			<dd>
				<input id="n0" type="text" name="value[N][0]" value="">
			</dd>
			<dt>
				<label for="n2"><?php echo $l->t('Additional names'); ?></label>
			</dd>
			<dd>
				<input id="n2" type="text" name="value[N][2]" value="">
				<input type="hidden" name="value[N][4]" value="">
				<input type="hidden" name="value[N][5]" value="">
			</dd>
		</dl>
	</fieldset>
	<fieldset class="inputs">
		<dl class="form">
			<dt>
				<label for="fn"><?php echo $l->t('Display name'); ?></label>
			</dd>
			<dd>
				<input id="fn" type="text" name="fn" placeholder="<?php echo $l->t('How you want the name displayed in the list'); ?>" value="">
			</dd>
			<dt>
				<label for="org"><?php echo $l->t('Organization'); ?></label>
			</dt>
			<dd>
				<input id="org" type="text" name="value[ORG]" value="">
			</dd>
		</dl>
	</fieldset>
	<fieldset class="inputs">
		<dl class="form">
			<dt>
				<label for="email"><?php echo $l->t('Email'); ?></label>
			</dt>
			<dd>
				<input id="email" type="email" name="value[EMAIL]" value="">
			</dd>
			<dt>
				<label for="tel"><?php echo $l->t('Telephone'); ?></label>
			</dt>
			<dd>
				<input type="tel" id="tel" name="value[TEL]" value="">
				<select id="TEL" name="parameters[TEL][TYPE][]" multiple="multiple">
					<?php echo html_select_options($_['phone_types'], 'CELL') ?>
				</select>
			</dd>
		</dl>
	</fieldset>
	<fieldset class="inputs">
		<legend><?php echo $l->t('Address'); ?></legend>
		<dl class="form">
			<dt>
				<label for="adr_type"><?php echo $l->t('Type'); ?></label>
			</dt>
			<dd>
				<select id="adr_type" name="parameters[ADR][TYPE]" size="1">
					<?php echo html_select_options($_['adr_types'], 'HOME') ?>
				</select>
			</dd>
			<dt>
				<label for="adr_pobox"><?php echo $l->t('PO Box'); ?></label>
			</dt>
			<dd>
				<input type="text" id="adr_pobox" name="value[ADR][0]" placeholder="<?php echo $l->t('Post Office box'); ?>" value="">
			</dd>
			<dd>
			<!-- dt>
				<label class="label" for="adr_extended"><?php echo $l->t('Extended'); ?></label>
			</dt>
			<dd>
				<input type="text" id="adr_extended" name="value[ADR][1]" value="">
			</dd -->
			<dt>
				<label for="adr_street"><?php echo $l->t('Street'); ?></label>
			</dt>
			<dd>
				<input style="width: 12em;" type="text" id="adr_street" name="value[ADR][2]" placeholder="<?php echo $l->t('Street name and no.'); ?>" value="">
				<label for="adr_extended"><?php echo $l->t('Extended'); ?></label>
				<input style="width: 7em;" type="text" id="adr_extended" name="value[ADR][1]" placeholder="<?php echo $l->t('Apart. no., floor'); ?>" value="">
			</dd>
			<dt>
				<label for="adr_city"><?php echo $l->t('City'); ?></label>
			</dt>
			<dd>
				<input style="width: 12em;" type="text" id="adr_city" name="value[ADR][3]" value="">
				<label for="adr_zipcode"><?php echo $l->t('Zipcode'); ?></label>
				<input style="width: 5em;" type="text" id="adr_zipcode" name="value[ADR][5]" value="">
			</dd>
			<dt>
				<label for="adr_region"><?php echo $l->t('Region'); ?></label>
			</dt>
			<dd>
				<input type="text" id="adr_region" name="value[ADR][4]" placeholder="<?php echo $l->t('E.g. state or province'); ?>" value="">
			</dd>
			<!-- dt>
				<label class="label" for="adr_zipcode"><?php echo $l->t('Zipcode'); ?></label>
			</dt>
			<dd>
				<input type="text" id="adr_zipcode" name="value[ADR][5]" value="">
			</dd -->
			<dt>
				<label for="adr_country"><?php echo $l->t('Country'); ?></label>
			</dt>
			<dd>
				<input type="text" id="adr_country" name="value[ADR][6]" value="">
			</dd>
		</dl>
	</fieldset>
	<input class="create" type="submit" name="submit" value="<?php echo $l->t('Create Contact'); ?>">
</form>
