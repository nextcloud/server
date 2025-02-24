<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV;

use OC\KnownUser\KnownUserService;
use OCA\DAV\AppInfo\PluginManager;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\CalendarRoot;
use OCA\DAV\CalDAV\Principal\Collection;
use OCA\DAV\CalDAV\Proxy\ProxyMapper;
use OCA\DAV\CalDAV\PublicCalendarRoot;
use OCA\DAV\CalDAV\ResourceBooking\ResourcePrincipalBackend;
use OCA\DAV\CalDAV\ResourceBooking\RoomPrincipalBackend;
use OCA\DAV\CalDAV\Sharing\Backend;
use OCA\DAV\CardDAV\AddressBookRoot;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\DAV\GroupPrincipalBackend;
use OCA\DAV\DAV\SystemPrincipalBackend;
use OCA\DAV\Provisioning\Apple\AppleProvisioningNode;
use OCA\DAV\SystemTag\SystemTagsByIdCollection;
use OCA\DAV\SystemTag\SystemTagsInUseCollection;
use OCA\DAV\SystemTag\SystemTagsRelationsCollection;
use OCA\DAV\Upload\CleanupService;
use OCP\Accounts\IAccountManager;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Comments\ICommentsManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Security\ISecureRandom;
use OCP\Server;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use Psr\Log\LoggerInterface;
use Sabre\DAV\SimpleCollection;

class RootCollection extends SimpleCollection {
	public function __construct() {
		$l10n = \OC::$server->getL10N('dav');
		$random = Server::get(ISecureRandom::class);
		$logger = Server::get(LoggerInterface::class);
		$userManager = Server::get(IUserManager::class);
		$userSession = Server::get(IUserSession::class);
		$groupManager = Server::get(IGroupManager::class);
		$shareManager = Server::get(\OCP\Share\IManager::class);
		$db = Server::get(IDBConnection::class);
		$dispatcher = Server::get(IEventDispatcher::class);
		$config = Server::get(IConfig::class);
		$proxyMapper = Server::get(ProxyMapper::class);
		$rootFolder = Server::get(IRootFolder::class);

		$userPrincipalBackend = new Principal(
			$userManager,
			$groupManager,
			Server::get(IAccountManager::class),
			$shareManager,
			Server::get(IUserSession::class),
			Server::get(IAppManager::class),
			$proxyMapper,
			Server::get(KnownUserService::class),
			Server::get(IConfig::class),
			\OC::$server->getL10NFactory()
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
		$calendarRoomPrincipals = new Collection($calendarRoomPrincipalBackend, 'principals/calendar-rooms');
		$calendarSharingBackend = Server::get(Backend::class);

		$filesCollection = new Files\RootCollection($userPrincipalBackend, 'principals/users');
		$filesCollection->disableListing = $disableListing;
		$caldavBackend = new CalDavBackend(
			$db,
			$userPrincipalBackend,
			$userManager,
			$random,
			$logger,
			$dispatcher,
			$config,
			$calendarSharingBackend,
			false,
		);
		$userCalendarRoot = new CalendarRoot($userPrincipalBackend, $caldavBackend, 'principals/users', $logger);
		$userCalendarRoot->disableListing = $disableListing;

		$resourceCalendarRoot = new CalendarRoot($calendarResourcePrincipalBackend, $caldavBackend, 'principals/calendar-resources', $logger);
		$resourceCalendarRoot->disableListing = $disableListing;
		$roomCalendarRoot = new CalendarRoot($calendarRoomPrincipalBackend, $caldavBackend, 'principals/calendar-rooms', $logger);
		$roomCalendarRoot->disableListing = $disableListing;

		$publicCalendarRoot = new PublicCalendarRoot($caldavBackend, $l10n, $config, $logger);

		$systemTagCollection = Server::get(SystemTagsByIdCollection::class);
		$systemTagRelationsCollection = new SystemTagsRelationsCollection(
			Server::get(ISystemTagManager::class),
			Server::get(ISystemTagObjectMapper::class),
			Server::get(IUserSession::class),
			$groupManager,
			$dispatcher,
			$rootFolder,
		);
		$systemTagInUseCollection = Server::get(SystemTagsInUseCollection::class);
		$commentsCollection = new Comments\RootCollection(
			Server::get(ICommentsManager::class),
			$userManager,
			Server::get(IUserSession::class),
			$dispatcher,
			$logger
		);

		$contactsSharingBackend = Server::get(\OCA\DAV\CardDAV\Sharing\Backend::class);

		$pluginManager = new PluginManager(\OC::$server, Server::get(IAppManager::class));
		$usersCardDavBackend = new CardDavBackend(
			$db,
			$userPrincipalBackend,
			$userManager,
			$dispatcher,
			$contactsSharingBackend,
		);
		$usersAddressBookRoot = new AddressBookRoot($userPrincipalBackend, $usersCardDavBackend, $pluginManager, $userSession->getUser(), $groupManager, 'principals/users');
		$usersAddressBookRoot->disableListing = $disableListing;

		$systemCardDavBackend = new CardDavBackend(
			$db,
			$userPrincipalBackend,
			$userManager,
			$dispatcher,
			$contactsSharingBackend,
		);
		$systemAddressBookRoot = new AddressBookRoot(new SystemPrincipalBackend(), $systemCardDavBackend, $pluginManager, $userSession->getUser(), $groupManager, 'principals/system');
		$systemAddressBookRoot->disableListing = $disableListing;

		$uploadCollection = new Upload\RootCollection(
			$userPrincipalBackend,
			'principals/users',
			Server::get(CleanupService::class),
			$rootFolder,
			$userSession,
		);
		$uploadCollection->disableListing = $disableListing;

		$avatarCollection = new Avatars\RootCollection($userPrincipalBackend, 'principals/users');
		$avatarCollection->disableListing = $disableListing;

		$appleProvisioning = new AppleProvisioningNode(
			Server::get(ITimeFactory::class));

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
			$systemTagInUseCollection,
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
