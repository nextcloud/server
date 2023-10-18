<?php

declare(strict_types=1);

namespace OC\FilesMetadata\Model;

use OC\FilesMetadata\Service\IndexRequestService;
use OC\FilesMetadata\Service\MetadataRequestService;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\FilesMetadata\Model\IFilesMetadata;
use OCP\FilesMetadata\Model\IMetadataQuery;

class MetadataQuery implements IMetadataQuery {

	public function __construct(
		private IQueryBuilder $queryBuilder,
		private string $fileTableAlias = 'fc',
		private string $fileIdField = 'fileid',
		private string $alias = 'meta',
		private string $aliasIndex = 'meta_index'
	) {
	}


	/*
	 * leftJoinIndex will left join the metadata_index table to the current query,
	 * link is done using the fileId (fileTableAlias, fileIdField provided to the constructor of the class)
	 *
	 * if $metadataKey is set, link also include an eq comparison on the provided metadata key
	 */
	public function leftJoinIndex(string $metadataKey = ''): void {
		$expr = $this->queryBuilder->expr();
		$andX = $expr->andX($expr->eq($this->aliasIndex . '.file_id', $this->fileTableAlias . '.' . $this->fileIdField));

		if ('' !== $metadataKey) {
			$andX->add($expr->eq($this->getMetadataKeyField(), $this->queryBuilder->createNamedParameter($metadataKey)));
		}

		$this->queryBuilder->leftJoin(
			$this->fileTableAlias,
			IndexRequestService::TABLE_METADATA_INDEX,
			$this->aliasIndex,
			$andX
		);
	}


	/**
	 * left join the metadata table to include a select of the stored json to the query
	 */
	public function retrieveMetadata(): void {
		$this->queryBuilder->selectAlias($this->alias . '.json', 'meta_json');
		$this->queryBuilder->leftJoin(
			$this->fileTableAlias, MetadataRequestService::TABLE_METADATA, $this->alias,
			$this->queryBuilder->expr()->eq($this->fileTableAlias . '.' . $this->fileIdField, $this->alias . '.file_id')
		);
	}

	public function enforceMetadataKey(string $metadataKey): void {
		$expr = $this->queryBuilder->expr();
		$this->queryBuilder->andWhere(
			$expr->eq(
				$this->getMetadataKeyField(),
				$this->queryBuilder->createNamedParameter($metadataKey)
			)
		);
	}

	public function enforceMetadataValue(string $value): void {
		$expr = $this->queryBuilder->expr();
		$this->queryBuilder->andWhere(
			$expr->eq(
				$this->getMetadataKeyField(),
				$this->queryBuilder->createNamedParameter($value)
			)
		);
	}

	public function enforceMetadataValueInt(int $value): void {
		$expr = $this->queryBuilder->expr();
		$this->queryBuilder->andWhere(
			$expr->eq(
				$this->getMetadataValueIntField(),
				$this->queryBuilder->createNamedParameter($value, IQueryBuilder::PARAM_INT)
			)
		);
	}

	public function getMetadataKeyField(): string {
		return $this->aliasIndex . '.meta_key';
	}

	public function getMetadataValueField(): string {
		return $this->aliasIndex . '.meta_value';
	}

	public function getMetadataValueIntField(): string {
		return $this->aliasIndex . '.meta_value_int';
	}

	public function extractMetadata(array $data): IFilesMetadata {
		$fileId = (array_key_exists($this->fileIdField, $data)) ? $data[$this->fileIdField] : 0;
		$metadata = new FilesMetadata($fileId);
		$metadata->importFromDatabase($data, $this->alias . '_');

		return $metadata;
	}
}
