<?php

/**
* ownCloud - App Framework
*
* @author Bernhard Posselt
* @copyright 2012 Bernhard Posselt dev@bernhard-posselt.com
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/

namespace OCP\AppFramework\Db;


/**
 * @method integer getId()
 * @method void setId(integer $id)
 */
abstract class Entity {

	public $id;

	private $_updatedFields = array();
	private $_fieldTypes = array('id' => 'integer');


	/**
	 * Simple alternative constructor for building entities from a request
	 * @param array $params the array which was obtained via $this->params('key')
	 * in the controller
	 * @return Entity
	 */
	public static function fromParams(array $params) {
		$instance = new static();

		foreach($params as $key => $value) {
			$method = 'set' . ucfirst($key);
			$instance->$method($value);
		}

		return $instance;
	}


	/**
	 * Maps the keys of the row array to the attributes
	 * @param array $row the row to map onto the entity
	 */
	public static function fromRow(array $row){
		$instance = new static();

		foreach($row as $key => $value){
			$prop = ucfirst($instance->columnToProperty($key));
			$setter = 'set' . $prop;
			$instance->$setter($value);
		}

		$instance->resetUpdatedFields();

		return $instance;
	}


	/**
	 * @return an array with attribute and type
	 */
	public function getFieldTypes() {
		return $this->_fieldTypes;
	}

	
	/**
	 * Marks the entity as clean needed for setting the id after the insertion
	 */
	public function resetUpdatedFields(){
		$this->_updatedFields = array();
	}


	protected function setter($name, $args) {
		// setters should only work for existing attributes
		if(property_exists($this, $name)){
			if($this->$name === $args[0]) {
				return;
			}
			$this->markFieldUpdated($name);

			// if type definition exists, cast to correct type
			if($args[0] !== null && array_key_exists($name, $this->_fieldTypes)) {
				settype($args[0], $this->_fieldTypes[$name]);
			}
			$this->$name = $args[0];

		} else {
			throw new \BadFunctionCallException($name . 
				' is not a valid attribute');
		}
	}


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
	 */
	public function __call($methodName, $args){
		$attr = lcfirst( substr($methodName, 3) );

		if(strpos($methodName, 'set') === 0){
			$this->setter($attr, $args);
		} elseif(strpos($methodName, 'get') === 0) {
			return $this->getter($attr);
		} else {
			throw new \BadFunctionCallException($methodName . 
					' does not exist');
		}

	}


	/**
	 * Mark am attribute as updated
	 * @param string $attribute the name of the attribute
	 */
	protected function markFieldUpdated($attribute){
		$this->_updatedFields[$attribute] = true;
	}


	/**
	 * Transform a database columnname to a property 
	 * @param string $columnName the name of the column
	 * @return string the property name
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
	 */
	public function getUpdatedFields(){
		return $this->_updatedFields;
	}


	/**
	 * Adds type information for a field so that its automatically casted to
	 * that value once its being returned from the database
	 * @param string $fieldName the name of the attribute
	 * @param string $type the type which will be used to call settype()
	 */
	protected function addType($fieldName, $type){
		$this->_fieldTypes[$fieldName] = $type;
	}


	/**
	 * Slugify the value of a given attribute
	 * Warning: This doesn't result in a unique value
	 * @param string $attributeName the name of the attribute, which value should be slugified
	 * @return string slugified value
	 */
	public function slugify($attributeName){
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

}
