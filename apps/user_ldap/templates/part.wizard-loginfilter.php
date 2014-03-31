<fieldset id="ldapWizard3">
	<div>
		<p>
			<?php p($l->t('Users login with this attribute:'));?>
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
			<label><a id='toggleRawLoginFilter'>↓ <?php p($l->t('Edit raw filter instead'));?></a></label>
		</p>
		<p id="rawLoginFilterContainer" class="invisible">
			<input type="text" id="ldap_login_filter" name="ldap_login_filter"
				class="lwautosave"
				placeholder="<?php p($l->t('Raw LDAP filter'));?>"
				title="<?php p($l->t('Defines the filter to apply, when login is attempted. %%uid replaces the username in the login action. Example: "uid=%%uid"'));?>"
			/>
		</p>
		<p>
			<div class="ldapWizardInfo invisible">&nbsp;</div>
		</p>

		<?php print_unescaped($_['wizardControls']); ?>
	</div>
</fieldset>