<?php OC_Util::addScript('files','admin'); ?>

<form name="filesForm" action='#' method='post'>
	<?php if($_['htaccessWorking']):?>
		<label for="maxUploadSize"><?php echo $l->t( 'Maximum upload size' ); ?> </label><input name='maxUploadSize' id="maxUploadSize" value='<?php echo $_['uploadMaxFilesize'] ?>'/><br/>
		<input type='submit' value='Save'/>
	<?php else:?>
		No settings currently available.
	<?php endif;?>
<!--	<input type="checkbox" name="publicEnable" id="publicEnable" /><label for="publicEnable"> <?php echo $l->t( 'Allow public folders' ); ?></label><br>

	<div style="padding-left: 20px">
		<input type="radio" name="sharingaim" id="separated" /><label for="separated"> <?php echo $l->t( 'separated from webdav storage' ); ?></label><br>
		<input type="radio" name="sharingaim" id="userdecide" /><label for="userdecide"> <?php echo $l->t( 'let the user decide' ); ?></label><br>
		<input type="radio" name="sharingaim" id="inwebdav" /><label for="inwebdav"> <?php echo $l->t( 'folder "/public" in webdav storage' ); ?></label>
	</div>

	<input type="checkbox" id="downloadShared" /><label for="downloadShared"> <?php echo $l->t( 'Allow downloading shared files' ); ?></label><br>
	<input type="checkbox" id="uploadShared" /><label for="uploadShared"> <?php echo $l->t( 'Allow uploading in shared directory' ); ?></label><br>-->
</form>
