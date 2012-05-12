<?php OCP\Util::addscript('files','admin'); ?>

<form name="filesForm" action='#' method='post'>
	<fieldset class="personalblock">
		<legend><strong><?php echo $l->t('File handling');?></strong></legend>
		<?php if($_['htaccessWorking']):?>
			<label for="maxUploadSize"><?php echo $l->t( 'Maximum upload size' ); ?> </label><input name='maxUploadSize' id="maxUploadSize" value='<?php echo $_['uploadMaxFilesize'] ?>'/>(<?php echo $l->t('max. possible: '); echo $_['maxPossibleUploadSize'] ?>)<br/>
		<?php endif;?>
		<input type="checkbox" name="allowZipDownload" id="allowZipDownload" value="1" title="<?php echo $l->t( 'Needed for multi-file and folder downloads.' ); ?>"<?php if ($_['allowZipDownload']) echo ' checked="checked"'; ?> /> <label for="allowZipDownload"><?php echo $l->t( 'Enable ZIP-download' ); ?></label> <br/>

			<input name="maxZipInputSize" id="maxZipInputSize" style="width:180px;" value='<?php echo $_['maxZipInputSize'] ?>' title="<?php echo $l->t( '0 is unlimited' ); ?>"<?php if (!$_['allowZipDownload']) echo ' disabled="disabled"'; ?> />
			<label for="maxZipInputSize"><?php echo $l->t( 'Maximum input size for ZIP files' ); ?> </label><br />

		<input type="submit" name="submitFilesAdminSettings" id="submitFilesAdminSettings" value="Save"/>
	</fieldset>
</form>



