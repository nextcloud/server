<?php

declare(strict_types=1);

namespace OC\FilesMetadata\Service;

use OC\FilesMetadata\Model\MetadataValueWrapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\Exceptions\FilesMetadataTypeException;
use OCP\FilesMetadata\Model\IFilesMetadata;
use OCP\IDBConnection;
use Psr\Log\LoggerInterface;

class IndexRequestService {
	public const TABLE_METADATA_INDEX = 'files_metadata_index';

	public function __construct(
		private IDBConnection $dbConnection,
		private LoggerInterface $logger
	) {
	}

	/**
	 * @param IFilesMetadata $filesMetadata
	 * @param string $key
	 *
	 * @return void
	 * @throws Exception
	 */
	public function updateIndex(IFilesMetadata $filesMetadata, string $key): void {
		$fileId = $filesMetadata->getFileId();

		/**
		 * might look harsh, but a lot simpler than comparing current indexed data, as we can expect
		 * conflict with a change of types.
		 * We assume that each time one random metadata were modified we can drop all index for this
		 * key and recreate them
		 */
		$this->dropIndex($fileId, $key);

		try {
			match ($filesMetadata->getType($key)) {
				MetadataValueWrapper::TYPE_STRING
				=> $this->insertIndexString($fileId, $key, $filesMetadata->get($key)),
				MetadataValueWrapper::TYPE_INT
				=> $this->insertIndexInt($fileId, $key, $filesMetadata->getInt($key)),
				MetadataValueWrapper::TYPE_STRING_LIST
				=> $this->insertIndexStringList($fileId, $key, $filesMetadata->getStringList($key)),
				MetadataValueWrapper::TYPE_INT_LIST
				=> $this->insertIndexIntList($fileId, $key, $filesMetadata->getIntList($key))
			};
		} catch (FilesMetadataNotFoundException|FilesMetadataTypeException $e) {
			$this->logger->warning('...');
		}
	}


	private function insertIndexString(int $fileId, string $key, string $value): void {
		try {
			$qb = $this->dbConnection->getQueryBuilder();
			$qb->insert(self::TABLE_METADATA_INDEX)
			   ->setValue('meta_key', $qb->createNamedParameter($key))
			   ->setValue('meta_value', $qb->createNamedParameter($value))
			   ->setValue('file_id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT));
			$qb->executeStatement();
		} catch (Exception $e) {
			$this->logger->warning('...');
		}
	}

	public function insertIndexInt(int $fileId, string $key, int $value): void {
		try {
			$qb = $this->dbConnection->getQueryBuilder();
			$qb->insert(self::TABLE_METADATA_INDEX)
			   ->setValue('meta_key', $qb->createNamedParameter($key))
			   ->setValue('meta_value_int', $qb->createNamedParameter($value, IQueryBuilder::PARAM_INT))
			   ->setValue('file_id', $qb->createNamedParameter($fileId, IQueryBuilder::PARAM_INT));
			$qb->executeStatement();
		} catch (Exception $e) {
			$this->logger->warning('...');
		}
	}

	/**
	 * @param int $fileId
	 * @param string $key
	 * @param string[] $values
	 *
	 * @return void
	 */
	public function insertIndexStringList(int $fileId, string $key, array $values): void {
		foreach ($values as $value) {
			$this->insertIndexString($fileId, $key, $value);
		}
	}

	/**
	 * @param int $fileId
	 * @param string $key
	 * @param int[] $values
	 *
	 * @return void
	 */
	public function insertIndexIntList(int $fileId, string $key, array $values): void {
		foreach ($values as $value) {
			$this->insertIndexInt($fileId, $key, $value);
		}
	}

	/**
	 * @param int $fileId
	 * @param string $key
	 *
	 * @return void
	 * @throws Exception
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
