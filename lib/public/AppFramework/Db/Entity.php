<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\AppFramework\Db;

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
	/**
	 * @var int
	 */
	public $id;

	private array $_updatedFields = [];
	/** @var array<string, \OCP\DB\Types::*> */
	private array $_fieldTypes = ['id' => 'integer'];

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
	 * @return array<string, \OCP\DB\Types::*> with attribute and type
	 * @since 7.0.0
	 */
	public function getFieldTypes(): array {
		return $this->_fieldTypes;
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
			if ($type === Types::BLOB) {
				// (B)LOB is treated as string when we read from the DB
				if (is_resource($args[0])) {
					$args[0] = stream_get_contents($args[0]);
				}
				$type = Types::STRING;
			}

			switch ($type) {
				case Types::BIGINT:
				case Types::SMALLINT:
					settype($args[0], Types::INTEGER);
					break;
				case Types::BINARY:
				case Types::DECIMAL:
				case Types::TEXT:
					settype($args[0], Types::STRING);
					break;
				case Types::TIME:
				case Types::DATE:
				case Types::DATETIME:
				case Types::DATETIME_TZ:
					if (!$args[0] instanceof \DateTime) {
						$args[0] = new \DateTime($args[0]);
					}
					break;
				case Types::TIME_IMMUTABLE:
				case Types::DATE_IMMUTABLE:
				case Types::DATETIME_IMMUTABLE:
				case Types::DATETIME_TZ_IMMUTABLE:
					if (!$args[0] instanceof \DateTimeImmutable) {
						$args[0] = new \DateTimeImmutable($args[0]);
					}
					break;
				case Types::JSON:
					if (!is_array($args[0])) {
						$args[0] = json_decode($args[0], true);
					}
					break;
				default:
					settype($args[0], $type);
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
			throw new \BadFunctionCallException($name .
				' is not a valid attribute');
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
			throw new \BadFunctionCallException($methodName .
				' does not exist');
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
			return isset($this->_fieldTypes[$fieldName]) && str_starts_with($this->_fieldTypes[$fieldName], 'bool');
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
	 * @return array array of updated fields for update query
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
	 * @param \OCP\DB\Types::* $type the type which will be used to match a cast
	 * @since 31.0.0 Parameter $type is now restricted to {@see \OCP\DB\Types} constants. The formerly accidentally supported types 'int'|'bool'|'double' are mapped to Types::INTEGER|Types::BOOLEAN|Types::FLOAT accordingly.
	 * @since 7.0.0
	 */
	protected function addType(string $fieldName, string $type): void {
		/** @psalm-suppress TypeDoesNotContainType */
		if (in_array($type, ['bool', 'double', 'int', 'array', 'object'], true)) {
			// Mapping legacy strings to the actual types
			$type = match ($type) {
				'int' => Types::INTEGER,
				'bool' => Types::BOOLEAN,
				'double' => Types::FLOAT,
				'array',
				'object' => Types::STRING,
			};
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
