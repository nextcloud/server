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

	public function __construct(IDBConnection $db) {
		parent::__construct($db, self::TABLE_NAME, Preview::class);
	}

	/**
	 * @param int[] $fileIds
	 * @return array<int, Preview[]>
	 * @throws Exception
	 */
	public function getAvailablePreviews(array $fileIds): array {
		$selectQb = $this->db->getQueryBuilder();
		$selectQb->select('*')
			->from(self::TABLE_NAME)
			->where(
				$selectQb->expr()->in('file_id', $selectQb->createNamedParameter($fileIds, IQueryBuilder::PARAM_INT_ARRAY)),
			);
		$previews = array_fill_keys($fileIds, []);
		foreach ($this->yieldEntities($selectQb) as $preview) {
			$previews[$preview->getFileId()][] = $preview;
		}
		return $previews;
	}

	public function getPreview(int $fileId, int $width, int $height, string $mode, int $mimetype = IPreview::MIMETYPE_JPEG): ?Preview {
		$selectQb = $this->db->getQueryBuilder();
		$selectQb->select('*')
			->from(self::TABLE_NAME)
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
}
