<?php
/*
 * Copyright 2010-2012 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 *  http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */


/*%******************************************************************************************%*/
// CLASS

/**
 * The <CFCredential> class represents an individual credential set.
 *
 * @version 2011.11.15
 * @license See the included NOTICE.md file for more information.
 * @copyright See the included NOTICE.md file for more information.
 * @link http://aws.amazon.com/php/ PHP Developer Center
 */
class CFCredential implements ArrayAccess
{
	/**
	 * Stores the internal <php:ArrayObject> representation of the collection.
	 */
	private $collection;

	/**
	* Default getter. Enables syntax such as $object->method->chained_method();. Also supports
	* $object->key. Matching methods are prioritized over matching keys.
	*
	* @param string $name (Required) The name of the method to execute or key to retrieve.
	* @return mixed The results of calling the function <code>$name()</code>, or the value of the key <code>$object[$name]</code>.
	*/
	public function __get($name)
	{
		return $this[$name];
	}

	/**
	* Default setter.
	*
	* @param string $name (Required) The name of the method to execute.
	* @param string $value (Required) The value to pass to the method.
	* @return mixed The results of calling the function, <code>$name</code>.
	*/
	public function __set($name, $value)
	{
		$this[$name] = $value;
		return $this;
	}

	/**
	 * Create a clone of the object.
	 *
	 * @return CFCredential A clone of the current instance.
	 */
	public function __clone()
	{
		$this->collection = clone $this->collection;
	}


	/*%******************************************************************************************%*/
	// CONSTRUCTOR

	/**
	 * Constructs a new instance of the <CFCredential> class.
	 */
	public function __construct($value = array())
	{
		$this->collection = new ArrayObject($value, ArrayObject::ARRAY_AS_PROPS);
	}

	/**
	 * Check whether or not a specific offset exists.
	 *
	 * @param integer $offset (Required) The location in the collection to verify the existence of.
	 * @return boolean A value of <code>true</code> indicates that the collection offset exists. A value of <code>false</code> indicates that it does not.
	 */
	public function offsetExists($offset)
	{
		return $this->collection->offsetExists($offset);
	}

	/**
	 * Get the value for a specific offset.
	 *
	 * @param integer $offset (Required) The location in the collection to retrieve the value for.
	 * @return mixed The value of the collection offset. <code>NULL</code> is returned if the offset does not exist.
	 */
	public function offsetGet($offset)
	{
		if ($this->collection->offsetExists($offset))
		{
			return $this->collection->offsetGet($offset);
		}

		return null;
	}

	/**
	 * Set the value for a specific offset.
	 *
	 * @param integer $offset (Required) The location in the collection to set a new value for.
	 * @param mixed $value (Required) The new value for the collection location.
	 * @return CFCredential A reference to the current collection.
	 */
	public function offsetSet($offset, $value)
	{
		$this->collection->offsetSet($offset, $value);
		return $this;
	}

	/**
	 * Unset the value for a specific offset.
	 *
	 * @param integer $offset (Required) The location in the collection to unset.
	 * @return CFCredential A reference to the current collection.
	 */
	public function offsetUnset($offset)
	{
		$this->collection->offsetUnset($offset);
		return $this;
	}

	/**
	 * Merge another instance of <CFCredential> onto this one.
	 *
	 * @param CFCredential $credential (Required) Another instance of <CFCredential>.
	 * @return CFCredential A reference to the current collection.
	 */
	public function merge(CFCredential $credential)
	{
		$merged = array_merge($this->to_array(), $credential->to_array());
		$this->collection->exchangeArray($merged);
		return $this;
	}

	/**
	 * Retrieves the data as a standard array.
	 *
	 * @return array The data as an array.
	 */
	public function to_array()
	{
		return $this->collection->getArrayCopy();
	}
}
