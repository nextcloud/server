<form id="ldap" action="#" method="post">
	<fieldset class="personalblock">
		<legend><strong>LDAP</strong></legend>
		<p><label for="ldap_host">Host<input type="text" id="ldap_host" name="ldap_host" value="<?php echo $_['ldap_host']; ?>"></label>
		<label for="ldap_port">Port</label><input type="text" id="ldap_port" name="ldap_port" value="<?php echo $_['ldap_port']; ?>" /></p>
		<p><label for="ldap_dn">Name</label><input type="text" id="ldap_dn" name="ldap_dn" value="<?php echo $_['ldap_dn']; ?>" />
		<label for="ldap_password">Password</label><input type="password" id="ldap_password" name="ldap_password" value="<?php echo $_['ldap_password']; ?>" /></p>
		<p><label for="ldap_base">Base</label><input type="text" id="ldap_base" name="ldap_base" value="<?php echo $_['ldap_base']; ?>" />
		<label for="ldap_filter">Filter (use %uid placeholder)</label><input type="text" id="ldap_filter" name="ldap_filter" value="<?php echo $_['ldap_filter']; ?>" /></p>
		<input type="submit" value="Save" />
	</fieldset>
</form>
