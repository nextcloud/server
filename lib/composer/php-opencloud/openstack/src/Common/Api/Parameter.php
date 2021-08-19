<?php

declare(strict_types=1);

namespace OpenStack\Common\Api;

use OpenStack\Common\HydratorStrategyTrait;

/**
 * Represents an individual request parameter in a RESTful operation. A parameter can take on many forms:
 * in a URL path, in a URL query, in a JSON body, and in a HTTP header. It is worth documenting brifly each
 * variety of parameter:.
 *
 * * Header parameters are those which populate a HTTP header in a request. Header parameters can have
 *   aliases; for example, a user-facing name of "Foo" can be sent over the wire as "X-Foo_Bar", as defined
 *   by ``sentAs``. Prefixes can also be used.
 *
 * * Query parameters are those which populate a URL query parameter. The value is therefore usually
 *   confined to a string.
 *
 * * JSON parameters are those which populate a JSON request body. These are the most complex variety
 *   of Parameter, since there are so many different ways a JSON document can be constructed. The SDK
 *   supports deep-nesting according to a XPath syntax; for more information, see {@see \OpenStack\Common\JsonPath}.
 *   Nested object and array properties are also supported since JSON is a recursive data type. What
 *   this means is that a Parameter can have an assortment of child Parameters, one for each object
 *   property or array element.
 *
 * * Raw parameters are those which populate a non-JSON request body. This is typically used for
 *   uploading payloads (such as Swift object data) to a remote API.
 *
 * * Path parameters are those which populate a URL path. They are serialized according to URL
 *   placeholders.
 */
class Parameter
{
    use HydratorStrategyTrait;

    const DEFAULT_LOCATION = 'json';

    /**
     * The human-friendly name of the parameter. This is what the user will input.
     *
     * @var string
     */
    private $name = '';

    /**
     * The alias for this parameter. Although the user will always interact with the human-friendly $name property,
     * the $sentAs is what's used over the wire.
     *
     * @var string
     */
    private $sentAs = '';

    /**
     * For array parameters (for example, an array of security group names when creating a server), each array element
     * will need to adhere to a common schema. For the aforementioned example, each element will need to be a string.
     * For more complicated parameters, you might be validated an array of complicated objects.
     *
     * @var Parameter
     */
    private $itemSchema;

    /**
     * For object parameters, each property will need to adhere to a specific schema. For every property in the
     * object, it has its own schema - meaning that this property is a hash of name/schema pairs.
     *
     * The *only* exception to this rule is for metadata parameters, which are arbitrary key/value pairs. Since it does
     * not make sense to have a schema for each metadata key, a common schema is use for every one. So instead of this
     * property being a hash of schemas, it is a single Parameter object instead. This single Parameter schema will
     * then be applied to each metadata key provided.
     *
     * @var []Parameter|Parameter
     */
    private $properties;

    /**
     * The value's PHP type which this parameter represents; either "string", "bool", "object", "array", "NULL".
     *
     * @var string
     */
    private $type = '';

    /**
     * Indicates whether this parameter requires a value from the user.
     *
     * @var bool
     */
    private $required;

    /**
     * The location in the HTTP request where this parameter will populate; either "header", "url", "query", "raw" or
     * "json".
     *
     * @var string
     */
    private $location = '';

    /**
     * Relevant to "json" location parameters only. This property allows for deep nesting through the use of
     * {@see OpenStack\Common\JsonPath}.
     *
     * @var string
     */
    private $path = '';

    /**
     * Allows for the prefixing of parameter names.
     *
     * @var string
     */
    private $prefix = '';

    /**
     * The enum values for which this param is restricted.
     *
     * @var array
     */
    private $enum;

    public function __construct(array $data)
    {
        $this->hydrate($data);

        $this->required = (bool) $this->required;

        $this->stockLocation($data);
        $this->stockItemSchema($data);
        $this->stockProperties($data);
    }

    private function stockLocation(array $data)
    {
        $this->location = isset($data['location']) ? $data['location'] : self::DEFAULT_LOCATION;

        if (!AbstractParams::isSupportedLocation($this->location)) {
            throw new \RuntimeException(sprintf('%s is not a permitted location', $this->location));
        }
    }

    private function stockItemSchema(array $data)
    {
        if (isset($data['items'])) {
            $this->itemSchema = new Parameter($data['items']);
        }
    }

    private function stockProperties(array $data)
    {
        if (isset($data['properties'])) {
            if ($this->name && false !== stripos($this->name, 'metadata')) {
                $this->properties = new Parameter($data['properties']);
            } else {
                foreach ($data['properties'] as $name => $property) {
                    $this->properties[$name] = new Parameter($property + ['name' => $name]);
                }
            }
        }
    }

    /**
     * Retrieve the name that will be used over the wire.
     */
    public function getName(): string
    {
        return $this->sentAs ?: $this->name;
    }

    /**
     * Indicates whether the user must provide a value for this parameter.
     */
    public function isRequired(): bool
    {
        return true === $this->required;
    }

    /**
     * Validates a given user value and checks whether it passes basic sanity checking, such as types.
     *
     * @param $userValues The value provided by the user
     *
     * @return bool TRUE if the validation passes
     *
     * @throws \Exception If validation fails
     */
    public function validate($userValues): bool
    {
        $this->validateEnums($userValues);
        $this->validateType($userValues);

        if ($this->isArray()) {
            $this->validateArray($userValues);
        } elseif ($this->isObject()) {
            $this->validateObject($userValues);
        }

        return true;
    }

    private function validateEnums($userValues)
    {
        if (!empty($this->enum) && 'string' == $this->type && !in_array($userValues, $this->enum)) {
            throw new \Exception(sprintf('The only permitted values are %s. You provided %s', implode(', ', $this->enum), print_r($userValues, true)));
        }
    }

    private function validateType($userValues)
    {
        if (!$this->hasCorrectType($userValues)) {
            throw new \Exception(sprintf('The key provided "%s" has the wrong value type. You provided %s (%s) but was expecting %s', $this->name, print_r($userValues, true), gettype($userValues), $this->type));
        }
    }

    private function validateArray($userValues)
    {
        foreach ($userValues as $userValue) {
            $this->itemSchema->validate($userValue);
        }
    }

    private function validateObject($userValues)
    {
        foreach ($userValues as $key => $userValue) {
            $property = $this->getNestedProperty($key);
            $property->validate($userValue);
        }
    }

    /**
     * Internal method which retrieves a nested property for object parameters.
     *
     * @param string $key The name of the child parameter
     *
     * @returns Parameter
     *
     * @throws \Exception
     */
    private function getNestedProperty($key): Parameter
    {
        if ($this->name && false !== stripos($this->name, 'metadata') && $this->properties instanceof Parameter) {
            return $this->properties;
        } elseif (isset($this->properties[$key])) {
            return $this->properties[$key];
        } else {
            throw new \Exception(sprintf('The key provided "%s" is not defined', $key));
        }
    }

    /**
     * Internal method which indicates whether the user value is of the same type as the one expected
     * by this parameter.
     *
     * @param $userValue The value being checked
     */
    private function hasCorrectType($userValue): bool
    {
        // Helper fn to see whether an array is associative (i.e. a JSON object)
        $isAssociative = function ($value) {
            return is_array($value) && array_keys($value) !== range(0, count($value) - 1);
        };

        // For params defined as objects, we'll let the user get away with
        // passing in an associative array - since it's effectively a hash
        if ('object' == $this->type && $isAssociative($userValue)) {
            return true;
        }

        if (class_exists($this->type) || interface_exists($this->type)) {
            return is_a($userValue, $this->type);
        }

        if (!$this->type) {
            return true;
        }

        // allow string nulls
        if ('string' == $this->type && null === $userValue) {
            return true;
        }

        return gettype($userValue) == $this->type;
    }

    /**
     * Indicates whether this parameter represents an array type.
     */
    public function isArray(): bool
    {
        return 'array' == $this->type && $this->itemSchema instanceof Parameter;
    }

    /**
     * Indicates whether this parameter represents an object type.
     */
    public function isObject(): bool
    {
        return 'object' == $this->type && !empty($this->properties);
    }

    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * Verifies whether the given location matches the parameter's location.
     *
     * @param $value
     */
    public function hasLocation($value): bool
    {
        return $this->location == $value;
    }

    /**
     * Retrieves the parameter's path.
     *
     * @return string|null
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Retrieves the common schema that an array parameter applies to all its child elements.
     *
     * @return Parameter|null
     */
    public function getItemSchema()
    {
        return $this->itemSchema;
    }

    /**
     * Sets the name of the parameter to a new value.
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * Retrieves the child parameter for an object parameter.
     *
     * @param string $name The name of the child property
     *
     * @return Parameter|null
     */
    public function getProperty(string $name)
    {
        if ($this->properties instanceof Parameter) {
            $this->properties->setName($name);

            return $this->properties;
        }

        return isset($this->properties[$name]) ? $this->properties[$name] : null;
    }

    /**
     * Retrieves the prefix for a parameter, if any.
     *
     * @return string|null
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getPrefixedName(): string
    {
        return $this->prefix.$this->getName();
    }
}
