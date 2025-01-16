<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** @var \OCP\IL10N $l */
/** @var array $_ */
/** @var \OCP\Defaults $theme */

?>

<div id="security-warning" class="section">
	<div class="security-warning__heading">
		<h2><?php p($l->t('Security & setup warnings'));?></h2>
		<a 	target="_blank"
			rel="noreferrer"
			class="icon-info"
			title="<?php p($l->t('Open documentation'));?>"
			href="<?php p(link_to_docs('admin-warnings')); ?>"
			aria-label="<?php p($l->t('Open documentation')); ?>"></a>
	</div>
	<p class="settings-hint"><?php p($l->t('It\'s important for the security and performance of your instance that everything is configured correctly. To help you with that we are doing some automatic checks. Please see the linked documentation for more information.'));?></p>

	<div id="security-warning-state-ok" class="hidden">
		<span class="icon icon-checkmark-white"></span><span class="message"><?php p($l->t('All checks passed.'));?></span>
	</div>
	<div id="security-warning-state-failure" class="hidden">
		<span class="icon icon-close-white"></span><span class="message"><?php p($l->t('There are some errors regarding your setup.'));?></span>
	</div>
	<div id="security-warning-state-warning" class="hidden">
		<span class="icon icon-error-white"></span><span class="message"><?php p($l->t('There are some warnings regarding your setup.'));?></span>
	</div>
	<div id="security-warning-state-loading">
		<span class="icon loading"></span><span class="message"><?php p($l->t('Checking for system and security issues.'));?></span>
	</div>

	<div id="postsetupchecks" data-check-wellknown="<?php if ($_['checkForWorkingWellKnownSetup']) {
		p('true');
	} else {
		p('false');
	} ?>">
		<ul class="errors hidden"></ul>
		<ul class="warnings hidden"></ul>
		<ul class="info hidden"></ul>
	</div>
	<p id="postsetupchecks-hint" class="hidden">
		<?php print_unescaped($l->t('Please double check the <a target="_blank" rel="noreferrer noopener" href="%1$s">installation guides ↗</a>, and check for any errors or warnings in the <a href="%2$s">log</a>.', [link_to_docs('admin-install'), \OC::$server->getURLGenerator()->linkToRoute('settings.AdminSettings.index', ['section' => 'logging'])])); ?>
	</p>

	<p class="extra-top-margin">
		<?php print_unescaped($l->t('Check the security of your Nextcloud over <a target="_blank" rel="noreferrer noopener" href="%s">our security scan ↗</a>.', ['https://scan.nextcloud.com']));?>
	</p>

</div>

<div id="version" class="section">
	<!-- should be the last part, so Updater can follow if enabled (it has no heading therefore). -->
	<h2><?php p($l->t('Version'));?></h2>
	<p><strong><a href="<?php print_unescaped($theme->getBaseUrl()); ?>" rel="noreferrer noopener" target="_blank">Nextcloud Hub 10</a> (<?php p($_['version']) ?>)</strong></p>
</div>
