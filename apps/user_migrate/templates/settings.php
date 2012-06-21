<fieldset class="personalblock">
	<legend><strong><?php echo $l->t('Export your user account');?></strong></legend>
	<p><?php echo $l->t('This will create a compressed file that contains your ownCloud account.');?>
	</p>
	<button id="exportbtn">Export<img style="display: none;" class="loading" src="<?php echo OCP\Util::linkTo('core', 'img/loading.gif'); ?>" /></button>
</fieldset>
<form id="import" action="#" method="post" enctype="multipart/form-data">
    <fieldset class="personalblock">
		<?php if(isset($_['error'])){ ?>
		<h3><?php echo $_['error']['error']; ?></h3>
		<p><?php echo $_['error']['hint']; ?></p>
		<?php } ?>
        <legend><strong><?php echo $l->t('Import user account');?></strong></legend>
        </p>
        <p><input type="file" id="owncloud_import" name="owncloud_import" style="width:180px;"><label for="owncloud_import"> <?php echo $l->t('ownCloud User Zip');?></label>
        </p>
        <input type="submit" name="user_import" value="<?php echo $l->t('Import'); ?>" />
    </fieldset>
</form>
