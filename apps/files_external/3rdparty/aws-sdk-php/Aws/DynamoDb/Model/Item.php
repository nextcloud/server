<?php
/**
 * Copyright 2010-2013 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 * http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

namespace Aws\DynamoDb\Model;

use Guzzle\Common\ToArrayInterface;

/**
 * Amazon DynamoDB item model
 */
class Item implements \ArrayAccess, \IteratorAggregate, ToArrayInterface, \Countable
{
    /**
     * @var string
     */
    protected $tableName;

    /**
     * @var array Data
     */
    protected $data = array();

    /**
     * Create an item from a simplified array
     *
     * @param array  $attributes Array of attributes
     * @param string $tableName  Name of the table associated with the item
     *
     * @return self
     */
    public static function fromArray(array $attributes, $tableName = null)
    {
        foreach ($attributes as &$value) {
            $value = Attribute::factory($value);
        }

        return new self($attributes, $tableName);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $result = $this->data;
        foreach ($result as &$value) {
            if ($value instanceof Attribute) {
                $value = $value->toArray();
            }
        }

        return $result;
    }

    /**
     * Construct a new Item
     *
     * @param array  $attributes Array of attributes
     * @param string $tableName  Table of the item (if known)
     */
    public function __construct(array $attributes = array(), $tableName = null)
    {
        $this->replace($attributes);
        $this->tableName = $tableName;
    }

    /**
     * Set the name of the table associated with the item
     *
     * @param string $tableName Table name
     *
     * @return self
     */
    public function setTableName($tableName)
    {
        $this->tableName = $tableName;

        return $this;
    }

    /**
     * Get the name of the table associated with the item
     *
     * @return string|null
     */
    public function getTableName()
    {
        return $this->tableName;
    }

    /**
     * Get an attribute object by name
     *
     * @param string $name Name of the attribute to retrieve
     *
     * @return Attribute|null
     */
    public function get($name)
    {
        return isset($this->data[$name]) ? $this->data[$name] : null;
    }

    /**
     * Get all of the attribute names of the item
     *
     * @return array
     */
    public function keys()
    {
        return array_keys($this->data);
    }

    /**
     * Check if a particular attribute exists on the item
     *
     * @param string $attribute Attribute name to check
     *
     * @return bool
     */
    public function has($attribute)
    {
        return isset($this->data[$attribute]);
    }

    /**
     * Get all of the {@see Attribute} objects
     *
     * @return array
     */
    public function all()
    {
        return $this->data;
    }

    /**
     * Add an attribute
     *
     * @param string    $name      Name of the attribute to add
     * @param Attribute $attribute Attribute to add
     *
     * @return self
     */
    public function add($name, Attribute $attribute)
    {
        $this->data[$name] = $attribute;

        return $this;
    }

    /**
     * Set all of the attributes
     *
     * @param array $attributes Array of {@see Attribute} objects
     *
     * @return self
     */
    public function replace(array $attributes)
    {
        foreach ($attributes as $name => $attribute) {
            if (!($attribute instanceof Attribute)) {
                $attribute = new Attribute(current($attribute), key($attribute));
            }
            $this->add($name, $attribute);
        }

        return $this;
    }

    /**
     * Remove an attribute by name
     *
     * @param string $name Name of the attribute to remove
     *
     * @return self
     */
    public function remove($name)
    {
        unset($this->data[$name]);

        return $this;
    }

    /**
     * Get the total number of attributes
     *
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * ArrayAccess implementation of offsetExists()
     *
     * @param string $offset Array key
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    /**
     * ArrayAccess implementation of offsetGet()
     *
     * @param string $offset Array key
     *
     * @return null|mixed
     */
    public function offsetGet($offset)
    {
        return isset($this->data[$offset]) ? $this->data[$offset] : null;
    }

    /**
     * ArrayAccess implementation of offsetGet()
     *
     * @param string $offset Array key
     * @param mixed  $value  Value to set
     */
    public function offsetSet($offset, $value)
    {
        $this->data[$offset] = $value;
    }

    /**
     * ArrayAccess implementation of offsetUnset()
     *
     * @param string $offset Array key
     */
    public function offsetUnset($offset)
    {
        unset($this->data[$offset]);
    }
}
