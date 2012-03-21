<?php OC_Util::addScript('files','admin'); ?>

<form name="filesForm" action='#' method='post'>
	<?php if($_['htaccessWorking']):?>
		<label for="maxUploadSize"><?php echo $l->t( 'Maximum upload size' ); ?> </label><input name='maxUploadSize' id="maxUploadSize" value='<?php echo $_['uploadMaxFilesize'] ?>'/><br/>
		<input type='submit' value='Save'/>
	<?php else:?>
		No settings currently available.
	<?php endif;?>
</form>
