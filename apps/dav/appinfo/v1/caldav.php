<?php

/**
 * SPDX-FileCopyrightText: 2016-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
use OC\KnownUser\KnownUserService;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\CalendarRoot;
use OCA\DAV\CalDAV\DefaultCalendarValidator;
use OCA\DAV\CalDAV\Proxy\ProxyMapper;
use OCA\DAV\CalDAV\Schedule\IMipPlugin;
use OCA\DAV\CalDAV\Security\RateLimitingPlugin;
use OCA\DAV\CalDAV\Validation\CalDavValidatePlugin;
use OCA\DAV\Connector\LegacyDAVACL;
use OCA\DAV\Connector\Sabre\Auth;
use OCA\DAV\Connector\Sabre\ExceptionLoggerPlugin;
use OCA\DAV\Connector\Sabre\MaintenancePlugin;
use OCA\DAV\Connector\Sabre\Principal;
use OCP\Accounts\IAccountManager;
use OCP\App\IAppManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\ISession;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Security\ISecureRandom;
use OCP\Server;
use Psr\Log\LoggerInterface;

/* Loads in the scope of /remote.php */

/**
 * Establish the backends
 */
$principalPrefix = 'principals/';

$principalBackend = new Principal( // /principals
	Server::get(IUserManager::class),
	Server::get(IGroupManager::class),
	Server::get(IAccountManager::class),
	Server::get(\OCP\Share\IManager::class),
	Server::get(IUserSession::class),
	Server::get(IAppManager::class),
	Server::get(ProxyMapper::class),
	Server::get(KnownUserService::class),
	Server::get(IConfig::class),
	Server::get(IFactory::class), // L10N
	$principalPrefix,
);

$calDavBackend = new CalDavBackend( // /calendars
	Server::get(IDBConnection::class),
	$principalBackend,
	Server::get(IUserManager::class),
	Server::get(ISecureRandom::class),
	Server::get(LoggerInterface::class),
	Server::get(IEventDispatcher::class),
	Server::get(IConfig::class),
	Server::get(\OCA\DAV\CalDAV\Sharing\Backend::class),
	true, // legacyEndpoint
);

$authBackend = new Auth(
	Server::get(ISession::class),
	Server::get(IUserSession::class),
	Server::get(IRequest::class),
	Server::get(\OC\Authentication\TwoFactorAuth\Manager::class),
	Server::get(IThrottler::class),
	$principalPrefix,
);

/**
 * Load config toggles
 */
$debugging = Server::get(IConfig::class)->getSystemValueBool('debug', false);
$sendInvitations = Server::get(IConfig::class)->getAppValue('dav', 'sendInvitations', 'yes') === 'yes'; // XXX IAppConfig?

/**
 * Define the nodes we're handling here:
 * - /principals
 * - /calendars
 */
$principalCollection = new \Sabre\CalDAV\Principal\Collection($principalBackend); // /principals
$principalCollection->disableListing = !$debugging;

$calendarRoot = new CalendarRoot( // /calendars
	$principalBackend,
	$calDavBackend,
	'principals',
	Server::get(LoggerInterface::class),
);
$calendarRoot->disableListing = !$debugging;

$nodes = [
	$principalCollection, // /principals
	$calendarRoot, // /calendars
];

/**
 * Set up a DAV server for the above nodes
 */
$server = new \Sabre\DAV\Server($nodes);
$server::$exposeVersion = false;
$server->httpRequest->setUrl(Server::get(IRequest::class)->getRequestUri());
/** @var string $baseuri defined in remote.php */
if (isset($baseuri)) {
	$server->setBaseUri($baseuri);
}

/**
 * Enable/configure plugins
 */
if ($debugging) {
	$server->addPlugin(new Sabre\DAV\Browser\Plugin());
}
if ($sendInvitations) {
	$server->addPlugin(Server::get(IMipPlugin::class));
}
$server->addPlugin(
	new MaintenancePlugin(
		Server::get(IConfig::class),
		Server::get(IFactory::class)->get('dav'), // L10N
	)
);
$server->addPlugin(new \Sabre\DAV\Auth\Plugin($authBackend));
$server->addPlugin(new \Sabre\CalDAV\Plugin());
$server->addPlugin(new LegacyDAVACL());
$server->addPlugin(new \Sabre\DAV\Sync\Plugin());
$server->addPlugin(new \Sabre\CalDAV\ICSExportPlugin());
$server->addPlugin(
	new \OCA\DAV\CalDAV\Schedule\Plugin(
		Server::get(IConfig::class),
		Server::get(LoggerInterface::class),
		Server::get(DefaultCalendarValidator::class)
	)
);
$server->addPlugin(
	new ExceptionLoggerPlugin(
		'caldav',
		Server::get(LoggerInterface::class),
	)
);
$server->addPlugin(Server::get(RateLimitingPlugin::class));
$server->addPlugin(Server::get(CalDavValidatePlugin::class));

// Start a DAV server
$server->start();
