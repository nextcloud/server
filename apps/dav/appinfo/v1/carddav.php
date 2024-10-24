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
use OCP\IGroupManager;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Sabre\CardDAV\Plugin;

$authBackend = new Auth(
	\OC::$server->getSession(),
	\OC::$server->getUserSession(),
	\OC::$server->getRequest(),
	\OC::$server->getTwoFactorAuthManager(),
	\OC::$server->getBruteForceThrottler(),
	'principals/'
);
$principalBackend = new Principal(
	\OC::$server->getUserManager(),
	\OC::$server->getGroupManager(),
	\OC::$server->get(IAccountManager::class),
	\OC::$server->getShareManager(),
	\OC::$server->getUserSession(),
	\OC::$server->getAppManager(),
	\OC::$server->query(ProxyMapper::class),
	\OC::$server->get(KnownUserService::class),
	\OC::$server->getConfig(),
	\OC::$server->getL10NFactory(),
	'principals/'
);
$db = \OC::$server->getDatabaseConnection();
$cardDavBackend = new CardDavBackend(
	$db,
	$principalBackend,
	\OC::$server->getUserManager(),
	\OC::$server->get(IEventDispatcher::class),
	\OC::$server->get(\OCA\DAV\CardDAV\Sharing\Backend::class),
);

$debugging = \OC::$server->getConfig()->getSystemValue('debug', false);

// Root nodes
$principalCollection = new \Sabre\CalDAV\Principal\Collection($principalBackend);
$principalCollection->disableListing = !$debugging; // Disable listing

$pluginManager = new PluginManager(\OC::$server, \OC::$server->query(IAppManager::class));
$addressBookRoot = new AddressBookRoot($principalBackend, $cardDavBackend, $pluginManager, \OC::$server->getUserSession()->getUser(), \OC::$server->get(IGroupManager::class));
$addressBookRoot->disableListing = !$debugging; // Disable listing

$nodes = [
	$principalCollection,
	$addressBookRoot,
];

// Fire up server
$server = new \Sabre\DAV\Server($nodes);
$server::$exposeVersion = false;
$server->httpRequest->setUrl(\OC::$server->getRequest()->getRequestUri());
$server->setBaseUri($baseuri);
// Add plugins
$server->addPlugin(new MaintenancePlugin(\OC::$server->getConfig(), \OC::$server->getL10N('dav')));
$server->addPlugin(new \Sabre\DAV\Auth\Plugin($authBackend));
$server->addPlugin(new Plugin());

$server->addPlugin(new LegacyDAVACL());
if ($debugging) {
	$server->addPlugin(new Sabre\DAV\Browser\Plugin());
}

$server->addPlugin(new \Sabre\DAV\Sync\Plugin());
$server->addPlugin(new \Sabre\CardDAV\VCFExportPlugin());
$server->addPlugin(new ImageExportPlugin(new PhotoCache(
	\OC::$server->getAppDataDir('dav-photocache'),
	\OC::$server->get(LoggerInterface::class)
)));
$server->addPlugin(new ExceptionLoggerPlugin('carddav', \OC::$server->get(LoggerInterface::class)));
$server->addPlugin(Server::get(CardDavRateLimitingPlugin::class));
$server->addPlugin(Server::get(CardDavValidatePlugin::class));

// And off we go!
$server->exec();
