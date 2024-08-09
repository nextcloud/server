<?php

/**
 * RelativeDistinguishedName
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
 * RelativeDistinguishedName
 *
 * In practice, RDNs containing multiple name-value pairs (called "multivalued RDNs") are rare,
 * but they can be useful at times when either there is no unique attribute in the entry or you
 * want to ensure that the entry's DN contains some useful identifying information.
 *
 * - https://www.opends.org/wiki/page/DefinitionRelativeDistinguishedName
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class RelativeDistinguishedName
{
    const MAP = [
        'type' => ASN1::TYPE_SET,
        'min' => 1,
        'max' => -1,
        'children' => AttributeTypeAndValue::MAP
    ];
}
