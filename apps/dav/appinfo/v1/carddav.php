<?php

/**
 * SPDX-FileCopyrightText: 2016-2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
use OC\KnownUser\KnownUserService;
use OCA\DAV\AppInfo\PluginManager;
use OCA\DAV\CalDAV\Proxy\ProxyMapper;
use OCA\DAV\CardDAV\AddressBookRoot;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\ImageExportPlugin;
use OCA\DAV\CardDAV\PhotoCache;
use OCA\DAV\CardDAV\Security\CardDavRateLimitingPlugin;
use OCA\DAV\CardDAV\Validation\CardDavValidatePlugin;
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

$cardDavBackend = new CardDavBackend( // /addressbooks
	Server::get(IDBConnection::class),
	$principalBackend,
	Server::get(IUserManager::class),
	Server::get(IEventDispatcher::class),
	Server::get(\OCA\DAV\CardDAV\Sharing\Backend::class),
	Server::get(IConfig::class),
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

/**
 * Define the nodes we're handling here:
 * - /principals
 * - /addressbooks
 */
$principalCollection = new \Sabre\CalDAV\Principal\Collection($principalBackend); // /principals
$principalCollection->disableListing = !$debugging;

$addressBookRoot = new AddressBookRoot( // /addressbooks
	$principalBackend,
	$cardDavBackend,
	new PluginManager(
		\OC::$server,
		Server::get(IAppManager::class)
	),
	Server::get(IUserSession::class)->getUser(),
	Server::get(IGroupManager::class),
	'principals',
);
$addressBookRoot->disableListing = !$debugging;

$nodes = [
	$principalCollection, // /principals
	$addressBookRoot, // /addressbooks
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
$server->addPlugin(
	new MaintenancePlugin(
		Server::get(IConfig::class),
		Server::get(IFactory::class)->get('dav'), // L10N
	)
);
$server->addPlugin(new \Sabre\DAV\Auth\Plugin($authBackend));
$server->addPlugin(new \Sabre\CardDAV\Plugin());
$server->addPlugin(new LegacyDAVACL());
$server->addPlugin(new \Sabre\DAV\Sync\Plugin());
$server->addPlugin(new \Sabre\CardDAV\VCFExportPlugin());
$server->addPlugin(new ImageExportPlugin(Server::get(PhotoCache::class)));
$server->addPlugin(
	new ExceptionLoggerPlugin(
		'carddav',
		Server::get(LoggerInterface::class)
	)
);
$server->addPlugin(Server::get(CardDavRateLimitingPlugin::class));
$server->addPlugin(Server::get(CardDavValidatePlugin::class));

// Start a DAV server
$server->start();
