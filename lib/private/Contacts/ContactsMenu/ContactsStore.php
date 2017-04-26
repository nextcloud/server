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
use OCP\IUser;

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
	 * @param IUser $user
	 * @param string|null $filter
	 * @return IEntry[]
	 */
	public function getContacts(IUser $user, $filter) {
		$allContacts = $this->contactsManager->search($filter ?: '', [
			'FN',
		]);

		$self = $user->getUID();
		$entries = array_map(function(array $contact) {
			return $this->contactArrayToEntry($contact);
		}, $allContacts);
		return array_filter($entries, function(IEntry $entry) use ($self) {
			return $entry->getProperty('UID') !== $self;
		});
	}

	/**
	 * @param IUser $user
	 * @param integer $shareType
	 * @param string $shareWith
	 * @return IEntry|null
	 */
	public function findOne(IUser $user, $shareType, $shareWith) {
		switch($shareType) {
			case 0:
			case 6:
				$filter = ['UID'];
				break;
			case 4:
				$filter = ['EMAIL'];
				break;
			default:
				return null;
		}

		$userId = $user->getUID();
		$allContacts = $this->contactsManager->search($shareWith, $filter);
		$contacts = array_filter($allContacts, function($contact) use ($userId) {
			return $contact['UID'] !== $userId;
		});
		$match = null;

		foreach ($contacts as $contact) {
			if ($shareType === 4 && isset($contact['EMAIL'])) {
				if (in_array($shareWith, $contact['EMAIL'])) {
					$match = $contact;
					break;
				}
			}
			if ($shareType === 0 || $shareType === 6) {
				if ($contact['UID'] === $shareWith && $contact['isLocalSystemBook'] === true) {
					$match = $contact;
					break;
				}
			}
		}

		return $match ? $this->contactArrayToEntry($match) : null;
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
