<?php OCP\Util::addscript('files', 'admin'); ?>

<form name="filesForm" class="section" action="#" method="post">
	<h2><?php p($l->t('File handling')); ?></h2>
	<?php if($_['uploadChangable']):?>
		<label for="maxUploadSize"><?php p($l->t( 'Maximum upload size' )); ?> </label>
		<input type="text" name='maxUploadSize' id="maxUploadSize" value='<?php p($_['uploadMaxFilesize']) ?>'/>
		<?php if($_['displayMaxPossibleUploadSize']):?>
			(<?php p($l->t('max. possible: ')); p($_['maxPossibleUploadSize']) ?>)
		<?php endif;?>
		<br/>
	<?php endif;?>
	<input type="hidden" value="<?php p($_['requesttoken']); ?>" name="requesttoken" />
	<input type="submit" name="submitFilesAdminSettings" id="submitFilesAdminSettings"
		   value="<?php p($l->t( 'Save' )); ?>"/>
</form>
