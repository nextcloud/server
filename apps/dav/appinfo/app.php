<?php
/**
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

$cm = \OC::$server->getContactsManager();
$cm->register(function() use ($cm) {
	$db = \OC::$server->getDatabaseConnection();
	$userId = \OC::$server->getUserSession()->getUser()->getUID();
	$principal = new \OCA\DAV\Connector\Sabre\Principal(
			\OC::$server->getConfig(),
			\OC::$server->getUserManager()
	);
	$cardDav = new \OCA\DAV\CardDAV\CardDavBackend($db, $principal, \OC::$server->getLogger());
	$addressBooks = $cardDav->getAddressBooksForUser("principals/$userId");
	foreach ($addressBooks as $addressBookInfo)  {
		$addressBook = new \OCA\DAV\CardDAV\AddressBook($cardDav, $addressBookInfo);
		$cm->registerAddressBook(
				new OCA\DAV\CardDAV\AddressBookImpl(
						$addressBook,
						$addressBookInfo,
						$cardDav
				)
		);
	}
});
