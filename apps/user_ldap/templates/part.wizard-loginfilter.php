<fieldset id="ldapWizard3">
	<div>
		<p>
			<?php p($l->t('When logging in, %s will find the user based on the following attributes:', [$theme->getName()]));?>
		</p>
		<p>
			<label for="ldap_loginfilter_username">
				<?php p($l->t('LDAP/AD Username:'));?>
			</label>

			<input type="checkbox" id="ldap_loginfilter_username"
				   aria-describedby="ldap_loginfilter_username_instructions"
				   title="<?php p($l->t('Allows login against the LDAP/AD username, which is either "uid" or "sAMAccountName" and will be detected.'));?>"
				   name="ldap_loginfilter_username" value="1" />
			<p class="hidden-visually" id="ldap_loginfilter_username_instructions">
				<?php p($l->t('Allows login against the LDAP/AD username, which is either "uid" or "sAMAccountName" and will be detected.'));?>
			</p>
		</p>
		<p>
			<label for="ldap_loginfilter_email">
				<?php p($l->t('LDAP/AD Email Address:'));?>
			</label>

			<input type="checkbox" id="ldap_loginfilter_email"
				   title="<?php p($l->t('Allows login against an email attribute. "mail" and "mailPrimaryAddress" allowed.'));?>"
				   aria-describedby="ldap_loginfilter_email_instructions"
				   name="ldap_loginfilter_email" value="1" />
			<p class="hidden-visually" id="ldap_loginfilter_email_instructions">
				<?php p($l->t('Allows login against an email attribute. "mail" and "mailPrimaryAddress" allowed.'));?>
			</p>
		</p>
		<p>
			<label for="ldap_loginfilter_attributes">
				<?php p($l->t('Other Attributes:'));?>
			</label>

			<select id="ldap_loginfilter_attributes" multiple="multiple"
			 name="ldap_loginfilter_attributes" class="multiSelectPlugin">
			</select>
		</p>
		<p>
			<label><a id='toggleRawLoginFilter' class='ldapToggle'>↓ <?php p($l->t('Edit LDAP Query'));?></a></label>
		</p>
		<p id="ldapReadOnlyLoginFilterContainer" class="hidden ldapReadOnlyFilterContainer">
			<label><?php p($l->t('LDAP Filter:'));?></label>
			<span class="ldapFilterReadOnlyElement ldapInputColElement"></span>
		</p>
		<p id="rawLoginFilterContainer" class="invisible">
			<textarea type="text" id="ldap_login_filter" name="ldap_login_filter"
				class="ldapFilterInputElement"
				placeholder="<?php p($l->t('Edit LDAP Query'));?>"
				aria-describedby="ldap_login_filter_instructions"
				title="<?php p($l->t('Defines the filter to apply, when login is attempted. "%%uid" replaces the username in the login action. Example: "uid=%%uid"'));?>">
			</textarea>
		<p class="hidden-visually" id="ldap_login_filter_instructions">
			<?php p($l->t('Defines the filter to apply, when login is attempted. "%%uid" replaces the username in the login action. Example: "uid=%%uid"'));?>
		</p>
		</p>
		<p>
			<div class="ldapWizardInfo invisible">&nbsp;</div>
		</p>
		<p class="ldap_verify">
			<input type="text" id="ldap_test_loginname" name="ldap_test_loginname"
				   placeholder="<?php p($l->t('Test Loginname'));?>"
				   class="ldapVerifyInput"
				   aria-describedby="ldap_test_loginname_instructions"
				   title="<?php p($l->t('Attempts to receive a DN for the given loginname and the current login filter'));?>"/>
			<p class="hidden-visually" id="ldap_test_loginname_instructions">
				<?php p($l->t('Attempts to receive a DN for the given loginname and the current login filter'));?>
			</p>
			<button class="ldapVerifyLoginName" name="ldapTestLoginSettings" type="button" disabled="disabled">
				<?php p($l->t('Verify settings'));?>
			</button>
		</p>
		<?php print_unescaped($_['wizardControls']); ?>
	</div>
</fieldset>
