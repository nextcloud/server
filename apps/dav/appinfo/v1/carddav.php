<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// Backends
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
use OCP\Security\Bruteforce\IThrottler;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Sabre\CardDAV\Plugin;

$authBackend = new Auth(
	Server::get(ISession::class),
	Server::get(IUserSession::class),
	Server::get(IRequest::class),
	Server::get(\OC\Authentication\TwoFactorAuth\Manager::class),
	Server::get(IThrottler::class),
	'principals/'
);
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
	\OC::$server->getL10NFactory(),
	'principals/'
);
$db = Server::get(IDBConnection::class);
$cardDavBackend = new CardDavBackend(
	$db,
	$principalBackend,
	Server::get(IUserManager::class),
	Server::get(IEventDispatcher::class),
	Server::get(\OCA\DAV\CardDAV\Sharing\Backend::class),
	Server::get(IConfig::class),
);

$debugging = Server::get(IConfig::class)->getSystemValue('debug', false);

// Root nodes
$principalCollection = new \Sabre\CalDAV\Principal\Collection($principalBackend);
$principalCollection->disableListing = !$debugging; // Disable listing

$pluginManager = new PluginManager(\OC::$server, Server::get(IAppManager::class));
$addressBookRoot = new AddressBookRoot($principalBackend, $cardDavBackend, $pluginManager, Server::get(IUserSession::class)->getUser(), Server::get(IGroupManager::class));
$addressBookRoot->disableListing = !$debugging; // Disable listing

$nodes = [
	$principalCollection,
	$addressBookRoot,
];

// Fire up server
$server = new \Sabre\DAV\Server($nodes);
$server::$exposeVersion = false;
$server->httpRequest->setUrl(Server::get(IRequest::class)->getRequestUri());
$server->setBaseUri($baseuri);
// Add plugins
$server->addPlugin(new MaintenancePlugin(Server::get(IConfig::class), \OC::$server->getL10N('dav')));
$server->addPlugin(new \Sabre\DAV\Auth\Plugin($authBackend));
$server->addPlugin(new Plugin());

$server->addPlugin(new LegacyDAVACL());
if ($debugging) {
	$server->addPlugin(new Sabre\DAV\Browser\Plugin());
}

$server->addPlugin(new \Sabre\DAV\Sync\Plugin());
$server->addPlugin(new \Sabre\CardDAV\VCFExportPlugin());
$server->addPlugin(new ImageExportPlugin(Server::get(PhotoCache::class)));
$server->addPlugin(new ExceptionLoggerPlugin('carddav', Server::get(LoggerInterface::class)));
$server->addPlugin(Server::get(CardDavRateLimitingPlugin::class));
$server->addPlugin(Server::get(CardDavValidatePlugin::class));

// And off we go!
$server->exec();
