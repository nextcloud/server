<form id="ldap" action="#" method="post">
	<fieldset class="personalblock">
		<legend><strong>LDAP</strong></legend>
		<p><label for="ldap_host"><?php echo $l->t('Host');?><input type="text" id="ldap_host" name="ldap_host" value="<?php echo $_['ldap_host']; ?>"></label>
		<label for="ldap_port"><?php echo $l->t('Port');?></label><input type="text" id="ldap_port" name="ldap_port" value="<?php echo $_['ldap_port']; ?>" /></p>
		<p><label for="ldap_dn"><?php echo $l->t('Name');?></label><input type="text" id="ldap_dn" name="ldap_dn" value="<?php echo $_['ldap_dn']; ?>" />
		<label for="ldap_password"><?php echo $l->t('Password');?></label><input type="password" id="ldap_password" name="ldap_password" value="<?php echo $_['ldap_password']; ?>" />
		<small><?php echo $l->t('Leave both empty for anonymous bind for search, then bind with users credentials.');?></small></p>
		<p><label for="ldap_base"><?php echo $l->t('Base');?></label><input type="text" id="ldap_base" name="ldap_base" value="<?php echo $_['ldap_base']; ?>" />
		<label for="ldap_login_filter"><?php echo $l->t('Filter (use %%uid placeholder)');?></label><input type="text" id="ldap_login_filter" name="ldap_login_filter" value="<?php echo $_['ldap_login_filter']; ?>" />
		<label for="ldap_userlist_filter"><?php echo $l->t('Filter to retrieve user list (without any placeholder)');?></label><input type="text" id="ldap_userlist_filter" name="ldap_userlist_filter" value="<?php echo $_['ldap_userlist_filter']; ?>" /><small><?php echo $l->t('For example "objectClass=person".');?>	</p>
		<p><label for="ldap_display_name"><?php echo $l->t('Display Name Field');?></label><input type="text" id="ldap_display_name" name="ldap_display_name" value="<?php echo $_['ldap_display_name']; ?>" />
		<small><?php echo $l->t('Currently the display name field needs to be the same you matched %%uid against in the filter above, because ownCloud doesn\'t distinguish between user id and user name.');?></small></p>
		<p><input type="checkbox" id="ldap_tls" name="ldap_tls" value="1"<?php if ($_['ldap_tls']) echo ' checked'; ?>><label for="ldap_tls"><?php echo $l->t('Use TLS');?></label></p>
		<p><input type="checkbox" id="ldap_nocase" name="ldap_nocase" value="1"<?php if ($_['ldap_nocase']) echo ' checked'; ?>><label for="ldap_nocase"><?php echo $l->t('Case insensitve LDAP server (Windows)');?></label></p>
		<p><label for="ldap_quota">Quota Attribute</label><input type="text" id="ldap_quota" name="ldap_quota" value="<?php echo $_['ldap_quota']; ?>" />
                <label for="ldap_quota_def">Quota Default</label><input type="text" id="ldap_quota_def" name="ldap_quota_def" value="<?php echo $_['ldap_quota_def']; ?>" />bytes</p>
                <p><label for="ldap_email">Email Attribute</label><input type="text" id="ldap_email" name="ldap_email" value="<?php echo $_['ldap_email']; ?>" /></p>
		<input type="submit" value="Save" />
	</fieldset>
</form>
