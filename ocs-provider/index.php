<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

require_once __DIR__ . '/../lib/base.php';

header('Content-Type: application/json');

$server = \OC::$server;

$controller = new \OC\OCS\Provider(
	'ocs_provider',
	$server->getRequest(),
	$server->getAppManager()
);
echo $controller->buildProviderList()->render();
