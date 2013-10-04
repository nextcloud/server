<fieldset id="ldapWizard2">

	<div>
		<p>
			<?php p($l->t('Limit the access to ownCloud to users meetignthis criteria:'));?>
		</p>

		<p>
			<label for="ldap_userfilter_objectclass">
				<?php p($l->t('only those object classes:'));?>
			</label>

			<select id="ldap_userfilter_objectclass" multiple="multiple"
			 name="ldap_userfilter_objectclass"
			 data-default="<?php p($_['ldap_userfilter_objectclass_default']); ?>">
<!-- 				<option><?php p($l->t('Any'));?></option> -->
			</select>
		</p>

		<p>
			<label for="ldap_userfilter_groups">
				<?php p($l->t('only from those groups:'));?>
			</label>

			<select id="ldap_userfilter_groups" multiple="multiple"
			 name="ldap_userfilter_groups" class="lwautosave"
			 data-default="<?php p($_['ldap_userfilter_groups_default']); ?>">
<!-- 				<option value="TODOfillIn">TODO: fill in object classes via Ajax</option> -->
<!-- 				<option value="TODOfillIn2">22222</option> -->
			</select>
		</p>

		<p>
			<label><a>↓ <?php p($l->t('Edit raw filter instead'));?></a></label>
		</p>

		<p class="invisible">
			<input type="text" id="ldap_userlistfilter_raw" name="ldap_userlistfilter_raw"
			class="lwautosave"
			data-default="<?php p($_['ldap_userlistfilter_raw_default']); ?>"
			placeholder="<?php p($l->t('Raw LDAP filter'));?>"
			title="<?php p($l->t('The filter specifies which LDAP users shall have access to the ownCloud instance.'));?>"
			/>
		</p>

		<p>
			<div class="ldapWizardInfo invisible">&nbsp;</div>
		</p>
		<?php print_unescaped($_['wizardControls']); ?>
	</div>
</fieldset>