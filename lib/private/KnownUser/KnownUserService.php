<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2021 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Vincent Petry <vincent@nextcloud.com>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\KnownUser;

class KnownUserService {
	/** @var KnownUserMapper */
	protected $mapper;
	/** @var array */
	protected $knownUsers = [];

	public function __construct(KnownUserMapper $mapper) {
		$this->mapper = $mapper;
	}

	/**
	 * Delete all matches where the given user is the owner of the phonebook
	 *
	 * @param string $knownTo
	 * @return int Number of deleted matches
	 */
	public function deleteKnownTo(string $knownTo): int {
		return $this->mapper->deleteKnownTo($knownTo);
	}

	/**
	 * Delete all matches where the given user is the one in the phonebook
	 *
	 * @param string $contactUserId
	 * @return int Number of deleted matches
	 */
	public function deleteByContactUserId(string $contactUserId): int {
		return $this->mapper->deleteKnownUser($contactUserId);
	}

	/**
	 * Store a match because $knownTo has $contactUserId in his phonebook
	 *
	 * @param string $knownTo User id of the owner of the phonebook
	 * @param string $contactUserId User id of the contact in the phonebook
	 */
	public function storeIsKnownToUser(string $knownTo, string $contactUserId): void {
		$entity = new KnownUser();
		$entity->setKnownTo($knownTo);
		$entity->setKnownUser($contactUserId);
		$this->mapper->insert($entity);
	}

	/**
	 * Check if $contactUserId is in the phonebook of $knownTo
	 *
	 * @param string $knownTo User id of the owner of the phonebook
	 * @param string $contactUserId User id of the contact in the phonebook
	 * @return bool
	 */
	public function isKnownToUser(string $knownTo, string $contactUserId): bool {
		if ($knownTo === $contactUserId) {
			return true;
		}

		if (!isset($this->knownUsers[$knownTo])) {
			$entities = $this->mapper->getKnownUsers($knownTo);
			$this->knownUsers[$knownTo] = [];
			foreach ($entities as $entity) {
				$this->knownUsers[$knownTo][$entity->getKnownUser()] = true;
			}
		}

		return isset($this->knownUsers[$knownTo][$contactUserId]);
	}
}
