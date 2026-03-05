<?php

declare(strict_types=1);

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
use Throwable;

readonly class AuthorizedGroupService {
	public function __construct(
		private AuthorizedGroupMapper $mapper,
	) {
	}

	/**
	 * @return AuthorizedGroup[]
	 * @throws Exception
	 */
	public function findAll(): array {
		return $this->mapper->findAll();
	}

	/**
	 * Find AuthorizedGroup by id.
	 * @throws DoesNotExistException
	 * @throws Exception
	 * @throws MultipleObjectsReturnedException
	 */
	public function find(int $id): ?AuthorizedGroup {
		return $this->mapper->find($id);
	}

	/**
	 * @throws NotFoundException
	 * @throws Throwable
	 */
	private function handleException(Throwable $e): void {
		if ($e instanceof DoesNotExistException
			|| $e instanceof MultipleObjectsReturnedException) {
			throw new NotFoundException('AuthorizedGroup not found');
		}

		throw $e;
	}

	/**
	 * Create a new AuthorizedGroup
	 *
	 * @throws Exception
	 * @throws ConflictException
	 * @throws MultipleObjectsReturnedException
	 */
	public function create(string $groupId, string $class): AuthorizedGroup {
		// Check if the group is already assigned to this class
		try {
			$this->mapper->findByGroupIdAndClass($groupId, $class);
			throw new ConflictException('Group is already assigned to this class');
		} catch (DoesNotExistException) {
			// This is expected when no duplicate exists, continue with creation
		}

		$authorizedGroup = new AuthorizedGroup();
		$authorizedGroup->setGroupId($groupId);
		$authorizedGroup->setClass($class);
		return $this->mapper->insert($authorizedGroup);
	}

	/**
	 * @throws NotFoundException
	 * @throws Throwable
	 */
	public function delete(int $id): void {
		try {
			$authorizedGroup = $this->mapper->find($id);
			$this->mapper->delete($authorizedGroup);
		} catch (\Exception $exception) {
			$this->handleException($exception);
		}
	}

	/**
	 * @return list<AuthorizedGroup>
	 */
	public function findExistingGroupsForClass(string $class): array {
		try {
			return $this->mapper->findExistingGroupsForClass($class);
		} catch (\Exception) {
			return [];
		}
	}

	/**
	 * @throws Throwable
	 * @throws NotFoundException
	 */
	public function removeAuthorizationAssociatedTo(IGroup $group): void {
		try {
			$this->mapper->removeGroup($group->getGID());
		} catch (\Exception $exception) {
			$this->handleException($exception);
		}
	}
}
