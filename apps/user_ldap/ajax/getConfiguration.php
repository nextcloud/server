<?php

use OCA\User_LDAP\LDAP;

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// Check user and app status
\OC_JSON::checkAdminUser();
\OC_JSON::checkAppEnabled('user_ldap');
\OC_JSON::callCheck();

$prefix = (string)$_POST['ldap_serverconfig_chooser'];
$ldapWrapper = new LDAP();
$connection = new \OCA\User_LDAP\Connection($ldapWrapper, $prefix);
$configuration = $connection->getConfiguration();
if (isset($configuration['ldap_agent_password']) && $configuration['ldap_agent_password'] !== '') {
	// hide password
	$configuration['ldap_agent_password'] = '**PASSWORD SET**';
}
\OC_JSON::success(['configuration' => $configuration]);
