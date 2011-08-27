<?php /**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */?>

<div id="quota" class="personalblock"><div style="width:<?php echo $_['usage_relative'];?>%;">
	<p><?php echo $l->t('You use');?> <strong><?php echo $_['usage'];?></strong> <?php echo $l->t('of the available');?> <strong><?php echo $_['total_space'];?></strong></p>
</div></div>

<form id="passwordform">
	<fieldset class="personalblock">
		<div id="passwordchanged"><?php echo $l->t('Your password got changed');?></div>
		<div id="passworderror"><?php echo $l->t('Unable to change your password');?></div>
		<input type="password" id="pass1" name="oldpassword" placeholder="<?php echo $l->t('Current password');?>" />
		<input type="password" id="pass2" name="password" placeholder="<?php echo $l->t('New password');?>" data-typetoggle="#show" />
		<input type="checkbox" id="show" name="show" /><label for="show"><?php echo $l->t('show');?></label>
		<input id="passwordbutton" type="submit" value="<?php echo $l->t('Change password');?>" />
	</fieldset>
</form>

<form>
	<fieldset class="personalblock">
		<label for="languageinput"><strong><?php echo $l->t('Language');?></strong></label>
		<select id="languageinput" name="lang">
		<?php foreach($_['languages'] as $language):?>
			<option value="<?php echo $language['code'];?>"><?php echo $language['name'];?></option>
		<?php endforeach;?>
		</select>
		<a href="https://www.transifex.net/projects/p/owncloud/team/<?php echo $_['languages'][0]['code'];?>/" target="_blank"><em><?php echo $l->t('Help translating');?></em></a>
	</fieldset>
</form>

<p class="personalblock">
	<label for="webdav"><strong>WebDAV</strong></label>
	<input id="webdav" type="text" value="<?php echo ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https' : 'http').'://'.$_SERVER['HTTP_HOST'].OC::$WEBROOT.'/files/webdav.php'; ?>" title="<?php echo $l->t('use this address to connect to your ownCloud in your file manager');?>" />
</p>

<?php foreach($_['forms'] as $form){
	echo $form;
};?>
