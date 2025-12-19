<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview\Db;

use OCP\AppFramework\Db\Entity;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\IMimeTypeLoader;
use OCP\IDBConnection;
use OCP\Snowflake\IGenerator;
use Override;

/**
 * @template-extends QBMapper<Preview>
 */
class PreviewMapper extends QBMapper {

	private const TABLE_NAME = 'previews';
	private const LOCATION_TABLE_NAME = 'preview_locations';
	private const VERSION_TABLE_NAME = 'preview_versions';

	public function __construct(
		IDBConnection $db,
		private readonly IMimeTypeLoader $mimeTypeLoader,
		private readonly IGenerator $snowflake,
	) {
		parent::__construct($db, self::TABLE_NAME, Preview::class);
	}

	protected function mapRowToEntity(array $row): Entity {
		$row['mimetype'] = $this->mimeTypeLoader->getMimetypeById((int)$row['mimetype_id']);
		$row['source_mimetype'] = $this->mimeTypeLoader->getMimetypeById((int)$row['source_mimetype_id']);

		return parent::mapRowToEntity($row);
	}

	#[Override]
	public function insert(Entity $entity): Entity {
		/** @var Preview $preview */
		$preview = $entity;

		$preview->setMimetypeId($this->mimeTypeLoader->getId($preview->getMimeType()));
		$preview->setSourceMimetypeId($this->mimeTypeLoader->getId($preview->getSourceMimeType()));

		if ($preview->getVersion() !== null && $preview->getVersion() !== '') {
			$qb = $this->db->getQueryBuilder();
			$id = $this->snowflake->nextId();
			$qb->insert(self::VERSION_TABLE_NAME)
				->values([
					'id' => $qb->createNamedParameter($id),
					'version' => $qb->createNamedParameter($preview->getVersion(), IQueryBuilder::PARAM_STR),
					'file_id' => $qb->createNamedParameter($preview->getFileId()),
				])
				->executeStatement();
			$entity->setVersionId($id);
		}
		return parent::insert($preview);
	}

	#[Override]
	public function update(Entity $entity): Entity {
		/** @var Preview $preview */
		$preview = $entity;

		$preview->setMimetypeId($this->mimeTypeLoader->getId($preview->getMimeType()));
		$preview->setSourceMimetypeId($this->mimeTypeLoader->getId($preview->getSourceMimeType()));

		return parent::update($preview);
	}

	#[Override]
	public function delete(Entity $entity): Entity {
		/** @var Preview $preview */
		$preview = $entity;
		if ($preview->getVersion() !== null && $preview->getVersion() !== '') {
			$qb = $this->db->getQueryBuilder();
			$qb->delete(self::VERSION_TABLE_NAME)
				->where($qb->expr()->eq('file_id', $qb->createNamedParameter($preview->getFileId())))
				->andWhere($qb->expr()->eq('version', $qb->createNamedParameter($preview->getVersion())))
				->executeStatement();
		}

		return parent::delete($entity);
	}

	/**
	 * @return \Generator<Preview>
	 * @throws Exception
	 */
	public function getAvailablePreviewsForFile(int $fileId): \Generator {
		$selectQb = $this->db->getQueryBuilder();
		$this->joinLocation($selectQb)
			->where($selectQb->expr()->eq('p.file_id', $selectQb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)));
		yield from $this->yieldEntities($selectQb);
	}

	/**
	 * @param int[] $fileIds
	 * @return array<int, Preview[]>
	 * @throws Exception
	 */
	public function getAvailablePreviews(array $fileIds): array {
		$selectQb = $this->db->getQueryBuilder();
		$this->joinLocation($selectQb)
			->where(
				$selectQb->expr()->in('p.file_id', $selectQb->createNamedParameter($fileIds, IQueryBuilder::PARAM_INT_ARRAY)),
			);
		$previews = array_fill_keys($fileIds, []);
		foreach ($this->yieldEntities($selectQb) as $preview) {
			$previews[$preview->getFileId()][] = $preview;
		}
		return $previews;
	}

	/**
	 * @return \Generator<Preview>
	 */
	public function getByFileId(int $fileId): \Generator {
		$selectQb = $this->db->getQueryBuilder();
		$this->joinLocation($selectQb)
			->where($selectQb->expr()->eq('file_id', $selectQb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)));
		yield from $this->yieldEntities($selectQb);
	}

	/**
	 * @param int[] $previewIds
	 */
	public function deleteByIds(array $previewIds): void {
		$qb = $this->db->getQueryBuilder();
		$qb->delete(self::TABLE_NAME)
			->where($qb->expr()->andX(
				$qb->expr()->in('id', $qb->createNamedParameter($previewIds, IQueryBuilder::PARAM_INT_ARRAY))
			))->executeStatement();
	}

	protected function joinLocation(IQueryBuilder $qb): IQueryBuilder {
		return $qb->select('p.*', 'l.bucket_name', 'l.object_store_name', 'v.version')
			->from(self::TABLE_NAME, 'p')
			->leftJoin('p', self::LOCATION_TABLE_NAME, 'l', $qb->expr()->eq(
				'p.location_id', 'l.id'
			))
			->leftJoin('p', self::VERSION_TABLE_NAME, 'v', $qb->expr()->eq(
				'p.version_id', 'v.id'
			));
	}

	/**
	 * Get the location id corresponding to the $bucket and $objectStore. Create one
	 * if not existing yet.
	 *
	 * @throws Exception
	 */
	public function getLocationId(string $bucket, string $objectStore): string {
		$qb = $this->db->getQueryBuilder();
		$result = $qb->select('id')
			->from(self::LOCATION_TABLE_NAME)
			->where($qb->expr()->eq('bucket_name', $qb->createNamedParameter($bucket)))
			->andWhere($qb->expr()->eq('object_store_name', $qb->createNamedParameter($objectStore)))
			->executeQuery();
		$data = $result->fetchOne();
		if ($data) {
			return (string)$data;
		} else {
			try {
				$id = $this->snowflake->nextId();
				$qb->insert(self::LOCATION_TABLE_NAME)
					->values([
						'id' => $qb->createNamedParameter($id),
						'bucket_name' => $qb->createNamedParameter($bucket),
						'object_store_name' => $qb->createNamedParameter($objectStore),
					])->executeStatement();
				return $id;
			} catch (Exception $e) {
				if ($e->getReason() === Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					// Fetch again as there seems to be another entry added meanwhile
					$result = $qb->select('id')
						->from(self::LOCATION_TABLE_NAME)
						->where($qb->expr()->eq('bucket_name', $qb->createNamedParameter($bucket)))
						->andWhere($qb->expr()->eq('object_store_name', $qb->createNamedParameter($objectStore)))
						->executeQuery();
					$data = $result->fetchOne();
					if ($data) {
						return (string)$data;
					}
				}

				throw $e;
			}
		}
	}

	public function deleteAll(): void {
		$delete = $this->db->getQueryBuilder();
		$delete->delete($this->getTableName());
	}

	/**
	 * @return \Generator<Preview>
	 */
	public function getPreviews(int $lastId, int $limit = 1000): \Generator {
		$qb = $this->db->getQueryBuilder();
		$this->joinLocation($qb)
			->where($qb->expr()->gt('p.id', $qb->createNamedParameter($lastId, IQueryBuilder::PARAM_INT)))
			->setMaxResults($limit);
		return $this->yieldEntities($qb);

	}

	/**
	 * @param string[] $mimeTypes
	 * @return \Generator<Preview>
	 */
	public function getPreviewsForMimeTypes(array $mimeTypes): \Generator {
		$qb = $this->db->getQueryBuilder();
		$this->joinLocation($qb)
			->where($qb->expr()->orX(
				...array_map(function (string $mimeType) use ($qb): string {
					return $qb->expr()->eq('source_mimetype_id', $qb->createNamedParameter($this->mimeTypeLoader->getId($mimeType), IQueryBuilder::PARAM_INT));
				}, $mimeTypes)
			));
		return $this->yieldEntities($qb);
	}
}
