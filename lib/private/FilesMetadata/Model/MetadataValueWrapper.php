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

	public function __construct(string $type = '') {
		$this->type = $type;
	}

	public function getType(): string {
		return $this->type;
	}

	public function isType(string $type): bool {
		return (strtolower($type) === strtolower($this->type));
	}

	public function assertType(string $type): self {
		if (!$this->isType($type)) {
			throw new FilesMetadataTypeException('type is \'' . $this->getType() . '\', expecting \'' . $type . '\'');
		}

		return $this;
	}

	public function setValueString(string $value): self {
		$this->assertType(self::TYPE_STRING);
		$this->value = $value;

		return $this;
	}

	public function setValueInt(int $value): self {
		$this->assertType(self::TYPE_INT);
		$this->value = $value;

		return $this;
	}

	public function setValueFloat(float $value): self {
		$this->assertType(self::TYPE_FLOAT);
		$this->value = $value;

		return $this;
	}

	public function setValueBool(bool $value): self {
		$this->assertType(self::TYPE_BOOL);
		$this->value = $value;


		return $this;
	}

	public function setValueArray(array $value): self {
		$this->assertType(self::TYPE_ARRAY);
		$this->value = $value;

		return $this;
	}

	public function setValueStringList(array $value): self {
		$this->assertType(self::TYPE_STRING_LIST);
		// TODO confirm value is an array or string ?
		$this->value = $value;

		return $this;
	}

	public function setValueIntList(array $value): self {
		$this->assertType(self::TYPE_INT_LIST);
		// TODO confirm value is an array of int ?
		$this->value = $value;

		return $this;
	}


	public function getValueString(): string {
		$this->assertType(self::TYPE_STRING);
		if ($this->value === null) {
			throw new FilesMetadataNotFoundException('value is not set');
		}

		return (string)$this->value;
	}

	public function getValueInt(): int {
		$this->assertType(self::TYPE_INT);
		if ($this->value === null) {
			throw new FilesMetadataNotFoundException('value is not set');
		}

		return (int)$this->value;
	}

	public function getValueFloat(): float {
		$this->assertType(self::TYPE_FLOAT);
		if ($this->value === null) {
			throw new FilesMetadataNotFoundException('value is not set');
		}

		return (float)$this->value;
	}

	public function getValueBool(): bool {
		$this->assertType(self::TYPE_BOOL);
		if ($this->value === null) {
			throw new FilesMetadataNotFoundException('value is not set');
		}

		return (bool)$this->value;
	}

	public function getValueArray(): array {
		$this->assertType(self::TYPE_ARRAY);
		if ($this->value === null) {
			throw new FilesMetadataNotFoundException('value is not set');
		}

		return (array)$this->value;
	}

	public function getValueStringList(): array {
		$this->assertType(self::TYPE_STRING_LIST);
		if ($this->value === null) {
			throw new FilesMetadataNotFoundException('value is not set');
		}

		return (array)$this->value;
	}

	public function getValueIntList(): array {
		$this->assertType(self::TYPE_INT_LIST);
		if ($this->value === null) {
			throw new FilesMetadataNotFoundException('value is not set');
		}

		return (array)$this->value;
	}

	public function getValueAny(): mixed {
		if ($this->value === null) {
			throw new FilesMetadataNotFoundException('value is not set');
		}

		return $this->value;
	}

	public function getEtag(): string {
		return $this->etag;
	}

	public function setEtag(string $etag): self {
		$this->etag = $etag;
		return $this;
	}

	public function setIndexed(bool $indexed): self {
		$this->indexed = $indexed;

		return $this;
	}

	public function isIndexed(): bool {
		return $this->indexed;
	}

	public function setEditPermission(int $permission): self {
		$this->editPermission = $permission;

		return $this;
	}

	public function getEditPermission(): int {
		return $this->editPermission;
	}

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
