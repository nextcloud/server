<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\FilesMetadata\Model;

use JsonSerializable;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\Exceptions\FilesMetadataTypeException;

/**
 * Model that store the value of a single metadata.
 * It stores the value, its type and the index status.
 *
 * @see IFilesMetadata
 * @since 28.0.0
 */
interface IMetadataValueWrapper extends JsonSerializable {
	/** @since 28.0.0 */
	public const TYPE_STRING = 'string';
	/** @since 28.0.0 */
	public const TYPE_INT = 'int';
	/** @since 28.0.0 */
	public const TYPE_FLOAT = 'float';
	/** @since 28.0.0 */
	public const TYPE_BOOL = 'bool';
	/** @since 28.0.0 */
	public const TYPE_ARRAY = 'array';
	/** @since 28.0.0 */
	public const TYPE_STRING_LIST = 'string[]';
	/** @since 28.0.0 */
	public const TYPE_INT_LIST = 'int[]';

	/** @since 28.0.0 */
	public const EDIT_FORBIDDEN = 0;
	/** @since 28.0.0 */
	public const EDIT_REQ_OWNERSHIP = 1;
	/** @since 28.0.0 */
	public const EDIT_REQ_WRITE_PERMISSION = 2;
	/** @since 28.0.0 */
	public const EDIT_REQ_READ_PERMISSION = 3;


	/**
	 * Unless a call of import() to deserialize an object is expected, a valid value type is needed here.
	 *
	 * @param string $type value type
	 *
	 * @see self::TYPE_INT
	 * @see self::TYPE_FLOAT
	 * @see self::TYPE_BOOL
	 * @see self::TYPE_ARRAY
	 * @see self::TYPE_STRING_LIST
	 * @see self::TYPE_INT_LIST
	 * @see self::TYPE_STRING
	 * @since 28.0.0
	 */
	public function __construct(string $type);

	/**
	 * returns the value type
	 *
	 * @return string value type
	 * @see self::TYPE_INT
	 * @see self::TYPE_FLOAT
	 * @see self::TYPE_BOOL
	 * @see self::TYPE_ARRAY
	 * @see self::TYPE_STRING_LIST
	 * @see self::TYPE_INT_LIST
	 * @see self::TYPE_STRING
	 * @since 28.0.0
	 */
	public function getType(): string;

	/**
	 * returns if the set value type is the one expected
	 *
	 * @param string $type value type
	 *
	 * @return bool
	 * @see self::TYPE_INT
	 * @see self::TYPE_FLOAT
	 * @see self::TYPE_BOOL
	 * @see self::TYPE_ARRAY
	 * @see self::TYPE_STRING_LIST
	 * @see self::TYPE_INT_LIST
	 * @see self::TYPE_STRING
	 * @since 28.0.0
	 */
	public function isType(string $type): bool;

	/**
	 * throws an exception if the type is not correctly set
	 *
	 * @param string $type value type
	 *
	 * @return self
	 * @throws FilesMetadataTypeException if type cannot be confirmed
	 * @see self::TYPE_INT
	 * @see self::TYPE_BOOL
	 * @see self::TYPE_ARRAY
	 * @see self::TYPE_STRING_LIST
	 * @see self::TYPE_INT_LIST
	 * @see self::TYPE_STRING
	 * @see self::TYPE_FLOAT
	 * @since 28.0.0
	 */
	public function assertType(string $type): self;

	/**
	 * set a string value
	 *
	 * @param string $value string to be set as value
	 *
	 * @return self
	 * @throws FilesMetadataTypeException if wrapper was not set to store a string
	 * @since 28.0.0
	 */
	public function setValueString(string $value): self;

	/**
	 * set a int value
	 *
	 * @param int $value int to be set as value
	 *
	 * @return self
	 * @throws FilesMetadataTypeException if wrapper was not set to store an int
	 * @since 28.0.0
	 */
	public function setValueInt(int $value): self;

	/**
	 * set a float value
	 *
	 * @param float $value float to be set as value
	 *
	 * @return self
	 * @throws FilesMetadataTypeException if wrapper was not set to store a float
	 * @since 28.0.0
	 */
	public function setValueFloat(float $value): self;

	/**
	 * set a bool value
	 *
	 * @param bool $value bool to be set as value
	 *
	 * @return self
	 * @throws FilesMetadataTypeException if wrapper was not set to store a bool
	 * @since 28.0.0
	 */
	public function setValueBool(bool $value): self;

	/**
	 * set an array value
	 *
	 * @param array $value array to be set as value
	 *
	 * @return self
	 * @throws FilesMetadataTypeException if wrapper was not set to store an array
	 * @since 28.0.0
	 */
	public function setValueArray(array $value): self;

	/**
	 * set a string list value
	 *
	 * @param string[] $value string list to be set as value
	 *
	 * @return self
	 * @throws FilesMetadataTypeException if wrapper was not set to store a string list
	 * @since 28.0.0
	 */
	public function setValueStringList(array $value): self;

	/**
	 * set an int list value
	 *
	 * @param int[] $value int list to be set as value
	 *
	 * @return self
	 * @throws FilesMetadataTypeException if wrapper was not set to store an int list
	 * @since 28.0.0
	 */
	public function setValueIntList(array $value): self;


	/**
	 * get stored value
	 *
	 * @return string set value
	 * @throws FilesMetadataTypeException if wrapper was not set to store a string
	 * @throws FilesMetadataNotFoundException if value is not set
	 * @since 28.0.0
	 */
	public function getValueString(): string;

	/**
	 * get stored value
	 *
	 * @return int set value
	 * @throws FilesMetadataTypeException if wrapper was not set to store an int
	 * @throws FilesMetadataNotFoundException if value is not set
	 * @since 28.0.0
	 */
	public function getValueInt(): int;

	/**
	 * get stored value
	 *
	 * @return float set value
	 * @throws FilesMetadataTypeException if wrapper was not set to store a float
	 * @throws FilesMetadataNotFoundException if value is not set
	 * @since 28.0.0
	 */
	public function getValueFloat(): float;

	/**
	 * get stored value
	 *
	 * @return bool set value
	 * @throws FilesMetadataTypeException if wrapper was not set to store a bool
	 * @throws FilesMetadataNotFoundException if value is not set
	 * @since 28.0.0
	 */
	public function getValueBool(): bool;

	/**
	 * get stored value
	 *
	 * @return array set value
	 * @throws FilesMetadataTypeException if wrapper was not set to store an array
	 * @throws FilesMetadataNotFoundException if value is not set
	 * @since 28.0.0
	 */
	public function getValueArray(): array;

	/**
	 * get stored value
	 *
	 * @return string[] set value
	 * @throws FilesMetadataTypeException if wrapper was not set to store a string list
	 * @throws FilesMetadataNotFoundException if value is not set
	 * @since 28.0.0
	 */
	public function getValueStringList(): array;

	/**
	 * get stored value
	 *
	 * @return int[] set value
	 * @throws FilesMetadataTypeException if wrapper was not set to store an int list
	 * @throws FilesMetadataNotFoundException if value is not set
	 * @since 28.0.0
	 */
	public function getValueIntList(): array;

	/**
	 * get stored value
	 *
	 * @return string|int|float|bool|array|string[]|int[] set value
	 * @throws FilesMetadataNotFoundException if value is not set
	 * @since 28.0.0
	 */
	public function getValueAny(): mixed;

	/**
	 * get stored etag value
	 *
	 * @return string stored etag
	 * @since 29.0.0
	 */
	public function getEtag(): string;

	/**
	 * set etag value
	 *
	 * @param string $etag etag value
	 *
	 * @return self
	 * @since 29.0.0
	 */
	public function setEtag(string $etag): self;

	/**
	 * @param bool $indexed TRUE to set the stored value as an indexed value
	 *
	 * @return self
	 * @since 28.0.0
	 */
	public function setIndexed(bool $indexed): self;

	/**
	 * returns if value is an indexed value
	 *
	 * @return bool TRUE if value is an indexed value
	 * @since 28.0.0
	 */
	public function isIndexed(): bool;

	/**
	 * set remote edit permission
	 * (Webdav PROPPATCH)
	 *
	 * @param int $permission edit permission
	 *
	 * @return self
	 * @since 28.0.0
	 */
	public function setEditPermission(int $permission): self;

	/**
	 * get remote edit permission
	 * (Webdav PROPPATCH)
	 *
	 * @return int edit permission
	 * @since 28.0.0
	 */
	public function getEditPermission(): int;

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
