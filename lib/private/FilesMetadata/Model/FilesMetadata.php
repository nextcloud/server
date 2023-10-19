<?php

declare(strict_types=1);

namespace OC\FilesMetadata\Model;

use JsonException;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\Exceptions\FilesMetadataTypeException;
use OCP\FilesMetadata\Model\IFilesMetadata;

class FilesMetadata implements IFilesMetadata {
	private const TYPE_STRING = 'string';
	private const TYPE_INT = 'int';
	private const TYPE_BOOT = 'bool';
	private const TYPE_ARRAY = 'array';

	/** @var array<string, MetadataValueWrapper> */
	private array $metadata = [];
	private int $lastUpdate = 0;
	private string $syncToken = '';

	public function __construct(
		private int $fileId = 0,
		private bool $updated = false
	) {
	}

	public function getFileId(): int {
		return $this->fileId;
	}

	/**
	 * @param array $data
	 *
	 * @return IFilesMetadata
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
	 * import from database using the json field.
	 *
	 * if using aliases (ie. myalias_json), use $prefix='myalias_'
	 *
	 * @param array $data
	 * @param string $prefix
	 *
	 * @return IFilesMetadata
	 * @throws FilesMetadataNotFoundException
	 */
	public function importFromDatabase(array $data, string $prefix = ''): IFilesMetadata {
		try {
			return $this->import(
				json_decode($data[$prefix . 'json'] ?? '[]',
					true,
					512,
					JSON_THROW_ON_ERROR)
			);
		} catch (JsonException $e) {
			throw new FilesMetadataNotFoundException();
		}
	}


	public function updated(): bool {
		return $this->updated;
	}

	public function lastUpdateTimestamp(): int {
		return $this->lastUpdate;
	}

	public function getSyncToken(): string {
		return $this->syncToken;
	}

	public function hasKey(string $needle): bool {
		return (in_array($needle, $this->getKeys()));
	}

	public function getKeys(): array {
		return array_keys($this->metadata);
	}

	/**
	 * @return string[]
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
	 * @param string $key
	 *
	 * @return string
	 * @throws FilesMetadataNotFoundException
	 * @throws FilesMetadataTypeException
	 */
	public function get(string $key): string {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getValueString();
	}

	/**
	 * @param string $key
	 *
	 * @return int
	 * @throws FilesMetadataNotFoundException
	 * @throws FilesMetadataTypeException
	 */
	public function getInt(string $key): int {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getValueInt();
	}

	/**
	 * @param string $key
	 *
	 * @return float
	 * @throws FilesMetadataNotFoundException
	 * @throws FilesMetadataTypeException
	 */
	public function getFloat(string $key): float {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getValueFloat();
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 * @throws FilesMetadataNotFoundException
	 * @throws FilesMetadataTypeException
	 */
	public function getBool(string $key): bool {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getValueBool();
	}

	/**
	 * @param string $key
	 *
	 * @return array
	 * @throws FilesMetadataNotFoundException
	 * @throws FilesMetadataTypeException
	 */
	public function getArray(string $key): array {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getValueArray();
	}

	/**
	 * @param string $key
	 *
	 * @return string[]
	 * @throws FilesMetadataNotFoundException
	 * @throws FilesMetadataTypeException
	 */
	public function getStringList(string $key): array {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getValueStringList();
	}

	/**
	 * @param string $key
	 *
	 * @return int[]
	 * @throws FilesMetadataNotFoundException
	 * @throws FilesMetadataTypeException
	 */
	public function getIntList(string $key): array {
		if (!array_key_exists($key, $this->metadata)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key]->getValueIntList();
	}

	public function getType(string $key): string {
		return $this->metadata[$key]->getType();
	}

	public function set(string $key, string $value, bool $index = false): IFilesMetadata {
		try {
			if ($this->get($key) === $value && $index === in_array($key, $this->getIndexes())) {
				return $this; // we ignore if value and index have not changed
			}
		} catch (FilesMetadataNotFoundException|FilesMetadataTypeException $e) {
			// if value does not exist, or type has changed, we keep on the writing
		}

		$meta = new MetadataValueWrapper(MetadataValueWrapper::TYPE_STRING);
		$this->updated = true;
		$this->metadata[$key] = $meta->setValueString($value)->setIndexed($index);

		return $this;
	}

	public function setInt(string $key, int $value, bool $index = false): IFilesMetadata {
		try {
			if ($this->getInt($key) === $value && $index === in_array($key, $this->getIndexes())) {
				return $this; // we ignore if value have not changed
			}
		} catch (FilesMetadataNotFoundException|FilesMetadataTypeException $e) {
			// if value does not exist, or type has changed, we keep on the writing
		}

		$meta = new MetadataValueWrapper(MetadataValueWrapper::TYPE_INT);
		$this->metadata[$key] = $meta->setValueInt($value)->setIndexed($index);
		$this->updated = true;

		return $this;
	}

	public function setFloat(string $key, float $value, bool $index = false): IFilesMetadata {
		try {
			if ($this->getFloat($key) === $value && $index === in_array($key, $this->getIndexes())) {
				return $this; // we ignore if value have not changed
			}
		} catch (FilesMetadataNotFoundException|FilesMetadataTypeException $e) {
			// if value does not exist, or type has changed, we keep on the writing
		}

		$meta = new MetadataValueWrapper(MetadataValueWrapper::TYPE_FLOAT);
		$this->metadata[$key] = $meta->setValueFloat($value)->setIndexed($index);
		$this->updated = true;

		return $this;
	}

	public function setBool(string $key, bool $value): IFilesMetadata {
		try {
			if ($this->getBool($key) === $value) {
				return $this; // we ignore if value have not changed
			}
		} catch (FilesMetadataNotFoundException|FilesMetadataTypeException $e) {
			// if value does not exist, or type has changed, we keep on the writing
		}

		$meta = new MetadataValueWrapper(MetadataValueWrapper::TYPE_BOOL);
		$this->metadata[$key] = $meta->setValueBool($value);
		$this->updated = true;

		return $this;
	}

	public function setArray(string $key, array $value): IFilesMetadata {
		try {
			if ($this->getArray($key) === $value) {
				return $this; // we ignore if value have not changed
			}
		} catch (FilesMetadataNotFoundException|FilesMetadataTypeException $e) {
			// if value does not exist, or type has changed, we keep on the writing
		}

		$meta = new MetadataValueWrapper(MetadataValueWrapper::TYPE_ARRAY);
		$this->metadata[$key] = $meta->setValueArray($value);
		$this->updated = true;

		return $this;
	}


	public function setStringList(string $key, array $values, bool $index = false): IFilesMetadata {
		$meta = new MetadataValueWrapper(MetadataValueWrapper::TYPE_STRING_LIST);
		$this->metadata[$key] = $meta->setValueStringList($values)->setIndexed($index);
		$this->updated = true;

		return $this;
	}

	public function setIntList(string $key, array $values, bool $index = false): IFilesMetadata {
		$valueWrapper = new MetadataValueWrapper(MetadataValueWrapper::TYPE_STRING_LIST);
		$this->metadata[$key] = $valueWrapper->setValueIntList($values)->setIndexed($index);
		$this->updated = true;

		return $this;
	}

	public function unset(string $key): IFilesMetadata {
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
	 * @return MetadataValueWrapper
	 * @throws FilesMetadataNotFoundException
	 */
	public function getValueWrapper(string $key): MetadataValueWrapper {
		if (!$this->hasKey($key)) {
			throw new FilesMetadataNotFoundException();
		}

		return $this->metadata[$key];
	}


	public function jsonSerialize(): array {
		$data = [];
		foreach ($this->metadata as $metaKey => $metaValueWrapper) {
			$data[$metaKey] = $metaValueWrapper->jsonSerialize();
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
}
