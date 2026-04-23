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
	 * Applies a bulk delegation update for a setting class in a single
	 * transaction-like sequence, then invalidates the cache exactly once.
	 *
	 * @param list<array{gid: string}> $newGroups Desired final state
	 * @throws Exception
	 * @throws Throwable
	 */
	public function saveSettings(array $newGroups, string $class): void {
		$currentGroups = $this->findExistingGroupsForClass($class);

		foreach ($currentGroups as $group) {
			$removed = true;
			foreach ($newGroups as $groupData) {
				if ($groupData['gid'] === $group->getGroupId()) {
					$removed = false;
					break;
				}
			}

			if ($removed) {
				try {
					// $group is already a hydrated AuthorizedGroup entity
					// returned by findExistingGroupsForClass()
					$this->mapper->delete($group);
				} catch (\Exception $exception) {
					$this->handleException($exception);
				}
			}
		}

		// We attempt the insert unconditionally and treat a unique-
		// constraint violation as idempotent — cheaper than a read-before-write
		// and race-safe under concurrent saveSettings() calls.
		foreach ($newGroups as $groupData) {
			$added = true;
			foreach ($currentGroups as $group) {
				if ($groupData['gid'] === $group->getGroupId()) {
					$added = false;
					break;
				}
			}

			if ($added) {
				try {
					$newGroup = new AuthorizedGroup();
					$newGroup->setGroupId($groupData['gid']);
					$newGroup->setClass($class);
					$this->mapper->insert($newGroup);
				} catch (Exception $e) {
					// The DB unique constraint prevented the duplicate
					// so treat as idempotent success.
					if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
						throw $e;
					}
				}
			}
		}

		$this->mapper->clearCache();
	}

	/**
	 * Create a new AuthorizedGroup and invalidate the distributed cache.
	 *
	 * Adding a delegation may grant access to every current member of the
	 * affected group. A full cache clear is used rather than per-user
	 * invalidation to avoid an extra group-membership backend call at write
	 * time.
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

		$result = $this->mapper->insert($authorizedGroup);

		// Invalidate after successful insert so the next request re-evaluates
		// all users' authorized classes.
		$this->mapper->clearCache();

		return $result;
	}

	/**
	 * @throws NotFoundException
	 * @throws Throwable
	 */
	public function delete(int $id): void {
		try {
			$authorizedGroup = $this->mapper->find($id);
			$this->mapper->delete($authorizedGroup);
			// Revoking a delegation must take effect immediately.
			$this->mapper->clearCache();
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
			// Group deletion removes all delegations for that GID
			// all affected users need re-evaluation on their next request
			$this->mapper->clearCache();
		} catch (\Exception $exception) {
			$this->handleException($exception);
		}
	}
}
