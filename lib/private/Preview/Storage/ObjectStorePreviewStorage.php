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
use OCP\Files\NotFoundException;
use OCP\Files\ObjectStore\IObjectStore;
use OCP\IConfig;

/**
 * @psalm-type ObjectStoreDefinition = array{store: IObjectStore, objectPrefix: string, config?: array}
 */
class ObjectStorePreviewStorage implements IPreviewStorage {

	/**
	 * @var array<'root'|int, ObjectStoreDefinition>
	 */
	private array $objectStoreCache = [];

	private bool $isMultibucketEnabled;
	private bool $isMultibucketPreviewDistributionEnabled;

	public function __construct(
		private readonly PrimaryObjectStoreConfig $objectStoreConfig,
		readonly private IConfig $config,
	) {
		$this->isMultibucketEnabled = is_array($config->getSystemValue('objectstore_multibucket'));
		$this->isMultibucketPreviewDistributionEnabled = $config->getSystemValueBool('objectstore.multibucket.preview-distribution');
	}

	public function writePreview(Preview $preview, $stream): false|int {
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
		] = $this->getObjectStoreForPreview($preview);

		$store->writeObject($this->constructUrn($objectPrefix, $preview->getId()), $countStream);
		return $size;
	}

	public function readPreview(Preview $preview) {
		[
			'objectPrefix' => $objectPrefix,
			'store' => $store,
		] = $this->getObjectStoreForPreview($preview);
		return $store->readObject($this->constructUrn($objectPrefix, $preview->getId()));
	}

	public function deletePreview(Preview $preview) {
		[
			'objectPrefix' => $objectPrefix,
			'store' => $store,
		] = $this->getObjectStoreForPreview($preview);
		return $store->deleteObject($this->constructUrn($objectPrefix, $preview->getId()));
	}

	public function migratePreview(Preview $preview, SimpleFile $file): void {
		foreach ([false, true] as $fallback) {
			[
				'objectPrefix' => $objectPrefix,
				'store' => $store,
				'config' => $config,
			] = $this->getObjectStoreForPreview($preview, $fallback);

			$oldObjectPrefix = 'urn:oid:';
			if (isset($config['objectPrefix'])) {
				$oldObjectPrefix = $config['objectPrefix'];
			}

			try {
				$store->copyObject($this->constructUrn($oldObjectPrefix, $file->getId()), $this->constructUrn($objectPrefix, $preview->getId()));
				break;
			} catch (NotFoundException $e) {
				if (!$fallback && $this->isMultibucketPreviewDistributionEnabled) {
					continue;
				}
				throw $e;
			}
		}
	}

	/**
	 * @return ObjectStoreDefinition
	 */
	private function getMultiBucketObjectStore(int $number): array {
		/**
		 * @var array{class: class-string<IObjectStore>, ...} $config
		 */
		$config = $this->config->getSystemValue('objectstore_multibucket');

		if (!isset($config['arguments'])) {
			$config['arguments'] = [];
		}

		/*
		 * Use any provided bucket argument as prefix
		 * and add the mapping from parent/child => bucket
		 */
		if (!isset($config['arguments']['bucket'])) {
			$config['arguments']['bucket'] = '';
		}

		$config['arguments']['bucket'] .= "-preview-$number";

		$objectPrefix = 'urn:oid:preview:';
		if (isset($config['objectPrefix'])) {
			$objectPrefix = $config['objectPrefix'] . 'preview:';
		}

		return [
			'store' => new $config['class']($config['arguments']),
			'objectPrefix' => $objectPrefix,
			'config' => $config,
		];
	}

	/**
	 * @return ObjectStoreDefinition
	 */
	private function getRootObjectStore(): array {
		if (!isset($this->objectStoreCache['root'])) {
			$rootConfig = $this->objectStoreConfig->getObjectStoreConfigForRoot();
			$objectPrefix = 'urn:oid:preview:';
			if (isset($rootConfig['arguments']['objectPrefix'])) {
				$objectPrefix = $rootConfig['arguments']['objectPrefix'] . 'preview:';
			}
			$this->objectStoreCache['root'] = [
				'store' => $this->objectStoreConfig->buildObjectStore($rootConfig),
				'objectPrefix' => $objectPrefix,
			];
		}
		return $this->objectStoreCache['root'];
	}

	/**
	 * @return ObjectStoreDefinition
	 */
	private function getObjectStoreForPreview(Preview $preview, bool $oldFallback = false): array {
		if (!$this->isMultibucketEnabled || !$this->isMultibucketPreviewDistributionEnabled || $oldFallback) {
			return $this->getRootObjectStore();
		}

		$oldLocationArray = str_split(substr(md5((string)$preview->getFileId()), 0, 2));
		$bucketNumber = hexdec('0x' . $oldLocationArray[0]) * 16 + hexdec('0x' . $oldLocationArray[0]);

		if (!isset($this->objectStoreCache[$bucketNumber])) {
			$this->objectStoreCache[$bucketNumber] = $this->getMultiBucketObjectStore($bucketNumber);
		}

		return $this->objectStoreCache[$bucketNumber];
	}

	private function constructUrn(string $objectPrefix, int $id): string {
		return $objectPrefix . $id;
	}
}
