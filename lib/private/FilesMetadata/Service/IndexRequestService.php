<?php

declare(strict_types=1);
/**
 * @copyright 2023 Maxence Lange <maxence@artificial-owl.com>
 *
 * @author Maxence Lange <maxence@artificial-owl.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\FilesMetadata\Service;

use OCP\DB\Exception as DbException;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\Exceptions\FilesMetadataTypeException;
use OCP\FilesMetadata\Model\IFilesMetadata;
use OCP\FilesMetadata\Model\IMetadataValueWrapper;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

/**
 * manage sql request to the metadata_index table
 */
class IndexRequestService {
	public const TABLE_METADATA_INDEX = 'files_metadata_index';

	public function __construct(
		private IDBConnection $dbConnection,
		private LoggerInterface $logger
	) {
	}

	/**
	 * update the index for a specific metadata key
	 *
	 * @param IFilesMetadata $filesMetadata metadata
	 * @param string $key metadata key to update
	 *
	 * @throws DbException
	 */
	public function updateIndex(IFilesMetadata $filesMetadata, string $key): void {
		$fileId = $filesMetadata->getFileId();
		try {
			$metadataType = $filesMetadata->getType($key);
		} catch (FilesMetadataNotFoundException $e) {
			return;
		}

		/**
		 * might look harsh, but a lot simpler than comparing current indexed data, as we can expect
		 * conflict with a change of types.
		 * We assume that each time one random metadata were modified we can drop all index for this
		 * key and recreate them.
		 * To make it slightly cleaner, we'll use transaction
		 */
		$this->dbConnection->beginTransaction();
		try {
			$this->dropIndex($fileId, $key);
			match ($metadataType) {
				IMetadataValueWrapper::TYPE_STRING => $this->insertIndexString($fileId, $key, $filesMetadata->getString($key)),
				IMetadataValueWrapper::TYPE_INT => $this->insertIndexInt($fileId, $key, $filesMetadata->getInt($key)),
				IMetadataValueWrapper::TYPE_BOOL => $this->insertIndexBool($fileId, $key, $filesMetadata->getBool($key)),
				IMetadataValueWrapper::TYPE_STRING_LIST => $this->insertIndexStringList($fileId, $key, $filesMetadata->getStringList($key)),
				IMetadataValueWrapper::TYPE_INT_LIST => $this->insertIndexIntList($fileId, $key, $filesMetadata->getIntList($key))
			};
		} catch (FilesMetadataNotFoundException|FilesMetadataTypeException|DbException $e) {
			$this->dbConnection->rollBack();
			$this->logger->warning('issue while updateIndex', ['exception' => $e, 'fileId' => $fileId, 'key' => $key]);
		}

		$this->dbConnection->commit();
	}

	/**
	 * insert a new entry in the metadata_index table for a string value
	 *
	 * @param int $fileId file id
	 * @param string $key metadata key
	 * @param string $value metadata value
	 *
	 * @throws DbException
	 */
	private function insertIndexString(int $fileId, string $key, string $value): void {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert(self::TABLE_METADATA_INDEX)
		   ->setValue('meta_key', $qb->createNamedParameter($key))
		   ->setValue('meta_value_string', $qb->createNamedParameter($value))
		   ->setValue('file_id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT));
		$qb->executeStatement();
	}

	/**
	 * insert a new entry in the metadata_index table for an int value
	 *
	 * @param int $fileId file id
	 * @param string $key metadata key
	 * @param int $value metadata value
	 *
	 * @throws DbException
	 */
	public function insertIndexInt(int $fileId, string $key, int $value): void {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert(self::TABLE_METADATA_INDEX)
		   ->setValue('meta_key', $qb->createNamedParameter($key))
		   ->setValue('meta_value_int', $qb->createNamedParameter($value, IQueryBuilder::PARAM_INT))
		   ->setValue('file_id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT));
		$qb->executeStatement();
	}

	/**
	 * insert a new entry in the metadata_index table for a bool value
	 *
	 * @param int $fileId file id
	 * @param string $key metadata key
	 * @param bool $value metadata value
	 *
	 * @throws DbException
	 */
	public function insertIndexBool(int $fileId, string $key, bool $value): void {
		$qb = $this->dbConnection->getQueryBuilder();
		$qb->insert(self::TABLE_METADATA_INDEX)
		   ->setValue('meta_key', $qb->createNamedParameter($key))
		   ->setValue('meta_value_int', $qb->createNamedParameter(($value) ? '1' : '0', IQueryBuilder::PARAM_INT))
		   ->setValue('file_id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT));
		$qb->executeStatement();
	}

	/**
	 * insert entries in the metadata_index table for list of string
	 *
	 * @param int $fileId file id
	 * @param string $key metadata key
	 * @param string[] $values metadata values
	 *
	 * @throws DbException
	 */
	public function insertIndexStringList(int $fileId, string $key, array $values): void {
		foreach ($values as $value) {
			$this->insertIndexString($fileId, $key, $value);
		}
	}

	/**
	 * insert entries in the metadata_index table for list of int
	 *
	 * @param int $fileId file id
	 * @param string $key metadata key
	 * @param int[] $values metadata values
	 *
	 * @throws DbException
	 */
	public function insertIndexIntList(int $fileId, string $key, array $values): void {
		foreach ($values as $value) {
			$this->insertIndexInt($fileId, $key, $value);
		}
	}

	/**
	 * drop indexes related to a file id
	 * if a key is specified, only drop entries related to it
	 *
	 * @param int $fileId file id
	 * @param string $key metadata key
	 *
	 * @throws DbException
	 */
	public function dropIndex(int $fileId, string $key = ''): void {
		$qb = $this->dbConnection->getQueryBuilder();
		$expr = $qb->expr();
		$qb->delete(self::TABLE_METADATA_INDEX)
		   ->where($expr->eq('file_id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT)));

		if ($key !== '') {
			$qb->andWhere($expr->eq('meta_key', $qb->createNamedParameter($key)));
		}

		$qb->executeStatement();
	}
}
