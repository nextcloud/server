<?php

/**
 * Characteristic_two
 *
 * PHP version 5
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2016 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\File\ASN1\Maps;

use phpseclib3\File\ASN1;

/**
 * Characteristic_two
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class Characteristic_two
{
    const MAP = [
        'type' => ASN1::TYPE_SEQUENCE,
        'children' => [
            'm' => ['type' => ASN1::TYPE_INTEGER], // field size 2**m
            'basis' => ['type' => ASN1::TYPE_OBJECT_IDENTIFIER],
            'parameters' => [
                'type' => ASN1::TYPE_ANY,
                'optional' => true
            ]
        ]
    ];
}
