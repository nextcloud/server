<form id="export" action="#" method="post">
    <fieldset class="personalblock">
        <legend><strong><?php echo $l->t('Export your user account');?></strong></legend>
        <p><?php echo $l->t('This will create a compressed file that contains the data of owncloud account.
            Please choose which components should be included:');?>
        </p>
        <p><input type="checkbox" id="user_files" name="user_files" value="true"><label for="user_files"><?php echo $l->t('Files');?></label><br/>
            <input type="checkbox" id="user_appdata" name="user_appdata" value="true"><label for="owncloud_system"><?php echo $l->t('User app data');?></label><br/>
        </p>
        <input type="submit" name="user_migrate" value="Export" />
    </fieldset>
</form>
