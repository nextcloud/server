<?php

declare(strict_types=1);

namespace OCP\FilesMetadata\Model;

use JsonSerializable;

/**
 *
 *
 * Example of json stored in the database
 * {
 *   "myapp:mymeta": {
 *     "value": "this is a test",
 *     "type": "string",
 *     "indexed": false
 *   },
 *   "myapp:anothermeta": {
 *     "value": 42,
 *     "type": "int",
 *     "indexed": true
 *   }
 * }
 *
 *
 * @since 28.0.0
 */
interface IFilesMetadata extends JsonSerializable {
	/**
	 * returns the fileId linked to this metadata
	 *
	 * @return int
	 */
	public function getFileId(): int;

	/**
	 * fill the object with a json
	 *
	 * @param array $data
	 *
	 * @return self
	 * @see jsonSerialize
	 */
	public function import(array $data): self;

	/**
	 * returns true if object have been updated since last import
	 * @return bool
	 */
	public function updated(): bool;
	public function lastUpdateTimestamp(): int;
	public function getSyncToken(): string;
	public function getKeys(): array;
	public function hasKey(string $needle): bool;

	/**
	 * return the list of indexed metadata keys
	 *
	 * @return string[]
	 */
	public function getIndexes(): array;

	public function get(string $key): string;
	public function getInt(string $key): int;
	public function getFloat(string $key): float;
	public function getBool(string $key): bool;
	public function getArray(string $key): array;
	public function getStringList(string $key): array;
	public function getIntList(string $key): array;
	public function getType(string $key): string;
	public function set(string $key, string $value): self;
	public function setInt(string $key, int $value): self;
	public function setFloat(string $key, float $value): self;
	public function setBool(string $key, bool $value): self;
	public function setArray(string $key, array $value): self;
	public function setStringList(string $key, array $value): self;
	public function setIntList(string $key, array $value): self;

}
