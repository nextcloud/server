<form id="export" action="#" method="post">
    <fieldset class="personalblock">
        <legend><strong><?php echo $l->t('Export this ownCloud instance');?></strong></legend>
        <p><?php echo $l->t('This will create a compressed file that contains the data of this owncloud instance.
            Please choose which components should be included:');?>
        </p>
        <p><input type="checkbox" id="user_files" name="user_files" value="true"><label for="user_files"><?php echo $l->t('User files');?></label><br/>
            <input type="checkbox" id="owncloud_system" name="owncloud_system" value="true"><label for="owncloud_system"><?php echo $l->t('ownCloud system files');?></label><br/>
            <input type="checkbox" id="owncloud_config" name="owncloud_config" value="true"><label for="owncloud_config"><?php echo $l->t('ownCloud configuration');?></label>
        </p>
        <input type="submit" name="admin_export" value="Export" />
    </fieldset>
</form>
