<?php

namespace OCA\DAV;

use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\CardDAV\CardDavBackend;
use OCA\DAV\Connector\Sabre\Principal;
use Sabre\CalDAV\CalendarRoot;
use Sabre\CalDAV\Principal\Collection;
use Sabre\CardDAV\AddressBookRoot;
use Sabre\DAV\SimpleCollection;

class RootCollection extends SimpleCollection {

	public function __construct() {
		$config = \OC::$server->getConfig();
		$db = \OC::$server->getDatabaseConnection();
		$principalBackend = new Principal(
				$config,
				\OC::$server->getUserManager()
		);
		// as soon as debug mode is enabled we allow listing of principals
		$disableListing = !$config->getSystemValue('debug', false);

		// setup the first level of the dav tree
		$principalCollection = new Collection($principalBackend);
		$principalCollection->disableListing = $disableListing;
		$filesCollection = new Files\RootCollection($principalBackend);
		$filesCollection->disableListing = $disableListing;
		$caldavBackend = new CalDavBackend($db);
		$calendarRoot = new CalendarRoot($principalBackend, $caldavBackend);
		$calendarRoot->disableListing = $disableListing;
		$cardDavBackend = new CardDavBackend($db);
		$addressBookRoot = new AddressBookRoot($principalBackend, $cardDavBackend);
		$addressBookRoot->disableListing = $disableListing;

		$children = [
				$principalCollection,
				$filesCollection,
				$calendarRoot,
				$addressBookRoot,
		];

		parent::__construct('root', $children);
	}

}
