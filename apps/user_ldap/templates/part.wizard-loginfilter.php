<fieldset id="ldapWizard3">
	<div>
		<p>
			<?php p($l->t('What attribute shall be used as login name:'));?>
		</p>
		<p>
			<label for="ldap_loginfilter_username">
				<?php p($l->t('LDAP Username:'));?>
			</label>

			<input type="checkbox" id="ldap_loginfilter_username"
			 name="ldap_loginfilter_username" value="1" class="lwautosave" />
		</p>
		<p>
			<label for="ldap_loginfilter_email">
				<?php p($l->t('LDAP Email Address:'));?>
			</label>

			<input type="checkbox" id="ldap_loginfilter_email"
			 name="ldap_loginfilter_email" value="1" class="lwautosave" />
		</p>
		<p>
			<label for="ldap_loginfilter_attributes">
				<?php p($l->t('Other Attributes:'));?>
			</label>

			<select id="ldap_loginfilter_attributes" multiple="multiple"
			 name="ldap_loginfilter_attributes">
			</select>
		</p>
		<p>
			<div class="ldapWizardInfo invisible">&nbsp;</div>
		</p>

		<?php print_unescaped($_['wizardControls']); ?>
	</div>
</fieldset>