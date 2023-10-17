<?php

declare(strict_types=1);

namespace OCP\FilesMetadata;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Node;
use OCP\FilesMetadata\Model\IFilesMetadata;
use OCP\FilesMetadata\Model\IMetadataQuery;

interface IFilesMetadataManager {
	public function refreshMetadata(
		Node $node,
		bool $asBackgroundJob = false,
		bool $fromScratch = false
	): IFilesMetadata;

	public function getMetadata(int $fileId): IFilesMetadata;

	public function saveMetadata(IFilesMetadata $filesMetadata): void;

	public function deleteMetadata(int $fileId): void;

	public function getMetadataQuery(
		IQueryBuilder $qb,
		string $fileTableAlias,
		string $fileIdField
	): IMetadataQuery;
}
