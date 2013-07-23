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

namespace Aws\Common;

/**
 * Represents an enumerable set of values
 */
abstract class Enum
{
    /**
     * @var array A cache of all enum values to increase performance
     */
    protected static $cache = array();

    /**
     * Returns the names (or keys) of all of constants in the enum
     *
     * @return array
     */
    public static function keys()
    {
        return array_keys(static::values());
    }

    /**
     * Return the names and values of all the constants in the enum
     *
     * @return array
     */
    public static function values()
    {
        $class = get_called_class();

        if (!isset(self::$cache[$class])) {
            $reflected = new \ReflectionClass($class);
            self::$cache[$class] = $reflected->getConstants();
        }

        return self::$cache[$class];
    }
}
