<?php

namespace Guzzle\Service\Description;

use Guzzle\Common\Exception\InvalidArgumentException;

/**
 * API parameter object used with service descriptions
 */
class Parameter
{
    protected $name;
    protected $description;
    protected $serviceDescription;
    protected $type;
    protected $required;
    protected $enum;
    protected $pattern;
    protected $minimum;
    protected $maximum;
    protected $minLength;
    protected $maxLength;
    protected $minItems;
    protected $maxItems;
    protected $default;
    protected $static;
    protected $instanceOf;
    protected $filters;
    protected $location;
    protected $sentAs;
    protected $data;
    protected $properties = array();
    protected $additionalProperties;
    protected $items;
    protected $parent;
    protected $ref;
    protected $format;
    protected $propertiesCache = null;

    /**
     * Create a new Parameter using an associative array of data. The array can contain the following information:
     * - name:          (string) Unique name of the parameter
     * - type:          (string|array) Type of variable (string, number, integer, boolean, object, array, numeric,
     *                  null, any). Types are using for validation and determining the structure of a parameter. You
     *                  can use a union type by providing an array of simple types. If one of the union types matches
     *                  the provided value, then the value is valid.
     * - instanceOf:    (string) When the type is an object, you can specify the class that the object must implement
     * - required:      (bool) Whether or not the parameter is required
     * - default:       (mixed) Default value to use if no value is supplied
     * - static:        (bool) Set to true to specify that the parameter value cannot be changed from the default
     * - description:   (string) Documentation of the parameter
     * - location:      (string) The location of a request used to apply a parameter. Custom locations can be registered
     *                  with a command, but the defaults are uri, query, header, body, json, xml, postField, postFile.
     * - sentAs:        (string) Specifies how the data being modeled is sent over the wire. For example, you may wish
     *                  to include certain headers in a response model that have a normalized casing of FooBar, but the
     *                  actual header is x-foo-bar. In this case, sentAs would be set to x-foo-bar.
     * - filters:       (array) Array of static method names to to run a parameter value through. Each value in the
     *                  array must be a string containing the full class path to a static method or an array of complex
     *                  filter information. You can specify static methods of classes using the full namespace class
     *                  name followed by '::' (e.g. Foo\Bar::baz()). Some filters require arguments in order to properly
     *                  filter a value. For complex filters, use a hash containing a 'method' key pointing to a static
     *                  method, and an 'args' key containing an array of positional arguments to pass to the method.
     *                  Arguments can contain keywords that are replaced when filtering a value: '@value' is replaced
     *                  with the value being validated, '@api' is replaced with the Parameter object.
     * - properties:    When the type is an object, you can specify nested parameters
     * - additionalProperties: (array) This attribute defines a schema for all properties that are not explicitly
     *                  defined in an object type definition. If specified, the value MUST be a schema or a boolean. If
     *                  false is provided, no additional properties are allowed beyond the properties defined in the
     *                  schema. The default value is an empty schema which allows any value for additional properties.
     * - items:         This attribute defines the allowed items in an instance array, and MUST be a schema or an array
     *                  of schemas. The default value is an empty schema which allows any value for items in the
     *                  instance array.
     *                  When this attribute value is a schema and the instance value is an array, then all the items
     *                  in the array MUST be valid according to the schema.
     * - pattern:       When the type is a string, you can specify the regex pattern that a value must match
     * - enum:          When the type is a string, you can specify a list of acceptable values
     * - minItems:      (int) Minimum number of items allowed in an array
     * - maxItems:      (int) Maximum number of items allowed in an array
     * - minLength:     (int) Minimum length of a string
     * - maxLength:     (int) Maximum length of a string
     * - minimum:       (int) Minimum value of an integer
     * - maximum:       (int) Maximum value of an integer
     * - data:          (array) Any additional custom data to use when serializing, validating, etc
     * - format:        (string) Format used to coax a value into the correct format when serializing or unserializing.
     *                  You may specify either an array of filters OR a format, but not both.
     *                  Supported values: date-time, date, time, timestamp, date-time-http
     * - $ref:          (string) String referencing a service description model. The parameter is replaced by the
     *                  schema contained in the model.
     *
     * @param array                       $data        Array of data as seen in service descriptions
     * @param ServiceDescriptionInterface $description Service description used to resolve models if $ref tags are found
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $data = array(), ServiceDescriptionInterface $description = null)
    {
        if ($description) {
            if (isset($data['$ref'])) {
                if ($model = $description->getModel($data['$ref'])) {
                    // The name of the original parameter should override the ref name if one is available
                    $name = empty($data['name']) ? null : $data['name'];
                    $data = $model->toArray();
                    if ($name) {
                        $data['name'] = $name;
                    }
                }
            } elseif (isset($data['extends'])) {
                // If this parameter extends from another parameter then start with the actual data
                // union in the parent's data (e.g. actual supersedes parent)
                if ($extends = $description->getModel($data['extends'])) {
                    $data += $extends->toArray();
                }
            }
        }

        // Pull configuration data into the parameter
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }

        $this->serviceDescription = $description;
        $this->required = (bool) $this->required;
        $this->data = (array) $this->data;

        if ($this->filters) {
            $this->setFilters((array) $this->filters);
        }

        if ($this->type == 'object' && $this->additionalProperties === null) {
            $this->additionalProperties = true;
        }
    }

    /**
     * Convert the object to an array
     *
     * @return array
     */
    public function toArray()
    {
        $result = array();
        $checks = array('required', 'description', 'static', 'type', 'format', 'instanceOf', 'location', 'sentAs',
            'pattern', 'minimum', 'maximum', 'minItems', 'maxItems', 'minLength', 'maxLength', 'data', 'enum',
            'filters');

        // Anything that is in the `Items` attribute of an array *must* include it's name if available
        if ($this->parent instanceof self && $this->parent->getType() == 'array' && isset($this->name)) {
            $result['name'] = $this->name;
        }

        foreach ($checks as $c) {
            if ($value = $this->{$c}) {
                $result[$c] = $value;
            }
        }

        if ($this->default !== null) {
            $result['default'] = $this->default;
        }

        if ($this->items !== null) {
            $result['items'] = $this->getItems()->toArray();
        }

        if ($this->additionalProperties !== null) {
            $result['additionalProperties'] = $this->getAdditionalProperties();
            if ($result['additionalProperties'] instanceof self) {
                $result['additionalProperties'] = $result['additionalProperties']->toArray();
            }
        }

        if ($this->type == 'object' && $this->properties) {
            $result['properties'] = array();
            foreach ($this->getProperties() as $name => $property) {
                $result['properties'][$name] = $property->toArray();
            }
        }

        return $result;
    }

    /**
     * Get the default or static value of the command based on a value
     *
     * @param string $value Value that is currently set
     *
     * @return mixed Returns the value, a static value if one is present, or a default value
     */
    public function getValue($value)
    {
        return $this->static || ($this->default !== null && !$value && ($this->type != 'boolean' || $value !== false))
            ? $this->default
            : $value;
    }

    /**
     * Run a value through the filters OR format attribute associated with the parameter
     *
     * @param mixed $value Value to filter
     *
     * @return mixed Returns the filtered value
     */
    public function filter($value)
    {
        // Formats are applied exclusively and supersed filters
        if ($this->format) {
            return SchemaFormatter::format($this->format, $value);
        }

        // Convert Boolean values
        if ($this->type == 'boolean' && !is_bool($value)) {
            $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }

        // Apply filters to the value
        if ($this->filters) {
            foreach ($this->filters as $filter) {
                if (is_array($filter)) {
                    // Convert complex filters that hold value place holders
                    foreach ($filter['args'] as &$data) {
                        if ($data == '@value') {
                            $data = $value;
                        } elseif ($data == '@api') {
                            $data = $this;
                        }
                    }
                    $value = call_user_func_array($filter['method'], $filter['args']);
                } else {
                    $value = call_user_func($filter, $value);
                }
            }
        }

        return $value;
    }

    /**
     * Get the name of the parameter
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Get the key of the parameter, where sentAs will supersede name if it is set
     *
     * @return string
     */
    public function getWireName()
    {
        return $this->sentAs ?: $this->name;
    }

    /**
     * Set the name of the parameter
     *
     * @param string $name Name to set
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the type(s) of the parameter
     *
     * @return string|array
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the type(s) of the parameter
     *
     * @param string|array $type Type of parameter or array of simple types used in a union
     *
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get if the parameter is required
     *
     * @return bool
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * Set if the parameter is required
     *
     * @param bool $isRequired Whether or not the parameter is required
     *
     * @return self
     */
    public function setRequired($isRequired)
    {
        $this->required = (bool) $isRequired;

        return $this;
    }

    /**
     * Get the default value of the parameter
     *
     * @return string|null
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Set the default value of the parameter
     *
     * @param string|null $default Default value to set
     *
     * @return self
     */
    public function setDefault($default)
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Get the description of the parameter
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the description of the parameter
     *
     * @param string $description Description
     *
     * @return self
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the minimum acceptable value for an integer
     *
     * @return int|null
     */
    public function getMinimum()
    {
        return $this->minimum;
    }

    /**
     * Set the minimum acceptable value for an integer
     *
     * @param int|null $min Minimum
     *
     * @return self
     */
    public function setMinimum($min)
    {
        $this->minimum = $min;

        return $this;
    }

    /**
     * Get the maximum acceptable value for an integer
     *
     * @return int|null
     */
    public function getMaximum()
    {
        return $this->maximum;
    }

    /**
     * Set the maximum acceptable value for an integer
     *
     * @param int $max Maximum
     *
     * @return self
     */
    public function setMaximum($max)
    {
        $this->maximum = $max;

        return $this;
    }

    /**
     * Get the minimum allowed length of a string value
     *
     * @return int
     */
    public function getMinLength()
    {
        return $this->minLength;
    }

    /**
     * Set the minimum allowed length of a string value
     *
     * @param int|null $min Minimum
     *
     * @return self
     */
    public function setMinLength($min)
    {
        $this->minLength = $min;

        return $this;
    }

    /**
     * Get the maximum allowed length of a string value
     *
     * @return int|null
     */
    public function getMaxLength()
    {
        return $this->maxLength;
    }

    /**
     * Set the maximum allowed length of a string value
     *
     * @param int $max Maximum length
     *
     * @return self
     */
    public function setMaxLength($max)
    {
        $this->maxLength = $max;

        return $this;
    }

    /**
     * Get the maximum allowed number of items in an array value
     *
     * @return int|null
     */
    public function getMaxItems()
    {
        return $this->maxItems;
    }

    /**
     * Set the maximum allowed number of items in an array value
     *
     * @param int $max Maximum
     *
     * @return self
     */
    public function setMaxItems($max)
    {
        $this->maxItems = $max;

        return $this;
    }

    /**
     * Get the minimum allowed number of items in an array value
     *
     * @return int
     */
    public function getMinItems()
    {
        return $this->minItems;
    }

    /**
     * Set the minimum allowed number of items in an array value
     *
     * @param int|null $min Minimum
     *
     * @return self
     */
    public function setMinItems($min)
    {
        $this->minItems = $min;

        return $this;
    }

    /**
     * Get the location of the parameter
     *
     * @return string|null
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set the location of the parameter
     *
     * @param string|null $location Location of the parameter
     *
     * @return self
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get the sentAs attribute of the parameter that used with locations to sentAs an attribute when it is being
     * applied to a location.
     *
     * @return string|null
     */
    public function getSentAs()
    {
        return $this->sentAs;
    }

    /**
     * Set the sentAs attribute
     *
     * @param string|null $name Name of the value as it is sent over the wire
     *
     * @return self
     */
    public function setSentAs($name)
    {
        $this->sentAs = $name;

        return $this;
    }

    /**
     * Retrieve a known property from the parameter by name or a data property by name. When not specific name value
     * is specified, all data properties will be returned.
     *
     * @param string|null $name Specify a particular property name to retrieve
     *
     * @return array|mixed|null
     */
    public function getData($name = null)
    {
        if (!$name) {
            return $this->data;
        }

        if (isset($this->data[$name])) {
            return $this->data[$name];
        } elseif (isset($this->{$name})) {
            return $this->{$name};
        }

        return null;
    }

    /**
     * Set the extra data properties of the parameter or set a specific extra property
     *
     * @param string|array|null $nameOrData The name of a specific extra to set or an array of extras to set
     * @param mixed|null        $data       When setting a specific extra property, specify the data to set for it
     *
     * @return self
     */
    public function setData($nameOrData, $data = null)
    {
        if (is_array($nameOrData)) {
            $this->data = $nameOrData;
        } else {
            $this->data[$nameOrData] = $data;
        }

        return $this;
    }

    /**
     * Get whether or not the default value can be changed
     *
     * @return mixed|null
     */
    public function getStatic()
    {
        return $this->static;
    }

    /**
     * Set to true if the default value cannot be changed
     *
     * @param bool $static True or false
     *
     * @return self
     */
    public function setStatic($static)
    {
        $this->static = (bool) $static;

        return $this;
    }

    /**
     * Get an array of filters used by the parameter
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters ?: array();
    }

    /**
     * Set the array of filters used by the parameter
     *
     * @param array $filters Array of functions to use as filters
     *
     * @return self
     */
    public function setFilters(array $filters)
    {
        $this->filters = array();
        foreach ($filters as $filter) {
            $this->addFilter($filter);
        }

        return $this;
    }

    /**
     * Add a filter to the parameter
     *
     * @param string|array $filter Method to filter the value through
     *
     * @return self
     * @throws InvalidArgumentException
     */
    public function addFilter($filter)
    {
        if (is_array($filter)) {
            if (!isset($filter['method'])) {
                throw new InvalidArgumentException('A [method] value must be specified for each complex filter');
            }
        }

        if (!$this->filters) {
            $this->filters = array($filter);
        } else {
            $this->filters[] = $filter;
        }

        return $this;
    }

    /**
     * Get the parent object (an {@see OperationInterface} or {@see Parameter}
     *
     * @return OperationInterface|Parameter|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set the parent object of the parameter
     *
     * @param OperationInterface|Parameter|null $parent Parent container of the parameter
     *
     * @return self
     */
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get the properties of the parameter
     *
     * @return array
     */
    public function getProperties()
    {
        if (!$this->propertiesCache) {
            $this->propertiesCache = array();
            foreach (array_keys($this->properties) as $name) {
                $this->propertiesCache[$name] = $this->getProperty($name);
            }
        }

        return $this->propertiesCache;
    }

    /**
     * Get a specific property from the parameter
     *
     * @param string $name Name of the property to retrieve
     *
     * @return null|Parameter
     */
    public function getProperty($name)
    {
        if (!isset($this->properties[$name])) {
            return null;
        }

        if (!($this->properties[$name] instanceof self)) {
            $this->properties[$name]['name'] = $name;
            $this->properties[$name] = new static($this->properties[$name], $this->serviceDescription);
            $this->properties[$name]->setParent($this);
        }

        return $this->properties[$name];
    }

    /**
     * Remove a property from the parameter
     *
     * @param string $name Name of the property to remove
     *
     * @return self
     */
    public function removeProperty($name)
    {
        unset($this->properties[$name]);
        $this->propertiesCache = null;

        return $this;
    }

    /**
     * Add a property to the parameter
     *
     * @param Parameter $property Properties to set
     *
     * @return self
     */
    public function addProperty(Parameter $property)
    {
        $this->properties[$property->getName()] = $property;
        $property->setParent($this);
        $this->propertiesCache = null;

        return $this;
    }

    /**
     * Get the additionalProperties value of the parameter
     *
     * @return bool|Parameter|null
     */
    public function getAdditionalProperties()
    {
        if (is_array($this->additionalProperties)) {
            $this->additionalProperties = new static($this->additionalProperties, $this->serviceDescription);
            $this->additionalProperties->setParent($this);
        }

        return $this->additionalProperties;
    }

    /**
     * Set the additionalProperties value of the parameter
     *
     * @param bool|Parameter|null $additional Boolean to allow any, an Parameter to specify a schema, or false to disallow
     *
     * @return self
     */
    public function setAdditionalProperties($additional)
    {
        $this->additionalProperties = $additional;

        return $this;
    }

    /**
     * Set the items data of the parameter
     *
     * @param Parameter|null $items Items to set
     *
     * @return self
     */
    public function setItems(Parameter $items = null)
    {
        if ($this->items = $items) {
            $this->items->setParent($this);
        }

        return $this;
    }

    /**
     * Get the item data of the parameter
     *
     * @return Parameter|null
     */
    public function getItems()
    {
        if (is_array($this->items)) {
            $this->items = new static($this->items, $this->serviceDescription);
            $this->items->setParent($this);
        }

        return $this->items;
    }

    /**
     * Get the class that the parameter must implement
     *
     * @return null|string
     */
    public function getInstanceOf()
    {
        return $this->instanceOf;
    }

    /**
     * Set the class that the parameter must be an instance of
     *
     * @param string|null $instanceOf Class or interface name
     *
     * @return self
     */
    public function setInstanceOf($instanceOf)
    {
        $this->instanceOf = $instanceOf;

        return $this;
    }

    /**
     * Get the enum of strings that are valid for the parameter
     *
     * @return array|null
     */
    public function getEnum()
    {
        return $this->enum;
    }

    /**
     * Set the enum of strings that are valid for the parameter
     *
     * @param array|null $enum Array of strings or null
     *
     * @return self
     */
    public function setEnum(array $enum = null)
    {
        $this->enum = $enum;

        return $this;
    }

    /**
     * Get the regex pattern that must match a value when the value is a string
     *
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * Set the regex pattern that must match a value when the value is a string
     *
     * @param string $pattern Regex pattern
     *
     * @return self
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;

        return $this;
    }

    /**
     * Get the format attribute of the schema
     *
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * Set the format attribute of the schema
     *
     * @param string $format Format to set (e.g. date, date-time, timestamp, time, date-time-http)
     *
     * @return self
     */
    public function setFormat($format)
    {
        $this->format = $format;

        return $this;
    }
}
