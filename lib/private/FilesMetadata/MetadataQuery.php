<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\FilesMetadata;

use OC\FilesMetadata\Model\FilesMetadata;
use OC\FilesMetadata\Service\IndexRequestService;
use OC\FilesMetadata\Service\MetadataRequestService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\Exceptions\FilesMetadataTypeException;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\FilesMetadata\IMetadataQuery;
use OCP\FilesMetadata\Model\IFilesMetadata;
use OCP\FilesMetadata\Model\IMetadataValueWrapper;
use Psr\Log\LoggerInterface;

/**
 * @inheritDoc
 * @since 28.0.0
 */
class MetadataQuery implements IMetadataQuery {
	private array $knownJoinedIndex = [];
	public function __construct(
		private IQueryBuilder $queryBuilder,
		private IFilesMetadata|IFilesMetadataManager $manager,
		private string $fileTableAlias = 'fc',
		private string $fileIdField = 'fileid',
		private string $alias = 'meta',
		private string $aliasIndexPrefix = 'meta_index',
	) {
		if ($manager instanceof IFilesMetadata) {
			/**
			 * Since 29, because knownMetadata is stored in lazy appconfig, it seems smarter
			 * to not call getKnownMetadata() at the load of this class as it is only needed
			 * in {@see getMetadataValueField}.
			 *
			 * FIXME: remove support for IFilesMetadata
			 */
			$logger = \OCP\Server::get(LoggerInterface::class);
			$logger->debug('It is deprecated to use IFilesMetadata as second parameter when calling MetadataQuery::__construct()');
		}
	}

	/**
	 * @inheritDoc
	 * @see self::extractMetadata()
	 * @since 28.0.0
	 */
	public function retrieveMetadata(): void {
		$this->queryBuilder->selectAlias($this->alias . '.json', 'meta_json');
		$this->queryBuilder->selectAlias($this->alias . '.sync_token', 'meta_sync_token');
		$this->queryBuilder->leftJoin(
			$this->fileTableAlias, MetadataRequestService::TABLE_METADATA, $this->alias,
			$this->queryBuilder->expr()->eq($this->fileTableAlias . '.' . $this->fileIdField, $this->alias . '.file_id')
		);
	}

	/**
	 * @param array $row result row
	 *
	 * @inheritDoc
	 * @return IFilesMetadata metadata
	 * @see self::retrieveMetadata()
	 * @since 28.0.0
	 */
	public function extractMetadata(array $row): IFilesMetadata {
		$fileId = (array_key_exists($this->fileIdField, $row)) ? $row[$this->fileIdField] : 0;
		$metadata = new FilesMetadata((int)$fileId);
		try {
			$metadata->importFromDatabase($row, $this->alias . '_');
		} catch (FilesMetadataNotFoundException) {
			// can be ignored as files' metadata are optional and might not exist in database
		}

		return $metadata;
	}

	/**
	 * @param string $metadataKey metadata key
	 * @param bool $enforce limit the request only to existing metadata
	 *
	 * @inheritDoc
	 * @since 28.0.0
	 */
	public function joinIndex(string $metadataKey, bool $enforce = false): string {
		if (array_key_exists($metadataKey, $this->knownJoinedIndex)) {
			return $this->knownJoinedIndex[$metadataKey];
		}

		$aliasIndex = $this->aliasIndexPrefix . '_' . count($this->knownJoinedIndex);
		$this->knownJoinedIndex[$metadataKey] = $aliasIndex;

		$expr = $this->queryBuilder->expr();
		$andX = $expr->andX($expr->eq($aliasIndex . '.file_id', $this->fileTableAlias . '.' . $this->fileIdField));
		$andX->add($expr->eq($this->getMetadataKeyField($metadataKey), $this->queryBuilder->createNamedParameter($metadataKey)));

		if ($enforce) {
			$this->queryBuilder->innerJoin(
				$this->fileTableAlias,
				IndexRequestService::TABLE_METADATA_INDEX,
				$aliasIndex,
				$andX
			);
		} else {
			$this->queryBuilder->leftJoin(
				$this->fileTableAlias,
				IndexRequestService::TABLE_METADATA_INDEX,
				$aliasIndex,
				$andX
			);
		}

		return $aliasIndex;
	}

	/**
	 * @throws FilesMetadataNotFoundException
	 */
	private function joinedTableAlias(string $metadataKey): string {
		if (!array_key_exists($metadataKey, $this->knownJoinedIndex)) {
			throw new FilesMetadataNotFoundException('table related to ' . $metadataKey . ' not initiated, you need to use leftJoin() first.');
		}

		return $this->knownJoinedIndex[$metadataKey];
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $metadataKey metadata key
	 *
	 * @return string table field
	 * @throws FilesMetadataNotFoundException
	 * @since 28.0.0
	 */
	public function getMetadataKeyField(string $metadataKey): string {
		return $this->joinedTableAlias($metadataKey) . '.meta_key';
	}

	/**
	 * @inheritDoc
	 *
	 * @param string $metadataKey metadata key
	 *
	 * @return string table field
	 * @throws FilesMetadataNotFoundException if metadataKey is not known
	 * @throws FilesMetadataTypeException is metadataKey is not set as indexed
	 * @since 28.0.0
	 */
	public function getMetadataValueField(string $metadataKey): string {
		if ($this->manager instanceof IFilesMetadataManager) {
			/**
			 * Since 29, because knownMetadata is stored in lazy appconfig, it seems smarter
			 * to not call getKnownMetadata() at the load of this class as it is only needed
			 * in this method.
			 *
			 * FIXME: keep only this line and remove support for previous IFilesMetadata in constructor
			 */
			$knownMetadata = $this->manager->getKnownMetadata();
		} else {
			$knownMetadata = $this->manager;
		}

		return match ($knownMetadata->getType($metadataKey)) {
			IMetadataValueWrapper::TYPE_STRING => $this->joinedTableAlias($metadataKey) . '.meta_value_string',
			IMetadataValueWrapper::TYPE_INT, IMetadataValueWrapper::TYPE_BOOL => $this->joinedTableAlias($metadataKey) . '.meta_value_int',
			default => throw new FilesMetadataTypeException('metadata is not set as indexed'),
		};
	}
}
