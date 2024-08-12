<?php

/**
 * Curve
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
 * Curve
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class Curve
{
    const MAP = [
        'type' => ASN1::TYPE_SEQUENCE,
        'children' => [
            'a' => FieldElement::MAP,
            'b' => FieldElement::MAP,
            'seed' => [
                'type' => ASN1::TYPE_BIT_STRING,
                'optional' => true
            ]
        ]
    ];
}
