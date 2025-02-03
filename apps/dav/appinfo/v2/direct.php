<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use OCA\DAV\Db\DirectMapper;
use OCA\DAV\Direct\ServerFactory;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Server;

// no php execution timeout for webdav
if (!str_contains(@ini_get('disable_functions'), 'set_time_limit')) {
	@set_time_limit(0);
}
ignore_user_abort(true);

// Turn off output buffering to prevent memory problems
\OC_Util::obEnd();

$requestUri = Server::get(IRequest::class)->getRequestUri();

/** @var ServerFactory $serverFactory */
$serverFactory = Server::get(ServerFactory::class);
$server = $serverFactory->createServer(
	$baseuri,
	$requestUri,
	Server::get(IRootFolder::class),
	Server::get(DirectMapper::class),
	Server::get(ITimeFactory::class),
	Server::get(IThrottler::class),
	Server::get(IRequest::class)
);

$server->exec();
