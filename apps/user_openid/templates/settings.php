<form id="openidform">
	<fieldset>
		<legend><?php echo $l->t( 'OpenID' );?></legend>
		<input type="text" name='identity' id='identity' value="<?php echo $_['identity']; ?>" placeholder="OpenID for <?php echo OC_User::getUser();?>" />
	</fieldset>
</form>