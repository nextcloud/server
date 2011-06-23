<form id="ldap" action='#' method='post'>
	<fieldset>
		<legend>LDAP</legend>
		<div>
			<div>
				<span>Host: *</span><span><input type="text" name="ldap_host" width="200" value="<?php echo $_['ldap_host']; ?>"></span>
			</div>
			<div>
				<span>Port: *</span><span><input type="text" name="ldap_port" width="200" value="<?php echo $_['ldap_port']; ?>"></span>
			</div>
			<div>
				<span>DN:<input type="text" name="ldap_dn" width="200" value="<?php echo $_['ldap_dn']; ?>"></span>
			</div>
			<div>
				<span>Password:<input type="text" name="ldap_password" width="200" value="<?php echo $_['ldap_password']; ?>"></span>
			</div>
			<div>
				<span>Base: *<input type="text" name="ldap_base" width="200" value="<?php echo $_['ldap_base']; ?>"></span>
			</div>
			<div>
				<span>Filter * (use %uid placeholder):<input type="text" name="ldap_filter" width="200" value="<?php echo $_['ldap_filter']; ?>"></span>
			</div>
		</div>
		<input type='submit' value='Save'/>
		<br/> * required
	</fieldset>
</form>