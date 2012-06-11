<form id="ldap" action="#" method="post">
	<div id="ldapSettings" class="personalblock">
	<ul>
		<li><a href="#ldapSettings-1">LDAP Basic</a></li>
		<li><a href="#ldapSettings-2">Advanced</a></li>
	</ul>
	<fieldset id="ldapSettings-1">
		<p><label for="ldap_host"><?php echo $l->t('Host');?><input type="text" id="ldap_host" name="ldap_host" value="<?php echo $_['ldap_host']; ?>"></label> <label for="ldap_base"><?php echo $l->t('Base');?></label><input type="text" id="ldap_base" name="ldap_base" value="<?php echo $_['ldap_base']; ?>" /></p>
		<p><label for="ldap_dn"><?php echo $l->t('Name');?></label><input type="text" id="ldap_dn" name="ldap_dn" value="<?php echo $_['ldap_dn']; ?>" />
		<label for="ldap_agent_password"><?php echo $l->t('Password');?></label><input type="password" id="ldap_agent_password" name="ldap_agent_password" value="<?php echo $_['ldap_agent_password']; ?>" />
		<small><?php echo $l->t('Leave both empty for anonymous bind for search, then bind with users credentials.');?></small></p>
		<p><label for="ldap_login_filter"><?php echo $l->t('User Login Filter');?></label><input type="text" id="ldap_login_filter" name="ldap_login_filter" value="<?php echo $_['ldap_login_filter']; ?>" /><small><?php echo $l->t('use %%uid placeholder, e.g. uid=%%uid');?></small></p>
		<p><label for="ldap_userlist_filter"><?php echo $l->t('User List Filter');?></label><input type="text" id="ldap_userlist_filter" name="ldap_userlist_filter" value="<?php echo $_['ldap_userlist_filter']; ?>" /><small><?php echo $l->t('without any placeholder, e.g. "objectClass=person".');?></small></p>
		<p><label for="ldap_group_filter"><?php echo $l->t('Group Filter');?></label><input type="text" id="ldap_group_filter" name="ldap_group_filter" value="<?php echo $_['ldap_group_filter']; ?>" /><small><?php echo $l->t('without any placeholder, e.g. "objectClass=posixGroup".');?></small></p>
	</fieldset>
	<fieldset id="ldapSettings-2">
		<p><label for="ldap_port"><?php echo $l->t('Port');?></label><input type="text" id="ldap_port" name="ldap_port" value="<?php echo $_['ldap_port']; ?>" /></p>
		<p><label for="ldap_base_users"><?php echo $l->t('Base User Tree');?></label><input type="text" id="ldap_base_users" name="ldap_base_users" value="<?php echo $_['ldap_base_users']; ?>" /></p>
		<p><label for="ldap_base_groups"><?php echo $l->t('Base Group Tree');?></label><input type="text" id="ldap_base_groups" name="ldap_base_groups" value="<?php echo $_['ldap_base_groups']; ?>" /></p>
		<p><label for="ldap_group_member_assoc_attribute"><?php echo $l->t('Group-Member association');?></label><select id="ldap_group_member_assoc_attribute" name="ldap_group_member_assoc_attribute"><option value="uniqueMember"<?php if (isset($_['ldap_group_member_assoc_attribute']) && ($_['ldap_group_member_assoc_attribute'] == 'uniqueMember')) echo ' selected'; ?>>uniqueMember</option><option value="memberUid"<?php if (isset($_['ldap_group_member_assoc_attribute']) && ($_['ldap_group_member_assoc_attribute'] == 'memberUid')) echo ' selected'; ?>>memberUid</option><option value="member"<?php if (isset($_['ldap_group_member_assoc_attribute']) && ($_['ldap_group_member_assoc_attribute'] == 'member')) echo ' selected'; ?>>member (AD)</option></select></p>
		<p><input type="checkbox" id="ldap_tls" name="ldap_tls" value="1"<?php if ($_['ldap_tls']) echo ' checked'; ?>><label for="ldap_tls"><?php echo $l->t('Use TLS');?></label></p>
		<p><input type="checkbox" id="ldap_nocase" name="ldap_nocase" value="1"<?php if (isset($_['ldap_nocase']) && ($_['ldap_nocase'])) echo ' checked'; ?>><label for="ldap_nocase"><?php echo $l->t('Case insensitve LDAP server (Windows)');?></label></p>
		<p><label for="ldap_display_name"><?php echo $l->t('Display Name Field');?></label><input type="text" id="ldap_display_name" name="ldap_display_name" value="<?php echo $_['ldap_display_name']; ?>" />
		<small><?php echo $l->t('Currently the display name field needs to be the same you matched %%uid against in the filter above, because ownCloud doesn\'t distinguish between user id and user name.');?></small></p>
		<p><label for="ldap_group_display_name"><?php echo $l->t('Group Display Name Field');?></label><input type="text" id="ldap_group_display_name" name="ldap_group_display_name" value="<?php echo $_['ldap_group_display_name']; ?>" /></p>
		<p><label for="ldap_quota_attr">Quota Attribute</label><input type="text" id="ldap_quota_attr" name="ldap_quota_attr" value="<?php echo $_['ldap_quota_attr']; ?>" />
		<label for="ldap_quota_def">Quota Default</label><input type="text" id="ldap_quota_def" name="ldap_quota_def" value="<?php if (isset($_['ldap_quota_def'])) echo $_['ldap_quota_def']; ?>" />bytes</p>
		<p><label for="ldap_email_attr">Email Attribute</label><input type="text" id="ldap_email_attr" name="ldap_email_attr" value="<?php echo $_['ldap_email_attr']; ?>" /></p>
	</fieldset>
	<input type="submit" value="Save" /> <a href="http://owncloud.org/support/ldap-backend/" target="_blank"><img src="<?php echo OCP\Util::imagePath('','actions/info.png'); ?>" style="height:1.75ex" /> <?php echo $l->t('Help');?></a>
	</div>

</form>
