<form id="ldap" action="#" method="post">
	<div id="ldapSettings" class="personalblock">
	<ul>
		<li><a href="#ldapSettings-1">LDAP Basic</a></li>
		<li><a href="#ldapSettings-2">Advanced</a></li>
	</ul>
		<?php if(OCP\App::isEnabled('user_webdavauth')) {
			echo '<p class="ldapwarning">'.$l->t('<b>Warning:</b> Apps user_ldap and user_webdavauth are incompatible. You may experience unexpected behaviour. Please ask your system administrator to disable one of them.').'</p>';
		}
		?>
	<fieldset id="ldapSettings-1">
		<p><label for="ldap_host"><?php echo $l->t('Host');?></label><input type="text" id="ldap_host" name="ldap_host" value="<?php echo $_['ldap_host']; ?>" title="<?php echo $l->t('You can omit the protocol, except you require SSL. Then start with ldaps://');?>"></p>
		<p><label for="ldap_base"><?php echo $l->t('Base DN');?></label><input type="text" id="ldap_base" name="ldap_base" value="<?php echo $_['ldap_base']; ?>" title="<?php echo $l->t('You can specify Base DN for users and groups in the Advanced tab');?>" /></p>
		<p><label for="ldap_dn"><?php echo $l->t('User DN');?></label><input type="text" id="ldap_dn" name="ldap_dn" value="<?php echo $_['ldap_dn']; ?>" title="<?php echo $l->t('The DN of the client user with which the bind shall be done, e.g. uid=agent,dc=example,dc=com. For anonymous access, leave DN and Password empty.');?>" /></p>
		<p><label for="ldap_agent_password"><?php echo $l->t('Password');?></label><input type="password" id="ldap_agent_password" name="ldap_agent_password" value="<?php echo $_['ldap_agent_password']; ?>" title="<?php echo $l->t('For anonymous access, leave DN and Password empty.');?>" /></p>
		<p><label for="ldap_login_filter"><?php echo $l->t('User Login Filter');?></label><input type="text" id="ldap_login_filter" name="ldap_login_filter" value="<?php echo $_['ldap_login_filter']; ?>" title="<?php echo $l->t('Defines the filter to apply, when login is attempted. %%uid replaces the username in the login action.');?>" /><br /><small><?php echo $l->t('use %%uid placeholder, e.g. "uid=%%uid"');?></small></p>
		<p><label for="ldap_userlist_filter"><?php echo $l->t('User List Filter');?></label><input type="text" id="ldap_userlist_filter" name="ldap_userlist_filter" value="<?php echo $_['ldap_userlist_filter']; ?>" title="<?php echo $l->t('Defines the filter to apply, when retrieving users.');?>" /><br /><small><?php echo $l->t('without any placeholder, e.g. "objectClass=person".');?></small></p>
		<p><label for="ldap_group_filter"><?php echo $l->t('Group Filter');?></label><input type="text" id="ldap_group_filter" name="ldap_group_filter" value="<?php echo $_['ldap_group_filter']; ?>" title="<?php echo $l->t('Defines the filter to apply, when retrieving groups.');?>" /><br /><small><?php echo $l->t('without any placeholder, e.g. "objectClass=posixGroup".');?></small></p>
	</fieldset>
	<fieldset id="ldapSettings-2">
		<p><label for="ldap_port"><?php echo $l->t('Port');?></label><input type="text" id="ldap_port" name="ldap_port" value="<?php echo $_['ldap_port']; ?>" /></p>
		<p><label for="ldap_base_users"><?php echo $l->t('Base User Tree');?></label><input type="text" id="ldap_base_users" name="ldap_base_users" value="<?php echo $_['ldap_base_users']; ?>" /></p>
		<p><label for="ldap_base_groups"><?php echo $l->t('Base Group Tree');?></label><input type="text" id="ldap_base_groups" name="ldap_base_groups" value="<?php echo $_['ldap_base_groups']; ?>" /></p>
		<p><label for="ldap_group_member_assoc_attribute"><?php echo $l->t('Group-Member association');?></label><select id="ldap_group_member_assoc_attribute" name="ldap_group_member_assoc_attribute"><option value="uniqueMember"<?php if (isset($_['ldap_group_member_assoc_attribute']) && ($_['ldap_group_member_assoc_attribute'] == 'uniqueMember')) echo ' selected'; ?>>uniqueMember</option><option value="memberUid"<?php if (isset($_['ldap_group_member_assoc_attribute']) && ($_['ldap_group_member_assoc_attribute'] == 'memberUid')) echo ' selected'; ?>>memberUid</option><option value="member"<?php if (isset($_['ldap_group_member_assoc_attribute']) && ($_['ldap_group_member_assoc_attribute'] == 'member')) echo ' selected'; ?>>member (AD)</option></select></p>
		<p><label for="ldap_tls"><?php echo $l->t('Use TLS');?></label><input type="checkbox" id="ldap_tls" name="ldap_tls" value="1"<?php if ($_['ldap_tls']) echo ' checked'; ?> title="<?php echo $l->t('Do not use it for SSL connections, it will fail.');?>" /></p>
		<p><label for="ldap_nocase"><?php echo $l->t('Case insensitve LDAP server (Windows)');?></label> <input type="checkbox" id="ldap_nocase" name="ldap_nocase" value="1"<?php if (isset($_['ldap_nocase']) && ($_['ldap_nocase'])) echo ' checked'; ?>></p>
		<p><label for="ldap_turn_off_cert_check"><?php echo $l->t('Turn off SSL certificate validation.');?></label><input type="checkbox" id="ldap_turn_off_cert_check" name="ldap_turn_off_cert_check" title="<?php echo $l->t('If connection only works with this option, import the LDAP server\'s SSL certificate in your ownCloud server.');?>" value="1"<?php if ($_['ldap_turn_off_cert_check']) echo ' checked'; ?>><br/><small><?php echo $l->t('Not recommended, use for testing only.');?></small></p>
		<p><label for="ldap_display_name"><?php echo $l->t('User Display Name Field');?></label><input type="text" id="ldap_display_name" name="ldap_display_name" value="<?php echo $_['ldap_display_name']; ?>" title="<?php echo $l->t('The LDAP attribute to use to generate the user`s ownCloud name.');?>" /></p>
		<p><label for="ldap_group_display_name"><?php echo $l->t('Group Display Name Field');?></label><input type="text" id="ldap_group_display_name" name="ldap_group_display_name" value="<?php echo $_['ldap_group_display_name']; ?>" title="<?php echo $l->t('The LDAP attribute to use to generate the groups`s ownCloud name.');?>" /></p>
		<p><label for="ldap_quota_attr">Quota Field</label><input type="text" id="ldap_quota_attr" name="ldap_quota_attr" value="<?php echo $_['ldap_quota_attr']; ?>" /></p>
		<p><label for="ldap_quota_def">Quota Default</label><input type="text" id="ldap_quota_def" name="ldap_quota_def" value="<?php if (isset($_['ldap_quota_def'])) echo $_['ldap_quota_def']; ?>" title="<?php echo $l->t('in bytes');?>" /></p>
		<p><label for="ldap_email_attr">Email Field</label><input type="text" id="ldap_email_attr" name="ldap_email_attr" value="<?php echo $_['ldap_email_attr']; ?>" /></p>
		<p><label for="ldap_cache_ttl">Cache Time-To-Live</label><input type="text" id="ldap_cache_ttl" name="ldap_cache_ttl" value="<?php echo $_['ldap_cache_ttl']; ?>" title="<?php echo $l->t('in seconds. A change empties the cache.');?>" /></p>
		<p><label for="home_folder_naming_rule">User Home Folder Naming Rule</label><input type="text" id="home_folder_naming_rule" name="home_folder_naming_rule" value="<?php echo $_['home_folder_naming_rule']; ?>" title="<?php echo $l->t('Leave empty for user name (default). Otherwise, specify an LDAP/AD attribute.');?>" /></p>
	</fieldset>
	<input type="submit" value="Save" /> <button id="ldap_action_test_connection" name="ldap_action_test_connection">Test Configuration</button> <a href="http://owncloud.org/support/ldap-backend/" target="_blank"><img src="<?php echo OCP\Util::imagePath('', 'actions/info.png'); ?>" style="height:1.75ex" /> <?php echo $l->t('Help');?></a>
	</div>

</form>
