<?php

declare(strict_types=1);

namespace OpenStack\Common;

/**
 * Represents common functionality for populating, or "hydrating", an object with arbitrary data.
 */
trait HydratorStrategyTrait
{
    /**
     * Hydrates an object with set data.
     *
     * @param array $data    The data to set
     * @param array $aliases Any aliases
     */
    public function hydrate(array $data, array $aliases = [])
    {
        foreach ($data as $key => $val) {
            $key = isset($aliases[$key]) ? $aliases[$key] : $key;
            if (property_exists($this, $key)) {
                $this->{$key} = $val;
            }
        }
    }

    public function set(string $key, $property, array $data, callable $fn = null)
    {
        if (isset($data[$key]) && property_exists($this, $property)) {
            $value           = $fn ? call_user_func($fn, $data[$key]) : $data[$key];
            $this->$property = $value;
        }
    }
}
