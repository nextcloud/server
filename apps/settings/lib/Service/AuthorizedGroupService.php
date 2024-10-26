<?php

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Service;

use OC\Settings\AuthorizedGroup;
use OC\Settings\AuthorizedGroupMapper;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\Exception;
use OCP\IGroup;

class AuthorizedGroupService {

	public function __construct(
		private AuthorizedGroupMapper $mapper,
	) {
	}

	/**
	 * @return AuthorizedGroup[]
	 */
	public function findAll(): array {
		return $this->mapper->findAll();
	}

	/**
	 * Find AuthorizedGroup by id.
	 *
	 * @param int $id
	 */
	public function find(int $id): ?AuthorizedGroup {
		return $this->mapper->find($id);
	}

	/**
	 * @param $e
	 * @throws NotFoundException
	 */
	private function handleException(\Exception $e): void {
		if ($e instanceof DoesNotExistException ||
			$e instanceof MultipleObjectsReturnedException) {
			throw new NotFoundException('AuthorizedGroup not found');
		} else {
			throw $e;
		}
	}

	/**
	 * Create a new AuthorizedGroup
	 *
	 * @param string $groupId
	 * @param string $class
	 * @return AuthorizedGroup
	 * @throws Exception
	 */
	public function create(string $groupId, string $class): AuthorizedGroup {
		$authorizedGroup = new AuthorizedGroup();
		$authorizedGroup->setGroupId($groupId);
		$authorizedGroup->setClass($class);
		return $this->mapper->insert($authorizedGroup);
	}

	/**
	 * @throws NotFoundException
	 */
	public function delete(int $id): void {
		try {
			$authorizedGroup = $this->mapper->find($id);
			$this->mapper->delete($authorizedGroup);
		} catch (\Exception $e) {
			$this->handleException($e);
		}
	}

	public function findExistingGroupsForClass(string $class): array {
		try {
			$authorizedGroup = $this->mapper->findExistingGroupsForClass($class);
			return $authorizedGroup;
		} catch (\Exception $e) {
			return [];
		}
	}

	public function removeAuthorizationAssociatedTo(IGroup $group): void {
		try {
			$this->mapper->removeGroup($group->getGID());
		} catch (\Exception $e) {
			$this->handleException($e);
		}
	}
}
