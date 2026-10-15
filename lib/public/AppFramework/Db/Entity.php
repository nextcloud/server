<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCP\AppFramework\Db;

use OCP\DB\Schema\ColumnType;
use OCP\DB\Types;
use function lcfirst;
use function substr;

/**
 * @method int getId()
 * @method void setId(int $id)
 * @since 7.0.0
 * @psalm-consistent-constructor
 */
abstract class Entity {
	/** @var int $id */
	public $id;
	/** @var array<string, true> $_updatedFields */
	private array $_updatedFields = [];
	/** @var array<string, ColumnType> $_fieldTypes */
	protected array $_fieldTypes = ['id' => ColumnType::Integer];

	/**
	 * Simple alternative constructor for building entities from a request
	 * @param array $params the array which was obtained via $this->params('key')
	 *                      in the controller
	 * @since 7.0.0
	 */
	public static function fromParams(array $params): static {
		$instance = new static();

		foreach ($params as $key => $value) {
			$method = 'set' . ucfirst($key);
			$instance->$method($value);
		}

		return $instance;
	}

	/**
	 * Maps the keys of the row array to the attributes
	 * @param array $row the row to map onto the entity
	 * @since 7.0.0
	 */
	public static function fromRow(array $row): static {
		$instance = new static();

		foreach ($row as $key => $value) {
			$prop = $instance->columnToProperty($key);
			$instance->setter($prop, [$value]);
		}

		$instance->resetUpdatedFields();

		return $instance;
	}

	/**
	 * @return array<string, Types::*> with attribute and type
	 * @since 7.0.0
	 */
	public function getFieldTypes(): array {
		return array_map(fn (ColumnType $type) => $type->value, $this->_fieldTypes);
	}

	/**
	 * Marks the entity as clean needed for setting the id after the insertion
	 * @since 7.0.0
	 */
	public function resetUpdatedFields(): void {
		$this->_updatedFields = [];
	}

	/**
	 * Generic setter for properties
	 *
	 * @throws \InvalidArgumentException
	 * @since 7.0.0
	 *
	 */
	protected function setter(string $name, array $args): void {
		// setters should only work for existing attributes
		if (!property_exists($this, $name)) {
			throw new \BadFunctionCallException($name . ' is not a valid attribute');
		}

		if ($args[0] === $this->$name) {
			return;
		}
		$this->markFieldUpdated($name);

		// if type definition exists, cast to correct type
		if ($args[0] !== null && array_key_exists($name, $this->_fieldTypes)) {
			$type = $this->_fieldTypes[$name];
			if ($type === ColumnType::Blob) {
				// (B)LOB is treated as string when we read from the DB
				if (is_resource($args[0])) {
					$args[0] = stream_get_contents($args[0]);
				}
				$type = ColumnType::String;
			}

			switch ($type) {
				case ColumnType::Bigint:
				case ColumnType::Smallint:
					settype($args[0], Types::INTEGER);
					break;
				case ColumnType::Binary:
				case ColumnType::Decimal:
				case ColumnType::Text:
					settype($args[0], Types::STRING);
					break;
				case ColumnType::Time:
				case ColumnType::Date:
				case ColumnType::Datetime:
				case ColumnType::DatetimeTz:
					if (!$args[0] instanceof \DateTime) {
						$args[0] = new \DateTime($args[0]);
					}
					break;
				case ColumnType::TimeImmutable:
				case ColumnType::DateImmutable:
				case ColumnType::DatetimeImmutable:
				case ColumnType::DatetimeTzImmutable:
					if (!$args[0] instanceof \DateTimeImmutable) {
						$args[0] = new \DateTimeImmutable($args[0]);
					}
					break;
				case ColumnType::Json:
					if (!is_array($args[0])) {
						$args[0] = json_decode($args[0], true);
					}
					break;
				default:
					settype($args[0], $type->value);
			}
		}
		$this->$name = $args[0];

	}

	/**
	 * Generic getter for properties
	 * @since 7.0.0
	 */
	protected function getter(string $name): mixed {
		// getters should only work for existing attributes
		if (property_exists($this, $name)) {
			return $this->$name;
		} else {
			throw new \BadFunctionCallException($name
				. ' is not a valid attribute');
		}
	}

	/**
	 * Each time a setter is called, push the part after set
	 * into an array: for instance setId will save Id in the
	 * updated fields array so it can be easily used to create the
	 * getter method
	 * @since 7.0.0
	 */
	public function __call(string $methodName, array $args) {
		if (str_starts_with($methodName, 'set')) {
			$this->setter(lcfirst(substr($methodName, 3)), $args);
		} elseif (str_starts_with($methodName, 'get')) {
			return $this->getter(lcfirst(substr($methodName, 3)));
		} elseif ($this->isGetterForBoolProperty($methodName)) {
			return $this->getter(lcfirst(substr($methodName, 2)));
		} else {
			throw new \BadFunctionCallException($methodName
				. ' does not exist');
		}
	}

	/**
	 * @param string $methodName
	 * @return bool
	 * @since 18.0.0
	 */
	protected function isGetterForBoolProperty(string $methodName): bool {
		if (str_starts_with($methodName, 'is')) {
			$fieldName = lcfirst(substr($methodName, 2));
			return isset($this->_fieldTypes[$fieldName]) && str_starts_with($this->_fieldTypes[$fieldName]->value, 'bool');
		}
		return false;
	}

	/**
	 * Mark am attribute as updated
	 * @param string $attribute the name of the attribute
	 * @since 7.0.0
	 */
	protected function markFieldUpdated(string $attribute): void {
		$this->_updatedFields[$attribute] = true;
	}

	/**
	 * Transform a database columnname to a property
	 *
	 * @param string $columnName the name of the column
	 * @return string the property name
	 * @since 7.0.0
	 */
	public function columnToProperty(string $columnName) {
		$parts = explode('_', $columnName);
		$property = '';

		foreach ($parts as $part) {
			if ($property === '') {
				$property = $part;
			} else {
				$property .= ucfirst($part);
			}
		}

		return $property;
	}

	/**
	 * Transform a property to a database column name
	 *
	 * @param string $property the name of the property
	 * @return string the column name
	 * @since 7.0.0
	 */
	public function propertyToColumn(string $property): string {
		$parts = preg_split('/(?=[A-Z])/', $property);

		$column = '';
		foreach ($parts as $part) {
			if ($column === '') {
				$column = $part;
			} else {
				$column .= '_' . lcfirst($part);
			}
		}

		return $column;
	}

	/**
	 * @return array<string, true> array of updated fields for update query
	 * @since 7.0.0
	 */
	public function getUpdatedFields(): array {
		return $this->_updatedFields;
	}

	/**
	 * Adds type information for a field so that it's automatically cast to
	 * that value once its being returned from the database
	 *
	 * @param string $fieldName the name of the attribute
	 * @param Types::*|ColumnType $type the type which will be used to match a cast
	 * @since 31.0.0 Parameter $type is now restricted to {@see Types} constants. The formerly accidentally supported types 'int'|'bool'|'double' are mapped to Types::INTEGER|Types::BOOLEAN|Types::FLOAT accordingly.
	 * @since 35.0.0 Parameter $type now prefers using one of the {@see ColumnType} enum values.
	 * @since 7.0.0
	 */
	protected function addType(string $fieldName, string|ColumnType $type): void {
		/** @psalm-suppress TypeDoesNotContainType */
		if (in_array($type, ['bool', 'double', 'int', 'array', 'object'], true)) {
			// Mapping legacy strings to the actual types
			$type = match ($type) {
				'int' => ColumnType::Integer,
				'bool' => ColumnType::Boolean,
				'double' => ColumnType::Float,
				'array',
				'object' => ColumnType::String,
			};
		}

		if (is_string($type)) {
			$type = ColumnType::from($type);
		}

		$this->_fieldTypes[$fieldName] = $type;
	}

	/**
	 * Slugify the value of a given attribute
	 * Warning: This doesn't result in a unique value
	 *
	 * @param string $attributeName the name of the attribute, which value should be slugified
	 * @return string slugified value
	 * @since 7.0.0
	 * @deprecated 24.0.0
	 */
	public function slugify(string $attributeName): string {
		// toSlug should only work for existing attributes
		if (property_exists($this, $attributeName)) {
			$value = $this->$attributeName;
			// replace everything except alphanumeric with a single '-'
			$value = preg_replace('/[^A-Za-z0-9]+/', '-', $value);
			$value = strtolower($value);
			// trim '-'
			return trim($value, '-');
		}

		throw new \BadFunctionCallException($attributeName . ' is not a valid attribute');
	}
}
