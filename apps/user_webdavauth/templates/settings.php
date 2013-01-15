<form id="webdavauth" action="#" method="post">
	<fieldset class="personalblock">
		<legend><strong><?php echo $l->t('WebDAV Authentication');?></strong></legend>
		<p><label for="webdav_url"><?php echo $l->t('URL: http://');?><input type="text" id="webdav_url" name="webdav_url" value="<?php echo $_['webdav_url']; ?>"></label>
		<input type="submit" value="Save" />
		<br /><?php echo $l->t('ownCloud will send the user credentials to this URL. This plugin checks the response and will interpret the HTTP statuscodes 401 and 403 as invalid credentials, and all other responses as valid credentials.'); ?>
	</fieldset>
</form>
