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
 * Set-up
 */

// Admin Config Customization
$debugging = Server::get(IConfig::class)->getSystemValueBool('debug', false);
$sendInvitations = Server::get(IConfig::class)->getAppValue('dav', 'sendInvitations', 'yes') === 'yes'; // XXX IAppConfig?

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

// DAV Principals (`/principals`)
$principalBackend = new Principal(
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
$principalCollection = new \Sabre\CalDAV\Principal\Collection($principalBackend);
$principalCollection->disableListing = !$debugging;

// CalDAV (`/calendars`)
$calDavBackend = new CalDavBackend(
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
$calendarRoot = new CalendarRoot(
	$principalBackend,
	$calDavBackend,
	trim($principalPrefix, '/'), // needed here unlike `Auth()` & `Principal()`
	Server::get(LoggerInterface::class),
);
$calendarRoot->disableListing = !$debugging;

// Server
$nodes = [ // Directory Tree
	$principalCollection, // `/principals`
	$calendarRoot, // `/calendars`
];
$server = new \Sabre\DAV\Server($nodes);
$server::$exposeVersion = false;
$server->httpRequest->setUrl(Server::get(IRequest::class)->getRequestUri());
if (isset($baseuri)) { /** @var string $baseuri defined in remote.php */
	$server->setBaseUri($baseuri);
}
if ($debugging) { // default is false
	$server->addPlugin(new Sabre\DAV\Browser\Plugin());
}
if ($sendInvitations) { // default is true
	$server->addPlugin(Server::get(IMipPlugin::class));
}
$server->addPlugin(
	new MaintenancePlugin(
		Server::get(IConfig::class),
		Server::get(IFactory::class)->get('dav'), // L10N
	)
);
$server->addPlugin($authPlugin);
$server->addPlugin(new \Sabre\CalDAV\Plugin());
$server->addPlugin(new LegacyDAVACL());
$server->addPlugin(new \Sabre\DAV\Sync\Plugin());
$server->addPlugin(new \Sabre\CalDAV\ICSExportPlugin());
$server->addPlugin(
	new \OCA\DAV\CalDAV\Schedule\Plugin(
		Server::get(IConfig::class),
		Server::get(LoggerInterface::class),
		Server::get(DefaultCalendarValidator::class),
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

/**
 * Start the Server!
 */
$server->start();
