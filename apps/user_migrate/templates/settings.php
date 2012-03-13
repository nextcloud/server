<form id="export" action="#" method="post">
    <fieldset class="personalblock">
        <legend><strong><?php echo $l->t('Export your user account');?></strong></legend>
        <p><?php echo $l->t('This will create a compressed file that contains your ownCloud account.');?>
        </p>
        <input type="submit" name="user_export" value="Export" />
    </fieldset>
</form>
<form id="import" action="#" method="post" enctype="multipart/form-data">
    <fieldset class="personalblock">
        <legend><strong><?php echo $l->t('Import user account');?></strong></legend>
        </p>
        <p><input type="file" id="owncloud_import" name="owncloud_import"><label for="owncloud_import"><?php echo $l->t('ownCloud User Zip');?></label>
        </p>
        <input type="submit" name="user_import" value="<?php echo $l->t('Import'); ?>" />
    </fieldset>
</form>
