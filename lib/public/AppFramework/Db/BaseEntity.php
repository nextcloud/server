<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\AppFramework\Db;

/**
 * Base entity class that does not require a id column
 *
 * @since 19.0.0
 */
class BaseEntity {

	private $_updatedFields = [];
	private $_fieldTypes = [];


	/**
	 * Simple alternative constructor for building entities from a request
	 * @param array $params the array which was obtained via $this->params('key')
	 * in the controller
	 * @return BaseEntity
	 * @since 7.0.0
	 */
	public static function fromParams(array $params) {
		$instance = new static();

		foreach($params as $key => $value) {
			$setter = 'set' . ucfirst($key);
			$value = $instance->convertToType($key, $value);
			$instance->$setter($value);
		}

		return $instance;
	}


	/**
	 * Maps the keys of the row array to the attributes
	 * @param array $row the row to map onto the entity
	 * @since 7.0.0
	 * @return BaseEntity
	 */
	public static function fromRow(array $row){
		$instance = new static();

		foreach($row as $key => $value){
			$key = $instance->columnToProperty($key);
			$setter = 'set' . ucfirst($key);
			$value = $instance->convertToType($key, $value);
			$instance->$setter($value);
		}

		$instance->resetUpdatedFields();

		return $instance;
	}

	/**
	 * @return array with attribute and type
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
	 * @since 7.0.0
	 */
	protected function setter($name, $args) {
		// setters should only work for existing attributes
		if(property_exists($this, $name)){
			if($this->$name === $args[0]) {
				return;
			}
			$this->markFieldUpdated($name);

			// if type definition exists, cast to correct type
			$value = $this->convertToType($name, $args[0]);
			$this->$name = $value;

		} else {
			throw new \BadFunctionCallException($name .
				' is not a valid attribute');
		}
	}

	/**
	 * Generic getter for properties
	 * @since 7.0.0
	 */
	protected function getter($name) {
		// getters should only work for existing attributes
		if(property_exists($this, $name)){
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
	public function __call($methodName, $args){
		if(strpos($methodName, 'set') === 0){
			$this->setter(lcfirst(substr($methodName, 3)), $args);
		} elseif(strpos($methodName, 'get') === 0) {
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
		if (strpos($methodName, 'is') === 0) {
			$fieldName = lcfirst(substr($methodName, 2));
			return isset($this->_fieldTypes[$fieldName]) && strpos($this->_fieldTypes[$fieldName], 'bool') === 0;
		}
		return false;
	}


	/**
	 * Mark am attribute as updated
	 * @param string $attribute the name of the attribute
	 * @since 7.0.0
	 */
	protected function markFieldUpdated($attribute){
		$this->_updatedFields[$attribute] = true;
	}


	/**
	 * Transform a database columnname to a property
	 * @param string $columnName the name of the column
	 * @return string the property name
	 * @since 7.0.0
	 */
	public function columnToProperty($columnName){
		$parts = explode('_', $columnName);
		$property = null;

		foreach($parts as $part){
			if($property === null){
				$property = $part;
			} else {
				$property .= ucfirst($part);
			}
		}

		return $property;
	}


	/**
	 * Transform a property to a database column name
	 * @param string $property the name of the property
	 * @return string the column name
	 * @since 7.0.0
	 */
	public function propertyToColumn($property){
		$parts = preg_split('/(?=[A-Z])/', $property);
		$column = null;

		foreach($parts as $part){
			if($column === null){
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
	public function getUpdatedFields(){
		return $this->_updatedFields;
	}


	/**
	 * Adds type information for a field so that its automatically casted to
	 * that value once its being returned from the database
	 * @param string $fieldName the name of the attribute
	 * @param string $type the type which will be used to call settype()
	 * @since 7.0.0
	 */
	protected function addType(string $fieldName, string $type){
		$this->_fieldTypes[$fieldName] = $type;
	}


	/**
	 * Slugify the value of a given attribute
	 * Warning: This doesn't result in a unique value
	 * @param string $attributeName the name of the attribute, which value should be slugified
	 * @return string slugified value
	 * @since 7.0.0
	 */
	public function slugify(string $attributeName): string {
		// toSlug should only work for existing attributes
		if(property_exists($this, $attributeName)){
			$value = $this->$attributeName;
			// replace everything except alphanumeric with a single '-'
			$value = preg_replace('/[^A-Za-z0-9]+/', '-', $value);
			$value = strtolower($value);
			// trim '-'
			return trim($value, '-');
		} else {
			throw new \BadFunctionCallException($attributeName .
				' is not a valid attribute');
		}
	}

	/**
	 * @since 19.0.0
	 */
	protected function convertToType(string $name, $value) {
		if ($value !== null && array_key_exists($name, $this->getFieldTypes())) {
			settype($value, $this->getFieldTypes()[$name]);
		}

		return $value;
	}

}
