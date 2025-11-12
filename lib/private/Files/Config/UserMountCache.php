<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Files\Config;

use OC\DB\Exceptions\DbalException;
use OC\User\LazyUser;
use OCP\Cache\CappedMemoryCache;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Diagnostics\IEventLogger;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\Config\Event\UserMountAddedEvent;
use OCP\Files\Config\Event\UserMountRemovedEvent;
use OCP\Files\Config\Event\UserMountUpdatedEvent;
use OCP\Files\Config\ICachedMountFileInfo;
use OCP\Files\Config\ICachedMountInfo;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\NotFoundException;
use OCP\IDBConnection;
use OCP\IUser;
use OCP\IUserManager;
use Psr\Log\LoggerInterface;

/**
 * Cache mounts points per user in the cache so we can easily look them up
 */
class UserMountCache implements IUserMountCache {

	/**
	 * Cached mount info.
	 * @var CappedMemoryCache<ICachedMountInfo[]>
	 **/
	private CappedMemoryCache $mountsForUsers;
	/**
	 * fileid => internal path mapping for cached mount info.
	 * @var CappedMemoryCache<string>
	 **/
	private CappedMemoryCache $internalPathCache;
	/** @var CappedMemoryCache<array> */
	private CappedMemoryCache $cacheInfoCache;

	/**
	 * UserMountCache constructor.
	 */
	public function __construct(
		private IDBConnection $connection,
		private IUserManager $userManager,
		private LoggerInterface $logger,
		private IEventLogger $eventLogger,
		private IEventDispatcher $eventDispatcher,
	) {
		$this->cacheInfoCache = new CappedMemoryCache();
		$this->internalPathCache = new CappedMemoryCache();
		$this->mountsForUsers = new CappedMemoryCache();
	}

	public function registerMounts(IUser $user, array $mounts, ?array $mountProviderClasses = null) {
		$this->eventLogger->start('fs:setup:user:register', 'Registering mounts for user');
		/** @var array<string, ICachedMountInfo> $newMounts */
		$newMounts = [];
		foreach ($mounts as $mount) {
			// filter out any storages which aren't scanned yet since we aren't interested in files from those storages (yet)
			if ($mount->getStorageRootId() !== -1) {
				$mountInfo = new LazyStorageMountInfo($user, $mount);
				$newMounts[$mountInfo->getKey()] = $mountInfo;
			}
		}

		$cachedMounts = $this->getMountsForUser($user);
		if (is_array($mountProviderClasses)) {
			$cachedMounts = array_filter($cachedMounts, function (ICachedMountInfo $mountInfo) use ($mountProviderClasses, $newMounts) {
				// for existing mounts that didn't have a mount provider set
				// we still want the ones that map to new mounts
				if ($mountInfo->getMountProvider() === '' && isset($newMounts[$mountInfo->getKey()])) {
					return true;
				}
				return in_array($mountInfo->getMountProvider(), $mountProviderClasses);
			});
		}

		$addedMounts = [];
		$removedMounts = [];

		foreach ($newMounts as $mountKey => $newMount) {
			if (!isset($cachedMounts[$mountKey])) {
				$addedMounts[] = $newMount;
			}
		}

		foreach ($cachedMounts as $mountKey => $cachedMount) {
			if (!isset($newMounts[$mountKey])) {
				$removedMounts[] = $cachedMount;
			}
		}

		$changedMounts = $this->findChangedMounts($newMounts, $cachedMounts);

		if ($addedMounts || $removedMounts || $changedMounts) {
			$this->connection->beginTransaction();
			$userUID = $user->getUID();
			try {
				foreach ($addedMounts as $mount) {
					$this->logger->debug("Adding mount '{$mount->getKey()}' for user '$userUID'", ['app' => 'files', 'mount_provider' => $mount->getMountProvider()]);
					$this->addToCache($mount);
					/** @psalm-suppress InvalidArgument */
					$this->mountsForUsers[$userUID][$mount->getKey()] = $mount;
				}
				foreach ($removedMounts as $mount) {
					$this->logger->debug("Removing mount '{$mount->getKey()}' for user '$userUID'", ['app' => 'files', 'mount_provider' => $mount->getMountProvider()]);
					$this->removeFromCache($mount);
					unset($this->mountsForUsers[$userUID][$mount->getKey()]);
				}
				foreach ($changedMounts as $mountPair) {
					$newMount = $mountPair[1];
					$this->logger->debug("Updating mount '{$newMount->getKey()}' for user '$userUID'", ['app' => 'files', 'mount_provider' => $newMount->getMountProvider()]);
					$this->updateCachedMount($newMount);
					/** @psalm-suppress InvalidArgument */
					$this->mountsForUsers[$userUID][$newMount->getKey()] = $newMount;
				}
				$this->connection->commit();
			} catch (\Throwable $e) {
				$this->connection->rollBack();
				throw $e;
			}

			// Only fire events after all mounts have already been adjusted in the database.
			foreach ($addedMounts as $mount) {
				$this->eventDispatcher->dispatchTyped(new UserMountAddedEvent($mount));
			}
			foreach ($removedMounts as $mount) {
				$this->eventDispatcher->dispatchTyped(new UserMountRemovedEvent($mount));
			}
			foreach ($changedMounts as $mountPair) {
				$this->eventDispatcher->dispatchTyped(new UserMountUpdatedEvent($mountPair[0], $mountPair[1]));
			}
		}
		$this->eventLogger->end('fs:setup:user:register');
	}

	/**
	 * @param array<string, ICachedMountInfo> $newMounts
	 * @param array<string, ICachedMountInfo> $cachedMounts
	 * @return list<list{0: ICachedMountInfo, 1: ICachedMountInfo}> Pairs of old and new mounts
	 */
	private function findChangedMounts(array $newMounts, array $cachedMounts): array {
		$changed = [];
		foreach ($cachedMounts as $key => $cachedMount) {
			if (isset($newMounts[$key])) {
				$newMount = $newMounts[$key];
				if (
					$newMount->getStorageId() !== $cachedMount->getStorageId()
					|| $newMount->getMountId() !== $cachedMount->getMountId()
					|| $newMount->getMountProvider() !== $cachedMount->getMountProvider()
				) {
					$changed[] = [$cachedMount, $newMount];
				}
			}
		}
		return $changed;
	}

	private function addToCache(ICachedMountInfo $mount) {
		if ($mount->getStorageId() !== -1) {
			$qb = $this->connection->getQueryBuilder();
			$qb
				->insert('mounts')
				->values([
					'storage_id' => $qb->createNamedParameter($mount->getStorageId(), IQueryBuilder::PARAM_INT),
					'root_id' => $qb->createNamedParameter($mount->getRootId(), IQueryBuilder::PARAM_INT),
					'user_id' => $qb->createNamedParameter($mount->getUser()->getUID()),
					'mount_point' => $qb->createNamedParameter($mount->getMountPoint()),
					'mount_point_hash' => $qb->createNamedParameter(hash('xxh128', $mount->getMountPoint())),
					'mount_id' => $qb->createNamedParameter($mount->getMountId(), IQueryBuilder::PARAM_INT),
					'mount_provider_class' => $qb->createNamedParameter($mount->getMountProvider()),
				]);
			try {
				$qb->executeStatement();
			} catch (Exception $e) {
				if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					throw $e;
				}
			}
		} else {
			// in some cases this is legitimate, like orphaned shares
			$this->logger->debug('Could not get storage info for mount at ' . $mount->getMountPoint());
		}
	}

	private function updateCachedMount(ICachedMountInfo $mount) {
		$builder = $this->connection->getQueryBuilder();

		$query = $builder->update('mounts')
			->set('storage_id', $builder->createNamedParameter($mount->getStorageId()))
			->set('mount_point', $builder->createNamedParameter($mount->getMountPoint()))
			->set('mount_point_hash', $builder->createNamedParameter(hash('xxh128', $mount->getMountPoint())))
			->set('mount_id', $builder->createNamedParameter($mount->getMountId(), IQueryBuilder::PARAM_INT))
			->set('mount_provider_class', $builder->createNamedParameter($mount->getMountProvider()))
			->where($builder->expr()->eq('user_id', $builder->createNamedParameter($mount->getUser()->getUID())))
			->andWhere($builder->expr()->eq('root_id', $builder->createNamedParameter($mount->getRootId(), IQueryBuilder::PARAM_INT)));

		$query->executeStatement();
	}

	private function removeFromCache(ICachedMountInfo $mount) {
		$builder = $this->connection->getQueryBuilder();

		$query = $builder->delete('mounts')
			->where($builder->expr()->eq('user_id', $builder->createNamedParameter($mount->getUser()->getUID())))
			->andWhere($builder->expr()->eq('root_id', $builder->createNamedParameter($mount->getRootId(), IQueryBuilder::PARAM_INT)))
			->andWhere($builder->expr()->eq('mount_point_hash', $builder->createNamedParameter(hash('xxh128', $mount->getMountPoint()))));
		$query->executeStatement();
	}

	/**
	 * @param array $row
	 * @param (callable(CachedMountInfo): string)|null $pathCallback
	 * @return CachedMountInfo
	 */
	private function dbRowToMountInfo(array $row, ?callable $pathCallback = null): ICachedMountInfo {
		$user = new LazyUser($row['user_id'], $this->userManager);
		$mount_id = $row['mount_id'];
		if (!is_null($mount_id)) {
			$mount_id = (int)$mount_id;
		}
		if ($pathCallback) {
			return new LazyPathCachedMountInfo(
				$user,
				(int)$row['storage_id'],
				(int)$row['root_id'],
				$row['mount_point'],
				$row['mount_provider_class'] ?? '',
				$mount_id,
				$pathCallback,
			);
		} else {
			return new CachedMountInfo(
				$user,
				(int)$row['storage_id'],
				(int)$row['root_id'],
				$row['mount_point'],
				$row['mount_provider_class'] ?? '',
				$mount_id,
				$row['path'] ?? '',
			);
		}
	}

	/**
	 * @param IUser $user
	 * @return ICachedMountInfo[]
	 */
	public function getMountsForUser(IUser $user) {
		$userUID = $user->getUID();
		if (!$this->userManager->userExists($userUID)) {
			return [];
		}
		if (!isset($this->mountsForUsers[$userUID])) {
			$builder = $this->connection->getQueryBuilder();
			$query = $builder->select('storage_id', 'root_id', 'user_id', 'mount_point', 'mount_id', 'mount_provider_class')
				->from('mounts', 'm')
				->where($builder->expr()->eq('user_id', $builder->createNamedParameter($userUID)));

			$result = $query->executeQuery();
			$rows = $result->fetchAll();
			$result->closeCursor();

			/** @var array<string, ICachedMountInfo> $mounts */
			$mounts = [];
			foreach ($rows as $row) {
				$mount = $this->dbRowToMountInfo($row, [$this, 'getInternalPathForMountInfo']);
				if ($mount !== null) {
					$mounts[$mount->getKey()] = $mount;
				}
			}
			$this->mountsForUsers[$userUID] = $mounts;
		}
		return $this->mountsForUsers[$userUID];
	}

	public function getInternalPathForMountInfo(CachedMountInfo $info): string {
		$cached = $this->internalPathCache->get($info->getRootId());
		if ($cached !== null) {
			return $cached;
		}
		$builder = $this->connection->getQueryBuilder();
		$query = $builder->select('path')
			->from('filecache')
			->where($builder->expr()->eq('fileid', $builder->createNamedParameter($info->getRootId())));
		return $query->executeQuery()->fetchOne() ?: '';
	}

	/**
	 * @param int $numericStorageId
	 * @param string|null $user limit the results to a single user
	 * @return CachedMountInfo[]
	 */
	public function getMountsForStorageId($numericStorageId, $user = null) {
		$builder = $this->connection->getQueryBuilder();
		$query = $builder->select('storage_id', 'root_id', 'user_id', 'mount_point', 'mount_id', 'f.path', 'mount_provider_class')
			->from('mounts', 'm')
			->innerJoin('m', 'filecache', 'f', $builder->expr()->eq('m.root_id', 'f.fileid'))
			->where($builder->expr()->eq('storage_id', $builder->createNamedParameter($numericStorageId, IQueryBuilder::PARAM_INT)));

		if ($user) {
			$query->andWhere($builder->expr()->eq('user_id', $builder->createNamedParameter($user)));
		}

		$result = $query->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();

		return array_filter(array_map([$this, 'dbRowToMountInfo'], $rows));
	}

	/**
	 * @param int $rootFileId
	 * @return CachedMountInfo[]
	 */
	public function getMountsForRootId($rootFileId) {
		$builder = $this->connection->getQueryBuilder();
		$query = $builder->select('storage_id', 'root_id', 'user_id', 'mount_point', 'mount_id', 'f.path', 'mount_provider_class')
			->from('mounts', 'm')
			->innerJoin('m', 'filecache', 'f', $builder->expr()->eq('m.root_id', 'f.fileid'))
			->where($builder->expr()->eq('root_id', $builder->createNamedParameter($rootFileId, IQueryBuilder::PARAM_INT)));

		$result = $query->executeQuery();
		$rows = $result->fetchAll();
		$result->closeCursor();

		return array_filter(array_map([$this, 'dbRowToMountInfo'], $rows));
	}

	/**
	 * @param $fileId
	 * @return array{int, string, int}
	 * @throws \OCP\Files\NotFoundException
	 */
	private function getCacheInfoFromFileId($fileId): array {
		if (!isset($this->cacheInfoCache[$fileId])) {
			$builder = $this->connection->getQueryBuilder();
			$query = $builder->select('storage', 'path', 'mimetype')
				->from('filecache')
				->where($builder->expr()->eq('fileid', $builder->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)));

			$result = $query->executeQuery();
			$row = $result->fetch();
			$result->closeCursor();

			if (is_array($row)) {
				$this->cacheInfoCache[$fileId] = [
					(int)$row['storage'],
					(string)$row['path'],
					(int)$row['mimetype']
				];
			} else {
				throw new NotFoundException('File with id "' . $fileId . '" not found');
			}
		}
		return $this->cacheInfoCache[$fileId];
	}

	/**
	 * @param int $fileId
	 * @param string|null $user optionally restrict the results to a single user
	 * @return ICachedMountFileInfo[]
	 * @since 9.0.0
	 */
	public function getMountsForFileId($fileId, $user = null) {
		try {
			[$storageId, $internalPath] = $this->getCacheInfoFromFileId($fileId);
		} catch (NotFoundException $e) {
			return [];
		}

		$builder = $this->connection->getQueryBuilder();
		$query = $builder->select('storage_id', 'root_id', 'user_id', 'mount_point', 'mount_id', 'f.path', 'mount_provider_class')
			->from('mounts', 'm')
			->innerJoin('m', 'filecache', 'f', $builder->expr()->eq('m.root_id', 'f.fileid'))
			->where($builder->expr()->eq('storage_id', $builder->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)))
			->andWhere(
				$builder->expr()->orX(
					$builder->expr()->eq('f.fileid', $builder->createNamedParameter($fileId)),
					$builder->expr()->emptyString('f.path'),
					$builder->expr()->eq(
						$builder->func()->concat('f.path', $builder->createNamedParameter('/')),
						$builder->func()->substring(
							$builder->createNamedParameter($internalPath),
							$builder->createNamedParameter(1, IQueryBuilder::PARAM_INT),
							$builder->func()->add(
								$builder->func()->charLength('f.path'),
								$builder->createNamedParameter(1, IQueryBuilder::PARAM_INT),
							),
						),
					),
				)
			);

		if ($user !== null) {
			$query->andWhere($builder->expr()->eq('user_id', $builder->createNamedParameter($user)));
		}
		$result = $query->executeQuery();

		$mounts = [];
		while ($row = $result->fetch()) {
			if ($user === null && !$this->userManager->userExists($row['user_id'])) {
				continue;
			}

			$mounts[] = new CachedMountFileInfo(
				new LazyUser($row['user_id'], $this->userManager),
				(int)$row['storage_id'],
				(int)$row['root_id'],
				$row['mount_point'],
				$row['mount_id'] === null ? null : (int)$row['mount_id'],
				$row['mount_provider_class'] ?? '',
				$row['path'] ?? '',
				$internalPath,
			);
		}

		return $mounts;
	}

	/**
	 * Remove all cached mounts for a user
	 *
	 * @param IUser $user
	 */
	public function removeUserMounts(IUser $user) {
		$builder = $this->connection->getQueryBuilder();

		$query = $builder->delete('mounts')
			->where($builder->expr()->eq('user_id', $builder->createNamedParameter($user->getUID())));
		$query->executeStatement();
	}

	public function removeUserStorageMount($storageId, $userId) {
		$builder = $this->connection->getQueryBuilder();

		$query = $builder->delete('mounts')
			->where($builder->expr()->eq('user_id', $builder->createNamedParameter($userId)))
			->andWhere($builder->expr()->eq('storage_id', $builder->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)));
		$query->executeStatement();
	}

	public function remoteStorageMounts($storageId) {
		$builder = $this->connection->getQueryBuilder();

		$query = $builder->delete('mounts')
			->where($builder->expr()->eq('storage_id', $builder->createNamedParameter($storageId, IQueryBuilder::PARAM_INT)));
		$query->executeStatement();
	}

	/**
	 * @param array $users
	 * @return array
	 */
	public function getUsedSpaceForUsers(array $users) {
		$builder = $this->connection->getQueryBuilder();

		$mountPointHashes = array_map(static fn (IUser $user) => hash('xxh128', '/' . $user->getUID() . '/'), $users);
		$userIds = array_map(static fn (IUser $user) => $user->getUID(), $users);

		$query = $builder->select('m.user_id', 'f.size')
			->from('mounts', 'm')
			->innerJoin('m', 'filecache', 'f',
				$builder->expr()->andX(
					$builder->expr()->eq('m.storage_id', 'f.storage'),
					$builder->expr()->eq('f.path_hash', $builder->createNamedParameter(md5('files')))
				))
			->where($builder->expr()->in('m.mount_point_hash', $builder->createNamedParameter($mountPointHashes, IQueryBuilder::PARAM_STR_ARRAY)))
			->andWhere($builder->expr()->in('m.user_id', $builder->createNamedParameter($userIds, IQueryBuilder::PARAM_STR_ARRAY)));

		$result = $query->executeQuery();

		$results = [];
		while ($row = $result->fetch()) {
			$results[$row['user_id']] = $row['size'];
		}
		$result->closeCursor();
		return $results;
	}

	public function clear(): void {
		$this->cacheInfoCache = new CappedMemoryCache();
		$this->mountsForUsers = new CappedMemoryCache();
	}

	public function getMountForPath(IUser $user, string $path): ICachedMountInfo {
		$mounts = $this->getMountsForUser($user);
		$mountPoints = array_map(function (ICachedMountInfo $mount) {
			return $mount->getMountPoint();
		}, $mounts);
		$mounts = array_combine($mountPoints, $mounts);

		$current = rtrim($path, '/');
		// walk up the directory tree until we find a path that has a mountpoint set
		// the loop will return if a mountpoint is found or break if none are found
		while (true) {
			$mountPoint = $current . '/';
			if (isset($mounts[$mountPoint])) {
				return $mounts[$mountPoint];
			} elseif ($current === '') {
				break;
			}

			$current = dirname($current);
			if ($current === '.' || $current === '/') {
				$current = '';
			}
		}

		throw new NotFoundException('No cached mount for path ' . $path);
	}

	public function getMountsInPath(IUser $user, string $path): array {
		$path = rtrim($path, '/') . '/';
		$mounts = $this->getMountsForUser($user);
		return array_filter($mounts, function (ICachedMountInfo $mount) use ($path) {
			return $mount->getMountPoint() !== $path && str_starts_with($mount->getMountPoint(), $path);
		});
	}

	public function removeMount(string $mountPoint): void {
		$query = $this->connection->getQueryBuilder();
		$query->delete('mounts')
			->where($query->expr()->eq('mount_point', $query->createNamedParameter($mountPoint)));
		$query->executeStatement();
	}

	public function addMount(IUser $user, string $mountPoint, ICacheEntry $rootCacheEntry, string $mountProvider, ?int $mountId = null): void {
		$query = $this->connection->getQueryBuilder();
		$query->insert('mounts')
			->values([
				'storage_id' => $query->createNamedParameter($rootCacheEntry->getStorageId()),
				'root_id' => $query->createNamedParameter($rootCacheEntry->getId()),
				'user_id' => $query->createNamedParameter($user->getUID()),
				'mount_point' => $query->createNamedParameter($mountPoint),
				'mount_id' => $query->createNamedParameter($mountId),
				'mount_provider_class' => $query->createNamedParameter($mountProvider)
			]);

		try {
			$query->executeStatement();
		} catch (DbalException $e) {
			if ($e->getReason() !== DbalException::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw $e;
			}
		}
	}
}
