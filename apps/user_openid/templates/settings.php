<form id="openidform">
	<fieldset class="personalblock">
		<label for="openid"><strong>OpenID</strong></label>
		<input type="text" id="openid" value="<?php echo ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].OC::$WEBROOT.'/?'; echo OC_User::getUser(); ?>" title="you can authenticate to other sites with this address" />
		<label for="identity">Authorized</label>
		<input type="text" name="identity" id="identity" value="<?php echo $_['identity']; ?>" placeholder="OpenID provider" title="Wordpress, Identi.ca, Launchpad, &hellip;" />
	</fieldset>
</form>
