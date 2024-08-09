<?php

/**
 * DistributionPoint
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
 * DistributionPoint
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class DistributionPoint
{
    const MAP = [
        'type' => ASN1::TYPE_SEQUENCE,
        'children' => [
            'distributionPoint' => [
                'constant' => 0,
                'optional' => true,
                'explicit' => true
            ] + DistributionPointName::MAP,
            'reasons' => [
                'constant' => 1,
                'optional' => true,
                'implicit' => true
            ] + ReasonFlags::MAP,
            'cRLIssuer' => [
                'constant' => 2,
                'optional' => true,
                'implicit' => true
            ] + GeneralNames::MAP
        ]
    ];
}
