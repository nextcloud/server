<form id="webdavauth" action="#" method="post">
	<fieldset class="personalblock">
		<legend><strong>WebDAV Authentication</strong></legend>
		<p><label for="webdav_url"><?php echo $l->t('webdav_url');?><input type="text" id="webdav_url" name="webdav_url" value="<?php echo $_['webdav_url']; ?>"></label>
		<input type="submit" value="Save" />
	</fieldset>
</form>
