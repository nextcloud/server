<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
use OC\Files\Filesystem;
use OCA\DAV\Connector\Sabre\Auth;
use OCA\DAV\Connector\Sabre\BearerAuth;
use OCA\DAV\Connector\Sabre\ServerFactory;
use OCA\DAV\Events\SabrePluginAddEvent;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Mount\IMountManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IPreview;
use OCP\IRequest;
use OCP\ISession;
use OCP\ITagManager;
use OCP\IUserSession;
use OCP\SabrePluginEvent;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Server;
use Psr\Log\LoggerInterface;

// no php execution timeout for webdav
if (!str_contains(@ini_get('disable_functions'), 'set_time_limit')) {
	@set_time_limit(0);
}
ignore_user_abort(true);

// Turn off output buffering to prevent memory problems
\OC_Util::obEnd();

$dispatcher = Server::get(IEventDispatcher::class);

$serverFactory = new ServerFactory(
	Server::get(IConfig::class),
	Server::get(LoggerInterface::class),
	Server::get(IDBConnection::class),
	Server::get(IUserSession::class),
	Server::get(IMountManager::class),
	Server::get(ITagManager::class),
	Server::get(IRequest::class),
	Server::get(IPreview::class),
	$dispatcher,
	\OC::$server->getL10N('dav')
);

// Backends
$authBackend = new Auth(
	Server::get(ISession::class),
	Server::get(IUserSession::class),
	Server::get(IRequest::class),
	Server::get(\OC\Authentication\TwoFactorAuth\Manager::class),
	Server::get(IThrottler::class),
	'principals/'
);
$authPlugin = new \Sabre\DAV\Auth\Plugin($authBackend);
$bearerAuthPlugin = new BearerAuth(
	Server::get(IUserSession::class),
	Server::get(ISession::class),
	Server::get(IRequest::class),
	Server::get(IConfig::class),
);
$authPlugin->addBackend($bearerAuthPlugin);

$requestUri = Server::get(IRequest::class)->getRequestUri();

$server = $serverFactory->createServer(false, $baseuri, $requestUri, $authPlugin, function () {
	// use the view for the logged in user
	return Filesystem::getView();
});

// allow setup of additional plugins
$event = new SabrePluginEvent($server);
$dispatcher->dispatch('OCA\DAV\Connector\Sabre::addPlugin', $event);
$event = new SabrePluginAddEvent($server);
$dispatcher->dispatchTyped($event);

// And off we go!
$server->exec();
