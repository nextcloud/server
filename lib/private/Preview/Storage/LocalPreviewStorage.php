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
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDBConnection;
use Override;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class LocalPreviewStorage implements IPreviewStorage {
	private readonly string $rootFolder;
	private readonly string $instanceId;

	public function __construct(
		private readonly IConfig $config,
		private readonly PreviewMapper $previewMapper,
		private readonly StorageFactory $previewStorage,
		private readonly IAppConfig $appConfig,
		private readonly IDBConnection $connection,
	) {
		$this->instanceId = $this->config->getSystemValueString('instanceid');
		$this->rootFolder = $this->config->getSystemValue('datadirectory', OC::$SERVERROOT . '/data');
	}

	#[Override]
	public function writePreview(Preview $preview, mixed $stream): false|int {
		$previewPath = $this->constructPath($preview);
		if (!$this->createParentFiles($previewPath)) {
			return false;
		}
		return file_put_contents($previewPath, $stream);
	}

	#[Override]
	public function readPreview(Preview $preview): mixed {
		return @fopen($this->constructPath($preview), 'r');
	}

	#[Override]
	public function deletePreview(Preview $preview): void {
		@unlink($this->constructPath($preview));
	}

	public function getPreviewRootFolder(): string {
		return $this->rootFolder . '/appdata_' . $this->instanceId . '/preview/';
	}

	private function constructPath(Preview $preview): string {
		return $this->getPreviewRootFolder() . implode('/', str_split(substr(md5((string)$preview->getFileId()), 0, 7))) . '/' . $preview->getFileId() . '/' . $preview->getName();
	}

	private function createParentFiles(string $path): bool {
		$dirname = dirname($path);
		mkdir($dirname, recursive: true);
		return is_dir($dirname);
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
			throw new LogicException('Failed to copy ' . $sourcePath . ' to ' . $destinationPath);
		}
	}

	#[Override]
	public function scan(): int {
		$checkForFileCache = !$this->appConfig->getValueBool('core', 'previewMovedDone');

		$scanner = new RecursiveDirectoryIterator($this->getPreviewRootFolder());
		$previewsFound = 0;
		foreach (new RecursiveIteratorIterator($scanner) as $file) {
			if ($file->isFile()) {
				$preview = Preview::fromPath((string)$file);
				try {
					$preview->setSize($file->getSize());
					$preview->setMtime($file->getMtime());
					$preview->setEncrypted(false);

					$qb = $this->connection->getQueryBuilder();
					$result = $qb->select('storage', 'etag', 'mimetype')
						->from('filecache')
						->where($qb->expr()->eq('fileid', $qb->createNamedParameter($preview->getFileId())))
						->setMaxResults(1)
						->runAcrossAllShards() // Unavoidable because we can't extract the storage_id from the preview name
						->executeQuery()
						->fetchAll();

					if (empty($result)) {
						// original file is deleted
						@unlink($file->getRealPath());
						continue;
					}

					if ($checkForFileCache) {
						$relativePath = str_replace($this->rootFolder . '/', '', $file->getRealPath());
						$rowAffected = $qb->delete('filecache')
							->where($qb->expr()->eq('path_hash', $qb->createNamedParameter(md5($relativePath))))
							->executeStatement();
						if ($rowAffected > 0) {
							$this->deleteParentsFromFileCache(dirname($relativePath));
						}
					}

					$preview->setStorageId($result[0]['storage']);
					$preview->setEtag($result[0]['etag']);
					$preview->setSourceMimetype($result[0]['mimetype']);

					// try to insert, if that fails the preview is already in the DB
					$this->previewMapper->insert($preview);

					// Move old flat preview to new format
					$this->previewStorage->migratePreview($preview, $file);
				} catch (Exception $e) {
					if ($e->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
						throw $e;
					}
				}
				$previewsFound++;
			}
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
