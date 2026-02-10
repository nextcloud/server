<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview\Storage;

use LogicException;
use OC;
use OC\Files\SimpleFS\SimpleFile;
use OC\Preview\Db\Preview;
use OC\Preview\Db\PreviewMapper;
use OCP\DB\Exception;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Snowflake\ISnowflakeGenerator;
use Override;
use Psr\Log\LoggerInterface;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class LocalPreviewStorage implements IPreviewStorage {
	private readonly string $rootFolder;
	private readonly string $instanceId;

	public function __construct(
		private readonly IConfig $config,
		private readonly PreviewMapper $previewMapper,
		private readonly IAppConfig $appConfig,
		private readonly IDBConnection $connection,
		private readonly IMimeTypeDetector $mimeTypeDetector,
		private readonly LoggerInterface $logger,
		private readonly ISnowflakeGenerator $generator,
	) {
		$this->instanceId = $this->config->getSystemValueString('instanceid');
		$this->rootFolder = $this->config->getSystemValue('datadirectory', OC::$SERVERROOT . '/data');
	}

	#[Override]
	public function writePreview(Preview $preview, mixed $stream): int {
		$previewPath = $this->constructPath($preview);
		$this->createParentFiles($previewPath);
		return file_put_contents($previewPath, $stream);
	}

	#[Override]
	public function readPreview(Preview $preview): mixed {
		$previewPath = $this->constructPath($preview);
		$resource = @fopen($previewPath, 'r');
		if ($resource === false) {
			throw new NotFoundException('Unable to open preview stream at ' . $previewPath);
		}
		return $resource;
	}

	#[Override]
	public function deletePreview(Preview $preview): void {
		$previewPath = $this->constructPath($preview);
		if (!@unlink($previewPath) && is_file($previewPath)) {
			throw new NotPermittedException('Unable to delete preview at ' . $previewPath);
		}
	}

	public function getPreviewRootFolder(): string {
		return $this->rootFolder . '/appdata_' . $this->instanceId . '/preview/';
	}

	private function constructPath(Preview $preview): string {
		return $this->getPreviewRootFolder() . implode('/', str_split(substr(md5((string)$preview->getFileId()), 0, 7))) . '/' . $preview->getFileId() . '/' . $preview->getName();
	}

	private function createParentFiles(string $path): void {
		$dirname = dirname($path);
		@mkdir($dirname, recursive: true);
		if (!is_dir($dirname)) {
			throw new NotPermittedException("Unable to create directory '$dirname'");
		}
	}

	#[Override]
	public function migratePreview(Preview $preview, SimpleFile $file): void {
		// legacy flat directory
		$sourcePath = $this->getPreviewRootFolder() . $preview->getFileId() . '/' . $preview->getName();
		if (!file_exists($sourcePath)) {
			return;
		}

		$destinationPath = $this->constructPath($preview);
		if (file_exists($destinationPath)) {
			@unlink($sourcePath); // We already have a new preview, just delete the old one
			return;
		}

		$this->createParentFiles($destinationPath);
		$ok = rename($sourcePath, $destinationPath);
		if (!$ok) {
			throw new LogicException('Failed to move ' . $sourcePath . ' to ' . $destinationPath);
		}
	}

	#[Override]
	public function scan(): int {
		$checkForFileCache = !$this->appConfig->getValueBool('core', 'previewMovedDone');

		$scanner = new RecursiveDirectoryIterator($this->getPreviewRootFolder());
		$previewsFound = 0;
		$previews = [];
		$pathHashes = [];
		$newPreviews = [];
		foreach (new RecursiveIteratorIterator($scanner) as $file) {
			if (!$file->isFile()) {
				continue;
			}

			$preview = Preview::fromPath((string)$file, $this->mimeTypeDetector);
			if ($preview === false) {
				$this->logger->error('Unable to parse preview information for ' . $file->getRealPath());
				continue;
			}
			$preview->generateId();
			$preview->setSize($file->getSize());
			$preview->setMtime($file->getMtime());
			$preview->setEncrypted(false);
			$previews[$preview->getFileId()] = $preview;
			$pathHashes[$preview->getFileId()] = md5(str_replace($this->rootFolder . '/', '', $file->getRealPath()));
		}

		$fileIds = array_keys($previews);
		$qb = $this->connection->getQueryBuilder();
		$qb->select('fileid', 'storage', 'etag', 'mimetype')
			->from('filecache')
			->where($qb->expr()->in('fileid', $qb->createParameter('fileIds')));

		foreach(array_chunk($fileIds, 1000) as $chunk) {
			// all rows from oc_filecache for previews
			$results = $qb->setParameter('fileIds', $chunk, IQueryBuilder::PARAM_INT_ARRAY)
				->runAcrossAllShards() // Unavoidable because we can't extract the storage_id from the preview name
				->executeQuery()
				->fetchAll();

			$toDelete = [];
			// Crosscheck existing preview files with oc_filecache entries
			try {
				$this->connection->beginTransaction();
				$qb = $this->connection->getQueryBuilder();
				foreach ($results as $item) {
					if (!isset($previews[$item['fileid']])) {
						// weird but ok
						continue;
					}

					$new = $previews[$item['fileid']];
					$new->setStorageId($item['storage']);
					$new->setEtag($item['etag']);
					$new->setSourceMimetype($item['mimetype']);
					$new->generateId();
					// File exists and can be recreated as a preview
					unset($previews[$item['fileid']]);
					$this->previewMapper->insert($new);
					$previewsFound++;
				}
				$this->connection->commit();
			} catch (Exception $e) {
				$this->connection->rollback();
				$this->logger->error('Transaction failed, rolling back ', ['exception' => $e]);
				throw $e;
			}

			if (!$checkForFileCache) {
				$this->connection->beginTransaction();
				$qb = $this->connection->getQueryBuilder();
				$rowAffected = $qb->delete('filecache')
					->where($qb->expr()->in('path_hash', $qb->createParameter('pathHashes')));
				foreach(array_chunk($pathHashes, 1000) as $hash) {
					$qb->setParameter('pathHashes', $hash, IQueryBuilder::PARAM_STR_ARRAY);
					$rows = $qb->executeStatement();
				}

				if ($rowAffected > 0) {
					$this->deleteParentsFromFileCache(dirname($relativePath));
				}
			}

			// Move old flat preview to new format
			$dirName = str_replace($this->getPreviewRootFolder(), '', $file->getPath());
			if (preg_match('/[0-9a-e]\/[0-9a-e]\/[0-9a-e]\/[0-9a-e]\/[0-9a-e]\/[0-9a-e]\/[0-9a-e]\/[0-9]+/', $dirName) !== 1) {
				$previewPath = $this->constructPath($preview);
				$this->createParentFiles($previewPath);
				$ok = rename($file->getRealPath(), $previewPath);
				if (!$ok) {
					throw new LogicException('Failed to move ' . $file->getRealPath() . ' to ' . $previewPath);
				}
			}
		}

		// Unlink whatever data is left over in the $previews array as they have no associated file
		foreach ($previews as $preview) {
			@unlink($preview->getRealPath());
		}

		return $previewsFound;
	}

	private function deleteParentsFromFileCache(string $dirname): void {
		$qb = $this->connection->getQueryBuilder();

		$result = $qb->select('fileid', 'path', 'storage', 'parent')
			->from('filecache')
			->where($qb->expr()->eq('path_hash', $qb->createNamedParameter(md5($dirname))))
			->setMaxResults(1)
			->runAcrossAllShards()
			->executeQuery()
			->fetchAll();

		if (empty($result)) {
			return;
		}

		$this->connection->beginTransaction();

		$parentId = $result[0]['parent'];
		$fileId = $result[0]['fileid'];
		$storage = $result[0]['storage'];

		try {
			while (true) {
				$qb = $this->connection->getQueryBuilder();
				$childs = $qb->select('fileid', 'path', 'storage')
					->from('filecache')
					->where($qb->expr()->eq('parent', $qb->createNamedParameter($fileId)))
					->hintShardKey('storage', $storage)
					->executeQuery()
					->fetchAll();

				if (!empty($childs)) {
					break;
				}

				$qb = $this->connection->getQueryBuilder();
				$qb->delete('filecache')
					->where($qb->expr()->eq('fileid', $qb->createNamedParameter($fileId)))
					->hintShardKey('storage', $result[0]['storage'])
					->executeStatement();

				$qb = $this->connection->getQueryBuilder();
				$result = $qb->select('fileid', 'path', 'storage', 'parent')
					->from('filecache')
					->where($qb->expr()->eq('fileid', $qb->createNamedParameter($parentId)))
					->setMaxResults(1)
					->hintShardKey('storage', $storage)
					->executeQuery()
					->fetchAll();

				if (empty($result)) {
					break;
				}

				$fileId = $parentId;
				$parentId = $result[0]['parent'];
			}
		} finally {
			$this->connection->commit();
		}
	}
}
