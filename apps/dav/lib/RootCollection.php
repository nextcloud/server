<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Citharel <nextcloud@tcit.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
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
namespace OCA\DAV;

use OC\EventDispatcher\SymfonyAdapter;
use OC\KnownUser\KnownUserService;
use OCA\DAV\AppInfo\PluginManager;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\CalendarRoot;
use OCA\DAV\CalDAV\Principal\Collection;
use OCA\DAV\CalDAV\Proxy\ProxyMapper;
use OCA\DAV\CalDAV\PublicCalendarRoot;
use OCA\DAV\CalDAV\ResourceBooking\ResourcePrincipalBackend;
use OCA\DAV\CalDAV\ResourceBooking\RoomPrincipalBackend;
use OCA\DAV\CardDAV\AddressBookRoot;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\DAV\GroupPrincipalBackend;
use OCA\DAV\DAV\SystemPrincipalBackend;
use OCA\DAV\Provisioning\Apple\AppleProvisioningNode;
use OCA\DAV\Upload\CleanupService;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Comments\ICommentsManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Security\ISecureRandom;
use OCP\Share\IManager;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use Sabre\DAV\SimpleCollection;

class RootCollection extends SimpleCollection {
	public function __construct() {
		$l10n = \OC::$server->get(IFactory::class)->get('dav');
		$random = \OC::$server->get(ISecureRandom::class);
		$logger = \OC::$server->getLogger();
		$psrLogger = \OC::$server->get(LoggerInterface::class);
		$userManager = \OC::$server->get(IUserManager::class);
		$userSession = \OC::$server->get(IUserSession::class);
		$groupManager = \OC::$server->get(IGroupManager::class);
		$shareManager = \OC::$server->get(IManager::class);
		$db = \OC::$server->get(IDBConnection::class);
		$dispatcher = \OC::$server->get(IEventDispatcher::class);
		$config = \OC::$server->get(IConfig::class);
		$proxyMapper = \OC::$server->get(ProxyMapper::class);

		$userPrincipalBackend = new Principal(
			$userManager,
			$groupManager,
			$shareManager,
			\OC::$server->get(IUserSession::class),
			\OC::$server->get(IAppManager::class),
			$proxyMapper,
			\OC::$server->get(KnownUserService::class),
			\OC::$server->get(IConfig::class),
			\OC::$server->get(IFactory::class)
		);
		$groupPrincipalBackend = new GroupPrincipalBackend($groupManager, $userSession, $shareManager, $config);
		$calendarResourcePrincipalBackend = new ResourcePrincipalBackend($db, $userSession, $groupManager, $logger, $proxyMapper);
		$calendarRoomPrincipalBackend = new RoomPrincipalBackend($db, $userSession, $groupManager, $logger, $proxyMapper);
		// as soon as debug mode is enabled we allow listing of principals
		$disableListing = !$config->getSystemValue('debug', false);

		// setup the first level of the dav tree
		$userPrincipals = new Collection($userPrincipalBackend, 'principals/users');
		$userPrincipals->disableListing = $disableListing;
		$groupPrincipals = new Collection($groupPrincipalBackend, 'principals/groups');
		$groupPrincipals->disableListing = $disableListing;
		$systemPrincipals = new Collection(new SystemPrincipalBackend(), 'principals/system');
		$systemPrincipals->disableListing = $disableListing;
		$calendarResourcePrincipals = new Collection($calendarResourcePrincipalBackend, 'principals/calendar-resources');
		$calendarResourcePrincipals->disableListing = $disableListing;
		$calendarRoomPrincipals = new Collection($calendarRoomPrincipalBackend, 'principals/calendar-rooms');
		$calendarRoomPrincipals->disableListing = $disableListing;


		$filesCollection = new Files\RootCollection($userPrincipalBackend, 'principals/users');
		$filesCollection->disableListing = $disableListing;
		$caldavBackend = new CalDavBackend(
			$db,
			$userPrincipalBackend,
			$userManager,
			$groupManager,
			$random,
			$logger,
			$dispatcher,
			$config
		);
		$userCalendarRoot = new CalendarRoot($userPrincipalBackend, $caldavBackend, 'principals/users', $psrLogger);
		$userCalendarRoot->disableListing = $disableListing;

		$resourceCalendarRoot = new CalendarRoot($calendarResourcePrincipalBackend, $caldavBackend, 'principals/calendar-resources', $psrLogger);
		$resourceCalendarRoot->disableListing = $disableListing;
		$roomCalendarRoot = new CalendarRoot($calendarRoomPrincipalBackend, $caldavBackend, 'principals/calendar-rooms', $psrLogger);
		$roomCalendarRoot->disableListing = $disableListing;

		$publicCalendarRoot = new PublicCalendarRoot($caldavBackend, $l10n, $config, $psrLogger);
		$publicCalendarRoot->disableListing = $disableListing;

		$systemTagCollection = new SystemTag\SystemTagsByIdCollection(
			\OC::$server->get(ISystemTagManager::class),
			\OC::$server->get(IUserSession::class),
			$groupManager
		);
		$systemTagRelationsCollection = new SystemTag\SystemTagsRelationsCollection(
			\OC::$server->get(ISystemTagManager::class),
			\OC::$server->get(ISystemTagObjectMapper::class),
			\OC::$server->get(IUserSession::class),
			$groupManager,
			\OC::$server->get(\OC\EventDispatcher\SymfonyAdapter::class)
		);
		$commentsCollection = new Comments\RootCollection(
			\OC::$server->get(ICommentsManager::class),
			$userManager,
			\OC::$server->get(IUserSession::class),
			\OC::$server->get(\OC\EventDispatcher\SymfonyAdapter::class),
			\OC::$server->getLogger()
		);

		$pluginManager = new PluginManager(\OC::$server, \OC::$server->get(IAppManager::class));
		$usersCardDavBackend = new CardDavBackend($db, $userPrincipalBackend, $userManager, $groupManager, $dispatcher);
		$usersAddressBookRoot = new AddressBookRoot($userPrincipalBackend, $usersCardDavBackend, $pluginManager, 'principals/users');
		$usersAddressBookRoot->disableListing = $disableListing;

		$systemCardDavBackend = new CardDavBackend($db, $userPrincipalBackend, $userManager, $groupManager, $dispatcher);
		$systemAddressBookRoot = new AddressBookRoot(new SystemPrincipalBackend(), $systemCardDavBackend, $pluginManager, 'principals/system');
		$systemAddressBookRoot->disableListing = $disableListing;

		$uploadCollection = new Upload\RootCollection(
			$userPrincipalBackend,
			'principals/users',
			\OC::$server->get(CleanupService::class));
		$uploadCollection->disableListing = $disableListing;

		$avatarCollection = new Avatars\RootCollection($userPrincipalBackend, 'principals/users');
		$avatarCollection->disableListing = $disableListing;

		$appleProvisioning = new AppleProvisioningNode(
			\OC::$server->get(ITimeFactory::class));

		$children = [
			new SimpleCollection('principals', [
				$userPrincipals,
				$groupPrincipals,
				$systemPrincipals,
				$calendarResourcePrincipals,
				$calendarRoomPrincipals]),
			$filesCollection,
			$userCalendarRoot,
			new SimpleCollection('system-calendars', [
				$resourceCalendarRoot,
				$roomCalendarRoot,
			]),
			$publicCalendarRoot,
			new SimpleCollection('addressbooks', [
				$usersAddressBookRoot,
				$systemAddressBookRoot]),
			$systemTagCollection,
			$systemTagRelationsCollection,
			$commentsCollection,
			$uploadCollection,
			$avatarCollection,
			new SimpleCollection('provisioning', [
				$appleProvisioning
			])
		];

		parent::__construct('root', $children);
	}
}
