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
	'settings',
	'templates',
	'vue-settings-personal-security',
]);

if($_['passwordChangeSupported']) {
	script('settings', 'security_password');
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
					<input type="checkbox" id="personal-show" class="hidden-visually" name="show" /><label for="personal-show" class="personal-show-label"></label>
				</div>

				<input id="passwordbutton" type="submit" value="<?php p($l->t('Change password')); ?>" />

			</form>
	</div>
	<span class="msg"></span>
</div>
<?php } ?>

<div id="two-factor-auth" class="section">
	<h2><?php p($l->t('Two-Factor Authentication'));?></h2>
	<a target="_blank" rel="noreferrer noopener" class="icon-info"
	   title="<?php p($l->t('Open documentation'));?>"
	   href="<?php p(link_to_docs('user-2fa')); ?>"></a>
	<p class="settings-hint"><?php p($l->t('Use a second factor besides your password to increase security for your account.'));?></p>
	<ul>
	<?php foreach ($_['twoFactorProviderData']['providers'] as $data) { ?>
		<li>
			<?php

			/** @var \OCP\Authentication\TwoFactorAuth\IProvidesPersonalSettings $provider */
			$provider = $data['provider'];
			//Handle 2FA provider icons and theme
			if ($provider instanceof \OCP\Authentication\TwoFactorAuth\IProvidesIcons) {
				if ($_['themedark']) {
					$icon = $provider->getLightIcon();
				}
				else {
					$icon = $provider->getDarkIcon();
				}
				//fallback icon if the 2factor provider doesn't provide an icon.
			} else {
				if ($_['themedark']) {
					$icon = image_path('core', 'actions/password-white.svg');
				}
				else {
					$icon = image_path('core', 'actions/password.svg');
				}

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

<div id="security" class="section"></div>
