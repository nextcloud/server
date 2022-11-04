<?php

/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Thomas Citharel <tcit@tcit.fr>
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

/** @var \OCP\IL10N $l */
/** @var array $_ */

script('settings', [
	'usersettings',
	'templates',
	'federationsettingsview',
	'federationscopemenu',
	'settings/personalInfo',
	'vue-settings-personal-info',
]);
?>
<?php if (!$_['isFairUseOfFreePushService']) : ?>
	<div class="section">
		<div class="warning">
			<?php p($l->t('This community release of Nextcloud is unsupported and instant notifications are unavailable.')); ?>
		</div>
	</div>
<?php endif; ?>

<div id="personal-settings" data-federation-enabled="<?php p($_['federationEnabled'] ? 'true' : 'false') ?>"
							data-lookup-server-upload-enabled="<?php p($_['lookupServerUploadEnabled'] ? 'true' : 'false') ?>">
	<h2 class="hidden-visually"><?php p($l->t('Personal info')); ?></h2>
	<div id="personal-settings-avatar-container" class="personal-settings-container">
		<div>
			<form id="avatarform" class="section" method="post" action="<?php p(\OC::$server->getURLGenerator()->linkToRoute('core.avatar.postAvatar')); ?>">
				<h3>
					<?php p($l->t('Profile picture')); ?>
					<a href="#" class="federation-menu" aria-label="<?php p($l->t('Change privacy level of profile picture')); ?>">
						<span class="icon-federation-menu icon-password">
							<span class="icon-triangle-s"></span>
						</span>
					</a>
				</h3>
				<div id="displayavatar">
					<div class="avatardiv"></div>
					<div class="warning hidden"></div>
					<?php if ($_['avatarChangeSupported']) : ?>
						<label for="uploadavatar" class="inlineblock button icon-upload svg" id="uploadavatarbutton" title="<?php p($l->t('Upload new')); ?>" tabindex="0"></label>
						<button class="inlineblock button icon-folder svg" id="selectavatar" title="<?php p($l->t('Select from Files')); ?>"></button>
						<button class="hidden button icon-delete svg" id="removeavatar" title="<?php p($l->t('Remove image')); ?>"></button>
						<input type="file" name="files[]" id="uploadavatar" class="hiddenuploadfield" accept="image/*">
						<p><em><?php p($l->t('png or jpg, max. 20 MB')); ?></em></p>
					<?php else : ?>
						<?php p($l->t('Picture provided by original account')); ?>
					<?php endif; ?>
				</div>

				<div id="cropper" class="hidden">
					<div class="inner-container">
						<p style="width: 300px; margin-top: 0.5rem"><?php p($l->t('Please note that it can take up to 24 hours for the avatar to get updated everywhere.')); ?></p>
						<div class="inlineblock button" id="abortcropperbutton"><?php p($l->t('Cancel')); ?></div>
						<div class="inlineblock button primary" id="sendcropperbutton"><?php p($l->t('Choose as profile picture')); ?></div>
					</div>
				</div>
				<span class="icon-checkmark hidden"></span>
				<span class="icon-error hidden"></span>
				<input type="hidden" id="avatarscope" value="<?php p($_['avatarScope']) ?>">
			</form>
		</div>
		<div class="personal-settings-setting-box personal-settings-group-box section">
			<h3><?php p($l->t('Details')); ?></h3>
			<div id="groups" class="personal-info icon-user">
				<p><?php p($l->t('You are a member of the following groups:')); ?></p>
				<p id="groups-groups">
					<strong><?php p(implode(', ', $_['groups'])); ?></strong>
				</p>
			</div>
			<div id="quota" class="personal-info icon-quota">
				<div class="quotatext-bg">
					<p class="quotatext">
						<?php if ($_['quota'] === \OCP\Files\FileInfo::SPACE_UNLIMITED) : ?>
							<?php print_unescaped($l->t(
								'You are using <strong>%s</strong>',
								[$_['usage']]
							)); ?>
						<?php else : ?>
							<?php print_unescaped($l->t(
								'You are using <strong>%1$s</strong> of <strong>%2$s</strong> (<strong>%3$s %%</strong>)',
								[$_['usage'], $_['total_space'],  $_['usage_relative']]
							)); ?>
						<?php endif ?>
					</p>
				</div>
				<progress value="<?php p($_['usage_relative']); ?>" max="100" <?php if ($_['usage_relative'] > 80) : ?> class="warn" <?php endif; ?>></progress>
			</div>
		</div>
	</div>

	<div class="personal-settings-container">
		<div class="personal-settings-setting-box">
			<div id="vue-displayname-section"></div>
		</div>
		<div class="personal-settings-setting-box">
			<div id="vue-email-section"></div>
		</div>
		<div class="personal-settings-setting-box">
			<div id="vue-phone-section"></div>
		</div>
		<div class="personal-settings-setting-box">
			<div id="vue-location-section"></div>
		</div>
		<div class="personal-settings-setting-box">
			<div id="vue-website-section"></div>
		</div>
		<div class="personal-settings-setting-box">
			<div id="vue-twitter-section"></div>
		</div>
		<?php if ($_['profileEnabledGlobally']) : ?>
			<div class="personal-settings-setting-box">
				<div id="vue-organisation-section"></div>
			</div>
			<div class="personal-settings-setting-box">
				<div id="vue-role-section"></div>
			</div>
			<div class="personal-settings-setting-box">
				<div id="vue-headline-section"></div>
			</div>
			<div class="personal-settings-setting-box">
				<div id="vue-biography-section"></div>
			</div>
		<?php endif; ?>
	</div>

	<div class="profile-settings-container">
		<?php if ($_['profileEnabledGlobally']) : ?>
			<div class="personal-settings-setting-box">
				<div id="vue-profile-section"></div>
			</div>
		<?php endif; ?>
		<div class="personal-settings-setting-box personal-settings-language-box">
			<div id="vue-language-section"></div>
		</div>
		<div class="personal-settings-setting-box personal-settings-locale-box">
			<?php if (isset($_['activelocale'])) { ?>
				<form id="locale" class="section">
					<h3>
						<label for="localeinput"><?php p($l->t('Locale')); ?></label>
					</h3>
					<select id="localeinput" name="lang" data-placeholder="<?php p($l->t('Locale')); ?>">
						<option value="<?php p($_['activelocale']['code']); ?>">
							<?php p($l->t($_['activelocale']['name'])); ?>
						</option>
						<optgroup label="––––––––––"></optgroup>
						<?php foreach ($_['localesForLanguage'] as $locale) : ?>
							<option value="<?php p($locale['code']); ?>">
								<?php p($l->t($locale['name'])); ?>
							</option>
						<?php endforeach; ?>
						<optgroup label="––––––––––"></optgroup>
						<option value="<?php p($_['activelocale']['code']); ?>">
							<?php p($l->t($_['activelocale']['name'])); ?>
						</option>
						<?php foreach ($_['locales'] as $locale) : ?>
							<option value="<?php p($locale['code']); ?>">
								<?php p($l->t($locale['name'])); ?>
							</option>
						<?php endforeach; ?>
					</select>
					<div id="localeexample" class="personal-info icon-timezone">
						<p>
							<span id="localeexample-date"></span> <span id="localeexample-time"></span>
						</p>
						<p id="localeexample-fdow"></p>
					</div>
				</form>
			<?php } ?>
		</div>
		<span class="msg"></span>
	</div>

	<div id="personal-settings-group-container">

	</div>

</div>

<?php if ($_['profileEnabledGlobally']) : ?>
	<div class="personal-settings-section">
		<div id="vue-profile-visibility-section"></div>
	</div>
<?php endif; ?>
