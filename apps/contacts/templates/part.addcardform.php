<form class="formtastic" id="contacts_addcardform">
	<?php if(count($_['addressbooks'])==1): ?>
		<input type="hidden" name="id" value="<?php echo $_['addressbooks'][0]['id']; ?>">
	<?php else: ?>
		<fieldset class="inputs">
			<ol>
				<li class="input stringish">
					<label class="label" for="id"><?php echo $l->t('Group'); ?></label>
					<select name="id" size="1">
						<?php echo html_select_options($_['addressbooks'], null, array('value'=>'id', 'label'=>'displayname')); ?>
					</select>
				</li>
			</ol>
		</fieldset>
	<?php endif; ?>
	<fieldset class="inputs">
		<ol>
			<li class="input stringish">
				<label class="label" for="fn"><?php echo $l->t('Name'); ?></label>
				<input id="fn" type="text" name="fn" value=""><br>
			</li>
			<li class="input stringish">
				<label class="label" for="org"><?php echo $l->t('Organization'); ?></label>
				<input id="org" type="text" name="value[ORG]" value="">
			</li>
		</ol>
	</fieldset>
	<fieldset class="inputs">
		<ol>
			<li class="input stringish">
				<label class="label" for="email"><?php echo $l->t('Email'); ?></label>
				<input id="email" type="text" name="value[EMAIL]" value="">
			</li>
			<li class="input">
				<fieldset class="fragments">
					<legend class="label">
						<label for="tel"><?php echo $l->t('Telephone'); ?></label>
					</legend>
					<ol class="fragments-group">
						<li class="fragment">
							<label for="tel"><?php echo $l->t('Number'); ?></label>
							<input type="phone" id="tel" name="value[TEL]" value="">
						</li>
						<li class="fragment">
							<label for="tel_type"><?php echo $l->t('Type'); ?></label>
							<select id="TEL" name="parameters[TEL][TYPE]" size="1">
								<?php echo html_select_options($_['phone_types'], 'CELL') ?>
							</select>
						</li>
					</ol>
				</fieldset>
			</li>
		</ol>
	</fieldset>
	<fieldset class="inputs">
		<legend><?php echo $l->t('Address'); ?></legend>
		<ol>
			<li class="input">
				<label class="label" for="adr_type"><?php echo $l->t('Type'); ?></label>
				<select id="adr_type" name="parameters[ADR][TYPE]" size="1">
					<?php echo html_select_options($_['adr_types'], 'HOME') ?>
				</select>
			</li>
			<li class="input stringish">
				<label class="label" for="adr_pobox"><?php echo $l->t('PO Box'); ?></label>
				<input type="text" id="adr_pobox" name="value[ADR][0]" value="">
			</li>
			<li class="input stringish">
				<label class="label" for="adr_extended"><?php echo $l->t('Extended'); ?></label>
				<input type="text" id="adr_extended" name="value[ADR][1]" value="">
			</li>
			<li class="input stringish">
				<label class="label" for="adr_street"><?php echo $l->t('Street'); ?></label>
				<input type="text" id="adr_street" name="value[ADR][2]" value="">
			</li>
			<li class="input stringish">
				<label class="label" for="adr_city"><?php echo $l->t('City'); ?></label>
				<input type="text" id="adr_city" name="value[ADR][3]" value="">
			</li>
			<li class="input stringish">
				<label class="label" for="adr_region"><?php echo $l->t('Region'); ?></label>
				<input type="text" id="adr_region" name="value[ADR][4]" value="">
			</li>
			<li class="input stringish">
				<label class="label" for="adr_zipcode"><?php echo $l->t('Zipcode'); ?></label>
				<input type="text" id="adr_zipcode" name="value[ADR][5]" value="">
			</li>
			<li class="input stringish">
				<label class="label" for="adr_country"><?php echo $l->t('Country'); ?></label>
				<input type="text" id="adr_country" name="value[ADR][6]" value="">
			</li>
		</ol>
	</fieldset>
	<fieldset class="buttons">
		<ol>
			<li class="commit button">
				<input class="create" type="submit" name="submit" value="<?php echo $l->t('Create Contact'); ?>">
			</li>
		</ol>
	</fieldset>
</form>
