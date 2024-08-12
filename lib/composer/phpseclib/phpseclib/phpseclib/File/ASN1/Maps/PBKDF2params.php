<?php

/**
 * PBKDF2params
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
 * PBKDF2params
 *
 * from https://tools.ietf.org/html/rfc2898#appendix-A.3
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class PBKDF2params
{
    const MAP = [
        'type' => ASN1::TYPE_SEQUENCE,
        'children' => [
            // technically, this is a CHOICE in RFC2898 but the other "choice" is, currently, more of a placeholder
            // in the RFC
            'salt' => ['type' => ASN1::TYPE_OCTET_STRING],
            'iterationCount' => ['type' => ASN1::TYPE_INTEGER],
            'keyLength' => [
                'type' => ASN1::TYPE_INTEGER,
                'optional' => true
            ],
            'prf' => AlgorithmIdentifier::MAP + ['optional' => true]
        ]
    ];
}
