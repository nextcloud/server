<?php OCP\Util::addscript('files', 'admin'); ?>

<form name="filesForm" action='#' method='post'>
	<fieldset class="personalblock">
		<legend><strong><?php p($l->t('File handling')); ?></strong></legend>
		<?php if($_['uploadChangable']):?>
			<label for="maxUploadSize"><?php p($l->t( 'Maximum upload size' )); ?> </label>
			<input name='maxUploadSize' id="maxUploadSize" value='<?php p($_['uploadMaxFilesize']) ?>'/>
			<?php if($_['displayMaxPossibleUploadSize']):?>
				(<?php p($l->t('max. possible: ')); p($_['maxPossibleUploadSize']) ?>)
			<?php endif;?>
			<br/>
		<?php endif;?>
		<input type="checkbox" name="allowZipDownload" id="allowZipDownload" value="1"
			   title="<?php p($l->t( 'Needed for multi-file and folder downloads.' )); ?>"
			   <?php if ($_['allowZipDownload']): ?> checked="checked"<?php endif; ?> />
		<label for="allowZipDownload"><?php p($l->t( 'Enable ZIP-download' )); ?></label><br/>

		<input type="text" name="maxZipInputSize" id="maxZipInputSize" style="width:180px;" value='<?php p($_['maxZipInputSize']) ?>'
			   title="<?php p($l->t( '0 is unlimited' )); ?>"
			   <?php if (!$_['allowZipDownload']): ?> disabled="disabled"<?php endif; ?> /><br />
		<em><?php p($l->t( 'Maximum input size for ZIP files' )); ?> </em><br />

		<input type="hidden" value="<?php p($_['requesttoken']); ?>" name="requesttoken" />
		<input type="submit" name="submitFilesAdminSettings" id="submitFilesAdminSettings"
			   value="<?php p($l->t( 'Save' )); ?>"/>
	</fieldset>
</form>
