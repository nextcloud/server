<form id="openidform">
	<fieldset class="personalblock">
		<strong>OpenID</strong>
		<?php echo ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].OC::$WEBROOT.'/?'; echo OC_User::getUser(); ?><br /><em><?php echo $l->t('you can authenticate to other sites with this address');?></em><br />
		<label for="identity"><?php echo $l->t('Authorized OpenID provider');?></label>
		<input type="text" name="identity" id="identity" value="<?php echo $_['identity']; ?>" placeholder="<?php echo $l->t('Your address at Wordpress, Identi.ca, &hellip;');?>" /><span class="msg"></span>
	</fieldset>
</form>
