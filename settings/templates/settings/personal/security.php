<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

script('settings', [
	'authtoken',
	'authtoken_collection',
	'templates',
	'authtoken_view',
	'settings/authtoken-init'
]);

if($_['passwordChangeSupported']) {
	script('settings', 'security_password');
	script('jquery-showpassword');
	vendor_script('strengthify/jquery.strengthify.min');
	vendor_style('strengthify/strengthify');
}

?>
<?php if($_['passwordChangeSupported']) { ?>
<div id="security-password" class="section">
	<h2 class="inlineblock"><?php p($l->t('Password'));?></h2>
	<span id="password-error-msg" class="msg success hidden">Saved</span>
	<div class="personal-settings-setting-box personal-settings-password-box">
			<form id="passwordform">
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

			</form>
	</div>
	<span class="msg"></span>
</div>
<?php } ?>

<div id="security" class="section">
	<h2><?php p($l->t('Devices & sessions'));?></h2>
	<p class="settings-hint hidden-when-empty"><?php p($l->t('Web, desktop and mobile clients currently logged in to your account.'));?></p>
	<table class="icon-loading">
		<thead class="token-list-header">
			<tr>
				<th></th>
				<th><?php p($l->t('Device'));?></th>
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

<div id="two-factor-auth" class="section">
	<h2><?php p($l->t('Two-Factor Authentication'));?></h2>
	<ul>
	<?php foreach ($_['twoFactorProviderData']['providers'] as $data) { ?>
		<li>
			<?php
			/** @var \OCP\Authentication\TwoFactorAuth\IProvidesPersonalSettings $provider */
			$provider = $data['provider'];
			if ($provider instanceof \OCP\Authentication\TwoFactorAuth\IProvidesIcons) {
				$icon = $provider->getDarkIcon();
			} else {
				$icon = image_path('core', 'actions/password.svg');
			}
			/** @var \OCP\Authentication\TwoFactorAuth\IPersonalProviderSettings $settings */
			$settings = $data['settings'];
			?>
			<h3>
				<img class="two-factor-provider-settings-icon" src="<?php p($icon) ?>" alt="">
				<?php p($provider->getDisplayName()) ?>
			</h3>
			<?php print_unescaped($settings->getBody()->fetchPage()) ?>
		</li>
	<?php } ?>
	</ul>
</div>
