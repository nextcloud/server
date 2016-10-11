<?php

namespace Guzzle\Common;

/**
 * Interfaces that adds a factory method which is used to instantiate a class from an array of configuration options.
 */
interface FromConfigInterface
{
    /**
     * Static factory method used to turn an array or collection of configuration data into an instantiated object.
     *
     * @param array|Collection $config Configuration data
     *
     * @return FromConfigInterface
     */
    public static function factory($config = array());
}
