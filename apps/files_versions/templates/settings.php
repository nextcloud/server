<form id="versionssettings">
        <fieldset class="personalblock">
	        <input type="checkbox" name="versions" id="versions" value="1" <?php if (OCP\Config::getSystemValue('versions', 'true')=='true') echo ' checked="checked"'; ?> /> <label for="versions"><?php echo $l->t('Enable Files Versioning'); ?></label> <br/>
        </fieldset>
</form>
