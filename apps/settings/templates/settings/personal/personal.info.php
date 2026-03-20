<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** @var \OCP\IL10N $l */
/** @var array $_ */

\OCP\Util::addScript('settings', 'vue-settings-personal-info');

$isFairUseOfFreePushService = (bool)($_['isFairUseOfFreePushService'] ?? true);
$profileEnabledGlobally = (bool)($_['profileEnabledGlobally'] ?? false);

$settingSections = [
	['id' => 'vue-displayname-section'],
	['id' => 'vue-pronouns-section'],
	['id' => 'vue-email-section'],
	['id' => 'vue-phone-section'],
	['id' => 'vue-location-section'],
	['id' => 'vue-birthday-section'],
	['id' => 'vue-language-section', 'boxClass' => 'personal-settings-setting-box personal-settings-language-box'],
	['id' => 'vue-locale-section', 'boxClass' => 'personal-settings-setting-box personal-settings-locale-box'],
	['id' => 'vue-fdow-section'],
	['id' => 'vue-timezone-section'],
	['id' => 'vue-website-section'],
	['id' => 'vue-twitter-section'],
	['id' => 'vue-bluesky-section'],
	['id' => 'vue-fediverse-section'],
	['id' => 'vue-organisation-section', 'profileOnly' => true],
	['id' => 'vue-role-section', 'profileOnly' => true],
	['id' => 'vue-headline-section', 'profileOnly' => true],
	['id' => 'vue-biography-section', 'profileOnly' => true],
];

$renderSettingBox = static function (string $sectionId, string $boxClass = 'personal-settings-setting-box'): void { ?>
	<div class="<?php p($boxClass); ?>">
		<div id="<?php p($sectionId); ?>"></div>
	</div>
<?php };
?>

<?php if (!$isFairUseOfFreePushService): ?>
	<div class="section">
		<div class="warning">
			<?php p($l->t('This community release of Nextcloud is unsupported and instant notifications are unavailable.')); ?>
		</div>
	</div>
<?php endif; ?>

<div id="personal-settings">
	<h2 class="hidden-visually"><?php p($l->t('Personal info')); ?></h2>

	<div id="vue-avatar-section"></div>

	<?php if ($profileEnabledGlobally): ?>
		<div class="personal-settings-setting-box personal-settings-setting-box-profile">
			<div id="vue-profile-section"></div>
		</div>
		<div class="personal-settings-setting-box personal-settings-setting-box-detail">
			<div id="vue-details-section"></div>
		</div>
	<?php else: ?>
		<div class="personal-settings-setting-box personal-settings-setting-box-detail--without-profile">
			<div id="vue-details-section"></div>
		</div>
	<?php endif; ?>

	<?php foreach ($settingSections as $section): ?>
		<?php
		$isProfileOnly = $section['profileOnly'] ?? false;
		if ($isProfileOnly && !$profileEnabledGlobally) {
			continue;
		}

		$sectionId = $section['id'];
		$boxClass = $section['boxClass'] ?? 'personal-settings-setting-box';
		$renderSettingBox($sectionId, $boxClass);
		?>
	<?php endforeach; ?>

	<span class="msg"></span>
	<div id="personal-settings-group-container"></div>
</div>

<?php if ($profileEnabledGlobally): ?>
	<div class="personal-settings-section">
		<div id="vue-profile-visibility-section"></div>
	</div>
<?php endif; ?>
