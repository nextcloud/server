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
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\IRootFolder;
use OCP\IConfig;
use OCP\Server;
use Psr\Log\LoggerInterface;
use Sabre\DAV\SimpleCollection;

class RootCollection extends SimpleCollection {
	public function __construct() {
		$l10n = \OC::$server->getL10N('dav');
		$random = \OC::$server->getSecureRandom();
		$logger = \OC::$server->get(LoggerInterface::class);
		$userManager = \OC::$server->getUserManager();
		$userSession = \OC::$server->getUserSession();
		$groupManager = \OC::$server->getGroupManager();
		$shareManager = \OC::$server->getShareManager();
		$db = \OC::$server->getDatabaseConnection();
		$dispatcher = \OC::$server->get(IEventDispatcher::class);
		$config = \OC::$server->get(IConfig::class);
		$proxyMapper = \OC::$server->query(ProxyMapper::class);
		$rootFolder = Server::get(IRootFolder::class);

		$userPrincipalBackend = new Principal(
			$userManager,
			$groupManager,
			\OC::$server->get(IAccountManager::class),
			$shareManager,
			\OC::$server->getUserSession(),
			\OC::$server->getAppManager(),
			$proxyMapper,
			\OC::$server->get(KnownUserService::class),
			\OC::$server->getConfig(),
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
		$calendarSharingBackend = \OC::$server->get(Backend::class);

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
			\OC::$server->getSystemTagManager(),
			\OC::$server->getSystemTagObjectMapper(),
			\OC::$server->getUserSession(),
			$groupManager,
			$dispatcher,
			$rootFolder,
		);
		$systemTagInUseCollection = Server::get(SystemTagsInUseCollection::class);
		$commentsCollection = new Comments\RootCollection(
			\OC::$server->getCommentsManager(),
			$userManager,
			\OC::$server->getUserSession(),
			$dispatcher,
			$logger
		);

		$contactsSharingBackend = \OC::$server->get(\OCA\DAV\CardDAV\Sharing\Backend::class);

		$pluginManager = new PluginManager(\OC::$server, \OC::$server->query(IAppManager::class));
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
			\OC::$server->query(CleanupService::class));
		$uploadCollection->disableListing = $disableListing;

		$avatarCollection = new Avatars\RootCollection($userPrincipalBackend, 'principals/users');
		$avatarCollection->disableListing = $disableListing;

		$appleProvisioning = new AppleProvisioningNode(
			\OC::$server->query(ITimeFactory::class));

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
