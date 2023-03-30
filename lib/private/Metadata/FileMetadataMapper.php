<?php

declare(strict_types=1);
/**
 * @copyright Copyright 2022 Carl Schwan <carl@carlschwan.eu>
 * @copyright Copyright 2022 Louis Chmn <louis@chmn.me>
 * @license AGPL-3.0-or-later
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Metadata;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\MultipleObjectsReturnedException;
use OCP\AppFramework\Db\QBMapper;
use OCP\AppFramework\Db\Entity;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

class FileMetadataMapper extends QBMapper {
	public function __construct(IDBConnection $db) {
		parent::__construct($db, 'file_metadata', FileMetadata::class);
	}

	/**
	 * @return FileMetadata[]
	 * @throws Exception
	 */
	public function findForFile(int $fileId): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)));

		return $this->findEntities($qb);
	}

	/**
	 * @throws DoesNotExistException
	 * @throws MultipleObjectsReturnedException
	 * @throws Exception
	 */
	public function findForGroupForFile(int $fileId, string $groupName): FileMetadata {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT_ARRAY)))
			->andWhere($qb->expr()->eq('group_name', $qb->createNamedParameter($groupName, IQueryBuilder::PARAM_STR)));

		return $this->findEntity($qb);
	}

	/**
	 * @return array<int, FileMetadata>
	 * @throws Exception
	 */
	public function findForGroupForFiles(array $fileIds, string $groupName): array {
		$qb = $this->db->getQueryBuilder();
		$qb->select('*')
			->from($this->getTableName())
			->where($qb->expr()->in('id', $qb->createParameter('fileIds')))
			->andWhere($qb->expr()->eq('group_name', $qb->createNamedParameter($groupName, IQueryBuilder::PARAM_STR)));

		$metadata = [];
		foreach (array_chunk($fileIds, 1000) as $fileIdsChunk) {
			$qb->setParameter('fileIds', $fileIdsChunk, IQueryBuilder::PARAM_INT_ARRAY);
			/** @var FileMetadata[] $rawEntities */
			$rawEntities = $this->findEntities($qb);
			foreach ($rawEntities as $entity) {
				$metadata[$entity->getId()] = $entity;
			}
		}

		foreach ($fileIds as $id) {
			if (isset($metadata[$id])) {
				continue;
			}
			$empty = new FileMetadata();
			$empty->setValue('');
			$empty->setGroupName($groupName);
			$empty->setId($id);
			$metadata[$id] = $empty;
		}
		return $metadata;
	}

	public function clear(int $fileId): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete($this->getTableName())
			->where($qb->expr()->eq('id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)));

		$qb->executeStatement();
	}

	/**
	 * Updates an entry in the db from an entity
	 *
	 * @param Entity $entity the entity that should be created
	 * @return Entity the saved entity with the set id
	 * @throws Exception
	 * @throws \InvalidArgumentException if entity has no id
	 */
	public function update(Entity $entity): Entity {
		if (!($entity instanceof FileMetadata)) {
			throw new \Exception("Entity should be a FileMetadata entity");
		}

		// entity needs an id
		$id = $entity->getId();
		if ($id === null) {
			throw new \InvalidArgumentException('Entity which should be updated has no id');
		}

		// entity needs an group_name
		$groupName = $entity->getGroupName();
		if ($groupName === null) {
			throw new \InvalidArgumentException('Entity which should be updated has no group_name');
		}

		$idType = $this->getParameterTypeForProperty($entity, 'id');
		$groupNameType = $this->getParameterTypeForProperty($entity, 'groupName');
		$value = $entity->getValue();
		$valueType = $this->getParameterTypeForProperty($entity, 'value');

		$qb = $this->db->getQueryBuilder();

		$qb->update($this->tableName)
			->set('value', $qb->createNamedParameter($value, $valueType))
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id, $idType)))
			->andWhere($qb->expr()->eq('group_name', $qb->createNamedParameter($groupName, $groupNameType)))
			->executeStatement();

		return $entity;
	}
}
