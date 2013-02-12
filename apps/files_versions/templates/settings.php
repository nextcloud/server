<form id="versionssettings">
	<fieldset class="personalblock">
		<legend><strong><?php echo $l->t('Files Versioning');?></strong></legend>
		<input type="checkbox" name="versions" id="versions" value="1" <?php if (OCP\Config::getSystemValue('versions', 'true')=='true') echo ' checked="checked"'; ?> /> <label for="versions"><?php echo $l->t('Enable'); ?></label> <br/>
	</fieldset>
</form>
