<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use OCA\DAV\Db\DirectMapper;
use OCA\DAV\Direct\ServerFactory;
use OCP\AppFramework\Utility\ITimeFactory;

// no php execution timeout for webdav
if (!str_contains(@ini_get('disable_functions'), 'set_time_limit')) {
	@set_time_limit(0);
}
ignore_user_abort(true);

// Turn off output buffering to prevent memory problems
\OC_Util::obEnd();

$requestUri = \OC::$server->getRequest()->getRequestUri();

/** @var ServerFactory $serverFactory */
$serverFactory = \OC::$server->query(ServerFactory::class);
$server = $serverFactory->createServer(
	$baseuri,
	$requestUri,
	\OC::$server->getRootFolder(),
	\OC::$server->query(DirectMapper::class),
	\OC::$server->query(ITimeFactory::class),
	\OC::$server->getBruteForceThrottler(),
	\OC::$server->getRequest()
);

$server->exec();
