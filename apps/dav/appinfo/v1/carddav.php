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
 * @author Anna Larch <anna.larch@gmx.net>
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
use OC\KnownUser\KnownUserService;
use OCA\DAV\AppInfo\PluginManager;
use OCA\DAV\CardDAV\AddressBookRoot;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\CardDAV\Security\CardDavRateLimitingPlugin;
use OCA\DAV\CardDAV\Validation\CardDavValidatePlugin;
use OCA\DAV\Connector\LegacyDAVACL;
use OCA\DAV\Connector\Sabre\Auth;
use OCA\DAV\Connector\Sabre\ExceptionLoggerPlugin;
use OCA\DAV\Connector\Sabre\MaintenancePlugin;
use OCA\DAV\Connector\Sabre\Principal;
use OCP\Accounts\IAccountManager;
use OCP\App\IAppManager;
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
	\OC::$server->query(\OCA\DAV\CalDAV\Proxy\ProxyMapper::class),
	\OC::$server->get(KnownUserService::class),
	\OC::$server->getConfig(),
	\OC::$server->getL10NFactory(),
	'principals/'
);
$db = \OC::$server->getDatabaseConnection();
$cardDavBackend = new CardDavBackend($db, $principalBackend, \OC::$server->getUserManager(), \OC::$server->getGroupManager(), \OC::$server->get(\OCP\EventDispatcher\IEventDispatcher::class));

$debugging = \OC::$server->getConfig()->getSystemValue('debug', false);

// Root nodes
$principalCollection = new \Sabre\CalDAV\Principal\Collection($principalBackend);
$principalCollection->disableListing = !$debugging; // Disable listing

$pluginManager = new PluginManager(\OC::$server, \OC::$server->query(IAppManager::class));
$addressBookRoot = new AddressBookRoot($principalBackend, $cardDavBackend, $pluginManager, \OC::$server->getUserSession()->getUser(), \OC::$server->get(\OCP\IGroupManager::class));
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
$server->addPlugin(new \OCA\DAV\CardDAV\ImageExportPlugin(new \OCA\DAV\CardDAV\PhotoCache(
	\OC::$server->getAppDataDir('dav-photocache'),
	\OC::$server->get(LoggerInterface::class)
)));
$server->addPlugin(new ExceptionLoggerPlugin('carddav', \OC::$server->get(LoggerInterface::class)));
$server->addPlugin(\OCP\Server::get(CardDavRateLimitingPlugin::class));
$server->addPlugin(\OCP\Server::get(CardDavValidatePlugin::class));

// And off we go!
$server->exec();
