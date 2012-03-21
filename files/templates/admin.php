<?php OC_Util::addScript('files','admin'); ?>

<form name="filesForm" action='#' method='post'>
	<fieldset class="personalblock">
		<legend><strong><?php echo $l->t('File handling');?></strong></legend>
		<?php if($_['htaccessWorking']):?>
			<label for="maxUploadSize"><?php echo $l->t( 'Maximum upload size' ); ?> </label><input name='maxUploadSize' id="maxUploadSize" value='<?php echo $_['uploadMaxFilesize'] ?>'/><br/>
		<?php endif;?>
		<label for="maxZipInputSize"><?php echo $l->t( 'Maximum input size for zip files (affects folder- and multi-file download)' ); ?> </label><input name="maxZipInputSize" id="maxZipInputSize" value='<?php echo $_['maxZipInputSize'] ?>'/><br/>
		<input type="submit" value="Save"/>
	</fieldset>
</form>
