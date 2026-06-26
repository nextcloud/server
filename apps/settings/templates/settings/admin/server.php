<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** @var \OCP\IL10N $l */
/** @var array $_ */

\OCP\Util::addScript('settings', 'vue-settings-admin-basic-settings');
?>

<div id="vue-admin-background-job"></div>

<?php if ($_['profileEnabledGlobally']) : ?>
	<div id="vue-admin-profile-settings"></div>
<?php endif; ?>
