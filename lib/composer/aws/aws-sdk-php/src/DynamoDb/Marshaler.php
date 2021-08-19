<?php
namespace Aws\DynamoDb;

use Psr\Http\Message\StreamInterface;

/**
 * Marshals and unmarshals JSON documents and PHP arrays into DynamoDB items.
 */
class Marshaler
{
    /** @var array Default options to merge into provided options. */
    private static $defaultOptions = [
        'ignore_invalid'  => false,
        'nullify_invalid' => false,
        'wrap_numbers'    => false,
    ];

    /** @var array Marshaler options. */
    private $options;

    /**
     * Instantiates a DynamoDB Marshaler.
     *
     * The following options are valid.
     *
     * - ignore_invalid: (bool) Set to `true` if invalid values should be
     *   ignored (i.e., not included) during marshaling.
     * - nullify_invalid: (bool) Set to `true` if invalid values should be set
     *   to null.
     * - wrap_numbers: (bool) Set to `true` to wrap numbers with `NumberValue`
     *   objects during unmarshaling to preserve the precision.
     *
     * @param array $options Marshaler options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options + self::$defaultOptions;
    }

    /**
     * Creates a special object to represent a DynamoDB binary (B) value.
     *
     * This helps disambiguate binary values from string (S) values.
     *
     * @param mixed $value A binary value compatible with Guzzle streams.
     *
     * @return BinaryValue
     * @see GuzzleHttp\Stream\Stream::factory
     */
    public function binary($value)
    {
        return new BinaryValue($value);
    }

    /**
     * Creates a special object to represent a DynamoDB number (N) value.
     *
     * This helps maintain the precision of large integer/float in PHP.
     *
     * @param string|int|float $value A number value.
     *
     * @return NumberValue
     */
    public function number($value)
    {
        return new NumberValue($value);
    }

    /**
     * Creates a special object to represent a DynamoDB set (SS/NS/BS) value.
     *
     * This helps disambiguate set values from list (L) values.
     *
     * @param array $values The values of the set.
     *
     * @return SetValue
     *
     */
    public function set(array $values)
    {
        return new SetValue($values);
    }

    /**
     * Marshal a JSON document from a string to a DynamoDB item.
     *
     * The result is an array formatted in the proper parameter structure
     * required by the DynamoDB API for items.
     *
     * @param string $json A valid JSON document.
     *
     * @return array Item formatted for DynamoDB.
     * @throws \InvalidArgumentException if the JSON is invalid.
     */
    public function marshalJson($json)
    {
        $data = json_decode($json);
        if (!($data instanceof \stdClass)) {
            throw new \InvalidArgumentException(
                'The JSON document must be valid and be an object at its root.'
            );
        }

        return current($this->marshalValue($data));
    }

    /**
     * Marshal a native PHP array of data to a DynamoDB item.
     *
     * The result is an array formatted in the proper parameter structure
     * required by the DynamoDB API for items.
     *
     * @param array|\stdClass $item An associative array of data.
     *
     * @return array Item formatted for DynamoDB.
     */
    public function marshalItem($item)
    {
        return current($this->marshalValue($item));
    }

    /**
     * Marshal a native PHP value into a DynamoDB attribute value.
     *
     * The result is an associative array that is formatted in the proper
     * `[TYPE => VALUE]` parameter structure required by the DynamoDB API.
     *
     * @param mixed $value A scalar, array, or `stdClass` value.
     *
     * @return array Attribute formatted for DynamoDB.
     * @throws \UnexpectedValueException if the value cannot be marshaled.
     */
    public function marshalValue($value)
    {
        $type = gettype($value);

        // Handle string values.
        if ($type === 'string') {
            return ['S' => $value];
        }

        // Handle number values.
        if ($type === 'integer'
            || $type === 'double'
            || $value instanceof NumberValue
        ) {
            return ['N' => (string) $value];
        }

        // Handle boolean values.
        if ($type === 'boolean') {
            return ['BOOL' => $value];
        }

        // Handle null values.
        if ($type === 'NULL') {
            return ['NULL' => true];
        }

        // Handle set values.
        if ($value instanceof SetValue) {
            if (count($value) === 0) {
                return $this->handleInvalid('empty sets are invalid');
            }
            $previousType = null;
            $data = [];
            foreach ($value as $v) {
                $marshaled = $this->marshalValue($v);
                $setType = key($marshaled);
                if (!$previousType) {
                    $previousType = $setType;
                } elseif ($setType !== $previousType) {
                    return $this->handleInvalid('sets must be uniform in type');
                }
                $data[] = current($marshaled);
            }

            return [$previousType . 'S' => array_values(array_unique($data))];
        }

        // Handle list and map values.
        $dbType = 'L';
        if ($value instanceof \stdClass) {
            $type = 'array';
            $dbType = 'M';
        }
        if ($type === 'array' || $value instanceof \Traversable) {
            $data = [];
            $index = 0;
            foreach ($value as $k => $v) {
                if ($v = $this->marshalValue($v)) {
                    $data[$k] = $v;
                    if ($dbType === 'L' && (!is_int($k) || $k != $index++)) {
                        $dbType = 'M';
                    }
                }
            }
            return [$dbType => $data];
        }

        // Handle binary values.
        if (is_resource($value) || $value instanceof StreamInterface) {
            $value = $this->binary($value);
        }
        if ($value instanceof BinaryValue) {
            return ['B' => (string) $value];
        }

        // Handle invalid values.
        return $this->handleInvalid('encountered unexpected value');
    }

    /**
     * Unmarshal a document (item) from a DynamoDB operation result into a JSON
     * document string.
     *
     * @param array $data            Item/document from a DynamoDB result.
     * @param int   $jsonEncodeFlags Flags to use with `json_encode()`.
     *
     * @return string
     */
    public function unmarshalJson(array $data, $jsonEncodeFlags = 0)
    {
        return json_encode(
            $this->unmarshalValue(['M' => $data], true),
            $jsonEncodeFlags
        );
    }

    /**
     * Unmarshal an item from a DynamoDB operation result into a native PHP
     * array. If you set $mapAsObject to true, then a stdClass value will be
     * returned instead.
     *
     * @param array $data Item from a DynamoDB result.
     * @param bool  $mapAsObject Whether maps should be represented as stdClass.
     *
     * @return array|\stdClass
     */
    public function unmarshalItem(array $data, $mapAsObject = false)
    {
        return $this->unmarshalValue(['M' => $data], $mapAsObject);
    }

    /**
     * Unmarshal a value from a DynamoDB operation result into a native PHP
     * value. Will return a scalar, array, or (if you set $mapAsObject to true)
     * stdClass value.
     *
     * @param array $value       Value from a DynamoDB result.
     * @param bool  $mapAsObject Whether maps should be represented as stdClass.
     *
     * @return mixed
     * @throws \UnexpectedValueException
     */
    public function unmarshalValue(array $value, $mapAsObject = false)
    {
        $type = key($value);
        $value = $value[$type];
        switch ($type) {
            case 'S':
            case 'BOOL':
                return $value;
            case 'NULL':
                return null;
            case 'N':
                if ($this->options['wrap_numbers']) {
                    return new NumberValue($value);
                }

                // Use type coercion to unmarshal numbers to int/float.
                return $value + 0;
            case 'M':
                if ($mapAsObject) {
                    $data = new \stdClass;
                    foreach ($value as $k => $v) {
                        $data->$k = $this->unmarshalValue($v, $mapAsObject);
                    }
                    return $data;
                }
                // NOBREAK: Unmarshal M the same way as L, for arrays.
            case 'L':
                foreach ($value as $k => $v) {
                    $value[$k] = $this->unmarshalValue($v, $mapAsObject);
                }
                return $value;
            case 'B':
                return new BinaryValue($value);
            case 'SS':
            case 'NS':
            case 'BS':
                foreach ($value as $k => $v) {
                    $value[$k] = $this->unmarshalValue([$type[0] => $v]);
                }
                return new SetValue($value);
        }

        throw new \UnexpectedValueException("Unexpected type: {$type}.");
    }

    /**
     * Handle invalid value based on marshaler configuration.
     *
     * @param string $message Error message
     *
     * @return array|null
     */
    private function handleInvalid($message)
    {
        if ($this->options['ignore_invalid']) {
            return null;
        }

        if ($this->options['nullify_invalid']) {
            return ['NULL' => true];
        }

        throw new \UnexpectedValueException("Marshaling error: {$message}.");
    }
}
