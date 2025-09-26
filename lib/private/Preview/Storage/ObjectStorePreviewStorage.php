<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Preview\Storage;

use Icewind\Streams\CountWrapper;
use OC\Files\ObjectStore\PrimaryObjectStoreConfig;
use OC\Files\SimpleFS\SimpleFile;
use OC\Preview\Db\Preview;
use OC\Preview\Db\PreviewMapper;
use OCP\Files\ObjectStore\IObjectStore;
use OCP\IConfig;
use Override;

/**
 * @psalm-import-type ObjectStoreConfig from PrimaryObjectStoreConfig
 * @psalm-type ObjectStoreDefinition = array{store: IObjectStore, objectPrefix: string, config?: ObjectStoreConfig}
 */
class ObjectStorePreviewStorage implements IPreviewStorage {

	/**
	 * @var array<string, array<string, ObjectStoreDefinition>>
	 */
	private array $objectStoreCache = [];

	private bool $isMultibucketPreviewDistributionEnabled;

	public function __construct(
		private readonly PrimaryObjectStoreConfig $objectStoreConfig,
		IConfig $config,
		readonly private PreviewMapper $previewMapper,
	) {
		$this->isMultibucketPreviewDistributionEnabled = $config->getSystemValueBool('objectstore.multibucket.preview-distribution');
	}

	#[Override]
	public function writePreview(Preview $preview, mixed $stream): false|int {
		if (!is_resource($stream)) {
			$fh = fopen('php://temp', 'w+');
			fwrite($fh, $stream);
			rewind($fh);

			$stream = $fh;
		}

		$size = 0;
		$countStream = CountWrapper::wrap($stream, function (int $writtenSize) use (&$size): void {
			$size = $writtenSize;
		});

		[
			'objectPrefix' => $objectPrefix,
			'store' => $store,
			'config' => $config,
		] = $this->getObjectStoreForPreview($preview);

		$store->writeObject($this->constructUrn($objectPrefix, $preview->getId()), $countStream);
		return $size;
	}

	#[Override]
	public function readPreview(Preview $preview): mixed {
		[
			'objectPrefix' => $objectPrefix,
			'store' => $store,
		] = $this->getObjectStoreForPreview($preview);
		return $store->readObject($this->constructUrn($objectPrefix, $preview->getId()));
	}

	#[Override]
	public function deletePreview(Preview $preview): void {
		[
			'objectPrefix' => $objectPrefix,
			'store' => $store,
		] = $this->getObjectStoreForPreview($preview);
		$store->deleteObject($this->constructUrn($objectPrefix, $preview->getId()));
	}

	#[Override]
	public function migratePreview(Preview $preview, SimpleFile $file): void {
		// Just set the Preview::bucket and Preview::objectStore
		$this->getObjectStoreForPreview($preview, true);
		$this->previewMapper->update($preview);
	}

	/**
	 * @return ObjectStoreDefinition
	 */
	private function getObjectStoreForPreview(Preview $preview, bool $oldFallback = false): array {
		if ($preview->getObjectStoreName() === null) {
			$config = $this->objectStoreConfig->getObjectStoreConfiguration($oldFallback ? 'root' : 'preview');
			$objectStoreName = $this->objectStoreConfig->resolveAlias($oldFallback ? 'root' : 'preview');

			$bucketName = $config['arguments']['bucket'];
			if ($config['arguments']['multibucket']) {
				if ($this->isMultibucketPreviewDistributionEnabled) {
					$oldLocationArray = str_split(substr(md5((string)$preview->getFileId()), 0, 2));
					$bucketNumber = hexdec('0x' . $oldLocationArray[0]) * 16 + hexdec('0x' . $oldLocationArray[0]);
					$bucketName .= '-preview-' . $bucketNumber;
				} else {
					$bucketName .= '0';
				}
			}
			$config['arguments']['bucket'] = $bucketName;

			$locationId = $this->previewMapper->getLocationId($bucketName, $objectStoreName);
			$preview->setLocationId($locationId);
			$preview->setObjectStoreName($objectStoreName);
			$preview->setBucketName($bucketName);
		} else {
			$config = $this->objectStoreConfig->getObjectStoreConfiguration($preview->getObjectStoreName());
			$config['arguments']['bucket'] = $bucketName = $preview->getBucketName();
			$objectStoreName = $preview->getObjectStoreName();
		}

		$objectPrefix = $this->getObjectPrefix($preview, $config);

		if (!isset($this->objectStoreCache[$objectStoreName])) {
			$this->objectStoreCache[$objectStoreName] = [];
			$this->objectStoreCache[$objectStoreName][$bucketName] = [
				'store' => $this->objectStoreConfig->buildObjectStore($config),
				'objectPrefix' => $objectPrefix,
				'config' => $config,
			];
		} elseif (!isset($this->objectStoreCache[$objectStoreName][$bucketName])) {
			$this->objectStoreCache[$objectStoreName][$bucketName] = [
				'store' => $this->objectStoreConfig->buildObjectStore($config),
				'objectPrefix' => $objectPrefix,
				'config' => $config,
			];
		}

		return $this->objectStoreCache[$objectStoreName][$bucketName];
	}

	private function constructUrn(string $objectPrefix, int $id): string {
		return $objectPrefix . $id;
	}

	public function getObjectPrefix(Preview $preview, array $config): string {
		if ($preview->getOldFileId()) {
			return $config['arguments']['objectPrefix'] ?? 'uri:oid:';
		}
		if (isset($config['arguments']['objectPrefix'])) {
			return $config['arguments']['objectPrefix'] . 'preview:';
		} else {
			return 'uri:oid:preview:';
		}
	}

	#[Override]
	public function scan(): int {
		return 0;
	}
}
