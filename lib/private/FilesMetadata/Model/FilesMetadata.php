<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\FilesMetadata\Model;

use JsonException;
use OCP\FilesMetadata\Exceptions\FilesMetadataKeyFormatException;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\Exceptions\FilesMetadataTypeException;
use OCP\FilesMetadata\Model\IFilesMetadata;
use OCP\FilesMetadata\Model\IMetadataValueWrapper;

/**
 * Model that represent metadata linked to a specific file.
 *
 * @inheritDoc
 * @since 28.0.0
 */
class FilesMetadata implements IFilesMetadata {
	/** @var array<string, MetadataValueWrapper> */
	private array $metadata = [];
	private bool $updated = false;
	private int $lastUpdate = 0;
	private string $syncToken = '';

	public function __construct(
		private int $fileId = 0
	) {
	}

	public function getFileId(): int {
		return $this->fileId;
	}

	public function lastUpdateTimestamp(): int {
		return $this->lastUpdate;
	}

	public function getSyncToken(): string {
		return $this->syncToken;
	}

	public function getKeys(): array {
		return array_keys($this->metadata);
	}

	public function hasKey(string $needle): bool {
		return (in_array($needle, $this->getKeys()));
	}

	public function getIndexes(): array {
		$indexes = [];
		foreach ($this->getKeys() as $key) {
			if ($this->metadata[$key]->isIndexed()) {
				$indexes[] = $key;
			}
		}

		return $indexes;
	}

	public function isIndex(string $key): bool {
		return $this->metadata[$key]?->isIndexed() ?? false;
	}

	public function getEditPermission(string $key): int {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getEditPermission();
	}

	public function setEditPermission(string $key, int $permission): void {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		$this->metadata[$key]->setEditPermission($permission);
	}


	public function getEtag(string $key): string {
		if (!array_key_exists($key, $this->metadata)) {
			return '';
		}

		return $this->metadata[$key]->getEtag();
	}

	public function setEtag(string $key, string $etag): void {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		$this->metadata[$key]->setEtag($etag);
	}

	public function getString(string $key): string {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getValueString();
	}

	public function getInt(string $key): int {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getValueInt();
	}

	public function getFloat(string $key): float {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getValueFloat();
	}

	public function getBool(string $key): bool {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getValueBool();
	}

	public function getArray(string $key): array {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getValueArray();
	}

	public function getStringList(string $key): array {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getValueStringList();
	}

	public function getIntList(string $key): array {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getValueIntList();
	}

	public function getType(string $key): string {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getType();
	}

	public function setString(string $key, string $value, bool $index = false): IFilesMetadata {
		$this->confirmKeyFormat($key);
		try {
			if ($this->getString($key) === $value && $index === $this->isIndex($key)) {
				return $this; // we ignore if value and index have not changed
			}
		} catch (FilesMetadataNotFoundException|FilesMetadataTypeException $e) {
			// if value does not exist, or type has changed, we keep on the writing
		}

		$meta = new MetadataValueWrapper(IMetadataValueWrapper::TYPE_STRING);
		$this->updated = true;
		$this->metadata[$key] = $meta->setValueString($value)->setIndexed($index);

		return $this;
	}

	public function setInt(string $key, int $value, bool $index = false): IFilesMetadata {
		$this->confirmKeyFormat($key);
		try {
			if ($this->getInt($key) === $value && $index === $this->isIndex($key)) {
				return $this; // we ignore if value have not changed
			}
		} catch (FilesMetadataNotFoundException|FilesMetadataTypeException $e) {
			// if value does not exist, or type has changed, we keep on the writing
		}

		$meta = new MetadataValueWrapper(IMetadataValueWrapper::TYPE_INT);
		$this->metadata[$key] = $meta->setValueInt($value)->setIndexed($index);
		$this->updated = true;

		return $this;
	}

	public function setFloat(string $key, float $value, bool $index = false): IFilesMetadata {
		$this->confirmKeyFormat($key);
		try {
			if ($this->getFloat($key) === $value && $index === $this->isIndex($key)) {
				return $this; // we ignore if value have not changed
			}
		} catch (FilesMetadataNotFoundException|FilesMetadataTypeException $e) {
			// if value does not exist, or type has changed, we keep on the writing
		}

		$meta = new MetadataValueWrapper(IMetadataValueWrapper::TYPE_FLOAT);
		$this->metadata[$key] = $meta->setValueFloat($value)->setIndexed($index);
		$this->updated = true;

		return $this;
	}


	public function setBool(string $key, bool $value, bool $index = false): IFilesMetadata {
		$this->confirmKeyFormat($key);
		try {
			if ($this->getBool($key) === $value && $index === $this->isIndex($key)) {
				return $this; // we ignore if value have not changed
			}
		} catch (FilesMetadataNotFoundException|FilesMetadataTypeException $e) {
			// if value does not exist, or type has changed, we keep on the writing
		}

		$meta = new MetadataValueWrapper(IMetadataValueWrapper::TYPE_BOOL);
		$this->metadata[$key] = $meta->setValueBool($value)->setIndexed($index);
		$this->updated = true;

		return $this;
	}


	public function setArray(string $key, array $value): IFilesMetadata {
		$this->confirmKeyFormat($key);
		try {
			if ($this->getArray($key) === $value) {
				return $this; // we ignore if value have not changed
			}
		} catch (FilesMetadataNotFoundException|FilesMetadataTypeException $e) {
			// if value does not exist, or type has changed, we keep on the writing
		}

		$meta = new MetadataValueWrapper(IMetadataValueWrapper::TYPE_ARRAY);
		$this->metadata[$key] = $meta->setValueArray($value);
		$this->updated = true;

		return $this;
	}

	public function setStringList(string $key, array $value, bool $index = false): IFilesMetadata {
		$this->confirmKeyFormat($key);
		try {
			if ($this->getStringList($key) === $value) {
				return $this; // we ignore if value have not changed
			}
		} catch (FilesMetadataNotFoundException|FilesMetadataTypeException $e) {
			// if value does not exist, or type has changed, we keep on the writing
		}

		$meta = new MetadataValueWrapper(IMetadataValueWrapper::TYPE_STRING_LIST);
		$this->metadata[$key] = $meta->setValueStringList($value)->setIndexed($index);
		$this->updated = true;

		return $this;
	}

	public function setIntList(string $key, array $value, bool $index = false): IFilesMetadata {
		$this->confirmKeyFormat($key);
		try {
			if ($this->getIntList($key) === $value) {
				return $this; // we ignore if value have not changed
			}
		} catch (FilesMetadataNotFoundException|FilesMetadataTypeException $e) {
			// if value does not exist, or type has changed, we keep on the writing
		}

		$valueWrapper = new MetadataValueWrapper(IMetadataValueWrapper::TYPE_INT_LIST);
		$this->metadata[$key] = $valueWrapper->setValueIntList($value)->setIndexed($index);
		$this->updated = true;

		return $this;
	}

	public function unset(string $key): IFilesMetadata {
		if (!array_key_exists($key, $this->metadata)) {
			return $this;
		}

		unset($this->metadata[$key]);
		$this->updated = true;

		return $this;
	}

	public function removeStartsWith(string $keyPrefix): IFilesMetadata {
		if ($keyPrefix === '') {
			return $this;
		}

		foreach ($this->getKeys() as $key) {
			if (str_starts_with($key, $keyPrefix)) {
				$this->unset($key);
			}
		}

		return $this;
	}

	/**
	 * @param string $key
	 *
	 * @return void
	 * @throws FilesMetadataKeyFormatException
	 */
	private function confirmKeyFormat(string $key): void {
		$acceptedChars = ['-', '_'];
		if (ctype_alnum(str_replace($acceptedChars, '', $key))) {
			return;
		}

		throw new FilesMetadataKeyFormatException('key can only contains alphanumerical characters, and dash (-, _)');
	}

	public function updated(): bool {
		return $this->updated;
	}

	public function jsonSerialize(bool $emptyValues = false): array {
		$data = [];
		foreach ($this->metadata as $metaKey => $metaValueWrapper) {
			$data[$metaKey] = $metaValueWrapper->jsonSerialize($emptyValues);
		}

		return $data;
	}

	public function asArray(): array {
		$data = [];
		foreach ($this->metadata as $metaKey => $metaValueWrapper) {
			try {
				$data[$metaKey] = $metaValueWrapper->getValueAny();
			} catch (FilesMetadataNotFoundException $e) {
				// ignore exception
			}
		}

		return $data;
	}

	public function import(array $data): IFilesMetadata {
		foreach ($data as $k => $v) {
			$valueWrapper = new MetadataValueWrapper();
			$this->metadata[$k] = $valueWrapper->import($v);
		}
		$this->updated = false;

		return $this;
	}

	/**
	 * import data from database to configure this model
	 *
	 * @param array $data
	 * @param string $prefix
	 *
	 * @return IFilesMetadata
	 * @throws FilesMetadataNotFoundException
	 * @since 28.0.0
	 */
	public function importFromDatabase(array $data, string $prefix = ''): IFilesMetadata {
		try {
			$this->syncToken = $data[$prefix . 'sync_token'] ?? '';

			return $this->import(
				json_decode(
					$data[$prefix . 'json'] ?? '[]',
					true,
					512,
					JSON_THROW_ON_ERROR
				)
			);
		} catch (JsonException) {
			throw new FilesMetadataNotFoundException();
		}
	}
}
