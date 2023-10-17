<?php

declare(strict_types=1);

namespace OC\FilesMetadata\Model;

use JsonSerializable;
use OCP\FilesMetadata\Exceptions\FilesMetadataNotFoundException;
use OCP\FilesMetadata\Exceptions\FilesMetadataTypeException;

/**
 * This is a small model to wrap values and generate a proper json including
 *  - metadata value (any type set as public const self::TYPE_*)
 *  - type (string) of the stored value.
 *  - index status
 *
 * Few example on the json returned when object is serialized for database storage:
 * {
 *   "value": "this is a test",
 *   "type": "string",
 *   "indexed": false
 * }
 *
 * {
 *   "value": 42,
 *   "type": "int",
 *   "indexed": true
 * }
 *
 */
class MetadataValueWrapper implements JsonSerializable {
	public const TYPE_STRING = 'string';
	public const TYPE_INT = 'int';
	public const TYPE_FLOAT = 'float';
	public const TYPE_BOOL = 'bool';
	public const TYPE_ARRAY = 'array';
	public const TYPE_STRING_LIST = 'string[]';
	public const TYPE_INT_LIST = 'int[]';

	private string $type;
	/** @var string|int|float|bool|array|string[]|int[] */
	private mixed $value = null;
	private bool $indexed = false;

	public function __construct(string $type = '') {
		$this->type = $type;
	}

	public function setType(string $type): self {
		$this->type = $type;
		return $this;
	}

	public function getType(): string {
		return $this->type;
	}

	public function isType(string $type): bool {
		return (strtolower($type) === strtolower($this->type));
	}

	/**
	 * confirm stored value exists and is typed as requested
	 * @param string $type
	 *
	 * @return $this
	 * @throws FilesMetadataTypeException
	 */
	public function confirmType(string $type): self {
		if (!$this->isType($type)) {
			throw new FilesMetadataTypeException('type is \'' . $this->getType() . '\', expecting \'' . $type . '\'');
		}

		return $this;
	}

	/**
	 * @param string $value
	 *
	 * @return $this
	 */
	public function setValueString(string $value): self {
		if ($this->isType(self::TYPE_STRING)) {
			$this->value = $value;
		}

		return $this;
	}

	/**
	 * @param int $value
	 *
	 * @return $this
	 */
	public function setValueInt(int $value): self {
		if ($this->isType(self::TYPE_INT)) {
			$this->value = $value;
		}

		return $this;
	}

	/**
	 * @param float $value
	 *
	 * @return $this
	 */
	public function setValueFloat(float $value): self {
		if ($this->isType(self::TYPE_FLOAT)) {
			$this->value = $value;
		}

		return $this;
	}

	/**
	 * @param bool $value
	 *
	 * @return $this
	 */
	public function setValueBool(bool $value): self {
		if ($this->isType(self::TYPE_BOOL)) {
			$this->value = $value;
		}

		return $this;
	}

	/**
	 * @param array $value
	 *
	 * @return $this
	 */
	public function setValueArray(array $value): self {
		if ($this->isType(self::TYPE_ARRAY)) {
			$this->value = $value;
		}

		return $this;
	}

	/**
	 * @param string[] $value
	 *
	 * @return $this
	 */
	public function setValueStringList(array $value): self {
		if ($this->isType(self::TYPE_STRING_LIST)) {
			// TODO confirm value is an array or string ?
			$this->value = $value;
		}

		return $this;
	}

	/**
	 * @param int[] $value
	 *
	 * @return $this
	 */
	public function setValueIntList(array $value): self {
		if ($this->isType(self::TYPE_INT_LIST)) {
			// TODO confirm value is an array of int ?
			$this->value = $value;
		}

		return $this;
	}


	/**
	 * @return string
	 * @throws FilesMetadataTypeException
	 * @throws FilesMetadataNotFoundException
	 */
	public function getValueString(): string {
		$this->confirmType(self::TYPE_STRING);
		if (null === $this->value) {
			throw new FilesMetadataNotFoundException('value is not set');
		}

		return (string) $this->value;
	}

	/**
	 * @return int
	 * @throws FilesMetadataTypeException
	 * @throws FilesMetadataNotFoundException
	 */
	public function getValueInt(): int {
		$this->confirmType(self::TYPE_INT);
		if (null === $this->value) {
			throw new FilesMetadataNotFoundException('value is not set');
		}

		return (int) $this->value;
	}

	/**
	 * @return float
	 * @throws FilesMetadataTypeException
	 * @throws FilesMetadataNotFoundException
	 */
	public function getValueFloat(): float {
		$this->confirmType(self::TYPE_FLOAT);
		if (null === $this->value) {
			throw new FilesMetadataNotFoundException('value is not set');
		}

		return (float) $this->value;
	}

	/**
	 * @return bool
	 * @throws FilesMetadataTypeException
	 * @throws FilesMetadataNotFoundException
	 */
	public function getValueBool(): bool {
		$this->confirmType(self::TYPE_BOOL);
		if (null === $this->value) {
			throw new FilesMetadataNotFoundException('value is not set');
		}

		return (bool) $this->value;
	}

	/**
	 * @return array
	 * @throws FilesMetadataTypeException
	 * @throws FilesMetadataNotFoundException
	 */
	public function getValueArray(): array {
		$this->confirmType(self::TYPE_ARRAY);
		if (null === $this->value) {
			throw new FilesMetadataNotFoundException('value is not set');
		}

		return (array) $this->value;
	}

	/**
	 * @return string[]
	 * @throws FilesMetadataTypeException
	 * @throws FilesMetadataNotFoundException
	 */
	public function getValueStringList(): array {
		$this->confirmType(self::TYPE_STRING_LIST);
		if (null === $this->value) {
			throw new FilesMetadataNotFoundException('value is not set');
		}

		return (array) $this->value;
	}

	/**
	 * @return array
	 * @throws FilesMetadataTypeException
	 * @throws FilesMetadataNotFoundException
	 */
	public function getValueIntList(): array {
		$this->confirmType(self::TYPE_INT_LIST);
		if (null === $this->value) {
			throw new FilesMetadataNotFoundException('value is not set');
		}

		return (array) $this->value;
	}


	public function setIndexed(bool $indexed): self {
		$this->indexed = $indexed;

		return $this;
	}

	public function isIndexed(): bool {
		return $this->indexed;
	}

	public function import(array $data): self {
		$this->value = $data['value'] ?? null;
		$this->setType($data['type'] ?? '');
		$this->setIndexed($data['indexed'] ?? false);

		return $this;
	}

	public function jsonSerialize(): array {
		return [
			'value' => $this->value,
			'type' => $this->getType(),
			'indexed' => $this->isIndexed()
		];
	}
}
