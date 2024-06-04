<?php

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
$helper = new \OCA\User_LDAP\Helper(\OC::$server->getConfig(), \OC::$server->getDatabaseConnection());
if ($helper->deleteServerConfiguration($prefix)) {
	\OC_JSON::success();
} else {
	$l = \OCP\Util::getL10N('user_ldap');
	\OC_JSON::error(['message' => $l->t('Failed to delete the server configuration')]);
}
