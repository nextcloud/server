<?php /**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

/** @var $_ array */
?>

<div id="app-navigation">
	<ul>
	<?php foreach($_['forms'] as $form) {
		if (isset($form['anchor'])) {
			$anchor = '#' . $form['anchor'];
			$sectionName = $form['section-name'];
			print_unescaped(sprintf("<li><a href='%s'>%s</a></li>", OC_Util::sanitizeHTML($anchor), OC_Util::sanitizeHTML($sectionName)));
		}
	}?>
	</ul>
</div>

<div id="app-content">

<div class="clientsbox center">
	<h2><?php p($l->t('Get the apps to sync your files'));?></h2>
	<a href="<?php p($_['clients']['desktop']); ?>" target="_blank">
		<img src="<?php print_unescaped(OCP\Util::imagePath('core', 'desktopapp.png')); ?>"
			alt="<?php p($l->t('Desktop client'));?>" />
	</a>
	<a href="<?php p($_['clients']['android']); ?>" target="_blank">
		<img src="<?php print_unescaped(OCP\Util::imagePath('core', 'googleplay.png')); ?>"
			alt="<?php p($l->t('Android app'));?>" />
	</a>
	<a href="<?php p($_['clients']['ios']); ?>" target="_blank">
		<img src="<?php print_unescaped(OCP\Util::imagePath('core', 'appstore.png')); ?>"
			alt="<?php p($l->t('iOS app'));?>" />
	</a>

	<?php if (OC_Util::getEditionString() === ''): ?>
	<p class="center">
		<?php print_unescaped($l->t('If you want to support the project
		<a href="https://owncloud.org/contribute"
			target="_blank">join development</a>
		or
		<a href="https://owncloud.org/promote"
			target="_blank">spread the word</a>!'));?>
	</p>
	<?php endif; ?>

	<?php if(OC_APP::isEnabled('firstrunwizard')) {?>
	<p class="center"><a class="button" href="#" id="showWizard"><?php p($l->t('Show First Run Wizard again'));?></a></p>
	<?php }?>
</div>


<div id="quota" class="section">
	<div style="width:<?php p($_['usage_relative']);?>%"
		<?php if($_['usage_relative'] > 80): ?> class="quota-warning" <?php endif; ?>>
		<p id="quotatext">
			<?php print_unescaped($l->t('You have used <strong>%s</strong> of the available <strong>%s</strong>',
			array($_['usage'], $_['total_space'])));?>
		</p>
	</div>
</div>


<?php
if($_['passwordChangeSupported']) {
	script('jquery-showpassword');
?>
<form id="passwordform" class="section">
	<h2><?php p($l->t('Password'));?></h2>
	<div id="passwordchanged"><?php echo $l->t('Your password was changed');?></div>
	<div id="passworderror"><?php echo $l->t('Unable to change your password');?></div>
	<input type="password" id="pass1" name="oldpassword"
		placeholder="<?php echo $l->t('Current password');?>"
		autocomplete="off" autocapitalize="off" autocorrect="off" />
	<input type="password" id="pass2" name="personal-password"
		placeholder="<?php echo $l->t('New password');?>"
		data-typetoggle="#personal-show"
		autocomplete="off" autocapitalize="off" autocorrect="off" />
	<input type="checkbox" id="personal-show" name="show" /><label for="personal-show"></label>
	<input id="passwordbutton" type="submit" value="<?php echo $l->t('Change password');?>" />
	<br/>
	<div class="strengthify-wrapper"></div>
</form>
<?php
}
?>

<?php
if($_['displayNameChangeSupported']) {
?>
<form id="displaynameform" class="section">
	<h2>
		<label for="displayName"><?php echo $l->t('Full Name');?></label>
	</h2>
	<input type="text" id="displayName" name="displayName"
		value="<?php p($_['displayName'])?>"
		autocomplete="on" autocapitalize="off" autocorrect="off" />
    <span class="msg"></span>
	<input type="hidden" id="oldDisplayName" name="oldDisplayName" value="<?php p($_['displayName'])?>" />
</form>
<?php
} else {
?>
<div class="section">
	<h2><?php echo $l->t('Full Name');?></h2>
	<span><?php if(isset($_['displayName'][0])) { p($_['displayName']); } else { p($l->t('No display name set')); } ?></span>
</div>
<?php
}
?>

<?php
if($_['passwordChangeSupported']) {
?>
<form id="lostpassword" class="section">
	<h2>
		<label for="email"><?php p($l->t('Email'));?></label>
	</h2>
	<input type="email" name="email" id="email" value="<?php p($_['email']); ?>"
		placeholder="<?php p($l->t('Your email address'));?>"
		autocomplete="on" autocapitalize="off" autocorrect="off" />
	<span class="msg"></span><br />
	<em><?php p($l->t('Fill in an email address to enable password recovery and receive notifications'));?></em>
</form>
<?php
} else {
?>
<div class="section">
	<h2><?php echo $l->t('Email'); ?></h2>
	<span><?php if(isset($_['email'][0])) { p($_['email']); } else { p($l->t('No email address set')); }?></span>
</div>
<?php
}
?>

<?php if ($_['enableAvatars']): ?>
<form id="avatar" class="section" method="post" action="<?php p(\OC_Helper::linkToRoute('core_avatar_post')); ?>">
	<h2><?php p($l->t('Profile picture')); ?></h2>
	<div id="displayavatar">
		<div class="avatardiv"></div><br>
		<div class="warning hidden"></div>
		<?php if ($_['avatarChangeSupported']): ?>
		<div class="inlineblock button" id="uploadavatarbutton"><?php p($l->t('Upload new')); ?></div>
		<input type="file" class="hidden" name="files[]" id="uploadavatar">
		<div class="inlineblock button" id="selectavatar"><?php p($l->t('Select new from Files')); ?></div>
		<div class="inlineblock button" id="removeavatar"><?php p($l->t('Remove image')); ?></div><br>
		<?php p($l->t('Either png or jpg. Ideally square but you will be able to crop it.')); ?>
		<?php else: ?>
		<?php p($l->t('Your avatar is provided by your original account.')); ?>
		<?php endif; ?>
	</div>
	<div id="cropper" class="hidden">
		<div class="inlineblock button" id="abortcropperbutton"><?php p($l->t('Cancel')); ?></div>
		<div class="inlineblock button primary" id="sendcropperbutton"><?php p($l->t('Choose as profile image')); ?></div>
	</div>
</form>
<?php endif; ?>

<form class="section">
	<h2>
		<label for="languageinput"><?php p($l->t('Language'));?></label>
	</h2>
	<select id="languageinput" name="lang" data-placeholder="<?php p($l->t('Language'));?>">
		<option value="<?php p($_['activelanguage']['code']);?>">
			<?php p($_['activelanguage']['name']);?>
		</option>
		<?php foreach($_['commonlanguages'] as $language):?>
			<option value="<?php p($language['code']);?>">
				<?php p($language['name']);?>
			</option>
		<?php endforeach;?>
		<optgroup label="––––––––––"></optgroup>
		<?php foreach($_['languages'] as $language):?>
			<option value="<?php p($language['code']);?>">
				<?php p($language['name']);?>
			</option>
		<?php endforeach;?>
	</select>
	<?php if (OC_Util::getEditionString() === ''): ?>
	<a href="https://www.transifex.com/projects/p/owncloud/team/<?php p($_['activelanguage']['code']);?>/"
		target="_blank">
		<em><?php p($l->t('Help translate'));?></em>
	</a>
	<?php endif; ?>
</form>

<?php foreach($_['forms'] as $form) {
	if (isset($form['form'])) {?>
	<div id="<?php isset($form['anchor']) ? p($form['anchor']) : p('');?>"><?php print_unescaped($form['form']);?></div>
	<?php }
};?>

<div id="ssl-root-certificates" class="section">
	<h2><?php p($l->t('SSL root certificates')); ?></h2>
	<table id="sslCertificate" class="grid">
		<thead>
			<th><?php p($l->t('Common Name')); ?></th>
			<th><?php p($l->t('Valid until')); ?></th>
			<th><?php p($l->t('Issued By')); ?></th>
			<th/>
		</thead>
		<tbody>
			<?php foreach ($_['certs'] as $rootCert): /**@var \OCP\ICertificate $rootCert*/ ?>
				<tr class="<?php echo ($rootCert->isExpired()) ? 'expired' : 'valid' ?>" data-name="<?php p($rootCert->getName()) ?>">
					<td class="rootCert" title="<?php p($rootCert->getOrganization())?>">
						<?php p($rootCert->getCommonName()) ?>
					</td>
					<td title="<?php p($l->t('Valid until %s', $l->l('date', $rootCert->getExpireDate()))) ?>">
						<?php echo $l->l('date', $rootCert->getExpireDate()) ?>
					</td>
					<td title="<?php p($rootCert->getIssuerOrganization()) ?>">
						<?php p($rootCert->getIssuerName()) ?>
					</td>
					<td <?php if ($rootCert != ''): ?>class="remove"
						<?php else: ?>style="visibility:hidden;"
						<?php endif; ?>><img alt="<?php p($l->t('Delete')); ?>"
											 title="<?php p($l->t('Delete')); ?>"
											 class="svg action"
											 src="<?php print_unescaped(image_path('core', 'actions/delete.svg')); ?>"/>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
	<form class="uploadButton" method="post" action="<?php p(\OC_Helper::linkToRoute('settings_cert_post')); ?>" target="certUploadFrame">
		<input type="file" id="rootcert_import" name="rootcert_import" class="hidden">
		<input type="button" id="rootcert_import_button" value="<?php p($l->t('Import Root Certificate')); ?>"/>
	</form>
</div>

<?php if($_['enableDecryptAll']): ?>
<div id="encryption" class="section">

	<h2>
		<?php p( $l->t( 'Encryption' ) ); ?>
	</h2>

	<?php if($_['filesStillEncrypted']): ?>

	<div id="decryptAll">
	<?php p($l->t( "The encryption app is no longer enabled, please decrypt all your files" )); ?>
	<p>
		<input
			type="password"
			name="privateKeyPassword"
			id="privateKeyPassword" />
		<label for="privateKeyPassword"><?php p($l->t( "Log-in password" )); ?></label>
		<br />
		<button
			type="button"
			disabled
			name="submitDecryptAll"><?php p($l->t( "Decrypt all Files" )); ?>
		</button>
		<span class="msg"></span>
	</p>
	<br />
	</div>
	<?php endif; ?>

	<div id="restoreBackupKeys" <?php $_['backupKeysExists'] ? '' : print_unescaped("class='hidden'") ?>>

	<?php p($l->t( "Your encryption keys are moved to a backup location. If something went wrong you can restore the keys. Only delete them permanently if you are sure that all files are decrypted correctly." )); ?>
	<p>
		<button
			type="button"
			name="submitRestoreKeys"><?php p($l->t( "Restore Encryption Keys" )); ?>
		</button>
		<button
			type="button"
			name="submitDeleteKeys"><?php p($l->t( "Delete Encryption Keys" )); ?>
		</button>
		<span class="msg"></span>

	</p>
	<br />

	</div>


</div>
	<?php endif; ?>

<div class="section">
	<h2><?php p($l->t('Version'));?></h2>
	<strong><?php p($theme->getTitle()); ?></strong> <?php p(OC_Util::getHumanVersion()) ?><br />
<?php if (OC_Util::getEditionString() === ''): ?>
	<?php print_unescaped($l->t('Developed by the <a href="http://ownCloud.org/contact" target="_blank">ownCloud community</a>, the <a href="https://github.com/owncloud" target="_blank">source code</a> is licensed under the <a href="http://www.gnu.org/licenses/agpl-3.0.html" target="_blank"><abbr title="Affero General Public License">AGPL</abbr></a>.')); ?>
<?php endif; ?>
</div>

<div class="section credits-footer">
	<p><?php print_unescaped($theme->getShortFooter()); ?></p>
</div>



</div>
