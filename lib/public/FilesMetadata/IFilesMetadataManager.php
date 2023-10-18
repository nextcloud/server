<?php

declare(strict_types=1);

namespace OCP\FilesMetadata;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\Node;
use OCP\FilesMetadata\Model\IFilesMetadata;
use OCP\FilesMetadata\Model\IMetadataQuery;

interface IFilesMetadataManager {
	public const PROCESS_LIVE = 1;
	public const PROCESS_BACKGROUND = 2;

	public function refreshMetadata(
		Node $node,
		int $process = self::PROCESS_LIVE,
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
