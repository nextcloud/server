<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

use OCP\App\IAppManager;
use OCP\IRequest;
use OCP\Server;

require_once __DIR__ . '/../lib/base.php';
OC::init();

header('Content-Type: application/json');

$controller = new \OC\OCS\Provider(
	'ocs_provider',
	Server::get(IRequest::class),
	Server::get(IAppManager::class),
);
echo $controller->buildProviderList()->render();
