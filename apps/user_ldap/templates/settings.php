<form id="ldap" action="#" method="post">
	<div id="ldapSettings" class="personalblock">
	<ul>
		<li><a href="#ldapSettings-1">LDAP Basic</a></li>
		<li><a href="#ldapSettings-2">Advanced</a></li>
	</ul>
		<?php if(OCP\App::isEnabled('user_webdavauth')) {
			echo '<p class="ldapwarning">'.$l->t('<b>Warning:</b> Apps user_ldap and user_webdavauth are incompatible.'
				.' You may experience unexpected behaviour.'
				.' Please ask your system administrator to disable one of them.').'</p>';
		}
		if(!function_exists('ldap_connect')) {
			echo '<p class="ldapwarning">'.$l->t('<b>Warning:</b> The PHP LDAP module is not installed,'
				.' the backend will not work. Please ask your system administrator to install it.').'</p>';
		}
		?>
	<fieldset id="ldapSettings-1">
		<p><label for="ldap_serverconfig_chooser"><?php echo $l->t('Server configuration');?></label>
		<select id="ldap_serverconfig_chooser" name="ldap_serverconfig_chooser">
		<?php echo $_['serverConfigurationOptions']; ?>
		<option value="NEW"><?php echo $l->t('Add Server Configuration');?></option>
		</select>
		<button id="ldap_action_delete_configuration"
			name="ldap_action_delete_configuration">Delete Configuration</button>
		</p>
		<p><label for="ldap_host"><?php echo $l->t('Host');?></label>
		<input type="text" id="ldap_host" name="ldap_host" data-default="<?php echo $_['ldap_host_default']; ?>"
			title="<?php echo $l->t('You can omit the protocol, except you require SSL.'
				.' Then start with ldaps://');?>"></p>
		<p><label for="ldap_base"><?php echo $l->t('Base DN');?></label>
		<textarea id="ldap_base" name="ldap_base" placeholder="<?php echo $l->t('One Base DN per line');?>"
			title="<?php echo $l->t('You can specify Base DN for users and groups in the Advanced tab');?>"
			data-default="<?php echo $_['ldap_base_default']; ?>" ></textarea></p>
		<p><label for="ldap_dn"><?php echo $l->t('User DN');?></label>
		<input type="text" id="ldap_dn" name="ldap_dn" data-default="<?php echo $_['ldap_dn_default']; ?>"
			title="<?php echo $l->t('The DN of the client user with which the bind shall be done,'
				.' e.g. uid=agent,dc=example,dc=com. For anonymous access, leave DN and Password empty.');?>" /></p>
		<p><label for="ldap_agent_password"><?php echo $l->t('Password');?></label>
		<input type="password" id="ldap_agent_password" name="ldap_agent_password"
			data-default="<?php echo $_['ldap_agent_password_default']; ?>"
			title="<?php echo $l->t('For anonymous access, leave DN and Password empty.');?>" /></p>
		<p><label for="ldap_login_filter"><?php echo $l->t('User Login Filter');?></label>
		<input type="text" id="ldap_login_filter" name="ldap_login_filter"
			data-default="<?php echo $_['ldap_login_filter_default']; ?>"
			title="<?php echo $l->t('Defines the filter to apply, when login is attempted.'
				.' %%uid replaces the username in the login action.');?>" />
				<br /><small><?php echo $l->t('use %%uid placeholder, e.g. "uid=%%uid"');?></small></p>
		<p><label for="ldap_userlist_filter"><?php echo $l->t('User List Filter');?></label>
		<input type="text" id="ldap_userlist_filter" name="ldap_userlist_filter"
			data-default="<?php echo $_['ldap_userlist_filter_default']; ?>"
			title="<?php echo $l->t('Defines the filter to apply, when retrieving users.');?>" />
			<br /><small><?php echo $l->t('without any placeholder, e.g. "objectClass=person".');?></small></p>
		<p><label for="ldap_group_filter"><?php echo $l->t('Group Filter');?></label>
		<input type="text" id="ldap_group_filter" name="ldap_group_filter"
			data-default="<?php echo $_['ldap_group_filter_default']; ?>"
			title="<?php echo $l->t('Defines the filter to apply, when retrieving groups.');?>" />
			<br /><small><?php echo $l->t('without any placeholder, e.g. "objectClass=posixGroup".');?></small></p>
	</fieldset>
	<fieldset id="ldapSettings-2">
		<div id="ldapAdvancedAccordion">
			<h3><?php echo $l->t('Connection Settings');?></h3>
			<div>
				<p><label for="ldap_configuration_active"><?php echo $l->t('Configuration Active');?></label><input type="checkbox" id="ldap_configuration_active" name="ldap_configuration_active" value="1" data-default="<?php echo $_['ldap_configuration_active_default']; ?>"  title="<?php echo $l->t('When unchecked, this configuration will be skipped.');?>" /></p>
				<p><label for="ldap_port"><?php echo $l->t('Port');?></label><input type="number" id="ldap_port" name="ldap_port" data-default="<?php echo $_['ldap_port_default']; ?>"  /></p>
				<p><label for="ldap_backup_host"><?php echo $l->t('Backup (Replica) Host');?></label><input type="text" id="ldap_backup_host" name="ldap_backup_host" data-default="<?php echo $_['ldap_backup_host_default']; ?>" title="<?php echo $l->t('Give an optional backup host. It must be a replica of the main LDAP/AD server.');?>"></p>
				<p><label for="ldap_backup_port"><?php echo $l->t('Backup (Replica) Port');?></label><input type="number" id="ldap_backup_port" name="ldap_backup_port" data-default="<?php echo $_['ldap_backup_port_default']; ?>"  /></p>
				<p><label for="ldap_override_main_server"><?php echo $l->t('Disable Main Server');?></label><input type="checkbox" id="ldap_override_main_server" name="ldap_override_main_server" value="1" data-default="<?php echo $_['ldap_override_main_server_default']; ?>"  title="<?php echo $l->t('When switched on, ownCloud will only connect to the replica server.');?>" /></p>
				<p><label for="ldap_tls"><?php echo $l->t('Use TLS');?></label><input type="checkbox" id="ldap_tls" name="ldap_tls" value="1" data-default="<?php echo $_['ldap_tls_default']; ?>" title="<?php echo $l->t('Do not use it additionally for LDAPS connections, it will fail.');?>" /></p>
				<p><label for="ldap_nocase"><?php echo $l->t('Case insensitve LDAP server (Windows)');?></label><input type="checkbox" id="ldap_nocase" name="ldap_nocase" data-default="<?php echo $_['ldap_nocase_default']; ?>"  value="1"<?php if (isset($_['ldap_nocase']) && ($_['ldap_nocase'])) echo ' checked'; ?>></p>
				<p><label for="ldap_turn_off_cert_check"><?php echo $l->t('Turn off SSL certificate validation.');?></label><input type="checkbox" id="ldap_turn_off_cert_check" name="ldap_turn_off_cert_check" title="<?php echo $l->t('If connection only works with this option, import the LDAP server\'s SSL certificate in your ownCloud server.');?>" data-default="<?php echo $_['ldap_turn_off_cert_check_default']; ?>" value="1"><br/><small><?php echo $l->t('Not recommended, use for testing only.');?></small></p>
				<p><label for="ldap_cache_ttl">Cache Time-To-Live</label><input type="number" id="ldap_cache_ttl" name="ldap_cache_ttl" title="<?php echo $l->t('in seconds. A change empties the cache.');?>" data-default="<?php echo $_['ldap_cache_ttl_default']; ?>" /></p>
			</div>
			<h3><?php echo $l->t('Directory Settings');?></h3>
			<div>
				<p><label for="ldap_display_name"><?php echo $l->t('User Display Name Field');?></label><input type="text" id="ldap_display_name" name="ldap_display_name" data-default="<?php echo $_['ldap_display_name_default']; ?>" title="<?php echo $l->t('The LDAP attribute to use to generate the user`s ownCloud name.');?>" /></p>
				<p><label for="ldap_base_users"><?php echo $l->t('Base User Tree');?></label><textarea id="ldap_base_users" name="ldap_base_users" placeholder="<?php echo $l->t('One User Base DN per line');?>" data-default="<?php echo $_['ldap_base_users_default']; ?>" title="<?php echo $l->t('Base User Tree');?>"></textarea></p>
				<p><label for="ldap_attributes_for_user_search"><?php echo $l->t('User Search Attributes');?></label><textarea id="ldap_attributes_for_user_search" name="ldap_attributes_for_user_search" placeholder="<?php echo $l->t('Optional; one attribute per line');?>" data-default="<?php echo $_['ldap_attributes_for_user_search_default']; ?>" title="<?php echo $l->t('User Search Attributes');?>"></textarea></p>
				<p><label for="ldap_group_display_name"><?php echo $l->t('Group Display Name Field');?></label><input type="text" id="ldap_group_display_name" name="ldap_group_display_name" data-default="<?php echo $_['ldap_group_display_name_default']; ?>" title="<?php echo $l->t('The LDAP attribute to use to generate the groups`s ownCloud name.');?>" /></p>
				<p><label for="ldap_base_groups"><?php echo $l->t('Base Group Tree');?></label><textarea id="ldap_base_groups" name="ldap_base_groups" placeholder="<?php echo $l->t('One Group Base DN per line');?>" data-default="<?php echo $_['ldap_base_groups_default']; ?>" title="<?php echo $l->t('Base Group Tree');?>"></textarea></p>
				<p><label for="ldap_attributes_for_group_search"><?php echo $l->t('Group Search Attributes');?></label><textarea id="ldap_attributes_for_group_search" name="ldap_attributes_for_group_search" placeholder="<?php echo $l->t('Optional; one attribute per line');?>" data-default="<?php echo $_['ldap_attributes_for_group_search_default']; ?>" title="<?php echo $l->t('Group Search Attributes');?>"></textarea></p>
				<p><label for="ldap_group_member_assoc_attribute"><?php echo $l->t('Group-Member association');?></label><select id="ldap_group_member_assoc_attribute" name="ldap_group_member_assoc_attribute" data-default="<?php echo $_['ldap_group_member_assoc_attribute_default']; ?>" ><option value="uniqueMember"<?php if (isset($_['ldap_group_member_assoc_attribute']) && ($_['ldap_group_member_assoc_attribute'] == 'uniqueMember')) echo ' selected'; ?>>uniqueMember</option><option value="memberUid"<?php if (isset($_['ldap_group_member_assoc_attribute']) && ($_['ldap_group_member_assoc_attribute'] == 'memberUid')) echo ' selected'; ?>>memberUid</option><option value="member"<?php if (isset($_['ldap_group_member_assoc_attribute']) && ($_['ldap_group_member_assoc_attribute'] == 'member')) echo ' selected'; ?>>member (AD)</option></select></p>
			</div>
			<h3><?php echo $l->t('Special Attributes');?></h3>
			<div>
				<p><label for="ldap_quota_attr">Quota Field</label><input type="text" id="ldap_quota_attr" name="ldap_quota_attr" data-default="<?php echo $_['ldap_quota_attr_default']; ?>"/></p>
				<p><label for="ldap_quota_def">Quota Default</label><input type="text" id="ldap_quota_def" name="ldap_quota_def" data-default="<?php echo $_['ldap_quota_def_default']; ?>" title="<?php echo $l->t('in bytes');?>" /></p>
				<p><label for="ldap_email_attr">Email Field</label><input type="text" id="ldap_email_attr" name="ldap_email_attr" data-default="<?php echo $_['ldap_email_attr_default']; ?>" /></p>
				<p><label for="home_folder_naming_rule">User Home Folder Naming Rule</label><input type="text" id="home_folder_naming_rule" name="home_folder_naming_rule" title="<?php echo $l->t('Leave empty for user name (default). Otherwise, specify an LDAP/AD attribute.');?>" data-default="<?php echo $_['home_folder_naming_rule_default']; ?>" /></p>
			</div>
		</div>
	</fieldset>
	<input id="ldap_submit" type="submit" value="Save" /> <button id="ldap_action_test_connection" name="ldap_action_test_connection">Test Configuration</button> <a href="http://doc.owncloud.org/server/5.0/admin_manual/auth_ldap.html" target="_blank"><img src="<?php echo OCP\Util::imagePath('', 'actions/info.png'); ?>" style="height:1.75ex" /> <?php echo $l->t('Help');?></a>
	</div>

</form>
