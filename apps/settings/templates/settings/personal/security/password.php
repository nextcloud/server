<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

if ($_['passwordChangeSupported']) {
	\OCP\Util::addScript('settings', 'vue-settings-personal-password');
}
?>
<div id="security-password"></div>
