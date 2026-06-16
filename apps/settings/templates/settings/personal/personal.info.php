<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** @var \OCP\IL10N $l */
/** @var array $_ */

\OCP\Util::addScript('settings', 'vue-settings-personal-info');
?>
<?php if (!$_['isFairUseOfFreePushService']) : ?>
	<div class="section">
		<div class="warning">
			<?php p($l->t('This community release of Nextcloud is unsupported and instant notifications are unavailable.')); ?>
		</div>
	</div>
<?php endif; ?>

<div id="personal-settings">
	<h2 class="hidden-visually"><?php p($l->t('Personal info')); ?></h2>
	<div id="vue-avatar-section"></div>
	<?php if ($_['profileEnabledGlobally']) : ?>
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
	<div class="personal-settings-setting-box">
		<div id="vue-displayname-section"></div>
	</div>
	<div class="personal-settings-setting-box">
		<div id="vue-pronouns-section"></div>
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
		<div id="vue-birthday-section"></div>
	</div>
	<div class="personal-settings-setting-box personal-settings-language-box">
		<div id="vue-language-section"></div>
	</div>
	<div class="personal-settings-setting-box personal-settings-locale-box">
		<div id="vue-locale-section"></div>
	</div>
	<div class="personal-settings-setting-box">
		<div id="vue-fdow-section"></div>
	</div>
	<div class="personal-settings-setting-box">
		<div id="vue-timezone-section"></div>
	</div>
	<div class="personal-settings-setting-box">
		<div id="vue-website-section"></div>
	</div>
	<div class="personal-settings-setting-box">
		<div id="vue-twitter-section"></div>
	</div>
	<div class="personal-settings-setting-box">
		<div id="vue-bluesky-section"></div>
	</div>
	<div class="personal-settings-setting-box">
		<div id="vue-fediverse-section"></div>
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
	<span class="msg"></span>

	<div id="personal-settings-group-container"></div>
</div>
<?php if ($_['profileEnabledGlobally']) : ?>
	<div class="personal-settings-section">
		<div id="vue-profile-visibility-section"></div>
	</div>
<?php endif; ?>
