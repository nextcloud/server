<?php

/**
 * @copyright Copyright (c) 2021 Nextcloud GmbH
 *
 * @author Carl Schwan <carl@carlschwan.eu>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Settings\Service;

use OC\Settings\AuthorizedGroup;
use OC\Settings\AuthorizedGroupMapper;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\DB\Exception;
use OCP\IGroup;

class AuthorizedGroupService {

	/** @var AuthorizedGroupMapper $mapper */
	private $mapper;

	public function __construct(AuthorizedGroupMapper $mapper) {
		$this->mapper = $mapper;
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
			throw new NotFoundException("AuthorizedGroup not found");
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
