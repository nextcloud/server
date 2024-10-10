<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
// Backends
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
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\Server;
use Psr\Log\LoggerInterface;

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
$userManager = \OC::$server->getUserManager();
$random = \OC::$server->getSecureRandom();
$logger = \OC::$server->get(LoggerInterface::class);
$dispatcher = \OC::$server->get(IEventDispatcher::class);
$config = \OC::$server->get(IConfig::class);

$calDavBackend = new CalDavBackend(
	$db,
	$principalBackend,
	$userManager,
	$random,
	$logger,
	$dispatcher,
	$config,
	OC::$server->get(\OCA\DAV\CalDAV\Sharing\Backend::class),
	true
);

$debugging = \OC::$server->getConfig()->getSystemValue('debug', false);
$sendInvitations = \OC::$server->getConfig()->getAppValue('dav', 'sendInvitations', 'yes') === 'yes';

// Root nodes
$principalCollection = new \Sabre\CalDAV\Principal\Collection($principalBackend);
$principalCollection->disableListing = !$debugging; // Disable listing

$addressBookRoot = new CalendarRoot($principalBackend, $calDavBackend, 'principals', $logger);
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
$server->addPlugin(new \Sabre\CalDAV\Plugin());

$server->addPlugin(new LegacyDAVACL());
if ($debugging) {
	$server->addPlugin(new Sabre\DAV\Browser\Plugin());
}

$server->addPlugin(new \Sabre\DAV\Sync\Plugin());
$server->addPlugin(new \Sabre\CalDAV\ICSExportPlugin());
$server->addPlugin(new \OCA\DAV\CalDAV\Schedule\Plugin(\OC::$server->getConfig(), \OC::$server->get(LoggerInterface::class), \OC::$server->get(DefaultCalendarValidator::class)));

if ($sendInvitations) {
	$server->addPlugin(\OC::$server->query(IMipPlugin::class));
}
$server->addPlugin(new ExceptionLoggerPlugin('caldav', $logger));
$server->addPlugin(Server::get(RateLimitingPlugin::class));
$server->addPlugin(Server::get(CalDavValidatePlugin::class));

// And off we go!
$server->exec();
