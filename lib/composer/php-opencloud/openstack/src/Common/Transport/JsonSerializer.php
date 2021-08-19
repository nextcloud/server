<?php

declare(strict_types=1);

namespace OpenStack\Common\Transport;

use OpenStack\Common\Api\Parameter;
use OpenStack\Common\JsonPath;

/**
 * Class responsible for populating the JSON body of a {@see GuzzleHttp\Message\Request} object.
 */
class JsonSerializer
{
    /**
     * Populates the actual value into a JSON field, i.e. it has reached the end of the line and no
     * further nesting is required.
     *
     * @param Parameter $param     The schema that defines how the JSON field is being populated
     * @param mixed     $userValue The user value that is populating a JSON field
     * @param array     $json      The existing JSON structure that will be populated
     *
     * @return array|mixed
     */
    private function stockValue(Parameter $param, $userValue, array $json): array
    {
        $name = $param->getName();
        if ($path = $param->getPath()) {
            $jsonPath = new JsonPath($json);
            $jsonPath->set(sprintf('%s.%s', $path, $name), $userValue);
            $json = $jsonPath->getStructure();
        } elseif ($name) {
            $json[$name] = $userValue;
        } else {
            $json[] = $userValue;
        }

        return $json;
    }

    /**
     * Populates a value into an array-like structure.
     *
     * @param Parameter $param     The schema that defines how the JSON field is being populated
     * @param mixed     $userValue The user value that is populating a JSON field
     *
     * @return array|mixed
     */
    private function stockArrayJson(Parameter $param, array $userValue): array
    {
        $elems = [];
        foreach ($userValue as $item) {
            $elems = $this->stockJson($param->getItemSchema(), $item, $elems);
        }

        return $elems;
    }

    /**
     * Populates a value into an object-like structure.
     *
     * @param Parameter $param     The schema that defines how the JSON field is being populated
     * @param mixed     $userValue The user value that is populating a JSON field
     */
    private function stockObjectJson(Parameter $param, \stdClass $userValue): array
    {
        $object = [];
        foreach ($userValue as $key => $val) {
            $object = $this->stockJson($param->getProperty($key), $val, $object);
        }

        return $object;
    }

    /**
     * A generic method that will populate a JSON structure with a value according to a schema. It
     * supports multiple types and will delegate accordingly.
     *
     * @param Parameter $param     The schema that defines how the JSON field is being populated
     * @param mixed     $userValue The user value that is populating a JSON field
     * @param array     $json      The existing JSON structure that will be populated
     */
    public function stockJson(Parameter $param, $userValue, array $json): array
    {
        if ($param->isArray()) {
            $userValue = $this->stockArrayJson($param, $userValue);
        } elseif ($param->isObject()) {
            $userValue = $this->stockObjectJson($param, $this->serializeObjectValue($userValue));
        }
        // Populate the final value
        return $this->stockValue($param, $userValue, $json);
    }

    private function serializeObjectValue($value)
    {
        if (is_object($value)) {
            if ($value instanceof Serializable) {
                $value = $value->serialize();
            } elseif (!($value instanceof \stdClass)) {
                throw new \InvalidArgumentException(sprintf('When an object value is provided, it must either be \stdClass or implement the Serializable '.'interface, you provided %s', print_r($value, true)));
            }
        }

        return (object) $value;
    }
}
