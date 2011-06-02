<form action='#' method='post'>
	<?php if($_['htaccessWorking']):?>
		Maximum upload size <input name='maxUploadSize' value='<?php echo $_['uploadMaxFilesize'] ?>'/><br/>
	<?php endif;?>
	<input type="checkbox" /> Allow public folders<br>

	(if public is enabled)<br>
		<input type="radio" name="sharingaim" checked="checked" /> separated from webdav storage<br>
		<input type="radio" name="sharingaim" /> let the user decide<br>
		<input type="radio" name="sharingaim" /> folder "/public" in webdav storage<br>
	(endif)<br>

	<input type="checkbox" /> Allow downloading shared files<br>
	<input type="checkbox" /> Allow uploading in shared directory<br>
	<input type='submit' value='Save'/>
</form>
