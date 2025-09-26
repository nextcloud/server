<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview\Db;

use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Db\QBMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\IPreview;

/**
 * @template-extends QBMapper<Preview>
 */
class PreviewMapper extends QBMapper {

	private const TABLE_NAME = 'previews';
	private const LOCATION_TABLE_NAME = 'preview_locations';

	public function __construct(IDBConnection $db) {
		parent::__construct($db, self::TABLE_NAME, Preview::class);
	}

	/**
	 * @return \Generator<Preview>
	 * @throws Exception
	 */
	public function getAvailablePreviewForFile(int $fileId): \Generator {
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

	public function getPreview(int $fileId, int $width, int $height, string $mode, int $mimetype = IPreview::MIMETYPE_JPEG): ?Preview {
		$selectQb = $this->db->getQueryBuilder();
		$this->joinLocation($selectQb)
			->where(
				$selectQb->expr()->eq('file_id', $selectQb->createNamedParameter($fileId)),
				$selectQb->expr()->eq('width', $selectQb->createNamedParameter($width)),
				$selectQb->expr()->eq('height', $selectQb->createNamedParameter($height)),
				$selectQb->expr()->eq('mode', $selectQb->createNamedParameter($mode)),
				$selectQb->expr()->eq('mimetype', $selectQb->createNamedParameter($mimetype)),
			);
		try {
			return $this->findEntity($selectQb);
		} catch (DoesNotExistException) {
			return null;
		}
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
		return $qb->select('p.*', 'l.bucket_name', 'l.object_store_name')
			->from(self::TABLE_NAME, 'p')
			->leftJoin('p', 'preview_locations', 'l', $qb->expr()->eq(
				'p.location_id', 'l.id'
			));
	}

	public function getLocationId(string $bucket, string $objectStore): int {
		$qb = $this->db->getQueryBuilder();
		$result = $qb->select('id')
			->from(self::LOCATION_TABLE_NAME)
			->where($qb->expr()->eq('bucket_name', $qb->createNamedParameter($bucket)))
			->andWhere($qb->expr()->eq('object_store_name', $qb->createNamedParameter($objectStore)))
			->executeQuery();
		$data = $result->fetchOne();
		if ($data) {
			return $data;
		} else {
			$qb->insert(self::LOCATION_TABLE_NAME)
				->values([
					'bucket_name' => $qb->createNamedParameter($bucket),
					'object_store_name' => $qb->createNamedParameter($objectStore),
				])->executeStatement();
			return $qb->getLastInsertId();
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
	 * @param int[] $mimeTypes
	 * @return \Generator<Preview>
	 */
	public function getPreviewsForMimeTypes(array $mimeTypes): \Generator {
		$qb = $this->db->getQueryBuilder();
		$this->joinLocation($qb)
			->where($qb->expr()->orX(
				...array_map(function (int $mimeType) use ($qb) {
					return $qb->expr()->eq('source_mimetype', $qb->createNamedParameter($mimeType, IQueryBuilder::PARAM_INT));
				}, $mimeTypes)
			));
		return $this->yieldEntities($qb);
	}
}
