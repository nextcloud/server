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
use OCP\Files\NotPermittedException;
use OCP\Files\ObjectStore\IObjectStore;
use OCP\IConfig;
use Override;

/**
 * @psalm-import-type ObjectStoreConfig from PrimaryObjectStoreConfig
 * @psalm-type ObjectStoreDefinition = array{store: IObjectStore, urn: string}
 */
class ObjectStorePreviewStorage implements IPreviewStorage {

	/**
	 * @var array<string, array<string, IObjectStore>>
	 */
	private array $objectStoreCache = [];

	private bool $isMultibucketPreviewDistributionEnabled;

	public function __construct(
		private readonly PrimaryObjectStoreConfig $objectStoreConfig,
		IConfig $config,
		private readonly PreviewMapper $previewMapper,
	) {
		$this->isMultibucketPreviewDistributionEnabled = $config->getSystemValueBool('objectstore.multibucket.preview-distribution');
	}

	#[Override]
	public function writePreview(Preview $preview, mixed $stream): int {
		$size = 0;
		$countStream = CountWrapper::wrap($stream, function (int $writtenSize) use (&$size): void {
			$size = $writtenSize;
		});

		[
			'urn' => $urn,
			'store' => $store,
		] = $this->getObjectStoreInfoForNewPreview($preview);

		try {
			$store->writeObject($urn, $countStream);
		} catch (\Exception $exception) {
			throw new NotPermittedException('Unable to save preview to object store', previous: $exception);
		}
		return $size;
	}

	#[Override]
	public function readPreview(Preview $preview): mixed {
		[
			'urn' => $urn,
			'store' => $store,
		] = $this->getObjectStoreInfoForExistingPreview($preview);

		try {
			return $store->readObject($urn);
		} catch (\Exception $exception) {
			throw new NotPermittedException('Unable to read preview from object store with urn:' . $urn, previous: $exception);
		}
	}

	#[Override]
	public function deletePreview(Preview $preview): void {
		if (defined('PHPUNIT_RUN') && $preview->getLocationId() === null) {
			// Should only be the case in unit tests when adding dummy previews in the database.
			return;
		}

		[
			'urn' => $urn,
			'store' => $store,
		] = $this->getObjectStoreInfoForExistingPreview($preview);

		try {
			$store->deleteObject($urn);
		} catch (\Exception $exception) {
			throw new NotPermittedException('Unable to delete preview from object store', previous: $exception);
		}
	}

	#[Override]
	public function migratePreview(Preview $preview, SimpleFile $file): void {
		// Just set the Preview::bucket and Preview::objectStore
		$this->getObjectStoreInfoForNewPreview($preview, migration: true);
		$this->previewMapper->update($preview);
	}

	/**
	 * @return ObjectStoreDefinition
	 */
	private function getObjectStoreInfoForExistingPreview(Preview $preview): array {
		$objectStoreName = $preview->getObjectStoreName();
		$bucketName = $preview->getBucketName();
		assert(!empty($objectStoreName));
		assert(!empty($bucketName));

		$config = $this->objectStoreConfig->getObjectStoreConfiguration($objectStoreName);
		$config['arguments']['bucket'] = $preview->getBucketName();
		$objectStoreName = $preview->getObjectStoreName();

		return [
			'urn' => $this->getUrn($preview, $config),
			'store' => $this->getObjectStore($objectStoreName, $config),
		];
	}

	/**
	 * @return ObjectStoreDefinition
	 */
	private function getObjectStoreInfoForNewPreview(Preview $preview, bool $migration = false): array {
		// When migrating old previews, use the 'root' object store configuration
		$config = $this->objectStoreConfig->getObjectStoreConfiguration($migration ? 'root' : 'preview');
		$objectStoreName = $this->objectStoreConfig->resolveAlias($migration ? 'root' : 'preview');

		$bucketName = $config['arguments']['bucket'];
		if ($config['arguments']['multibucket']) {
			if ($this->isMultibucketPreviewDistributionEnabled) {
				// Spread the previews on different buckets depending on their corresponding fileId
				$oldLocationArray = str_split(substr(md5((string)$preview->getFileId()), 0, 2));
				$bucketNumber = hexdec('0x' . $oldLocationArray[0]) * 16 + hexdec('0x' . $oldLocationArray[0]);
				$bucketName .= '-preview-' . $bucketNumber;
			} else {
				// Put all previews in the root (0) bucket
				$bucketName .= '0';
			}
		}
		$config['arguments']['bucket'] = $bucketName;

		// Get the locationId corresponding to the bucketName and objectStoreName, this will create
		// a new one, if no matching location is found in the DB.
		$locationId = $this->previewMapper->getLocationId($bucketName, $objectStoreName);
		$preview->setLocationId($locationId);
		$preview->setObjectStoreName($objectStoreName);
		$preview->setBucketName($bucketName);

		return [
			'urn' => $this->getUrn($preview, $config),
			'store' => $this->getObjectStore($objectStoreName, $config),
		];
	}

	private function getObjectStore(string $objectStoreName, array $config): IObjectStore {
		$bucketName = $config['arguments']['bucket'];

		if (!isset($this->objectStoreCache[$objectStoreName])) {
			$this->objectStoreCache[$objectStoreName] = [];
			$this->objectStoreCache[$objectStoreName][$bucketName] = $this->objectStoreConfig->buildObjectStore($config);
		} elseif (!isset($this->objectStoreCache[$objectStoreName][$bucketName])) {
			$this->objectStoreCache[$objectStoreName][$bucketName] = $this->objectStoreConfig->buildObjectStore($config);
		}

		return $this->objectStoreCache[$objectStoreName][$bucketName];
	}

	public function getUrn(Preview $preview, array $config): string {
		if ($preview->getOldFileId()) {
			return ($config['arguments']['objectPrefix'] ?? 'urn:oid:') . $preview->getOldFileId();
		}
		if (isset($config['arguments']['objectPrefix'])) {
			return ($config['arguments']['objectPrefix'] . 'preview:') . $preview->getId();
		} else {
			return 'uri:oid:preview:' . $preview->getId();
		}
	}

	#[Override]
	public function scan(): int {
		return 0;
	}
}
