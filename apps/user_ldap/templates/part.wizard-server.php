<div class="outoftheway">
	<!-- Hack for Safari and Chromium/Chrome which ignore autocomplete="off" -->
	<input type="text" id="fake_user" name="fake_user" autocomplete="off" />
	<input type="password" id="fake_password" name="fake_password"
				autocomplete="off" />
</div>

<fieldset id="ldapWizard1">
		<p>
		<select id="ldap_serverconfig_chooser" name="ldap_serverconfig_chooser">
		<?php if(count($_['serverConfigurationPrefixes']) === 0 ) {
			?>
				<option value="" selected><?php p($l->t('1. Server'));?></option>');
			<?php
		} else {
			$i = 1;
			$sel = ' selected';
			foreach($_['serverConfigurationPrefixes'] as $prefix) {
				?>
				<option value="<?php p($prefix); ?>"<?php p($sel); $sel = ''; ?>><?php p($l->t('%s. Server:', array($i++)));?> <?php p(' '.$_['serverConfigurationHosts'][$prefix]); ?></option>
				<?php
			}
		}
		?>
		<option value="NEW"><?php p($l->t('Add Server Configuration'));?></option>
		</select>
		<button type="button" id="ldap_action_delete_configuration"
			name="ldap_action_delete_configuration"><?php p($l->t('Delete Configuration'));?></button>
		</p>

		<div class="hostPortCombinator">
			<div class="tablerow">
				<div class="tablecell">
					<div class="table">
						<input type="text" class="host tablecell lwautosave" id="ldap_host"
							name="ldap_host"
							placeholder="<?php p($l->t('Host'));?>"
							title="<?php p($l->t('You can omit the protocol, except you require SSL. Then start with ldaps://'));?>"
							/>
						<span>
							<input type="number" id="ldap_port" name="ldap_port"
								class="lwautosave"
								placeholder="<?php p($l->t('Port'));?>" />
						</span>
					</div>
				</div>
			</div>
			<div class="tablerow">
				<input type="text" id="ldap_dn" name="ldap_dn"
				class="tablecell lwautosave"
				placeholder="<?php p($l->t('User DN'));?>" autocomplete="off"
				title="<?php p($l->t('The DN of the client user with which the bind shall be done, e.g. uid=agent,dc=example,dc=com. For anonymous access, leave DN and Password empty.'));?>"
				/>
			</div>

			<div class="tablerow">
				<input type="password" id="ldap_agent_password"
				class="tablecell lwautosave" name="ldap_agent_password"
				placeholder="<?php p($l->t('Password'));?>" autocomplete="off"
				title="<?php p($l->t('For anonymous access, leave DN and Password empty.'));?>"
				/>
			</div>

			<div class="tablerow">
				<textarea id="ldap_base" name="ldap_base"
					class="tablecell lwautosave"
					placeholder="<?php p($l->t('One Base DN per line'));?>"
					title="<?php p($l->t('You can specify Base DN for users and groups in the Advanced tab'));?>">
				</textarea>
			</div>

			<div class="tablerow left">
				<input type="checkbox" id="ldap_experienced_admin" value="1"
					name="ldap_experienced_admin" class="tablecell lwautosave"
					title="<?php p($l->t('Avoids automatic LDAP requests. Better for bigger setups, but requires some LDAP knowledge.'));?>"
					/>
				<label for="ldap_experienced_admin" class="tablecell">
					<?php p($l->t('Manually enter LDAP filters (recommended for large directories)'));?>
				</label>
			</div>

			<div class="tablerow">
				<div class="tablecell ldapWizardInfo invisible">&nbsp;
				</div>
			</div>
		</div>
		<?php print_unescaped($_['wizardControls']); ?>
	</fieldset>
