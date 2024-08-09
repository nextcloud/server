<?php

/**
 * PersonalName
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
 * PersonalName
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class PersonalName
{
    const MAP = [
        'type' => ASN1::TYPE_SET,
        'children' => [
            'surname' => [
                'type' => ASN1::TYPE_PRINTABLE_STRING,
                'constant' => 0,
                'optional' => true,
                'implicit' => true
            ],
            'given-name' => [
                'type' => ASN1::TYPE_PRINTABLE_STRING,
                'constant' => 1,
                'optional' => true,
                'implicit' => true
            ],
            'initials' => [
                'type' => ASN1::TYPE_PRINTABLE_STRING,
                'constant' => 2,
                'optional' => true,
                'implicit' => true
            ],
            'generation-qualifier' => [
                'type' => ASN1::TYPE_PRINTABLE_STRING,
                'constant' => 3,
                'optional' => true,
                'implicit' => true
            ]
        ]
    ];
}
