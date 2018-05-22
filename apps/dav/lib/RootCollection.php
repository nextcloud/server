<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <pvince81@owncloud.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CalDAV\CalendarRoot;
use OCA\DAV\CalDAV\PublicCalendarRoot;
use OCA\DAV\CardDAV\AddressBookRoot;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\Connector\Sabre\Principal;
use OCA\DAV\DAV\GroupPrincipalBackend;
use OCA\DAV\DAV\SystemPrincipalBackend;
use OCA\DAV\CalDAV\Principal\Collection;
use Sabre\DAV\SimpleCollection;

class RootCollection extends SimpleCollection {

	public function __construct() {
		$config = \OC::$server->getConfig();
		$l10n = \OC::$server->getL10N('dav');
		$random = \OC::$server->getSecureRandom();
		$logger = \OC::$server->getLogger();
		$userManager = \OC::$server->getUserManager();
		$groupManager = \OC::$server->getGroupManager();
		$shareManager = \OC::$server->getShareManager();
		$db = \OC::$server->getDatabaseConnection();
		$dispatcher = \OC::$server->getEventDispatcher();
		$userPrincipalBackend = new Principal(
			$userManager,
			$groupManager,
			$shareManager,
			\OC::$server->getUserSession(),
			$config
		);
		$groupPrincipalBackend = new GroupPrincipalBackend($groupManager);
		// as soon as debug mode is enabled we allow listing of principals
		$disableListing = !$config->getSystemValue('debug', false);

		// setup the first level of the dav tree
		$userPrincipals = new Collection($userPrincipalBackend, 'principals/users');
		$userPrincipals->disableListing = $disableListing;
		$groupPrincipals = new Collection($groupPrincipalBackend, 'principals/groups');
		$groupPrincipals->disableListing = $disableListing;
		$systemPrincipals = new Collection(new SystemPrincipalBackend(), 'principals/system');
		$systemPrincipals->disableListing = $disableListing;
		$filesCollection = new Files\RootCollection($userPrincipalBackend, 'principals/users');
		$filesCollection->disableListing = $disableListing;
		$caldavBackend = new CalDavBackend($db, $userPrincipalBackend, $userManager, $groupManager, $random, $logger, $dispatcher);
		$calendarRoot = new CalendarRoot($userPrincipalBackend, $caldavBackend, 'principals/users');
		$calendarRoot->disableListing = $disableListing;
		$publicCalendarRoot = new PublicCalendarRoot($caldavBackend, $l10n, $config);
		$publicCalendarRoot->disableListing = $disableListing;

		$systemTagCollection = new SystemTag\SystemTagsByIdCollection(
			\OC::$server->getSystemTagManager(),
			\OC::$server->getUserSession(),
			$groupManager
		);
		$systemTagRelationsCollection = new SystemTag\SystemTagsRelationsCollection(
			\OC::$server->getSystemTagManager(),
			\OC::$server->getSystemTagObjectMapper(),
			\OC::$server->getUserSession(),
			$groupManager,
			\OC::$server->getEventDispatcher()
		);
		$commentsCollection = new Comments\RootCollection(
			\OC::$server->getCommentsManager(),
			$userManager,
			\OC::$server->getUserSession(),
			\OC::$server->getEventDispatcher(),
			\OC::$server->getLogger()
		);

		$usersCardDavBackend = new CardDavBackend($db, $userPrincipalBackend, $userManager, $groupManager, $dispatcher);
		$usersAddressBookRoot = new AddressBookRoot($userPrincipalBackend, $usersCardDavBackend, 'principals/users');
		$usersAddressBookRoot->disableListing = $disableListing;

		$systemCardDavBackend = new CardDavBackend($db, $userPrincipalBackend, $userManager, $groupManager, $dispatcher);
		$systemAddressBookRoot = new AddressBookRoot(new SystemPrincipalBackend(), $systemCardDavBackend, 'principals/system');
		$systemAddressBookRoot->disableListing = $disableListing;

		$uploadCollection = new Upload\RootCollection($userPrincipalBackend, 'principals/users');
		$uploadCollection->disableListing = $disableListing;

		$avatarCollection = new Avatars\RootCollection($userPrincipalBackend, 'principals/users');
		$avatarCollection->disableListing = $disableListing;

		$children = [
				new SimpleCollection('principals', [
						$userPrincipals,
						$groupPrincipals,
						$systemPrincipals]),
				$filesCollection,
				$calendarRoot,
				$publicCalendarRoot,
				new SimpleCollection('addressbooks', [
						$usersAddressBookRoot,
						$systemAddressBookRoot]),
				$systemTagCollection,
				$systemTagRelationsCollection,
				$commentsCollection,
				$uploadCollection,
				$avatarCollection
		];

		parent::__construct('root', $children);
	}

}
