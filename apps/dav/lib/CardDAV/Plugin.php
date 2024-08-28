<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\CardDAV;

use OCA\DAV\CardDAV\Xml\Groups;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;

class Plugin extends \Sabre\CardDAV\Plugin {
	public function initialize(Server $server) {
		$server->on('propFind', [$this, 'propFind']);
		parent::initialize($server);
	}

	/**
	 * Returns the addressbook home for a given principal
	 *
	 * @param string $principal
	 * @return string|null
	 */
	protected function getAddressbookHomeForPrincipal($principal) {
		if (strrpos($principal, 'principals/users', -strlen($principal)) !== false) {
			[, $principalId] = \Sabre\Uri\split($principal);
			return self::ADDRESSBOOK_ROOT . '/users/' . $principalId;
		}
		if (strrpos($principal, 'principals/groups', -strlen($principal)) !== false) {
			[, $principalId] = \Sabre\Uri\split($principal);
			return self::ADDRESSBOOK_ROOT . '/groups/' . $principalId;
		}
		if (strrpos($principal, 'principals/system', -strlen($principal)) !== false) {
			[, $principalId] = \Sabre\Uri\split($principal);
			return self::ADDRESSBOOK_ROOT . '/system/' . $principalId;
		}
	}

	/**
	 * Adds all CardDAV-specific properties
	 *
	 * @param PropFind $propFind
	 * @param INode $node
	 * @return void
	 */
	public function propFind(PropFind $propFind, INode $node) {
		$ns = '{http://owncloud.org/ns}';

		if ($node instanceof AddressBook) {
			$propFind->handle($ns . 'groups', function () use ($node) {
				return new Groups($node->getContactsGroups());
			});
		}
	}
}
