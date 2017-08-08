<?php /**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

/** @var $_ mixed[]|\OCP\IURLGenerator[] */
/** @var \OCP\Defaults $theme */
?>

<div id="app-navigation">
	<ul class="with-icon">
	<?php foreach($_['forms'] as $form) {
		if (isset($form['anchor'])) {
			$anchor = '#' . $form['anchor'];
			$class = 'nav-icon-' . $form['anchor'];
			$sectionName = $form['section-name'];
			print_unescaped(sprintf("<li><a href='%s' class='%s'>%s</a></li>", \OCP\Util::sanitizeHTML($anchor),
			\OCP\Util::sanitizeHTML($class), \OCP\Util::sanitizeHTML($sectionName)));
		}
	}?>
	</ul>
</div>

<div id="app-content">

<div id="quota" class="section">
	<div style="width:<?php p($_['usage_relative']);?>%"
		<?php if($_['usage_relative'] > 80): ?> class="quota-warning" <?php endif; ?>>
		<p id="quotatext">
			<?php if ($_['quota'] === \OCP\Files\FileInfo::SPACE_UNLIMITED): ?>
				<?php print_unescaped($l->t('You are using <strong>%s</strong> of <strong>%s</strong>',
					[$_['usage'], $_['total_space']]));?>
			<?php else: ?>
				<?php print_unescaped($l->t('You are using <strong>%s</strong> of <strong>%s</strong> (<strong>%s %%</strong>)',
					[$_['usage'], $_['total_space'],  $_['usage_relative']]));?>
			<?php endif ?>
		</p>
	</div>
</div>

<div id="personal-settings">
<div id="personal-settings-avatar-container">
	<form id="avatarform" class="section" method="post" action="<?php p(\OC::$server->getURLGenerator()->linkToRoute('core.avatar.postAvatar')); ?>">
		<h2>
			<label><?php p($l->t('Profile picture')); ?></label>
			<span class="icon-password"/>
		</h2>
		<div id="displayavatar">
			<div class="avatardiv"></div>
			<div class="warning hidden"></div>
			<?php if ($_['avatarChangeSupported']): ?>
				<label for="uploadavatar" class="inlineblock button icon-upload svg" id="uploadavatarbutton" title="<?php p($l->t('Upload new')); ?>"></label>
				<div class="inlineblock button icon-folder svg" id="selectavatar" title="<?php p($l->t('Select from Files')); ?>"></div>
				<div class="hidden button icon-delete svg" id="removeavatar" title="<?php p($l->t('Remove image')); ?>"></div>
				<input type="file" name="files[]" id="uploadavatar" class="hiddenuploadfield">
				<p><em><?php p($l->t('png or jpg, max. 20 MB')); ?></em></p>
			<?php else: ?>
				<?php p($l->t('Picture provided by original account')); ?>
			<?php endif; ?>
		</div>

		<div id="cropper" class="hidden">
			<div class="inner-container">
				<div class="inlineblock button" id="abortcropperbutton"><?php p($l->t('Cancel')); ?></div>
				<div class="inlineblock button primary" id="sendcropperbutton"><?php p($l->t('Choose as profile picture')); ?></div>
			</div>
		</div>
		<span class="icon-checkmark hidden"></span>
		<?php if($_['lookupServerUploadEnabled']) { ?>
		<input type="hidden" id="avatarscope" value="<?php p($_['avatarScope']) ?>">
		<?php } ?>
	</form>
</div>

<div id="personal-settings-container">
	<div class="personal-settings-setting-box">
		<form id="displaynameform" class="section">
			<h2>
				<label for="displayname"><?php p($l->t('Full name')); ?></label>
				<span class="icon-password"/>
			</h2>
			<input type="text" id="displayname" name="displayname"
				<?php if(!$_['displayNameChangeSupported']) { print_unescaped('disabled="1"'); } ?>
				value="<?php p($_['displayName']) ?>"
				autocomplete="on" autocapitalize="none" autocorrect="off" />
			<span class="icon-checkmark hidden" ></span>
			<span class="icon-error hidden" ></span>
			<?php if($_['lookupServerUploadEnabled']) { ?>
			<input type="hidden" id="displaynamescope" value="<?php p($_['displayNameScope']) ?>">
			<?php } ?>
		</form>
	</div>
	<div class="personal-settings-setting-box">
		<form id="emailform" class="section">
			<h2>
				<label for="email"><?php p($l->t('Email')); ?></label>
				<span class="icon-password"/>
			</h2>
			<div class="verify <?php if ($_['email'] === ''  || $_['emailScope'] !== 'public') p('hidden'); ?>">
				<img id="verify-email" title="<?php p($_['emailMessage']); ?>" data-status="<?php p($_['emailVerification']) ?>" src="
				<?php
				switch($_['emailVerification']) {
					case \OC\Accounts\AccountManager::VERIFICATION_IN_PROGRESS:
						p(image_path('core', 'actions/verifying.svg'));
						break;
					case \OC\Accounts\AccountManager::VERIFIED:
						p(image_path('core', 'actions/verified.svg'));
						break;
					default:
						p(image_path('core', 'actions/verify.svg'));
				}
				?>">
			</div>
			<input type="email" name="email" id="email" value="<?php if(!$_['displayNameChangeSupported'] && empty($_['email'])) p($l->t('No email address set')); else p($_['email']); ?>"
				<?php if(!$_['displayNameChangeSupported']) { print_unescaped('disabled="1"'); } ?>
				placeholder="<?php p($l->t('Your email address')) ?>"
				autocomplete="on" autocapitalize="none" autocorrect="off" />
			<?php if($_['displayNameChangeSupported']) { ?>
				<br />
				<em><?php p($l->t('For password reset and notifications')); ?></em>
			<?php } ?>
			<span class="icon-checkmark hidden"></span>
			<span class="icon-error hidden" ></span>
			<?php if($_['lookupServerUploadEnabled']) { ?>
			<input type="hidden" id="emailscope" value="<?php p($_['emailScope']) ?>">
			<?php } ?>
		</form>
	</div>
	<?php if (!empty($_['phone']) || $_['lookupServerUploadEnabled']) { ?>
	<div class="personal-settings-setting-box">
		<form id="phoneform" class="section">
			<h2>
				<label for="phone"><?php p($l->t('Phone number')); ?></label>
				<span class="icon-password"/>
			</h2>
			<input type="tel" id="phone" name="phone" <?php if(!$_['lookupServerUploadEnabled']) print_unescaped('disabled="1"'); ?>
				   value="<?php p($_['phone']) ?>"
				   placeholder="<?php p($l->t('Your phone number')); ?>"
			       autocomplete="on" autocapitalize="none" autocorrect="off" />
			<span class="icon-checkmark hidden"/>
			<?php if($_['lookupServerUploadEnabled']) { ?>
			<input type="hidden" id="phonescope" value="<?php p($_['phoneScope']) ?>">
			<?php } ?>
		</form>
	</div>
	<?php } ?>
	<?php if (!empty($_['address']) || $_['lookupServerUploadEnabled']) { ?>
	<div class="personal-settings-setting-box">
		<form id="addressform" class="section">
			<h2>
				<label for="address"><?php p($l->t('Address')); ?></label>
				<span class="icon-password"/>
			</h2>
			<input type="text" id="address" name="address" <?php if(!$_['lookupServerUploadEnabled']) print_unescaped('disabled="1"');  ?>
				   placeholder="<?php p($l->t('Your postal address')); ?>"
				   value="<?php p($_['address']) ?>"
				   autocomplete="on" autocapitalize="none" autocorrect="off" />
			<span class="icon-checkmark hidden"/>
			<?php if($_['lookupServerUploadEnabled']) { ?>
			<input type="hidden" id="addressscope" value="<?php p($_['addressScope']) ?>">
			<?php } ?>
		</form>
	</div>
	<?php } ?>
	<?php if (!empty($_['website']) || $_['lookupServerUploadEnabled']) { ?>
	<div class="personal-settings-setting-box">
		<form id="websiteform" class="section">
			<h2>
				<label for="website"><?php p($l->t('Website')); ?></label>
				<span class="icon-password"/>
			</h2>
			<?php if($_['lookupServerUploadEnabled']) { ?>
			<div class="verify <?php if ($_['website'] === ''  || $_['websiteScope'] !== 'public') p('hidden'); ?>">
				<img id="verify-website" title="<?php p($_['websiteMessage']); ?>" data-status="<?php p($_['websiteVerification']) ?>" src="
				<?php
				switch($_['websiteVerification']) {
					case \OC\Accounts\AccountManager::VERIFICATION_IN_PROGRESS:
						p(image_path('core', 'actions/verifying.svg'));
						break;
					case \OC\Accounts\AccountManager::VERIFIED:
						p(image_path('core', 'actions/verified.svg'));
						break;
					default:
						p(image_path('core', 'actions/verify.svg'));
				}
				?>"
				<?php if($_['websiteVerification'] === \OC\Accounts\AccountManager::VERIFICATION_IN_PROGRESS || $_['websiteVerification'] === \OC\Accounts\AccountManager::NOT_VERIFIED) print_unescaped(' class="verify-action"') ?>
				>
				<div class="verification-dialog popovermenu bubble menu">
					<div class="verification-dialog-content">
						<p class="explainVerification"></p>
						<p class="verificationCode"></p>
						<p><?php p($l->t('It can take up to 24 hours before the account is displayed as verified.'));?></p>
					</div>
				</div>
			</div>
			<?php } ?>
			<input type="text" name="website" id="website" value="<?php p($_['website']); ?>"
			       placeholder="<?php p($l->t('Link https://…')); ?>"
			       autocomplete="on" autocapitalize="none" autocorrect="off"
				   <?php if(!$_['lookupServerUploadEnabled']) print_unescaped('disabled="1"');  ?>
			/>
			<span class="icon-checkmark hidden"/>
			<?php if($_['lookupServerUploadEnabled']) { ?>
			<input type="hidden" id="websitescope" value="<?php p($_['websiteScope']) ?>">
			<?php } ?>
		</form>
	</div>
	<?php } ?>
	<?php if (!empty($_['twitter']) || $_['lookupServerUploadEnabled']) { ?>
	<div class="personal-settings-setting-box">
		<form id="twitterform" class="section">
			<h2>
				<label for="twitter"><?php p($l->t('Twitter')); ?></label>
				<span class="icon-password"/>
			</h2>
			<?php if($_['lookupServerUploadEnabled']) { ?>
			<div class="verify <?php if ($_['twitter'] === ''  || $_['twitterScope'] !== 'public') p('hidden'); ?>">
				<img id="verify-twitter" title="<?php p($_['twitterMessage']); ?>" data-status="<?php p($_['twitterVerification']) ?>" src="
				<?php
				switch($_['twitterVerification']) {
					case \OC\Accounts\AccountManager::VERIFICATION_IN_PROGRESS:
						p(image_path('core', 'actions/verifying.svg'));
						break;
					case \OC\Accounts\AccountManager::VERIFIED:
						p(image_path('core', 'actions/verified.svg'));
						break;
					default:
						p(image_path('core', 'actions/verify.svg'));
				}
				?>"
				<?php if($_['twitterVerification'] === \OC\Accounts\AccountManager::VERIFICATION_IN_PROGRESS || $_['twitterVerification'] === \OC\Accounts\AccountManager::NOT_VERIFIED) print_unescaped(' class="verify-action"') ?>
				>
				<div class="verification-dialog popovermenu bubble menu">
					<div class="verification-dialog-content">
						<p class="explainVerification"></p>
						<p class="verificationCode"></p>
						<p><?php p($l->t('It can take up to 24 hours before the account is displayed as verified.'));?></p>
					</div>
				</div>
			</div>
			<?php } ?>
			<input type="text" name="twitter" id="twitter" value="<?php p($_['twitter']); ?>"
				   placeholder="<?php p($l->t('Twitter handle @…')); ?>"
				   autocomplete="on" autocapitalize="none" autocorrect="off"
				   <?php if(!$_['lookupServerUploadEnabled']) print_unescaped('disabled="1"');  ?>
			/>
			<span class="icon-checkmark hidden"/>
			<?php if($_['lookupServerUploadEnabled']) { ?>
			<input type="hidden" id="twitterscope" value="<?php p($_['twitterScope']) ?>">
			<?php } ?>
		</form>
	</div>
	<?php } ?>
	<span class="msg"></span>
</div>
</div>

<div class="clear"></div>

<div id="groups" class="section">
	<h2><?php p($l->t('Groups')); ?></h2>
	<p><?php p($l->t('You are member of the following groups:')); ?></p>
	<p>
	<?php p(implode(', ', $_['groups'])); ?>
	</p>
</div>

<?php
if($_['passwordChangeSupported']) {
	script('jquery-showpassword');
?>
<form id="passwordform" class="section">
	<h2 class="inlineblock"><?php p($l->t('Password'));?></h2>
	<div id="password-error-msg" class="msg success inlineblock" style="display: none;">Saved</div>
	<br>
	<label for="pass1" class="hidden-visually"><?php p($l->t('Current password')); ?>: </label>
	<input type="password" id="pass1" name="oldpassword"
		placeholder="<?php p($l->t('Current password'));?>"
		autocomplete="off" autocapitalize="none" autocorrect="off" />
	<div class="personal-show-container">
		<label for="pass2" class="hidden-visually"><?php p($l->t('New password'));?>: </label>
		<input type="password" id="pass2" name="newpassword"
			placeholder="<?php p($l->t('New password')); ?>"
			data-typetoggle="#personal-show"
			autocomplete="off" autocapitalize="none" autocorrect="off" />
		<input type="checkbox" id="personal-show" name="show" /><label for="personal-show" class="personal-show-label"></label>
	</div>
	<input id="passwordbutton" type="submit" value="<?php p($l->t('Change password')); ?>" />
	<br/>
</form>
<?php
}
?>

<?php if (isset($_['activelanguage'])) { ?>
<form id="language" class="section">
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
	<a href="https://www.transifex.com/nextcloud/nextcloud/"
		target="_blank" rel="noreferrer">
		<em><?php p($l->t('Help translate'));?></em>
	</a>
</form>
<?php } ?>


<?php if(OC_APP::isEnabled('firstrunwizard')) {?>
<div id="clientsbox" class="section clientsbox">
	<h2><?php p($l->t('Get the apps to sync your files'));?></h2>
	<a href="<?php p($_['clients']['desktop']); ?>" rel="noreferrer" target="_blank">
		<img src="<?php print_unescaped(image_path('core', 'desktopapp.svg')); ?>"
			 alt="<?php p($l->t('Desktop client'));?>" />
	</a>
	<a href="<?php p($_['clients']['android']); ?>" rel="noreferrer" target="_blank">
		<img src="<?php print_unescaped(image_path('core', 'googleplay.png')); ?>"
			 alt="<?php p($l->t('Android app'));?>" />
	</a>
	<a href="<?php p($_['clients']['ios']); ?>" rel="noreferrer" target="_blank">
		<img src="<?php print_unescaped(image_path('core', 'appstore.svg')); ?>"
			 alt="<?php p($l->t('iOS app'));?>" />
	</a>

		<p>
			<?php print_unescaped(str_replace(
				[
					'{contributeopen}',
					'{linkclose}',
				],
				[
					'<a href="https://nextcloud.com/contribute" target="_blank" rel="noreferrer">',
					'</a>',
				],
				$l->t('If you want to support the project {contributeopen}join development{linkclose} or {contributeopen}spread the word{linkclose}!'))); ?>
		</p>

		<p><a class="button" href="#" id="showWizard"><?php p($l->t('Show First Run Wizard again'));?></a></p>
</div>
<?php }?>

<div id="security" class="section">
	<h2><?php p($l->t('Security'));?></h2>
	<p class="settings-hint hidden-when-empty"><?php p($l->t('Web, desktop, mobile clients and app specific passwords that currently have access to your account.'));?></p>
	<table class="icon-loading">
		<thead class="token-list-header">
			<tr>
				<th><?php p($l->t('Device'));?></th>
				<th><?php p($l->t('Last activity'));?></th>
				<th></th>
			</tr>
		</thead>
		<tbody class="token-list">
		</tbody>
	</table>

	<h3><?php p($l->t('App passwords'));?></h3>
	<p class="settings-hint"><?php p($l->t('Here you can generate individual passwords for apps so you don’t have to give out your password. You can revoke them individually too.'));?></p>

	<div id="app-password-form">
		<input id="app-password-name" type="text" placeholder="<?php p($l->t('App name')); ?>">
		<button id="add-app-password" class="button"><?php p($l->t('Create new app password')); ?></button>
	</div>
	<div id="app-password-result" class="hidden">
		<span>
			<?php p($l->t('Use the credentials below to configure your app or device.')); ?>
			<?php p($l->t('For security reasons this password will only be shown once.')); ?>
		</span>
		<div class="app-password-row">
			<span class="app-password-label"><?php p($l->t('Username')); ?></span>
			<input id="new-app-login-name" type="text" readonly="readonly"/>
		</div>
		<div class="app-password-row">
			<span class="app-password-label"><?php p($l->t('Password')); ?></span>
			<input id="new-app-password" type="text" readonly="readonly"/>
			<a class="clipboardButton icon icon-clippy" data-clipboard-target="#new-app-password"></a>
			<button id="app-password-hide" class="button"><?php p($l->t('Done')); ?></button>
		</div>
	</div>
</div>

<?php foreach($_['forms'] as $form) {
	if (isset($form['form'])) {?>
	<div id="<?php isset($form['anchor']) ? p($form['anchor']) : p('');?>"><?php print_unescaped($form['form']);?></div>
	<?php }
};?>

<div class="section">
	<h2><?php p($l->t('Version'));?></h2>
	<p><a href="<?php print_unescaped($theme->getBaseUrl()); ?>" target="_blank"><?php p($theme->getTitle()); ?></a> <?php p(OC_Util::getHumanVersion()) ?></p>
	<p><?php include('settings.development.notice.php'); ?></p>
</div>

</div>
