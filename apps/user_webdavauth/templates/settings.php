<form id="webdavauth" action="#" method="post">
	<fieldset class="personalblock">
		<legend><strong>WebDAV Authentication</strong></legend>
		<p><label for="webdav_url"><?php echo $l->t('URL: http://');?><input type="text" id="webdav_url" name="webdav_url" value="<?php echo $_['webdav_url']; ?>"></label>
		<input type="submit" value="Save" />
		<br /><?php echo $l->t('ownCloud will send the user credentials to this URL is interpret http 401 and http 403 as credentials wrong and all other codes as credentials correct.'); ?>
	</fieldset>
</form>
