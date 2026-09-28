<?php

use OCA\User_LDAP\LDAP;

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// Check user and app status
\OC_JSON::checkAdminUser();
\OC_JSON::checkAppEnabled('user_ldap');
\OC_JSON::callCheck();

$prefix = (string)$_POST['ldap_serverconfig_chooser'];

// Checkboxes are not submitted, when they are unchecked. Set them manually.
// only legacy checkboxes (Advanced and Expert tab) need to be handled here,
// the Wizard-like tabs handle it on their own
$chkboxes = ['ldap_configuration_active', 'ldap_override_main_server',
	'ldap_turn_off_cert_check'];
foreach ($chkboxes as $boxid) {
	if (!isset($_POST[$boxid])) {
		$_POST[$boxid] = 0;
	}
}

$ldapWrapper = new LDAP();
$connection = new \OCA\User_LDAP\Connection($ldapWrapper, $prefix);
$connection->setConfiguration($_POST);
$connection->saveConfiguration();
\OC_JSON::success();
