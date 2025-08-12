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
use OCP\L10N\IFactory;
use OCP\SabrePluginEvent;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Server;
use Psr\Log\LoggerInterface;

/* Loads in the scope of /remote.php */

/**
 * Low-level
 */

// try to keep client disconnects from abruptly aborting execution (best effort)
ignore_user_abort(true);
// Turn off output buffer (to prevent memory problems)
while (ob_get_level()) {
	ob_end_clean();
}

/** XXX Not why the above aren't either:
 * (a) universal and thus handled elsewhere so all code paths benefit
 * (such as in existing `OC::setRequiredIniValues()`); or (b) removed
 * since we seem not to need them elsewhere for the most part.
 */

/**
 * Set-up
 */

// DAV Authentication
$principalPrefix = 'principals/';
$authBackend = new Auth(
	Server::get(ISession::class),
	Server::get(IUserSession::class),
	Server::get(IRequest::class),
	Server::get(\OC\Authentication\TwoFactorAuth\Manager::class),
	Server::get(IThrottler::class),
	$principalPrefix,
);
$authPlugin = new \Sabre\DAV\Auth\Plugin($authBackend);
$bearerAuthPlugin = new BearerAuth( // XXX why is Bearer not enabled on all DAV routes?
	Server::get(IUserSession::class),
	Server::get(ISession::class),
	Server::get(IRequest::class),
	Server::get(IConfig::class),
);
$authPlugin->addBackend($bearerAuthPlugin);

// Server
$serverFactory = new ServerFactory(
	Server::get(IConfig::class),
	Server::get(LoggerInterface::class),
	Server::get(IDBConnection::class),
	Server::get(IUserSession::class),
	Server::get(IMountManager::class),
	Server::get(ITagManager::class),
	Server::get(IRequest::class),
	Server::get(IPreview::class),
	Server::get(IEventDispatcher::class),
	Server::get(IFactory::class)->get('dav'), // L10N
);
$viewCallback = function () {
	return Filesystem::getView(); // i.e. use the View of the logged in user
};
$server = $serverFactory->createServer( // default plugins are specified within `createServer()` not here)
	false,
	$baseuri, /** @var string $baseuri defined in remote.php */
	Server::get(IRequest::class)->getRequestUri(),
	$authPlugin,
	$viewCallback,
);

// Trigger registration of any additional plugins
$dispatcher = Server::get(IEventDispatcher::class);
$event = new SabrePluginAddEvent($server);
$dispatcher->dispatchTyped($event);
/** @deprecated 28.0.0 */
$legacyEvent = new SabrePluginEvent($server);
$dispatcher->dispatch('OCA\DAV\Connector\Sabre::addPlugin', $legacyEvent);

/**
 * Start the Server!
 */
$server->start();
