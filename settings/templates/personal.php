<?php /**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */?>

<div id="quota" class="personalblock"><div style="width:<?php echo $_['usage_relative'];?>%;">
	<p id="quotatext"><?php echo $l->t('You have used <strong>%s</strong> of the available <strong>%s</strong>', array($_['usage'], $_['total_space']));?></p>
</div></div>

<fieldset class="personalblock">
	<legend><strong><?php echo $l->t('Clients');?></strong></legend>
	<a class="button" href="http://owncloud.org/sync-clients/" target="_blank"><?php echo $l->t('Download Desktop Clients');?></a>
	<a class="button" href="https://play.google.com/store/apps/details?id=com.owncloud.android" target="_blank"><?php echo $l->t('Download Android Client');?></a>
	<a class="button" href="https://itunes.apple.com/us/app/owncloud/id543672169?mt=8" target="_blank"><?php echo $l->t('Download iOS Client');?></a>
</fieldset>


<form id="passwordform">
	<fieldset class="personalblock">
		<legend><strong><?php echo $l->t('Password');?></strong></legend>
		<div id="passwordchanged"><?php echo $l->t('Your password was changed');?></div>
		<div id="passworderror"><?php echo $l->t('Unable to change your password');?></div>
		<input type="password" id="pass1" name="oldpassword" placeholder="<?php echo $l->t('Current password');?>" />
		<input type="password" id="pass2" name="password" placeholder="<?php echo $l->t('New password');?>" data-typetoggle="#show" />
		<input type="checkbox" id="show" name="show" /><label for="show"> <?php echo $l->t('show');?></label>
		<input id="passwordbutton" type="submit" value="<?php echo $l->t('Change password');?>" />
	</fieldset>
</form>

<form id="lostpassword">
	<fieldset class="personalblock">
		<legend><strong><?php echo $l->t('Email');?></strong></legend>
		<input type="text" name="email" id="email" value="<?php echo $_['email']; ?>" placeholder="<?php echo $l->t('Your email address');?>" /><span class="msg"></span><br />
		<em><?php echo $l->t('Fill in an email address to enable password recovery');?></em>
	</fieldset>
</form>

<form>
	<fieldset class="personalblock">
		<legend><strong><?php echo $l->t('Language');?></strong></legend>
		<select id="languageinput" class="chzen-select" name="lang" data-placeholder="<?php echo $l->t('Language');?>">
		<?php foreach($_['languages'] as $language):?>
			<option value="<?php echo $language['code'];?>"><?php echo $language['name'];?></option>
		<?php endforeach;?>
		</select>
		<a href="https://www.transifex.net/projects/p/owncloud/team/<?php echo $_['languages'][0]['code'];?>/" target="_blank"><em><?php echo $l->t('Help translate');?></em></a>
	</fieldset>
</form>

<fieldset class="personalblock">
	<legend><strong><?php echo $l->t('WebDAV');?></strong></legend>
	<code><?php echo OC_Helper::linkToRemote('webdav'); ?></code><br />
	<em><?php echo $l->t('Use this address to connect to your ownCloud in your file manager');?></em>
</fieldset>

<?php foreach($_['forms'] as $form) {
	echo $form;
};?>


<fieldset class="personalblock">
	<legend><strong><?php echo $l->t('Version');?></strong></legend>
	<strong>ownCloud</strong> <?php echo(OC_Util::getVersionString()); ?> <?php echo(OC_Util::getEditionString()); ?> <br />
	<?php echo $l->t('Developed by the <a href="http://ownCloud.org/contact" target="_blank">ownCloud community</a>, the <a href="https://github.com/owncloud" target="_blank">source code</a> is licensed under the <a href="http://www.gnu.org/licenses/agpl-3.0.html" target="_blank"><abbr title="Affero General Public License">AGPL</abbr></a>.'); ?>
</fieldset>


