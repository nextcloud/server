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
use OCA\DAV\CalDAV\Federation\FederatedCalendarFactory;
use OCA\DAV\CalDAV\Federation\FederatedCalendarMapper;
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
use OCP\L10N\IFactory as IL10NFactory;
use OCP\Security\Bruteforce\IThrottler;
use OCP\Security\ISecureRandom;
use OCP\Server;
use Psr\Log\LoggerInterface;

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
$userManager = Server::get(IUserManager::class);
$random = Server::get(ISecureRandom::class);
$logger = Server::get(LoggerInterface::class);
$dispatcher = Server::get(IEventDispatcher::class);
$config = Server::get(IConfig::class);
$l10nFactory = Server::get(IL10NFactory::class);
$davL10n = $l10nFactory->get('dav');
$federatedCalendarFactory = Server::get(FederatedCalendarFactory::class);

$calDavBackend = new CalDavBackend(
	$db,
	$principalBackend,
	$userManager,
	$random,
	$logger,
	$dispatcher,
	$config,
	Server::get(\OCA\DAV\CalDAV\Sharing\Backend::class),
	Server::get(FederatedCalendarMapper::class),
	Server::get(\OCP\ICacheFactory::class),
	true
);

$debugging = Server::get(IConfig::class)->getSystemValue('debug', false);
$sendInvitations = Server::get(IConfig::class)->getAppValue('dav', 'sendInvitations', 'yes') === 'yes';

// Root nodes
$principalCollection = new \Sabre\CalDAV\Principal\Collection($principalBackend);
$principalCollection->disableListing = !$debugging; // Disable listing

$addressBookRoot = new CalendarRoot($principalBackend, $calDavBackend, 'principals', $logger, $davL10n, $config, $federatedCalendarFactory);
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
$server->addPlugin(new MaintenancePlugin(Server::get(IConfig::class), $davL10n));
$server->addPlugin(new \Sabre\DAV\Auth\Plugin($authBackend));
$server->addPlugin(new \Sabre\CalDAV\Plugin());

$server->addPlugin(new LegacyDAVACL());
if ($debugging) {
	$server->addPlugin(new Sabre\DAV\Browser\Plugin());
}

$server->addPlugin(new \Sabre\DAV\Sync\Plugin());
$server->addPlugin(new \Sabre\CalDAV\ICSExportPlugin());
$server->addPlugin(new \OCA\DAV\CalDAV\Schedule\Plugin(Server::get(IConfig::class), Server::get(LoggerInterface::class), Server::get(DefaultCalendarValidator::class)));

if ($sendInvitations) {
	$server->addPlugin(Server::get(IMipPlugin::class));
}
$server->addPlugin(new ExceptionLoggerPlugin('caldav', $logger));
$server->addPlugin(Server::get(RateLimitingPlugin::class));
$server->addPlugin(Server::get(CalDavValidatePlugin::class));

// And off we go!
$server->exec();
