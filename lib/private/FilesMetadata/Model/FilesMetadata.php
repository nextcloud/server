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

	/**
	 * @inheritDoc
	 * @return int related file id
	 * @since 28.0.0
	 */
	public function getFileId(): int {
		return $this->fileId;
	}

	/**
	 * @inheritDoc
	 * @return int timestamp
	 * @since 28.0.0
	 */
	public function lastUpdateTimestamp(): int {
		return $this->lastUpdate;
	}

	/**
	 * @inheritDoc
	 * @return string token
	 * @since 28.0.0
	 */
	public function getSyncToken(): string {
		return $this->syncToken;
	}

	/**
	 * @inheritDoc
	 * @return string[] list of keys
	 * @since 28.0.0
	 */
	public function getKeys(): array {
		return array_keys($this->metadata);
	}

	/**
	 * @param string $needle metadata key to search
	 *
	 * @inheritDoc
	 * @return bool TRUE if key exist
	 * @since 28.0.0
	 */
	public function hasKey(string $needle): bool {
		return (in_array($needle, $this->getKeys()));
	}

	/**
	 * @inheritDoc
	 * @return string[] list of indexes
	 * @since 28.0.0
	 */
	public function getIndexes(): array {
		$indexes = [];
		foreach ($this->getKeys() as $key) {
			if ($this->metadata[$key]->isIndexed()) {
				$indexes[] = $key;
			}
		}

		return $indexes;
	}

	/**
	 * @param string $key metadata key
	 *
	 * @inheritDoc
	 * @return bool TRUE if key exists and is set as indexed
	 * @since 28.0.0
	 */
	public function isIndex(string $key): bool {
		return $this->metadata[$key]?->isIndexed() ?? false;
	}

	/**
	 * @param string $key metadata key
	 *
	 * @inheritDoc
	 * @return string metadata value
	 * @throws FilesMetadataNotFoundException
	 * @throws FilesMetadataTypeException
	 * @since 28.0.0
	 */
	public function getString(string $key): string {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getValueString();
	}

	/**
	 * @param string $key metadata key
	 *
	 * @inheritDoc
	 * @return int metadata value
	 * @throws FilesMetadataNotFoundException
	 * @throws FilesMetadataTypeException
	 * @since 28.0.0
	 */
	public function getInt(string $key): int {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getValueInt();
	}

	/**
	 * @param string $key metadata key
	 *
	 * @inheritDoc
	 * @return float metadata value
	 * @throws FilesMetadataNotFoundException
	 * @throws FilesMetadataTypeException
	 * @since 28.0.0
	 */
	public function getFloat(string $key): float {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getValueFloat();
	}

	/**
	 * @param string $key metadata key
	 *
	 * @inheritDoc
	 * @return bool metadata value
	 * @throws FilesMetadataNotFoundException
	 * @throws FilesMetadataTypeException
	 * @since 28.0.0
	 */
	public function getBool(string $key): bool {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getValueBool();
	}

	/**
	 * @param string $key metadata key
	 *
	 * @inheritDoc
	 * @return array metadata value
	 * @throws FilesMetadataNotFoundException
	 * @throws FilesMetadataTypeException
	 * @since 28.0.0
	 */
	public function getArray(string $key): array {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getValueArray();
	}

	/**
	 * @param string $key metadata key
	 *
	 * @inheritDoc
	 * @return string[] metadata value
	 * @throws FilesMetadataNotFoundException
	 * @throws FilesMetadataTypeException
	 * @since 28.0.0
	 */
	public function getStringList(string $key): array {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getValueStringList();
	}

	/**
	 * @param string $key metadata key
	 *
	 * @inheritDoc
	 * @return int[] metadata value
	 * @throws FilesMetadataNotFoundException
	 * @throws FilesMetadataTypeException
	 * @since 28.0.0
	 */
	public function getIntList(string $key): array {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getValueIntList();
	}

	/**
	 * @param string $key metadata key
	 *
	 * @inheritDoc
	 * @return string value type
	 * @throws FilesMetadataNotFoundException
	 * @see IMetadataValueWrapper::TYPE_STRING
	 * @see IMetadataValueWrapper::TYPE_INT
	 * @see IMetadataValueWrapper::TYPE_FLOAT
	 * @see IMetadataValueWrapper::TYPE_BOOL
	 * @see IMetadataValueWrapper::TYPE_ARRAY
	 * @see IMetadataValueWrapper::TYPE_STRING_LIST
	 * @see IMetadataValueWrapper::TYPE_INT_LIST
	 * @since 28.0.0
	 */
	public function getType(string $key): string {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getType();
	}

	/**
	 * @param string $key metadata key
	 * @param string $value metadata value
	 * @param bool $index set TRUE if value must be indexed
	 *
	 * @inheritDoc
	 * @return self
	 * @throws FilesMetadataKeyFormatException
	 * @since 28.0.0
	 */
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

	/**
	 * @param string $key metadata key
	 * @param int $value metadata value
	 * @param bool $index set TRUE if value must be indexed
	 *
	 * @inheritDoc
	 * @return self
	 * @throws FilesMetadataKeyFormatException
	 * @since 28.0.0
	 */
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

	/**
	 * @param string $key metadata key
	 * @param float $value metadata value
	 *
	 * @inheritDoc
	 * @return self
	 * @throws FilesMetadataKeyFormatException
	 * @since 28.0.0
	 */
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


	/**
	 * @param string $key metadata key
	 * @param bool $value metadata value
	 * @param bool $index set TRUE if value must be indexed
	 *
	 * @inheritDoc
	 * @return self
	 * @throws FilesMetadataKeyFormatException
	 * @since 28.0.0
	 */
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


	/**
	 * @param string $key metadata key
	 * @param array $value metadata value
	 *
	 * @inheritDoc
	 * @return self
	 * @throws FilesMetadataKeyFormatException
	 * @since 28.0.0
	 */
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

	/**
	 * @param string $key metadata key
	 * @param string[] $value metadata value
	 * @param bool $index set TRUE if each values from the list must be indexed
	 *
	 * @inheritDoc
	 * @return self
	 * @throws FilesMetadataKeyFormatException
	 * @since 28.0.0
	 */
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

	/**
	 * @param string $key metadata key
	 * @param int[] $value metadata value
	 * @param bool $index set TRUE if each values from the list must be indexed
	 *
	 * @inheritDoc
	 * @return self
	 * @throws FilesMetadataKeyFormatException
	 * @since 28.0.0
	 */
	public function setIntList(string $key, array $value, bool $index = false): IFilesMetadata {
		$this->confirmKeyFormat($key);
		try {
			if ($this->getIntList($key) === $value) {
				return $this; // we ignore if value have not changed
			}
		} catch (FilesMetadataNotFoundException|FilesMetadataTypeException $e) {
			// if value does not exist, or type has changed, we keep on the writing
		}

		$valueWrapper = new MetadataValueWrapper(IMetadataValueWrapper::TYPE_STRING_LIST);
		$this->metadata[$key] = $valueWrapper->setValueIntList($value)->setIndexed($index);
		$this->updated = true;

		return $this;
	}

	/**
	 * @param string $key metadata key
	 *
	 * @inheritDoc
	 * @return self
	 * @since 28.0.0
	 */
	public function unset(string $key): IFilesMetadata {
		if (!array_key_exists($key, $this->metadata)) {
			return $this;
		}

		unset($this->metadata[$key]);
		$this->updated = true;

		return $this;
	}

	/**
	 * @param string $keyPrefix metadata key prefix
	 *
	 * @inheritDoc
	 * @return self
	 * @since 28.0.0
	 */
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

	/**
	 * @inheritDoc
	 * @return bool TRUE if metadata have been modified
	 * @since 28.0.0
	 */
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

	/**
	 * @return array<string, string|int|bool|float|string[]|int[]>
	 */
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

	/**
	 * @param array $data
	 *
	 * @inheritDoc
	 * @return IFilesMetadata
	 * @since 28.0.0
	 */
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
		} catch (JsonException $e) {
			throw new FilesMetadataNotFoundException();
		}
	}
}
