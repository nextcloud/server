<?php

/**
 * SPDX-FileCopyrightText: 2018-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_External\Service;

use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IParameter;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Security\ICrypto;

/**
 * Stores the mount config in the database
 *
 * @psalm-type ApplicableConfig = array{type: int, value: string}
 * @psalm-type ExternalMountInfo = array{
 *      mount_id: int,
 *      mount_point: string,
 *      storage_backend: string,
 *      auth_backend: string,
 *      priority: int,
 *      type: self::MOUNT_TYPE_ADMIN|self::MOUNT_TYPE_PERSONAL,
 *      applicable: list<ApplicableConfig>,
 *      config: array,
 *      options: array,
 *  }
 */
class DBConfigService {
	public const MOUNT_TYPE_ADMIN = 1;
	public const MOUNT_TYPE_PERSONAL = 2;
	/** @deprecated use MOUNT_TYPE_PERSONAL (full uppercase) instead */
	public const MOUNT_TYPE_PERSONAl = 2;

	public const APPLICABLE_TYPE_GLOBAL = 1;
	public const APPLICABLE_TYPE_GROUP = 2;
	public const APPLICABLE_TYPE_USER = 3;

	public function __construct(
		private IDBConnection $connection,
		private ICrypto $crypto,
	) {
	}

	/**
	 * @return ?ExternalMountInfo
	 */
	public function getMountById(int $mountId): ?array {
		$builder = $this->connection->getQueryBuilder();
		$query = $builder->select(['mount_id', 'mount_point', 'storage_backend', 'auth_backend', 'priority', 'type'])
			->from('external_mounts', 'm')
			->where($builder->expr()->eq('mount_id', $builder->createNamedParameter($mountId, IQueryBuilder::PARAM_INT)));
		$mounts = $this->getMountsFromQuery($query);
		if (count($mounts) > 0) {
			return $mounts[0];
		} else {
			return null;
		}
	}

	/**
	 * Get all configured mounts
	 *
	 * @return list<ExternalMountInfo>
	 */
	public function getAllMounts(): array {
		$builder = $this->connection->getQueryBuilder();
		$query = $builder->select(['mount_id', 'mount_point', 'storage_backend', 'auth_backend', 'priority', 'type'])
			->from('external_mounts');
		return $this->getMountsFromQuery($query);
	}

	public function getMountsForUser(string $userId, array $groupIds): array {
		$builder = $this->getSelectQueryBuilder();
		$builder = $builder->where($builder->expr()->orX(
			$builder->expr()->andX( // global mounts
				$builder->expr()->eq('a.type', $builder->createNamedParameter(self::APPLICABLE_TYPE_GLOBAL, IQueryBuilder::PARAM_INT)),
				$builder->expr()->isNull('a.value'),
			),
			$builder->expr()->andX( // mounts for user
				$builder->expr()->eq('a.type', $builder->createNamedParameter(self::APPLICABLE_TYPE_USER, IQueryBuilder::PARAM_INT)),
				$builder->expr()->eq('a.value', $builder->createNamedParameter($userId)),
			),
			$builder->expr()->andX( // mounts for group
				$builder->expr()->eq('a.type', $builder->createNamedParameter(self::APPLICABLE_TYPE_GROUP, IQueryBuilder::PARAM_INT)),
				$builder->expr()->in('a.value', $builder->createNamedParameter($groupIds, IQueryBuilder::PARAM_STR_ARRAY)),
			),
		));

		return $this->getMountsFromQuery($builder);
	}

	/**
	 * @param list<string> $groupIds
	 * @return list<ExternalMountInfo>
	 */
	public function getMountsForGroups(array $groupIds): array {
		$builder = $this->getSelectQueryBuilder();
		$builder = $builder->where($builder->expr()->andX( // mounts for group
			$builder->expr()->eq('a.type', $builder->createNamedParameter(self::APPLICABLE_TYPE_GROUP, IQueryBuilder::PARAM_INT)),
			$builder->expr()->in('a.value', $builder->createNamedParameter($groupIds, IQueryBuilder::PARAM_STR_ARRAY)),
		));

		return $this->getMountsFromQuery($builder);
	}

	private function getSelectQueryBuilder(): IQueryBuilder {
		$builder = $this->connection->getQueryBuilder();
		return $builder->select(['m.mount_id', 'mount_point', 'storage_backend', 'auth_backend', 'priority', 'm.type'])
			->from('external_mounts', 'm')
			->innerJoin('m', 'external_applicable', 'a', $builder->expr()->eq('m.mount_id', 'a.mount_id'));
	}

	/*
	 * @return list<ExternalMountInfo>
	 */
	public function getMountsForUserAndPath(string $userId, array $groupIds, string $path, bool $forChildren): array {
		$path = str_replace('/' . $userId . '/files', '', $path);
		$path = rtrim($path, '/');
		$builder = $this->getSelectQueryBuilder();
		$builder->where($builder->expr()->orX(
			$builder->expr()->andX( // global mounts
				$builder->expr()->eq('a.type', $builder->createNamedParameter(self::APPLICABLE_TYPE_GLOBAL, IQueryBuilder::PARAM_INT)),
				$builder->expr()->isNull('a.value'),
				$forChildren ? $builder->expr()->like('m.mount_point', $builder->createNamedParameter($this->connection->escapeLikeParameter($path) . '_%', IQueryBuilder::PARAM_STR))
							 : $builder->expr()->eq('m.mount_point', $builder->createNamedParameter($path, IQueryBuilder::PARAM_STR)),
			),
			$builder->expr()->andX( // mounts for user
				$builder->expr()->eq('a.type', $builder->createNamedParameter(self::APPLICABLE_TYPE_USER, IQueryBuilder::PARAM_INT)),
				$builder->expr()->eq('a.value', $builder->createNamedParameter($userId)),
				$forChildren ? $builder->expr()->like('m.mount_point', $builder->createNamedParameter($this->connection->escapeLikeParameter($path) . '_%', IQueryBuilder::PARAM_STR))
							 : $builder->expr()->eq('m.mount_point', $builder->createNamedParameter($path, IQueryBuilder::PARAM_STR)),
			),
			$builder->expr()->andX( // mounts for group
				$builder->expr()->eq('a.type', $builder->createNamedParameter(self::APPLICABLE_TYPE_GROUP, IQueryBuilder::PARAM_INT)),
				$builder->expr()->in('a.value', $builder->createNamedParameter($groupIds, IQueryBuilder::PARAM_STR_ARRAY)),
				$forChildren ? $builder->expr()->like('m.mount_point', $builder->createNamedParameter($this->connection->escapeLikeParameter($path) . '_%', IQueryBuilder::PARAM_STR))
							 : $builder->expr()->eq('m.mount_point', $builder->createNamedParameter($path, IQueryBuilder::PARAM_STR)),
			),
		));

		return $this->getMountsFromQuery($builder);
	}

	/**
	 * @return list<ExternalMountInfo>
	 */
	public function getGlobalMounts(): array {
		$builder = $this->connection->getQueryBuilder();
		$query = $builder->select(['m.mount_id', 'mount_point', 'storage_backend', 'auth_backend', 'priority', 'm.type'])
			->from('external_mounts', 'm')
			->innerJoin('m', 'external_applicable', 'a', $builder->expr()->eq('m.mount_id', 'a.mount_id'))
			->where($builder->expr()->andX( // global mounts
				$builder->expr()->eq('a.type', $builder->createNamedParameter(self::APPLICABLE_TYPE_GLOBAL, IQueryBuilder::PARAM_INT)),
				$builder->expr()->isNull('a.value'),
			), );

		return $this->getMountsFromQuery($query);
	}

	public function modifyMountsOnUserDelete(string $uid): void {
		$this->modifyMountsOnDelete($uid, self::APPLICABLE_TYPE_USER);
	}

	public function modifyMountsOnGroupDelete(string $gid): void {
		$this->modifyMountsOnDelete($gid, self::APPLICABLE_TYPE_GROUP);
	}

	protected function modifyMountsOnDelete(string $applicableId, int $applicableType): void {
		$builder = $this->connection->getQueryBuilder();
		$query = $builder->select(['a.mount_id', $builder->func()->count('a.mount_id', 'count')])
			->from('external_applicable', 'a')
			->leftJoin('a', 'external_applicable', 'b', $builder->expr()->eq('a.mount_id', 'b.mount_id'))
			->where($builder->expr()->andX(
				$builder->expr()->eq('b.type', $builder->createNamedParameter($applicableType, IQueryBuilder::PARAM_INT)),
				$builder->expr()->eq('b.value', $builder->createNamedParameter($applicableId)),
			),
			)
			->groupBy(['a.mount_id']);
		$stmt = $query->executeQuery();
		$result = $stmt->fetchAllAssociative();
		$stmt->closeCursor();

		foreach ($result as $row) {
			if ((int)$row['count'] > 1) {
				$this->removeApplicable($row['mount_id'], $applicableType, $applicableId);
			} else {
				$this->removeMount($row['mount_id']);
			}
		}
	}

	/**
	 * Get admin defined mounts
	 *
	 * @return list<ExternalMountInfo>
	 */
	public function getAdminMounts(): array {
		$builder = $this->connection->getQueryBuilder();
		$query = $builder->select(['mount_id', 'mount_point', 'storage_backend', 'auth_backend', 'priority', 'type'])
			->from('external_mounts')
			->where($builder->expr()->eq('type', $builder->expr()->literal(self::MOUNT_TYPE_ADMIN, IQueryBuilder::PARAM_INT)));
		return $this->getMountsFromQuery($query);
	}

	protected function getForQuery(IQueryBuilder $builder, int $type, ?string $value): IQueryBuilder {
		$builder = $this->getSelectQueryBuilder();
		$builder = $builder->where($builder->expr()->eq('a.type', $builder->createNamedParameter($type, IQueryBuilder::PARAM_INT)));

		if (is_null($value)) {
			$builder = $builder->andWhere($builder->expr()->isNull('a.value'));
		} else {
			$builder = $builder->andWhere($builder->expr()->eq('a.value', $builder->createNamedParameter($value)));
		}

		return $builder;
	}

	/**
	 * Get mounts by applicable
	 *
	 * @param int $type any of the self::APPLICABLE_TYPE_ constants
	 * @param string|null $value user_id, group_id or null for global mounts
	 * @return list<ExternalMountInfo>
	 */
	public function getMountsFor(int $type, ?string $value): array {
		$builder = $this->connection->getQueryBuilder();
		$query = $this->getForQuery($builder, $type, $value);

		return $this->getMountsFromQuery($query);
	}

	/**
	 * Get admin defined mounts by applicable
	 *
	 * @param int $type any of the self::APPLICABLE_TYPE_ constants
	 * @param string|null $value user_id, group_id or null for global mounts
	 * @return list<ExternalMountInfo>
	 */
	public function getAdminMountsFor(int $type, ?string $value): array {
		$builder = $this->connection->getQueryBuilder();
		$query = $this->getForQuery($builder, $type, $value);
		$query->andWhere($builder->expr()->eq('m.type', $builder->expr()->literal(self::MOUNT_TYPE_ADMIN, IQueryBuilder::PARAM_INT)));

		return $this->getMountsFromQuery($query);
	}

	/**
	 * Get admin defined mounts for multiple applicable
	 *
	 * @param int $type any of the self::APPLICABLE_TYPE_ constants
	 * @param string[] $values user_ids or group_ids
	 * @return list<ExternalMountInfo>
	 */
	public function getAdminMountsForMultiple(int $type, array $values): array {
		$builder = $this->getSelectQueryBuilder();
		$params = array_map(function (string $value) use ($builder): IParameter {
			return $builder->createNamedParameter($value, IQueryBuilder::PARAM_STR);
		}, $values);

		$builder = $builder
			->where($builder->expr()->eq('a.type', $builder->createNamedParameter($type, IQueryBuilder::PARAM_INT)))
			->andWhere($builder->expr()->in('a.value', $params));
		$builder->andWhere($builder->expr()->eq('m.type', $builder->expr()->literal(self::MOUNT_TYPE_ADMIN, IQueryBuilder::PARAM_INT)));

		return $this->getMountsFromQuery($builder);
	}

	/**
	 * Get user defined mounts by applicable
	 *
	 * @param int $type any of the self::APPLICABLE_TYPE_ constants
	 * @param string|null $value user_id, group_id or null for global mounts
	 * @return list<ExternalMountInfo>
	 */
	public function getUserMountsFor(int $type, ?string $value): array {
		$builder = $this->connection->getQueryBuilder();
		$query = $this->getForQuery($builder, $type, $value);
		$query->andWhere($builder->expr()->eq('m.type', $builder->expr()->literal(self::MOUNT_TYPE_PERSONAL, IQueryBuilder::PARAM_INT)));

		return $this->getMountsFromQuery($query);
	}

	/**
	 * Add a mount to the database
	 *
	 * @param self::MOUNT_TYPE_ADMIN|self::MOUNT_TYPE_PERSONAL $type
	 * @return int the id of the new mount
	 */
	public function addMount(string $mountPoint, string $storageBackend, string $authBackend, ?int $priority, int $type): int {
		if (!$priority) {
			$priority = 100;
		}
		$builder = $this->connection->getQueryBuilder();
		$query = $builder->insert('external_mounts')
			->values([
				'mount_point' => $builder->createNamedParameter($mountPoint, IQueryBuilder::PARAM_STR),
				'storage_backend' => $builder->createNamedParameter($storageBackend, IQueryBuilder::PARAM_STR),
				'auth_backend' => $builder->createNamedParameter($authBackend, IQueryBuilder::PARAM_STR),
				'priority' => $builder->createNamedParameter($priority, IQueryBuilder::PARAM_INT),
				'type' => $builder->createNamedParameter($type, IQueryBuilder::PARAM_INT),
			]);
		$query->executeStatement();
		return $query->getLastInsertId();
	}

	/**
	 * Remove a mount from the database
	 */
	public function removeMount(int $mountId): void {
		$builder = $this->connection->getQueryBuilder();
		$query = $builder->delete('external_mounts')
			->where($builder->expr()->eq('mount_id', $builder->createNamedParameter($mountId, IQueryBuilder::PARAM_INT)));
		$query->executeStatement();

		$builder = $this->connection->getQueryBuilder();
		$query = $builder->delete('external_applicable')
			->where($builder->expr()->eq('mount_id', $builder->createNamedParameter($mountId, IQueryBuilder::PARAM_INT)));
		$query->executeStatement();

		$builder = $this->connection->getQueryBuilder();
		$query = $builder->delete('external_config')
			->where($builder->expr()->eq('mount_id', $builder->createNamedParameter($mountId, IQueryBuilder::PARAM_INT)));
		$query->executeStatement();

		$builder = $this->connection->getQueryBuilder();
		$query = $builder->delete('external_options')
			->where($builder->expr()->eq('mount_id', $builder->createNamedParameter($mountId, IQueryBuilder::PARAM_INT)));
		$query->executeStatement();
	}

	public function setMountPoint(int $mountId, string $newMountPoint): void {
		$builder = $this->connection->getQueryBuilder();

		$query = $builder->update('external_mounts')
			->set('mount_point', $builder->createNamedParameter($newMountPoint))
			->where($builder->expr()->eq('mount_id', $builder->createNamedParameter($mountId, IQueryBuilder::PARAM_INT)));

		$query->executeStatement();
	}

	public function setAuthBackend(int $mountId, string $newAuthBackend): void {
		$builder = $this->connection->getQueryBuilder();

		$query = $builder->update('external_mounts')
			->set('auth_backend', $builder->createNamedParameter($newAuthBackend))
			->where($builder->expr()->eq('mount_id', $builder->createNamedParameter($mountId, IQueryBuilder::PARAM_INT)));

		$query->executeStatement();
	}

	public function setConfig(int $mountId, string $key, string $value): void {
		if ($key === 'password') {
			$value = $this->encryptValue($value);
		}

		try {
			$builder = $this->connection->getQueryBuilder();
			$builder->insert('external_config')
				->setValue('mount_id', $builder->createNamedParameter($mountId, IQueryBuilder::PARAM_INT))
				->setValue('key', $builder->createNamedParameter($key, IQueryBuilder::PARAM_STR))
				->setValue('value', $builder->createNamedParameter($value, IQueryBuilder::PARAM_STR))
				->executeStatement();
		} catch (Exception $e) {
			if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw $e;
			}
			$builder = $this->connection->getQueryBuilder();
			$query = $builder->update('external_config')
				->set('value', $builder->createNamedParameter($value, IQueryBuilder::PARAM_STR))
				->where($builder->expr()->eq('mount_id', $builder->createNamedParameter($mountId, IQueryBuilder::PARAM_INT)))
				->andWhere($builder->expr()->eq('key', $builder->createNamedParameter($key, IQueryBuilder::PARAM_STR)));
			$query->executeStatement();
		}
	}

	public function setOption(int $mountId, string $key, string $value): void {
		try {
			$builder = $this->connection->getQueryBuilder();
			$builder->insert('external_options')
				->setValue('mount_id', $builder->createNamedParameter($mountId, IQueryBuilder::PARAM_INT))
				->setValue('key', $builder->createNamedParameter($key, IQueryBuilder::PARAM_STR))
				->setValue('value', $builder->createNamedParameter(json_encode($value), IQueryBuilder::PARAM_STR))
				->executeStatement();
		} catch (Exception $e) {
			if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw $e;
			}
			$builder = $this->connection->getQueryBuilder();
			$query = $builder->update('external_options')
				->set('value', $builder->createNamedParameter(json_encode($value), IQueryBuilder::PARAM_STR))
				->where($builder->expr()->eq('mount_id', $builder->createNamedParameter($mountId, IQueryBuilder::PARAM_INT)))
				->andWhere($builder->expr()->eq('key', $builder->createNamedParameter($key, IQueryBuilder::PARAM_STR)));
			$query->executeStatement();
		}
	}

	public function addApplicable(int $mountId, int $type, ?string $value): void {
		try {
			$builder = $this->connection->getQueryBuilder();
			$builder->insert('external_applicable')
				->setValue('mount_id', $builder->createNamedParameter($mountId))
				->setValue('type', $builder->createNamedParameter($type))
				->setValue('value', $builder->createNamedParameter($value))
				->executeStatement();
		} catch (Exception $e) {
			// applicable exists already
			if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
				throw $e;
			}
		}
	}

	public function removeApplicable(int $mountId, int $type, ?string $value): void {
		$builder = $this->connection->getQueryBuilder();
		$query = $builder->delete('external_applicable')
			->where($builder->expr()->eq('mount_id', $builder->createNamedParameter($mountId, IQueryBuilder::PARAM_INT)))
			->andWhere($builder->expr()->eq('type', $builder->createNamedParameter($type, IQueryBuilder::PARAM_INT)));

		if (is_null($value)) {
			$query = $query->andWhere($builder->expr()->isNull('value'));
		} else {
			$query = $query->andWhere($builder->expr()->eq('value', $builder->createNamedParameter($value, IQueryBuilder::PARAM_STR)));
		}

		$query->executeStatement();
	}

	/**
	 * @return list<ExternalMountInfo>
	 * @throws Exception
	 */
	private function getMountsFromQuery(IQueryBuilder $query): array {
		$result = $query->executeQuery();
		/** @var \Generator<array{mount_id: int, mount_point: string, storage_backend: string, auth_backend: string, priority: mixed, type: int}> $mounts */
		$mounts = $result->iterateAssociative();
		$uniqueMounts = [];
		foreach ($mounts as $mount) {
			$id = $mount['mount_id'];
			if (!isset($uniqueMounts[$id])) {
				$uniqueMounts[$id] = $mount;
			}
		}
		$uniqueMounts = array_values($uniqueMounts);

		$mountIds = array_map(function (array $mount): int {
			return $mount['mount_id'];
		}, $uniqueMounts);
		$mountIds = array_values(array_unique($mountIds));

		$applicable = $this->getApplicableForMounts($mountIds);
		$config = $this->getConfigForMounts($mountIds);
		$options = $this->getOptionsForMounts($mountIds);

		return array_map(function (array $mount, array $applicable, array $config, array $options): array {
			$mountType = (int)$mount['type'];
			assert($mountType === self::MOUNT_TYPE_ADMIN || $mountType === self::MOUNT_TYPE_PERSONAL);
			$mount['type'] = $mountType;
			$mount['priority'] = (int)$mount['priority'];
			$mount['applicable'] = $applicable;
			$mount['config'] = $config;
			$mount['options'] = $options;
			return $mount;
		}, $uniqueMounts, $applicable, $config, $options);
	}

	/**
	 * Get mount options from a table grouped by mount id
	 *
	 * @param string[] $fields
	 * @param int[] $mountIds
	 * @return array<int, list<array>> [$mountId => [['field1' => $value1, ...], ...], ...]
	 */
	private function selectForMounts(string $table, array $fields, array $mountIds): array {
		if (count($mountIds) === 0) {
			return [];
		}
		$builder = $this->connection->getQueryBuilder();
		$fields[] = 'mount_id';
		$placeHolders = array_map(fn ($id) => $builder->createPositionalParameter($id, IQueryBuilder::PARAM_INT), $mountIds);
		$query = $builder->select($fields)
			->from($table)
			->where($builder->expr()->in('mount_id', $placeHolders));

		$result = $query->executeQuery();
		$rows = $result->fetchAllAssociative();
		$result->closeCursor();

		$result = [];
		foreach ($mountIds as $mountId) {
			$result[$mountId] = [];
		}
		foreach ($rows as $row) {
			if (isset($row['type'])) {
				$row['type'] = (int)$row['type'];
			}
			$result[$row['mount_id']][] = $row;
		}
		return $result;
	}

	/**
	 * @param int[] $mountIds
	 * @return array<int, list<array{type: mixed, value: string}>> [$id => [['type' => $type, 'value' => $value], ...], ...]
	 */
	public function getApplicableForMounts(array $mountIds): array {
		/** @var array<int, list<array{type: mixed, value: string}>> $result */
		$result = $this->selectForMounts('external_applicable', ['type', 'value'], $mountIds);
		return $result;
	}

	/**
	 * @param int[] $mountIds
	 * @return array<int, array> [$id => ['key1' => $value1, ...], ...]
	 */
	public function getConfigForMounts(array $mountIds): array {
		$mountConfigs = $this->selectForMounts('external_config', ['key', 'value'], $mountIds);
		return array_map([$this, 'createKeyValueMap'], $mountConfigs);
	}

	/**
	 * @param int[] $mountIds
	 * @return array<int, array> [$id => ['key1' => $value1, ...], ...]
	 */
	public function getOptionsForMounts(array $mountIds): array {
		$mountOptions = $this->selectForMounts('external_options', ['key', 'value'], $mountIds);
		$optionsMap = array_map([$this, 'createKeyValueMap'], $mountOptions);
		return array_map(function (array $options) {
			return array_map(function ($option) {
				return json_decode($option);
			}, $options);
		}, $optionsMap);
	}

	/**
	 * @param list<array{key: string, value: string}> $keyValuePairs [['key'=>$key, 'value=>$value], ...]
	 * @return array ['key1' => $value1, ...]
	 */
	private function createKeyValueMap(array $keyValuePairs): array {
		$decryptedPairts = array_map(function ($pair) {
			if ($pair['key'] === 'password') {
				$pair['value'] = $this->decryptValue($pair['value']);
			}
			return $pair;
		}, $keyValuePairs);
		$keys = array_map(function ($pair) {
			return $pair['key'];
		}, $decryptedPairts);
		$values = array_map(function ($pair) {
			return $pair['value'];
		}, $decryptedPairts);

		return array_combine($keys, $values);
	}

	private function encryptValue(string $value): string {
		return $this->crypto->encrypt($value);
	}

	private function decryptValue(string $value): string {
		try {
			return $this->crypto->decrypt($value);
		} catch (\Exception) {
			return $value;
		}
	}

	/**
	 * Check if any mountpoint is configured that overwrite the home folder
	 */
	public function hasHomeFolderOverwriteMount(): bool {
		$builder = $this->connection->getQueryBuilder();
		$query = $builder->select('mount_id')
			->from('external_mounts')
			->where($builder->expr()->eq('mount_point', $builder->createNamedParameter('/')))
			->setMaxResults(1);
		$result = $query->executeQuery();
		return count($result->fetchAllAssociative()) > 0;
	}
}
