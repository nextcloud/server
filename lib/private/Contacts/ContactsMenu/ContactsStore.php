<?php

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Contacts\ContactsMenu;

use OCP\Contacts\ContactsMenu\IEntry;
use OCP\Contacts\IManager;

class ContactsStore {

	/** @var IManager */
	private $contactsManager;

	/**
	 * @param IManager $contactsManager
	 */
	public function __construct(IManager $contactsManager) {
		$this->contactsManager = $contactsManager;
	}

	/**
	 * @param string|null $filter
	 * @return IEntry[]
	 */
	public function getContacts($filter) {
		$allContacts = $this->contactsManager->search($filter ?: '', [
			'FN',
		]);

		return array_map(function(array $contact) {
			return $this->contactArrayToEntry($contact);
		}, $allContacts);
	}

	/**
	 * @param array $contact
	 * @return Entry
	 */
	private function contactArrayToEntry(array $contact) {
		$entry = new Entry();

		if (isset($contact['id'])) {
			$entry->setId($contact['id']);
		}

		if (isset($contact['FN'])) {
			$entry->setFullName($contact['FN']);
		}

		$avatarPrefix = "VALUE=uri:";
		if (isset($contact['PHOTO']) && strpos($contact['PHOTO'], $avatarPrefix) === 0) {
			$entry->setAvatar(substr($contact['PHOTO'], strlen($avatarPrefix)));
		}

		if (isset($contact['EMAIL'])) {
			foreach ($contact['EMAIL'] as $email) {
				$entry->addEMailAddress($email);
			}
		}

		// Attach all other properties to the entry too because some
		// providers might make use of it.
		$entry->setProperties($contact);

		return $entry;
	}

}
