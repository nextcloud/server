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

namespace OC\FilesMetadata;

use OC\FilesMetadata\Model\FilesMetadata;
use OC\FilesMetadata\Service\IndexRequestService;
use OC\FilesMetadata\Service\MetadataRequestService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\Exceptions\FilesMetadataTypeException;
use OCP\FilesMetadata\IMetadataQuery;
use OCP\FilesMetadata\Model\IFilesMetadata;
use OCP\FilesMetadata\Model\IMetadataValueWrapper;

/**
 * @inheritDoc
 * @since 28.0.0
 */
class MetadataQuery implements IMetadataQuery {
	private array $knownJoinedIndex = [];
	public function __construct(
		private IQueryBuilder $queryBuilder,
		private IFilesMetadata $knownMetadata,
		private string $fileTableAlias = 'fc',
		private string $fileIdField = 'fileid',
		private string $alias = 'meta',
		private string $aliasIndexPrefix = 'meta_index'
	) {
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
		return match ($this->knownMetadata->getType($metadataKey)) {
			IMetadataValueWrapper::TYPE_STRING => $this->joinedTableAlias($metadataKey) . '.meta_value_string',
			IMetadataValueWrapper::TYPE_INT, IMetadataValueWrapper::TYPE_BOOL => $this->joinedTableAlias($metadataKey) . '.meta_value_int',
			default => throw new FilesMetadataTypeException('metadata is not set as indexed'),
		};
	}
}
