<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
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
use OC\Files\AppData\Factory as AppDataFactory;
use OC\KnownUser\KnownUserService;
use OC\Security\Bruteforce\Throttler;
use OCA\DAV\AppInfo\PluginManager;
use OCA\DAV\CalDAV\Proxy\ProxyMapper;
use OCA\DAV\CardDAV\AddressBookRoot;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\ImageExportPlugin;
use OCA\DAV\CardDAV\PhotoCache;
use OCA\DAV\Connector\LegacyDAVACL;
use OCA\DAV\Connector\Sabre\Auth;
use OCA\DAV\Connector\Sabre\ExceptionLoggerPlugin;
use OCA\DAV\Connector\Sabre\MaintenancePlugin;
use OCA\DAV\Connector\Sabre\Principal;
use OCP\App\IAppManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory as IL10NFactory;
use OCP\Share\IManager as IShareManager;
use Psr\Log\LoggerInterface;
use Sabre\CalDAV\Principal\Collection;
use Sabre\CardDAV\Plugin;
use Sabre\CardDAV\VCFExportPlugin;
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

$cardDavBackend = new CardDavBackend(\OC::$server->get(IDBConnection::class), $principalBackend, $userManager, $groupManager, \OC::$server->get(IEventDispatcher::class));

$debugging = $config->getSystemValue('debug', false);

// Root nodes
$principalCollection = new Collection($principalBackend);
$principalCollection->disableListing = !$debugging; // Disable listing

$pluginManager = new PluginManager(\OC::$server, \OC::$server->get(IAppManager::class));
$addressBookRoot = new AddressBookRoot($principalBackend, $cardDavBackend, $pluginManager);
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
$server->addPlugin(new Plugin());

$server->addPlugin(new LegacyDAVACL());
if ($debugging) {
	$server->addPlugin(new BrowserPlugin());
}

$server->addPlugin(new SyncPlugin());
$server->addPlugin(new VCFExportPlugin());
$server->addPlugin(new ImageExportPlugin(new PhotoCache(
	\OC::$server->get(AppDataFactory::class)->get('dav-photocache'),
	$logger
)));
$server->addPlugin(new ExceptionLoggerPlugin('carddav', $logger));

// And off we go!
$server->start();
