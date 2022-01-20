<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
// Backends
use OC\Authentication\TwoFactorAuth\Manager as TwoFactorAuthManager;
use OC\KnownUser\KnownUserService;
use OC\Security\Bruteforce\Throttler;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\Proxy\ProxyMapper;
use OCA\DAV\CalDAV\Schedule\IMipPlugin;
use OCA\DAV\CalDAV\Schedule\Plugin as SchedulePlugin;
use OCA\DAV\Connector\LegacyDAVACL;
use OCA\DAV\CalDAV\CalendarRoot;
use OCA\DAV\Connector\Sabre\Auth;
use OCA\DAV\Connector\Sabre\ExceptionLoggerPlugin;
use OCA\DAV\Connector\Sabre\MaintenancePlugin;
use OCA\DAV\Connector\Sabre\Principal;
use Psr\Log\LoggerInterface;
use OCP\App\IAppManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory as IL10NFactory;
use OCP\Security\ISecureRandom;
use OCP\Share\IManager as IShareManager;
use Sabre\CalDAV\ICSExportPlugin;
use Sabre\CalDAV\Plugin as CalDAVPlugin;
use Sabre\CalDAV\Principal\Collection;
use Sabre\DAV\Auth\Plugin as AuthPlugin;
use Sabre\DAV\Browser\Plugin as BrowserPlugin;
use Sabre\DAV\Server;
use Sabre\DAV\Sync\Plugin as SyncPlugin;

/** @var IUserSession $userSession */
$userSession = \OC::$server->get(IUserSession::class);
/** @var IRequest $request */
$request = \OC::$server->get(IRequest::class);
/** @var IUserManager $userManager */
$userManager = \OC::$server->get(IUserManager::class);
/** @var IGroupManager $groupManager */
$groupManager = \OC::$server->get(IGroupManager::class);
/** @var IConfig $config */
$config = \OC::$server->get(IConfig::class);
/** @var IL10NFactory $l10nFactory */
$l10nFactory = \OC::$server->get(IL10NFactory::class);
/** @var LoggerInterface $logger */
$logger = \OC::$server->get(LoggerInterface::class);

$authBackend = new Auth(
	$userSession->getSession(),
	$userSession,
	$request,
	\OC::$server->get(TwoFactorAuthManager::class),
	\OC::$server->get(Throttler::class),
	'principals/'
);
$principalBackend = new Principal(
	$userManager,
	$groupManager,
	\OC::$server->get(IShareManager::class),
	$userSession,
	\OC::$server->get(IAppManager::class),
	\OC::$server->get(ProxyMapper::class),
	\OC::$server->get(KnownUserService::class),
	$config,
	$l10nFactory,
	'principals/'
);

$calDavBackend = new CalDavBackend(
	\OC::$server->get(IDBConnection::class),
	$principalBackend,
	$userManager,
	$groupManager,
	\OC::$server->get(ISecureRandom::class),
	$logger,
	\OC::$server->get(IEventDispatcher::class),
	$config,
	true
);

$debugging = $config->getSystemValue('debug', false);
$sendInvitations = $config->getAppValue('dav', 'sendInvitations', 'yes') === 'yes';

// Root nodes
$principalCollection = new Collection($principalBackend);
$principalCollection->disableListing = !$debugging; // Disable listing

$addressBookRoot = new CalendarRoot($principalBackend, $calDavBackend, 'principals', \OC::$server->get(LoggerInterface::class));
$addressBookRoot->disableListing = !$debugging; // Disable listing

$nodes = [
	$principalCollection,
	$addressBookRoot,
];

// Fire up server
$server = new Server($nodes);
$server::$exposeVersion = false;
$server->httpRequest->setUrl($request->getRequestUri());
$server->setBaseUri($baseuri);

// Add plugins
$server->addPlugin(new MaintenancePlugin($config, $l10nFactory->get('dav')));
$server->addPlugin(new AuthPlugin($authBackend));
$server->addPlugin(new CalDAVPlugin());

$server->addPlugin(new LegacyDAVACL());
if ($debugging) {
	$server->addPlugin(new BrowserPlugin());
}

$server->addPlugin(new SyncPlugin());
$server->addPlugin(new ICSExportPlugin());
$server->addPlugin(new SchedulePlugin($config));

if ($sendInvitations) {
	$server->addPlugin(\OC::$server->get(IMipPlugin::class));
}
$server->addPlugin(new ExceptionLoggerPlugin('caldav', $logger));

// And off we go!
$server->start();
