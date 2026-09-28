<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\FilesMetadata\Service;

use OC\FilesMetadata\Model\FilesMetadata;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\Model\IFilesMetadata;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * manage sql request to the metadata table
 */
class MetadataRequestService {
	public const TABLE_METADATA = 'files_metadata';

	public function __construct(
		private IDBConnection $dbConnection,
		private LoggerInterface $logger,
	) {
	}

	private function getStorageId(IFilesMetadata $filesMetadata): int {
		if ($filesMetadata instanceof FilesMetadata) {
			$storage = $filesMetadata->getStorageId();
			if ($storage) {
				return $storage;
			}
		}
		// all code paths that lead to saving metadata *should* have the storage id set
		// this fallback is there just in case
		$query = $this->dbConnection->getQueryBuilder();
		$query->select('storage')
			->from('filecache')
			->where($query->expr()->eq('fileid', $query->createNamedParameter($filesMetadata->getFileId(), IQueryBuilder::PARAM_INT)));
		$storageId = $query->executeQuery()->fetchColumn();

		if ($filesMetadata instanceof FilesMetadata) {
			$filesMetadata->setStorageId($storageId);
		}
		return $storageId;
	}

	/**
	 * store metadata into database
	 *
	 * @param IFilesMetadata $filesMetadata
	 *
	 * @throws Exception
	 */
	public function store(IFilesMetadata $filesMetadata): void {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert(self::TABLE_METADATA)
			->hintShardKey('storage', $this->getStorageId($filesMetadata))
			->setValue('file_id', $qb->createNamedParameter($filesMetadata->getFileId(), IQueryBuilder::PARAM_INT))
			->setValue('json', $qb->createNamedParameter(json_encode($filesMetadata->jsonSerialize())))
			->setValue('sync_token', $qb->createNamedParameter($this->generateSyncToken()))
			->setValue('last_update', (string)$qb->createFunction('NOW()'));
		$qb->executeStatement();
	}

	/**
	 * returns metadata for a file id
	 *
	 * @param int $fileId file id
	 *
	 * @return IFilesMetadata
	 * @throws FilesMetadataNotFoundException if no metadata are found in database
	 */
	public function getMetadataFromFileId(int $fileId): IFilesMetadata {
		try {
			$qb = $this->dbConnection->getQueryBuilder();
			$qb->select('json', 'sync_token')->from(self::TABLE_METADATA);
			$qb->where($qb->expr()->eq('file_id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)));
			$result = $qb->executeQuery();
			$data = $result->fetch();
			$result->closeCursor();
		} catch (Exception $e) {
			$this->logger->warning('exception while getMetadataFromDatabase()', ['exception' => $e, 'fileId' => $fileId]);
			throw new FilesMetadataNotFoundException();
		}

		if ($data === false) {
			throw new FilesMetadataNotFoundException();
		}

		$metadata = new FilesMetadata($fileId);
		$metadata->importFromDatabase($data);

		return $metadata;
	}

	/**
	 * returns metadata for multiple file ids
	 *
	 * @param array $fileIds file ids
	 *
	 * @return array File ID is the array key, files without metadata are not returned in the array
	 * @psalm-return array<int, IFilesMetadata>
	 */
	public function getMetadataFromFileIds(array $fileIds): array {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->select('file_id', 'json', 'sync_token')->from(self::TABLE_METADATA);
		$qb->where($qb->expr()->in('file_id', $qb->createNamedParameter($fileIds, IQueryBuilder::PARAM_INT_ARRAY)));

		$list = [];
		$result = $qb->executeQuery();
		while ($data = $result->fetch()) {
			$fileId = (int)$data['file_id'];
			$metadata = new FilesMetadata($fileId);
			try {
				$metadata->importFromDatabase($data);
			} catch (FilesMetadataNotFoundException) {
				continue;
			}
			$list[$fileId] = $metadata;
		}
		$result->closeCursor();

		return $list;
	}

	/**
	 * drop metadata related to a file id
	 *
	 * @param int $fileId file id
	 *
	 * @return void
	 * @throws Exception
	 */
	public function dropMetadata(int $fileId): void {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->delete(self::TABLE_METADATA)
			->where($qb->expr()->eq('file_id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)));
		$qb->executeStatement();
	}

	/**
	 * update metadata in the database
	 *
	 * @param IFilesMetadata $filesMetadata metadata
	 *
	 * @return int number of affected rows
	 * @throws Exception
	 */
	public function updateMetadata(IFilesMetadata $filesMetadata): int {
		$qb = $this->dbConnection->getQueryBuilder();
		$expr = $qb->expr();

		$qb->update(self::TABLE_METADATA)
			->hintShardKey('files_metadata', $this->getStorageId($filesMetadata))
			->set('json', $qb->createNamedParameter(json_encode($filesMetadata->jsonSerialize())))
			->set('sync_token', $qb->createNamedParameter($this->generateSyncToken()))
			->set('last_update', $qb->createFunction('NOW()'))
			->where(
				$expr->andX(
					$expr->eq('file_id', $qb->createNamedParameter($filesMetadata->getFileId(), IQueryBuilder::PARAM_INT)),
					$expr->eq('sync_token', $qb->createNamedParameter($filesMetadata->getSyncToken()))
				)
			);

		return $qb->executeStatement();
	}

	/**
	 * generate a random token
	 * @return string
	 */
	private function generateSyncToken(): string {
		$chars = 'qwertyuiopasdfghjklzxcvbnmQWERTYUIOPASDFGHJKLZXCVBNM1234567890';

		$str = '';
		$max = strlen($chars);
		for ($i = 0; $i < 7; $i++) {
			try {
				$str .= $chars[random_int(0, $max - 2)];
			} catch (\Exception $e) {
				$this->logger->warning('exception during generateSyncToken', ['exception' => $e]);
			}
		}

		return $str;
	}
}
