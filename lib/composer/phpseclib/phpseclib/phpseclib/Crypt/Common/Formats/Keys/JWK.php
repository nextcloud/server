<?php

/**
 * JSON Web Key (RFC7517) Handler
 *
 * PHP version 5
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2015 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt\Common\Formats\Keys;

use phpseclib3\Common\Functions\Strings;

/**
 * JSON Web Key Formatted Key Handler
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class JWK
{
    /**
     * Break a public or private key down into its constituent components
     *
     * @param string $key
     * @param string $password
     * @return array
     */
    public static function load($key, $password = '')
    {
        if (!Strings::is_stringable($key)) {
            throw new \UnexpectedValueException('Key should be a string - not a ' . gettype($key));
        }

        $key = preg_replace('#\s#', '', $key); // remove whitespace

        if (PHP_VERSION_ID >= 73000) {
            $key = json_decode($key, null, 512, JSON_THROW_ON_ERROR);
        } else {
            $key = json_decode($key);
            if (!$key) {
                throw new \RuntimeException('Unable to decode JSON');
            }
        }

        if (isset($key->kty)) {
            return $key;
        }

        if (count($key->keys) != 1) {
            throw new \RuntimeException('Although the JWK key format supports multiple keys phpseclib does not');
        }

        return $key->keys[0];
    }

    /**
     * Wrap a key appropriately
     *
     * @return string
     */
    protected static function wrapKey(array $key, array $options)
    {
        return json_encode(['keys' => [$key + $options]]);
    }
}
