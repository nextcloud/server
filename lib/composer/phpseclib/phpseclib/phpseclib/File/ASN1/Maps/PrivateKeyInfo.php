<?php

/**
 * PrivateKeyInfo
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
 * PrivateKeyInfo
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class PrivateKeyInfo
{
    const MAP = [
        'type' => ASN1::TYPE_SEQUENCE,
        'children' => [
            'version' => [
                'type' => ASN1::TYPE_INTEGER,
                'mapping' => ['v1']
            ],
            'privateKeyAlgorithm' => AlgorithmIdentifier::MAP,
            'privateKey' => PrivateKey::MAP,
            'attributes' => [
                'constant' => 0,
                'optional' => true,
                'implicit' => true
            ] + Attributes::MAP
        ]
    ];
}
