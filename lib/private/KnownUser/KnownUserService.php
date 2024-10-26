<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	 * Store a match because $knownTo has $contactUserId in their phonebook
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
