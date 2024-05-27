<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\FilesMetadata\Model;

use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\Exceptions\FilesMetadataTypeException;
use OCP\FilesMetadata\Model\IMetadataValueWrapper;

/**
 * @inheritDoc
 * @see IFilesMetadata
 * @since 28.0.0
 */
class MetadataValueWrapper implements IMetadataValueWrapper {
	private string $type;
	/** @var string|int|float|bool|array|string[]|int[] */
	private mixed $value = null;
	private string $etag = '';
	private bool $indexed = false;
	private int $editPermission = self::EDIT_FORBIDDEN;

	/**
	 * @param string $type value type
	 *
	 * @inheritDoc
	 * @see self::TYPE_INT
	 * @see self::TYPE_FLOAT
	 * @see self::TYPE_BOOL
	 * @see self::TYPE_ARRAY
	 * @see self::TYPE_STRING_LIST
	 * @see self::TYPE_INT_LIST
	 * @see self::TYPE_STRING
	 * @since 28.0.0
	 */
	public function __construct(string $type = '') {
		$this->type = $type;
	}

	/**
	 * @inheritDoc
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
	public function getType(): string {
		return $this->type;
	}

	/**
	 * @param string $type value type
	 *
	 * @inheritDoc
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
	public function isType(string $type): bool {
		return (strtolower($type) === strtolower($this->type));
	}

	/**
	 * @param string $type value type
	 *
	 * @inheritDoc
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
	public function assertType(string $type): self {
		if (!$this->isType($type)) {
			throw new FilesMetadataTypeException('type is \'' . $this->getType() . '\', expecting \'' . $type . '\'');
		}

		return $this;
	}

	/**
	 * @param string $value string to be set as value
	 *
	 * @inheritDoc
	 * @return self
	 * @throws FilesMetadataTypeException if wrapper was not set to store a string
	 * @since 28.0.0
	 */
	public function setValueString(string $value): self {
		$this->assertType(self::TYPE_STRING);
		$this->value = $value;

		return $this;
	}

	/**
	 * @param int $value int to be set as value
	 *
	 * @inheritDoc
	 * @return self
	 * @throws FilesMetadataTypeException if wrapper was not set to store an int
	 * @since 28.0.0
	 */
	public function setValueInt(int $value): self {
		$this->assertType(self::TYPE_INT);
		$this->value = $value;

		return $this;
	}

	/**
	 * @param float $value float to be set as value
	 *
	 * @inheritDoc
	 * @return self
	 * @throws FilesMetadataTypeException if wrapper was not set to store a float
	 * @since 28.0.0
	 */
	public function setValueFloat(float $value): self {
		$this->assertType(self::TYPE_FLOAT);
		$this->value = $value;

		return $this;
	}

	/**
	 * @param bool $value bool to be set as value
	 *
	 * @inheritDoc
	 * @return self
	 * @throws FilesMetadataTypeException if wrapper was not set to store a bool
	 * @since 28.0.0
	 */
	public function setValueBool(bool $value): self {
		$this->assertType(self::TYPE_BOOL);
		$this->value = $value;


		return $this;
	}

	/**
	 * @param array $value array to be set as value
	 *
	 * @inheritDoc
	 * @return self
	 * @throws FilesMetadataTypeException if wrapper was not set to store an array
	 * @since 28.0.0
	 */
	public function setValueArray(array $value): self {
		$this->assertType(self::TYPE_ARRAY);
		$this->value = $value;

		return $this;
	}

	/**
	 * @param string[] $value string list to be set as value
	 *
	 * @inheritDoc
	 * @return self
	 * @throws FilesMetadataTypeException if wrapper was not set to store a string list
	 * @since 28.0.0
	 */
	public function setValueStringList(array $value): self {
		$this->assertType(self::TYPE_STRING_LIST);
		// TODO confirm value is an array or string ?
		$this->value = $value;

		return $this;
	}

	/**
	 * @param int[] $value int list to be set as value
	 *
	 * @inheritDoc
	 * @return self
	 * @throws FilesMetadataTypeException if wrapper was not set to store an int list
	 * @since 28.0.0
	 */
	public function setValueIntList(array $value): self {
		$this->assertType(self::TYPE_INT_LIST);
		// TODO confirm value is an array of int ?
		$this->value = $value;

		return $this;
	}


	/**
	 * @inheritDoc
	 * @return string set value
	 * @throws FilesMetadataTypeException if wrapper was not set to store a string
	 * @throws FilesMetadataNotFoundException if value is not set
	 * @since 28.0.0
	 */
	public function getValueString(): string {
		$this->assertType(self::TYPE_STRING);
		if ($this->value === null) {
			throw new FilesMetadataNotFoundException('value is not set');
		}

		return (string)$this->value;
	}

	/**
	 * @inheritDoc
	 * @return int set value
	 * @throws FilesMetadataTypeException if wrapper was not set to store an int
	 * @throws FilesMetadataNotFoundException if value is not set
	 * @since 28.0.0
	 */
	public function getValueInt(): int {
		$this->assertType(self::TYPE_INT);
		if ($this->value === null) {
			throw new FilesMetadataNotFoundException('value is not set');
		}

		return (int)$this->value;
	}

	/**
	 * @inheritDoc
	 * @return float set value
	 * @throws FilesMetadataTypeException if wrapper was not set to store a float
	 * @throws FilesMetadataNotFoundException if value is not set
	 * @since 28.0.0
	 */
	public function getValueFloat(): float {
		$this->assertType(self::TYPE_FLOAT);
		if ($this->value === null) {
			throw new FilesMetadataNotFoundException('value is not set');
		}

		return (float)$this->value;
	}

	/**
	 * @inheritDoc
	 * @return bool set value
	 * @throws FilesMetadataTypeException if wrapper was not set to store a bool
	 * @throws FilesMetadataNotFoundException if value is not set
	 * @since 28.0.0
	 */
	public function getValueBool(): bool {
		$this->assertType(self::TYPE_BOOL);
		if ($this->value === null) {
			throw new FilesMetadataNotFoundException('value is not set');
		}

		return (bool)$this->value;
	}

	/**
	 * @inheritDoc
	 * @return array set value
	 * @throws FilesMetadataTypeException if wrapper was not set to store an array
	 * @throws FilesMetadataNotFoundException if value is not set
	 * @since 28.0.0
	 */
	public function getValueArray(): array {
		$this->assertType(self::TYPE_ARRAY);
		if ($this->value === null) {
			throw new FilesMetadataNotFoundException('value is not set');
		}

		return (array)$this->value;
	}

	/**
	 * @inheritDoc
	 * @return string[] set value
	 * @throws FilesMetadataTypeException if wrapper was not set to store a string list
	 * @throws FilesMetadataNotFoundException if value is not set
	 * @since 28.0.0
	 */
	public function getValueStringList(): array {
		$this->assertType(self::TYPE_STRING_LIST);
		if ($this->value === null) {
			throw new FilesMetadataNotFoundException('value is not set');
		}

		return (array)$this->value;
	}

	/**
	 * @inheritDoc
	 * @return int[] set value
	 * @throws FilesMetadataTypeException if wrapper was not set to store an int list
	 * @throws FilesMetadataNotFoundException if value is not set
	 * @since 28.0.0
	 */
	public function getValueIntList(): array {
		$this->assertType(self::TYPE_INT_LIST);
		if ($this->value === null) {
			throw new FilesMetadataNotFoundException('value is not set');
		}

		return (array)$this->value;
	}

	/**
	 * @inheritDoc
	 * @return string|int|float|bool|array|string[]|int[] set value
	 * @throws FilesMetadataNotFoundException if value is not set
	 * @since 28.0.0
	 */
	public function getValueAny(): mixed {
		if ($this->value === null) {
			throw new FilesMetadataNotFoundException('value is not set');
		}

		return $this->value;
	}

	/**
	 * @inheritDoc
	 * @return string stored etag
	 * @since 29.0.0
	 */
	public function getEtag(): string {
		return $this->etag;
	}

	/**
	 * @param string $etag etag value
	 *
	 * @inheritDoc
	 * @return self
	 * @since 29.0.0
	 */
	public function setEtag(string $etag): self {
		$this->etag = $etag;
		return $this;
	}

	/**
	 * @param bool $indexed TRUE to set the stored value as an indexed value
	 *
	 * @inheritDoc
	 * @return self
	 * @since 28.0.0
	 */
	public function setIndexed(bool $indexed): self {
		$this->indexed = $indexed;

		return $this;
	}

	/**
	 * @inheritDoc
	 * @return bool TRUE if value is an indexed value
	 * @since 28.0.0
	 */
	public function isIndexed(): bool {
		return $this->indexed;
	}

	/**
	 * @param int $permission edit permission
	 *
	 * @inheritDoc
	 * @return self
	 * @since 28.0.0
	 */
	public function setEditPermission(int $permission): self {
		$this->editPermission = $permission;

		return $this;
	}

	/**
	 * @inheritDoc
	 * @return int edit permission
	 * @since 28.0.0
	 */
	public function getEditPermission(): int {
		return $this->editPermission;
	}

	/**
	 * @param array $data serialized version of the object
	 *
	 * @inheritDoc
	 * @return self
	 * @see jsonSerialize
	 * @since 28.0.0
	 */
	public function import(array $data): self {
		$this->value = $data['value'] ?? null;
		$this->type = $data['type'] ?? '';
		$this->setEtag($data['etag'] ?? '');
		$this->setIndexed($data['indexed'] ?? false);
		$this->setEditPermission($data['editPermission'] ?? self::EDIT_FORBIDDEN);
		return $this;
	}

	public function jsonSerialize(bool $emptyValues = false): array {
		return [
			'value' => ($emptyValues) ? null : $this->value,
			'type' => $this->getType(),
			'etag' => $this->getEtag(),
			'indexed' => $this->isIndexed(),
			'editPermission' => $this->getEditPermission()
		];
	}
}
