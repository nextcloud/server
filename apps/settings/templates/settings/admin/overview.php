<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** @var \OCP\IL10N $l */
/** @var array $_ */
/** @var \OCP\Defaults $theme */

?>

<div id="vue-admin-settings-setup-checks"></div>

<div id="version" class="section">
	<!-- should be the last part, so Updater can follow if enabled (it has no heading therefore). -->
	<h2><?php p($l->t('Version'));?></h2>
	<p><strong><a href="<?php print_unescaped($theme->getBaseUrl()); ?>" rel="noreferrer noopener" target="_blank">Nextcloud Hub 25 Autumn</a> (<?php p($_['version']) ?>)</strong></p>
</div>
