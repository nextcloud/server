<form id="openidform">
	<fieldset class="personalblock">
		<p><strong>OpenID</strong>
		<a href="<?php echo ((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on') ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].OC::$WEBROOT.'/?'; echo OC_User::getUser(); ?>" title="OpenID">
			<?php echo ((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on') ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].OC::$WEBROOT.'/?'; echo OC_User::getUser(); ?></p>
		</a>
		<label for="identity">Authorized</label>
		<input type="text" name="identity" id="identity" value="<?php echo $_['identity']; ?>" placeholder="OpenID provider" title="Wordpress, Identi.ca, Launchpad, &hellip;" />
	</fieldset>
</form>
