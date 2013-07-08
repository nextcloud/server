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

use Aws\Common\Exception\InvalidArgumentException;
use Aws\DynamoDb\Enum\Type;
use Guzzle\Common\ToArrayInterface;

/**
 * Class representing a DynamoDB item attribute. Contains helpers for building
 * attributes and arrays of attributes.
 */
class Attribute implements ToArrayInterface
{
    /**
     * @var string A constant used to express the attribute be formatted for expected conditions
     */
    const FORMAT_EXPECTED = 'expected';

    /**
     * @var string A constant used to express the attribute be formatted for put operations
     */
    const FORMAT_PUT = 'put';

    /**
     * @var string A constant used to express the attribute be formatted for update operations
     */
    const FORMAT_UPDATE = 'update';

    /**
     * @var string The suffix for all string types
     */
    const SET_SUFFIX = 'S';

    /**
     * @var string The DynamoDB attribute type (e.g. N, S, B, NS, SS, BS)
     */
    protected $type;

    /**
     * @var string|array The DynamoDB attribute value
     */
    protected $value;

    /**
     * Creates a DynamoDB attribute, validates it, and prepares the type and
     * value. Some objects can be used as values as well. If the object has a
     * __toString method or implements the Traversable interface, it can be
     * converted to a string or array, respectively.
     *
     * @param mixed $value The DynamoDB attribute value
     * @param int   $depth A variable used internally to keep track of recursion
     * depth of array processing
     *
     * @return Attribute
     *
     * @throws InvalidArgumentException
     */
    public static function factory($value, $depth = 0)
    {
        // Handle deep recursion
        if ($depth > 1) {
            throw new InvalidArgumentException('Sets must be at most one level deep.');
        }

        // Handle specific, allowed object types
        if ($value instanceof Attribute) {
            return $value;
        } elseif ($value instanceof \Traversable) {
            $value = iterator_to_array($value);
        } elseif (is_object($value) && method_exists($value, '__toString')) {
            $value = (string) $value;
        }

        // Ensure that the value is valid
        if ($value === null || $value === array() || $value === '') {
            // Note: "Empty" values are not allowed except for zero and false.
            throw new InvalidArgumentException('The value must not be empty.');
        } elseif (is_resource($value) || is_object($value)) {
            throw new InvalidArgumentException('The value must be able to be converted to string.');
        }

        // Create the attribute to return
        if (is_int($value) || is_float($value)) {
            // Handle numeric values
            $attribute = new Attribute((string) $value, Type::NUMBER);
        } elseif (is_bool($value)) {
            // Handle boolean values
            $attribute = new Attribute($value ? '1' : '0', Type::NUMBER);
        } elseif (is_array($value) || $value instanceof \Traversable) {
            // Handle arrays
            $setType = null;
            $attribute = new Attribute(array());

            // Loop through each value to analyze and prepare it
            foreach ($value as $subValue) {
                // Recursively get the attribute for the set. The depth param only allows one level of recursion
                $subAttribute = static::factory($subValue, $depth + 1);

                // The type of each sub-value must be the same, or else the whole array is invalid
                if ($setType === null) {
                    $setType = $subAttribute->type;
                } elseif ($setType !== $subAttribute->type) {
                    throw new InvalidArgumentException('The set did not contain values of a uniform type.');
                }

                // Save the value for the upstream array
                $attribute->value[] = (string) $subAttribute->value;
            }

            // Make sure the type is changed to be a set type
            $attribute->type = $setType . self::SET_SUFFIX;
        } else {
            $attribute = new Attribute((string) $value);
        }

        return $attribute;
    }

    /**
     * Instantiates a DynamoDB attribute.
     *
     * @param string|array $value The DynamoDB attribute value
     * @param string       $type  The DynamoDB attribute type (N, S, B, NS, SS, BS)
     */
    public function __construct($value, $type = Type::STRING)
    {
        $this->setValue($value);
        $this->setType($type);
    }

    /**
     * Convert the attribute to a string
     *
     * @return string
     */
    public function __toString()
    {
        return implode(', ', (array) $this->value);
    }

    /**
     * Retrieve the formatted data.
     *
     * @param string $format The format to apply to the data.
     *
     * @return string The formatted version of the data.
     */
    public function getFormatted($format = Attribute::FORMAT_PUT)
    {
        switch ($format) {
            case self::FORMAT_EXPECTED:
                // no break
            case self::FORMAT_UPDATE:
                $formatted = array('Value' => array($this->type => $this->value));
                break;
            case self::FORMAT_PUT:
                // no break
            default:
                $formatted = array($this->type => $this->value);
        }

        return $formatted;
    }

    /**
     * Retrieve the attribute type.
     *
     * @return string The attribute type.
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Retrieve the attribute value.
     *
     * @return string The attribute value.
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set the attribute type.
     *
     * @param string $type The attribute type to set.
     *
     * @return string The attribute type.
     */
    public function setType($type)
    {
        if (in_array($type, Type::values())) {
            $this->type = $type;
        } else {
            throw new InvalidArgumentException('An attribute type must be a valid DynamoDB type.');
        }

        return $this;
    }

    /**
     * Set the attribute value.
     *
     * @param string $type The attribute value to set.
     *
     * @return string The attribute value.
     */
    public function setValue($value)
    {
        if (is_string($value) || is_array($value)) {
            $this->value = $value;
        } else {
            throw new InvalidArgumentException('An attribute value may only be a string or array.');
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->getFormatted();
    }
}
