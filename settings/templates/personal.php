<?php /**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */

/** @var $_ mixed[]|\OCP\IURLGenerator[] */
/** @var \OC_Defaults $theme */
?>

<div id="app-navigation">
	<ul>
	<?php foreach($_['forms'] as $form) {
		if (isset($form['anchor'])) {
			$anchor = '#' . $form['anchor'];
			$sectionName = $form['section-name'];
			print_unescaped(sprintf("<li><a href='%s'>%s</a></li>", \OCP\Util::sanitizeHTML($anchor), \OCP\Util::sanitizeHTML($sectionName)));
		}
	}?>
	</ul>
</div>

<div id="app-content">

<div id="quota" class="section">
	<div style="width:<?php p($_['usage_relative']);?>%"
		<?php if($_['usage_relative'] > 80): ?> class="quota-warning" <?php endif; ?>>
		<p id="quotatext">
			<?php print_unescaped($l->t('You are using <strong>%s</strong> of <strong>%s</strong>',
			array($_['usage'], $_['total_space'])));?>
		</p>
	</div>
</div>

<div id="personal-settings">
<?php if ($_['enableAvatars']): ?>
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
		<span class="icon-checkmark hidden"/>
		<input type="hidden" id="avatarscope" value="<?php p($_['avatarScope']) ?>">
	</form>
</div>
<?php endif; ?>

<div id="personal-settings-container">
	<div class="personal-settings-setting-box">
		<form id="displaynameform" class="section">
			<h2>
				<label for="displayname"><?php p($l->t('Full name')); ?></label>
				<span class="icon-password"/>
			</h2>
			<input type="text" id="displayname" name="displayname"
				<?php if(!$_['displayNameChangeSupported']) { print_unescaped('class="hidden"'); } ?>
				value="<?php p($_['displayName']) ?>"
				autocomplete="on" autocapitalize="off" autocorrect="off" />
			<?php if(!$_['displayNameChangeSupported']) { ?>
				<span><?php if(isset($_['displayName']) && !empty($_['displayName'])) { p($_['displayName']); } else { p($l->t('No display name set')); } ?></span>
			<?php } ?>
			<span class="icon-checkmark hidden"/>
			<input type="hidden" id="displaynamescope" value="<?php p($_['displayNameScope']) ?>">
		</form>
	</div>
	<div class="personal-settings-setting-box">
		<form id="emailform" class="section">
			<h2>
				<label for="email"><?php p($l->t('Email')); ?></label>
				<span class="icon-password"/>
			</h2>
			<input type="email" name="email" id="email" value="<?php p($_['email']); ?>"
				<?php if(!$_['displayNameChangeSupported']) { print_unescaped('class="hidden"'); } ?>
				placeholder="<?php p($l->t('Your email address')); ?>"
				autocomplete="on" autocapitalize="off" autocorrect="off" />
			<?php if(!$_['displayNameChangeSupported']) { ?>
				<span><?php if(isset($_['email']) && !empty($_['email'])) { p($_['email']); } else { p($l->t('No email address set')); }?></span>
			<?php } ?>
			<?php if($_['displayNameChangeSupported']) { ?>
				<br />
				<em><?php p($l->t('For password recovery and notifications')); ?></em>
			<?php } ?>
			<span class="icon-checkmark hidden"/>
			<input type="hidden" id="emailscope" value="<?php p($_['emailScope']) ?>">
		</form>
	</div>
	<div class="personal-settings-setting-box">
		<form id="phoneform" class="section">
			<h2>
				<label for="phone"><?php p($l->t('Phone number')); ?></label>
				<span class="icon-password"/>
			</h2>
			<input type="tel" id="phone" name="phone"
			       value="<?php p($_['phone']) ?>"
				   placeholder="<?php p($l->t('Your phone number')); ?>"
			       autocomplete="on" autocapitalize="off" autocorrect="off" />
			<span class="icon-checkmark hidden"/>
			<input type="hidden" id="phonescope" value="<?php p($_['phoneScope']) ?>">
		</form>
	</div>
	<div class="personal-settings-setting-box">
		<form id="addressform" class="section">
			<h2>
				<label for="address"><?php p($l->t('Address')); ?></label>
				<span class="icon-password"/>
			</h2>
			<input type="text" id="address" name="address"
				   placeholder="<?php p($l->t('Your postal address')); ?>"
				   value="<?php p($_['address']) ?>"
				   autocomplete="on" autocapitalize="off" autocorrect="off" />
			<span class="icon-checkmark hidden"/>
			<input type="hidden" id="addressscope" value="<?php p($_['addressScope']) ?>">
		</form>
	</div>
	<div class="personal-settings-setting-box">
		<form id="websiteform" class="section">
			<h2>
				<label for="website"><?php p($l->t('Website')); ?></label>
				<span class="icon-password"/>
			</h2>
			<input type="text" name="website" id="website" value="<?php p($_['website']); ?>"
			       placeholder="<?php p($l->t('Your website')); ?>"
			       autocomplete="on" autocapitalize="off" autocorrect="off" />
			<span class="icon-checkmark hidden"/>
			<input type="hidden" id="websitescope" value="<?php p($_['websiteScope']) ?>">
		</form>
	</div>
	<div class="personal-settings-setting-box">
		<form id="twitterform" class="section">
			<h2>
				<label for="twitter"><?php p($l->t('Twitter')); ?></label>
				<span class="icon-password"/>
			</h2>
			<input type="text" name="twitter" id="twitter" value="<?php p($_['twitter']); ?>"
				   placeholder="<?php p($l->t('Your Twitter handle')); ?>"
				   autocomplete="on" autocapitalize="off" autocorrect="off" />
			<span class="icon-checkmark hidden"/>
			<input type="hidden" id="twitterscope" value="<?php p($_['twitterScope']) ?>">
		</form>
	</div>

	<span class="msg"></span>
</div>
</div>

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
		autocomplete="off" autocapitalize="off" autocorrect="off" />
	<label for="pass2" class="hidden-visually"><?php p($l->t('New password'));?>: </label>
	<input type="password" id="pass2" name="newpassword"
		placeholder="<?php p($l->t('New password')); ?>"
		data-typetoggle="#personal-show"
		autocomplete="off" autocapitalize="off" autocorrect="off" />
	<input type="checkbox" id="personal-show" name="show" /><label for="personal-show" class="personal-show-label"></label>
	<input id="passwordbutton" type="submit" value="<?php p($l->t('Change password')); ?>" />
	<br/>
</form>
<?php
}
?>

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

	<?php if(OC_APP::isEnabled('firstrunwizard')) {?>
		<p><a class="button" href="#" id="showWizard"><?php p($l->t('Show First Run Wizard again'));?></a></p>
	<?php }?>
</div>

<div id="sessions" class="section">
	<h2><?php p($l->t('Sessions'));?></h2>
	<span class="hidden-when-empty"><?php p($l->t('Web, desktop and mobile clients currently logged in to your account.'));?></span>
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
</div>

<div id="apppasswords" class="section">
	<h2><?php p($l->t('App passwords'));?></h2>
	<p><?php p($l->t('Passcodes that give an app or device permissions to access your account.'));?></p>
	<table class="icon-loading">
		<thead class="hidden-when-empty">
			<tr>
				<th><?php p($l->t('Name'));?></th>
				<th><?php p($l->t('Last activity'));?></th>
				<th></th>
			</tr>
		</thead>
		<tbody class="token-list">
		</tbody>
	</table>
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
