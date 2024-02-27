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

namespace OCP\FilesMetadata\Model;

use JsonSerializable;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\Exceptions\FilesMetadataTypeException;

/**
 * Model that represent metadata linked to a specific file.
 *
 * Example of json stored in the database
 * {
 *   "mymeta": {
 *     "value": "this is a test",
 *     "type": "string",
 *     "indexed": false,
 *     "editPermission": 1
 *   },
 *   "myapp-anothermeta": {
 *     "value": 42,
 *     "type": "int",
 *     "indexed": true,
 *     "editPermission": 0
 *   }
 * }
 *
 * @see IMetadataValueWrapper
 * @since 28.0.0
 */
interface IFilesMetadata extends JsonSerializable {
	/**
	 * returns the file id linked to this metadata
	 *
	 * @return int related file id
	 * @since 28.0.0
	 */
	public function getFileId(): int;

	/**
	 * returns last time metadata were updated in the database
	 *
	 * @return int timestamp
	 * @since 28.0.0
	 */
	public function lastUpdateTimestamp(): int;

	/**
	 * returns the token known at the time the metadata were extracted from database
	 *
	 * @return string token
	 * @since 28.0.0
	 */
	public function getSyncToken(): string;

	/**
	 * returns all current metadata keys
	 *
	 * @return string[] list of keys
	 * @since 28.0.0
	 */
	public function getKeys(): array;

	/**
	 * returns true if search metadata key exists
	 *
	 * @param string $needle metadata key to search
	 *
	 * @return bool TRUE if key exist
	 * @since 28.0.0
	 */
	public function hasKey(string $needle): bool;

	/**
	 * return the list of metadata keys set as indexed
	 *
	 * @return string[] list of indexes
	 * @since 28.0.0
	 */
	public function getIndexes(): array;

	/**
	 * returns true if key exists and is set as indexed
	 *
	 * @param string $key metadata key
	 *
	 * @return bool
	 * @since 28.0.0
	 */
	public function isIndex(string $key): bool;

	/**
	 * set remote edit permission
	 * (Webdav PROPPATCH)
	 *
	 * @param string $key metadata key
	 * @param int $permission remote edit permission
	 *
	 * @since 28.0.0
	 */
	public function setEditPermission(string $key, int $permission): void;

	/**
	 * returns remote edit permission
	 * (Webdav PROPPATCH)
	 *
	 * @param string $key metadata key
	 *
	 * @return int
	 * @since 28.0.0
	 */
	public function getEditPermission(string $key): int;

	/**
	 * returns string value for a metadata key
	 *
	 * @param string $key metadata key
	 *
	 * @return string metadata value
	 * @throws FilesMetadataNotFoundException
	 * @throws FilesMetadataTypeException
	 * @since 28.0.0
	 */
	public function getString(string $key): string;

	/**
	 * returns int value for a metadata key
	 *
	 * @param string $key metadata key
	 *
	 * @return int metadata value
	 * @throws FilesMetadataNotFoundException
	 * @throws FilesMetadataTypeException
	 * @since 28.0.0
	 */
	public function getInt(string $key): int;

	/**
	 * returns float value for a metadata key
	 *
	 * @param string $key metadata key
	 *
	 * @return float metadata value
	 * @throws FilesMetadataNotFoundException
	 * @throws FilesMetadataTypeException
	 * @since 28.0.0
	 */
	public function getFloat(string $key): float;

	/**
	 * returns bool value for a metadata key
	 *
	 * @param string $key metadata key
	 *
	 * @return bool metadata value
	 * @throws FilesMetadataNotFoundException
	 * @throws FilesMetadataTypeException
	 * @since 28.0.0
	 */
	public function getBool(string $key): bool;

	/**
	 * returns array for a metadata key
	 *
	 * @param string $key metadata key
	 *
	 * @return array metadata value
	 * @throws FilesMetadataNotFoundException
	 * @throws FilesMetadataTypeException
	 * @since 28.0.0
	 */
	public function getArray(string $key): array;

	/**
	 * returns string[] value for a metadata key
	 *
	 * @param string $key metadata key
	 *
	 * @return string[] metadata value
	 * @throws FilesMetadataNotFoundException
	 * @throws FilesMetadataTypeException
	 * @since 28.0.0
	 */
	public function getStringList(string $key): array;

	/**
	 * returns int[] value for a metadata key
	 *
	 * @param string $key metadata key
	 *
	 * @return int[] metadata value
	 * @throws FilesMetadataNotFoundException
	 * @throws FilesMetadataTypeException
	 * @since 28.0.0
	 */
	public function getIntList(string $key): array;

	/**
	 * returns the value type of the metadata (string, int, ...)
	 *
	 * @param string $key metadata key
	 *
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
	public function getType(string $key): string;

	/**
	 * set a metadata key/value pair for string value
	 *
	 * @param string $key metadata key
	 * @param string $value metadata value
	 * @param bool $index set TRUE if value must be indexed
	 *
	 * @return self
	 * @since 28.0.0
	 */
	public function setString(string $key, string $value, bool $index = false): self;

	/**
	 * set a metadata key/value pair for int value
	 *
	 * @param string $key metadata key
	 * @param int $value metadata value
	 * @param bool $index set TRUE if value must be indexed
	 *
	 * @return self
	 * @since 28.0.0
	 */
	public function setInt(string $key, int $value, bool $index = false): self;

	/**
	 * set a metadata key/value pair for float value
	 *
	 * @param string $key metadata key
	 * @param float $value metadata value
	 *
	 * @return self
	 * @since 28.0.0
	 */
	public function setFloat(string $key, float $value): self;

	/**
	 * set a metadata key/value pair for bool value
	 *
	 * @param string $key metadata key
	 * @param bool $value metadata value
	 * @param bool $index set TRUE if value must be indexed
	 *
	 * @return self
	 * @since 28.0.0
	 */
	public function setBool(string $key, bool $value, bool $index = false): self;

	/**
	 * set a metadata key/value pair for array
	 *
	 * @param string $key metadata key
	 * @param array $value metadata value
	 *
	 * @return self
	 * @since 28.0.0
	 */
	public function setArray(string $key, array $value): self;

	/**
	 * set a metadata key/value pair for list of string
	 *
	 * @param string $key metadata key
	 * @param string[] $value metadata value
	 * @param bool $index set TRUE if each values from the list must be indexed
	 *
	 * @return self
	 * @since 28.0.0
	 */
	public function setStringList(string $key, array $value, bool $index = false): self;

	/**
	 * set a metadata key/value pair for list of int
	 *
	 * @param string $key metadata key
	 * @param int[] $value metadata value
	 * @param bool $index set TRUE if each values from the list must be indexed
	 *
	 * @return self
	 * @since 28.0.0
	 */
	public function setIntList(string $key, array $value, bool $index = false): self;

	/**
	 * unset a metadata
	 *
	 * @param string $key metadata key
	 *
	 * @return self
	 * @since 28.0.0
	 */
	public function unset(string $key): self;

	/**
	 * unset metadata with key starting with prefix
	 *
	 * @param string $keyPrefix metadata key prefix
	 *
	 * @return self
	 * @since 28.0.0
	 */
	public function removeStartsWith(string $keyPrefix): self;

	/**
	 * returns true if object have been updated since last import
	 *
	 * @return bool TRUE if metadata have been modified
	 * @since 28.0.0
	 */
	public function updated(): bool;

	/**
	 * returns metadata in a simple array with METADATA_KEY => METADATA_VALUE
	 *
	 * @return array metadata
	 * @since 28.0.0
	 */
	public function asArray(): array;

	/**
	 * deserialize the object from a json
	 *
	 * @param array $data serialized version of the object
	 *
	 * @return self
	 * @see jsonSerialize
	 * @since 28.0.0
	 */
	public function import(array $data): self;
}
