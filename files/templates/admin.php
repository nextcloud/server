<form action='#' method='post'>
	<?php if($_['htaccessWorking']):?>
		<?php echo $l->t( 'Maximum upload size' ); ?> <input name='maxUploadSize' value='<?php echo $_['uploadMaxFilesize'] ?>'/><br/>
	<?php endif;?>
	<input type="checkbox" /> <?php echo $l->t( 'Allow public folders' ); ?><br>

	<?php echo $l->t( '(if public is enabled)' ); ?><br>
		<input type="radio" name="sharingaim" checked="checked" /> <?php echo $l->t( 'separated from webdav storage' ); ?><br>
		<input type="radio" name="sharingaim" /> <?php echo $l->t( 'let the user decide' ); ?><br>
		<input type="radio" name="sharingaim" /> <?php echo $l->t( 'folder "/public" in webdav storage' ); ?><br>
	<?php echo $l->t( '(endif)' ); ?><br>

	<input type="checkbox" /> <?php echo $l->t( 'Allow downloading shared files' ); ?><br>
	<input type="checkbox" /> <?php echo $l->t( 'Allow uploading in shared directory' ); ?><br>
	<input type='submit' value='Save'/>
</form>
