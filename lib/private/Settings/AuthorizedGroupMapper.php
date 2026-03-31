<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Settings;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\Server;

/**
 * @template-extends QBMapper<AuthorizedGroup>
 */
class AuthorizedGroupMapper extends QBMapper {

	private const CACHE_PREFIX = 'nc_authorized_group_';

	private const CACHE_TTL_DISTRIBUTED = 300;

	private const CACHE_TTL_LOCAL = 60;

	private readonly ICache $distributedCache;

	private readonly ICache $localCache;

	/**
	 * @param IDBConnection $db The database connection
	 * @param ICacheFactory $cacheFactory Factory to create the cache instances
	 */
	public function __construct(
		IDBConnection $db,
		private readonly ICacheFactory $cacheFactory,
	) {
		parent::__construct($db, 'authorized_groups', AuthorizedGroup::class);
		// Distributed so multiple nodes share the same cached delegation map.
		$this->distributedCache = $this->cacheFactory->createDistributed(self::CACHE_PREFIX);
		// Per-process shield: absorbs burst traffic on a single node without
		// hitting the distributed cache (and network) on every request.
		$this->localCache = $this->cacheFactory->createLocal(self::CACHE_PREFIX);
	}

	/**
	 * Returns all setting class names that the given user is authorized to
	 * access via group delegation.
	 *
	 * Uses a two-tier cache strategy:
	 * 1. Per-process local cache (TTL: {@see self::CACHE_TTL_LOCAL} s) — shields
	 *    the distributed cache from burst traffic within a single node.
	 * 2. Distributed cache (TTL: {@see self::CACHE_TTL_DISTRIBUTED} s) — shared
	 *    across all cluster nodes; populated on cold path, backfilled to local
	 *    tier on hit to short-circuit subsequent intra-process calls.
	 *
	 * @return list<string> Fully-qualified class names of authorized settings
	 * @throws Exception When the database query fails
	 */
	public function findAllClassesForUser(IUser $user): array {
		$uid = $user->getUID();
		$cacheKey = 'user_' . $uid;

		/** @var list<string>|null $local */
		$local = $this->localCache->get($cacheKey);
		if ($local !== null) {
			return $local;
		}

		/** @var list<string>|null $distributed */
		$distributed = $this->distributedCache->get($cacheKey);
		if ($distributed !== null) {
			// Backfill local tier to short-circuit future intra-process calls.
			$this->localCache->set($cacheKey, $distributed, self::CACHE_TTL_LOCAL);
			return $distributed;
		}

		$groupManager = Server::get(IGroupManager::class);
		$groups = $groupManager->getUserGroups($user);
		if (count($groups) === 0) {
			// Cache empty result to avoid repeated backend calls for users
			// who belong to no groups.
			$this->localCache->set($cacheKey, [], self::CACHE_TTL_LOCAL);
			$this->distributedCache->set($cacheKey, [], self::CACHE_TTL_DISTRIBUTED);
			return [];
		}

		$qb = $this->db->getQueryBuilder();

		/** @var list<string> $rows */
		$rows = $qb->select('class')
			->from($this->getTableName(), 'auth')
			->where($qb->expr()->in(
				'group_id',
				array_map(
					static fn (IGroup $group) => $qb->createNamedParameter($group->getGID()),
					$groups
				),
				IQueryBuilder::PARAM_STR
			))
			->executeQuery()
			->fetchFirstColumn();

		$this->localCache->set($cacheKey, $rows, self::CACHE_TTL_LOCAL);
		$this->distributedCache->set($cacheKey, $rows, self::CACHE_TTL_DISTRIBUTED);

		return $rows;
	}

	/**
	 * Clears the cached authorized-classes entry for a specific user.
	 *
	 * Must be called whenever a group delegation is added or removed so that
	 * the next request re-evaluates the user's authorizations.
	 */
	public function clearUserCache(string $userId): void {
		$key = 'user_' . $userId;
		$this->localCache->remove($key);
		$this->distributedCache->remove($key);
	}

	/**
	 * Clears all cached authorized-classes entries across both cache tiers.
	 */
	public function clearCache(): void {
		$this->localCache->clear();
		$this->distributedCache->clear();
	}

	/**
	 * @throws Exception
	 */
	public function find(int $id): AuthorizedGroup {
		$queryBuilder = $this->db->getQueryBuilder();
		$queryBuilder->select('*')
			->from($this->getTableName())
			->where($queryBuilder->expr()->eq('id', $queryBuilder->createNamedParameter($id)));
		return $this->findEntity($queryBuilder);
	}

	/**
	 * Get all the authorizations stored in the database.
	 *
	 * @return AuthorizedGroup[]
	 * @throws Exception
	 */
	public function findAll(): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')->from($this->getTableName());
		return $this->findEntities($qb);
	}

	/**
	 * @throws DoesNotExistException
	 * @throws Exception
	 * @throws MultipleObjectsReturnedException
	 */
	public function findByGroupIdAndClass(string $groupId, string $class): AuthorizedGroup {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('group_id', $qb->createNamedParameter($groupId)))
			->andWhere($qb->expr()->eq('class', $qb->createNamedParameter($class)));
		return $this->findEntity($qb);
	}

	/**
	 * @return list<AuthorizedGroup>
	 * @throws Exception
	 */
	public function findExistingGroupsForClass(string $class): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('class', $qb->createNamedParameter($class)));
		return $this->findEntities($qb);
	}

	/**
	 * @throws Exception
	 */
	public function removeGroup(string $gid): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where($qb->expr()->eq('group_id', $qb->createNamedParameter($gid)))
			->executeStatement();
	}
}
