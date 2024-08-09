<?php

/**
 * OrganizationalUnitNames
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
 * OrganizationalUnitNames
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class OrganizationalUnitNames
{
    const MAP = [
        'type' => ASN1::TYPE_SEQUENCE,
        'min' => 1,
        'max' => 4, // ub-organizational-units
        'children' => ['type' => ASN1::TYPE_PRINTABLE_STRING]
    ];
}
