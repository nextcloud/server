<form id="identity" action='#' method='post'>
	<fieldset>
		<legend><?php echo $l->t( 'OpenID identity' );?></legend>
		<label for='input_identity'>OpenID identity for <b><?php echo $_['user'];?></b></label><br/>
		<input name='input_identity' id='input_identity' value="<?php echo $_['identity'];?>"/><input type='submit' value='Save'/>
	</fieldset>
</form>
