<?php

/**
 * PKCS Formatted Key Handler
 *
 * PHP version 5
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2015 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt\Common\Formats\Keys;

/**
 * PKCS1 Formatted Key Handler
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class PKCS
{
    /**
     * Auto-detect the format
     */
    const MODE_ANY = 0;
    /**
     * Require base64-encoded PEM's be supplied
     */
    const MODE_PEM = 1;
    /**
     * Require raw DER's be supplied
     */
    const MODE_DER = 2;
    /**#@-*/

    /**
     * Is the key a base-64 encoded PEM, DER or should it be auto-detected?
     *
     * @var int
     */
    protected static $format = self::MODE_ANY;

    /**
     * Require base64-encoded PEM's be supplied
     *
     */
    public static function requirePEM()
    {
        self::$format = self::MODE_PEM;
    }

    /**
     * Require raw DER's be supplied
     *
     */
    public static function requireDER()
    {
        self::$format = self::MODE_DER;
    }

    /**
     * Accept any format and auto detect the format
     *
     * This is the default setting
     *
     */
    public static function requireAny()
    {
        self::$format = self::MODE_ANY;
    }
}
