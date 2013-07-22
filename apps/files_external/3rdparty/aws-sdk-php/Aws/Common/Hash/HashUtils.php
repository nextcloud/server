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

namespace Aws\Common\Hash;

use Aws\Common\Exception\InvalidArgumentException;

/**
 * Contains hashing utilities
 */
class HashUtils
{
    /**
     * Converts a hash in hex form to binary form
     *
     * @param string $hash Hash in hex form
     *
     * @return string Hash in binary form
     */
    public static function hexToBin($hash)
    {
        // If using PHP 5.4, there is a native function to convert from hex to binary
        static $useNative;
        if ($useNative === null) {
            $useNative = function_exists('hex2bin');
        }

        return $useNative ? hex2bin($hash) : pack("H*", $hash);
    }

    /**
     * Converts a hash in binary form to hex form
     *
     * @param string $hash Hash in binary form
     *
     * @return string Hash in hex form
     */
    public static function binToHex($hash)
    {
        return bin2hex($hash);
    }

    /**
     * Checks if the algorithm specified exists and throws an exception if it does not
     *
     * @param string $algorithm Name of the algorithm to validate
     *
     * @return bool
     * @throws InvalidArgumentException if the algorithm doesn't exist
     */
    public static function validateAlgorithm($algorithm)
    {
        if (!in_array($algorithm, hash_algos(), true)) {
            throw new InvalidArgumentException("The hashing algorithm specified ({$algorithm}) does not exist.");
        }

        return true;
    }
}
