<form id="openidform">
	<fieldset class="personalblock">
		<p><strong>OpenID</strong> <?php echo ((isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == 'on') ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].$WEBROOT.'/?'; echo OC_User::getUser(); ?></p>
		<label for="identity">Authorized</label>
		<input type="text" name="identity" id="identity" value="<?php echo $_['identity']; ?>" placeholder="OpenID provider" title="Wordpress, Identi.ca, Launchpad, &hellip;" />
	</fieldset>
</form>
