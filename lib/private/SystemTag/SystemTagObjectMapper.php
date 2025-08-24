<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\SystemTag;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IDBConnection;
use OCP\SystemTag\ISystemTag;
use OCP\SystemTag\ISystemTagManager;
use OCP\SystemTag\ISystemTagObjectMapper;
use OCP\SystemTag\MapperEvent;
use OCP\SystemTag\TagNotFoundException;

class SystemTagObjectMapper implements ISystemTagObjectMapper {
	public const RELATION_TABLE = 'systemtag_object_mapping';

	public function __construct(
		protected IDBConnection $connection,
		protected ISystemTagManager $tagManager,
		protected IEventDispatcher $dispatcher,
	) {
	}

	/**
	 * {@inheritdoc}
	 */
	public function getTagIdsForObjects($objIds, string $objectType): array {
		if (!\is_array($objIds)) {
			$objIds = [$objIds];
		} elseif (empty($objIds)) {
			return [];
		}

		$query = $this->connection->getQueryBuilder();
		$query->select(['systemtagid', 'objectid'])
			->from(self::RELATION_TABLE)
			->where($query->expr()->in('objectid', $query->createParameter('objectids')))
			->andWhere($query->expr()->eq('objecttype', $query->createParameter('objecttype')))
			->setParameter('objecttype', $objectType)
			->addOrderBy('objectid', 'ASC')
			->addOrderBy('systemtagid', 'ASC');
		$chunks = array_chunk($objIds, 900, false);
		$mapping = [];
		foreach ($objIds as $objId) {
			$mapping[$objId] = [];
		}
		foreach ($chunks as $chunk) {
			$query->setParameter('objectids', $chunk, IQueryBuilder::PARAM_STR_ARRAY);
			$result = $query->executeQuery();
			while ($row = $result->fetch()) {
				$objectId = $row['objectid'];
				$mapping[$objectId][] = $row['systemtagid'];
			}

			$result->closeCursor();
		}

		return $mapping;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getObjectIdsForTags($tagIds, string $objectType, int $limit = 0, string $offset = ''): array {
		if (!\is_array($tagIds)) {
			$tagIds = [$tagIds];
		}

		$this->assertTagsExist($tagIds);

		$query = $this->connection->getQueryBuilder();
		$query->selectDistinct('objectid')
			->from(self::RELATION_TABLE)
			->where($query->expr()->in('systemtagid', $query->createNamedParameter($tagIds, IQueryBuilder::PARAM_INT_ARRAY)))
			->andWhere($query->expr()->eq('objecttype', $query->createNamedParameter($objectType)));

		if ($limit) {
			if (\count($tagIds) !== 1) {
				throw new \InvalidArgumentException('Limit is only allowed with a single tag');
			}

			$query->setMaxResults($limit)
				->orderBy('objectid', 'ASC');

			if ($offset !== '') {
				$query->andWhere($query->expr()->gt('objectid', $query->createNamedParameter($offset)));
			}
		}

		$objectIds = [];

		$result = $query->executeQuery();
		while ($row = $result->fetch()) {
			$objectIds[] = $row['objectid'];
		}
		$result->closeCursor();

		return $objectIds;
	}

	/**
	 * {@inheritdoc}
	 */
	public function assignTags(string $objId, string $objectType, $tagIds): void {
		if (!\is_array($tagIds)) {
			$tagIds = [$tagIds];
		}

		$this->assertTagsExist($tagIds);
		$this->connection->beginTransaction();

		$query = $this->connection->getQueryBuilder();
		$query->select('systemtagid')
			->from(self::RELATION_TABLE)
			->where($query->expr()->in('systemtagid', $query->createNamedParameter($tagIds, IQueryBuilder::PARAM_INT_ARRAY)))
			->andWhere($query->expr()->eq('objecttype', $query->createNamedParameter($objectType)))
			->andWhere($query->expr()->eq('objectid', $query->createNamedParameter($objId)));
		$result = $query->executeQuery();
		$rows = $result->fetchAll();
		$existingTags = [];
		foreach ($rows as $row) {
			$existingTags[] = $row['systemtagid'];
		}
		//filter only tags that do not exist in db
		$tagIds = array_diff($tagIds, $existingTags);
		if (empty($tagIds)) {
			// no tags to insert so return here
			$this->connection->commit();
			return;
		}

		$query = $this->connection->getQueryBuilder();
		$query->insert(self::RELATION_TABLE)
			->values([
				'objectid' => $query->createNamedParameter($objId),
				'objecttype' => $query->createNamedParameter($objectType),
				'systemtagid' => $query->createParameter('tagid'),
			]);

		$tagsAssigned = [];
		foreach ($tagIds as $tagId) {
			try {
				$query->setParameter('tagid', $tagId);
				$query->execute();
				$tagsAssigned[] = $tagId;
			} catch (UniqueConstraintViolationException $e) {
				// ignore existing relations
			}
		}

		$this->updateEtagForTags($tagIds);

		$this->connection->commit();
		if (empty($tagsAssigned)) {
			return;
		}

		$this->dispatcher->dispatch(MapperEvent::EVENT_ASSIGN, new MapperEvent(
			MapperEvent::EVENT_ASSIGN,
			$objectType,
			$objId,
			$tagsAssigned
		));
	}

	/**
	 * {@inheritdoc}
	 */
	public function unassignTags(string $objId, string $objectType, $tagIds): void {
		if (!\is_array($tagIds)) {
			$tagIds = [$tagIds];
		}

		$this->assertTagsExist($tagIds);

		$query = $this->connection->getQueryBuilder();
		$query->delete(self::RELATION_TABLE)
			->where($query->expr()->eq('objectid', $query->createParameter('objectid')))
			->andWhere($query->expr()->eq('objecttype', $query->createParameter('objecttype')))
			->andWhere($query->expr()->in('systemtagid', $query->createParameter('tagids')))
			->setParameter('objectid', $objId)
			->setParameter('objecttype', $objectType)
			->setParameter('tagids', $tagIds, IQueryBuilder::PARAM_INT_ARRAY)
			->executeStatement();

		$this->updateEtagForTags($tagIds);

		$this->dispatcher->dispatch(MapperEvent::EVENT_UNASSIGN, new MapperEvent(
			MapperEvent::EVENT_UNASSIGN,
			$objectType,
			$objId,
			$tagIds
		));
	}

	/**
	 * Update the etag for the given tags.
	 *
	 * @param string[] $tagIds
	 */
	private function updateEtagForTags(array $tagIds): void {
		// Update etag after assigning tags
		$md5 = md5(json_encode(time()));
		$query = $this->connection->getQueryBuilder();
		$query->update('systemtag')
			->set('etag', $query->createNamedParameter($md5))
			->where($query->expr()->in('id', $query->createNamedParameter($tagIds, IQueryBuilder::PARAM_INT_ARRAY)));
		$query->execute();
	}

	/**
	 * {@inheritdoc}
	 */
	public function haveTag($objIds, string $objectType, string $tagId, bool $all = true): bool {
		$this->assertTagsExist([$tagId]);

		if (!\is_array($objIds)) {
			$objIds = [$objIds];
		}

		$query = $this->connection->getQueryBuilder();

		if (!$all) {
			// If we only need one entry, we make the query lighter, by not
			// counting the elements
			$query->select('*')
				->setMaxResults(1);
		} else {
			$query->select($query->func()->count($query->expr()->literal(1)));
		}

		$query->from(self::RELATION_TABLE)
			->where($query->expr()->in('objectid', $query->createParameter('objectids')))
			->andWhere($query->expr()->eq('objecttype', $query->createParameter('objecttype')))
			->andWhere($query->expr()->eq('systemtagid', $query->createParameter('tagid')))
			->setParameter('objectids', $objIds, IQueryBuilder::PARAM_STR_ARRAY)
			->setParameter('tagid', $tagId)
			->setParameter('objecttype', $objectType);

		$result = $query->executeQuery();
		$row = $result->fetch(\PDO::FETCH_NUM);
		$result->closeCursor();

		if ($all) {
			return ((int)$row[0] === \count($objIds));
		}

		return (bool)$row;
	}

	/**
	 * Asserts that all the given tag ids exist.
	 *
	 * @param string[] $tagIds tag ids to check
	 *
	 * @throws \OCP\SystemTag\TagNotFoundException if at least one tag did not exist
	 */
	private function assertTagsExist(array $tagIds): void {
		$tags = $this->tagManager->getTagsByIds($tagIds);
		if (\count($tags) !== \count($tagIds)) {
			// at least one tag missing, bail out
			$foundTagIds = array_map(
				function (ISystemTag $tag) {
					return $tag->getId();
				},
				$tags
			);
			$missingTagIds = array_diff($tagIds, $foundTagIds);
			throw new TagNotFoundException(
				'Tags not found', 0, null, $missingTagIds
			);
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function setObjectIdsForTag(string $tagId, string $objectType, array $objectIds): void {
		$currentObjectIds = $this->getObjectIdsForTags($tagId, $objectType);
		$removedObjectIds = array_diff($currentObjectIds, $objectIds);
		$addedObjectIds = array_diff($objectIds, $currentObjectIds);

		$this->connection->beginTransaction();
		$query = $this->connection->getQueryBuilder();
		$query->delete(self::RELATION_TABLE)
			->where($query->expr()->eq('systemtagid', $query->createNamedParameter($tagId, IQueryBuilder::PARAM_INT)))
			->andWhere($query->expr()->eq('objecttype', $query->createNamedParameter($objectType)))
			->executeStatement();
		$this->connection->commit();

		foreach ($removedObjectIds as $objectId) {
			$this->dispatcher->dispatch(MapperEvent::EVENT_UNASSIGN, new MapperEvent(
				MapperEvent::EVENT_UNASSIGN,
				$objectType,
				(string)$objectId,
				[(int)$tagId]
			));
		}

		if (empty($objectIds)) {
			return;
		}

		$this->connection->beginTransaction();
		$query = $this->connection->getQueryBuilder();
		$query->insert(self::RELATION_TABLE)
			->values([
				'systemtagid' => $query->createNamedParameter($tagId, IQueryBuilder::PARAM_INT),
				'objecttype' => $query->createNamedParameter($objectType),
				'objectid' => $query->createParameter('objectid'),
			]);

		foreach (array_unique($objectIds) as $objectId) {
			$query->setParameter('objectid', (string)$objectId);
			$query->executeStatement();
		}

		$this->updateEtagForTags([$tagId]);
		$this->connection->commit();

		// Dispatch assign events for new object ids
		foreach ($addedObjectIds as $objectId) {
			$this->dispatcher->dispatch(MapperEvent::EVENT_ASSIGN, new MapperEvent(
				MapperEvent::EVENT_ASSIGN,
				$objectType,
				(string)$objectId,
				[(int)$tagId]
			));
		}

		// Dispatch unassign events for removed object ids
		foreach ($removedObjectIds as $objectId) {
			$this->dispatcher->dispatch(MapperEvent::EVENT_UNASSIGN, new MapperEvent(
				MapperEvent::EVENT_UNASSIGN,
				$objectType,
				(string)$objectId,
				[(int)$tagId]
			));
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function getAvailableObjectTypes(): array {
		$query = $this->connection->getQueryBuilder();
		$query->selectDistinct('objecttype')
			->from(self::RELATION_TABLE);

		$result = $query->executeQuery();
		$objectTypes = [];
		while ($row = $result->fetch()) {
			$objectTypes[] = $row['objecttype'];
		}
		$result->closeCursor();

		return $objectTypes;
	}
}
