<?php

use OCA\User_LDAP\Configuration;
use OCA\User_LDAP\Helper;
use OCP\Server;

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// Check user and app status
\OC_JSON::checkAdminUser();
\OC_JSON::checkAppEnabled('user_ldap');
\OC_JSON::callCheck();

$helper = Server::get(Helper::class);
$serverConnections = $helper->getServerConfigurationPrefixes();
sort($serverConnections);
$lk = array_pop($serverConnections);
$ln = (int)str_replace('s', '', $lk);
$nk = 's' . str_pad((string)($ln + 1), 2, '0', STR_PAD_LEFT);

$resultData = ['configPrefix' => $nk];

$newConfig = new Configuration($nk, false);
if (isset($_POST['copyConfig'])) {
	$originalConfig = new Configuration($_POST['copyConfig']);
	$newConfig->setConfiguration($originalConfig->getConfiguration());
} else {
	$configuration = new Configuration($nk, false);
	$newConfig->setConfiguration($configuration->getDefaults());
	$resultData['defaults'] = $configuration->getDefaults();
}
$newConfig->saveConfiguration();

\OC_JSON::success($resultData);
