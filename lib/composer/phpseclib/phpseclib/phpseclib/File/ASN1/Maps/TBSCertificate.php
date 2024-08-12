<?php

/**
 * TBSCertificate
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
 * TBSCertificate
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class TBSCertificate
{
    // assert($TBSCertificate['children']['signature'] == $Certificate['children']['signatureAlgorithm'])
    const MAP = [
        'type' => ASN1::TYPE_SEQUENCE,
        'children' => [
            // technically, default implies optional, but we'll define it as being optional, none-the-less, just to
            // reenforce that fact
            'version' => [
                'type' => ASN1::TYPE_INTEGER,
                'constant' => 0,
                'optional' => true,
                'explicit' => true,
                'mapping' => ['v1', 'v2', 'v3'],
                'default' => 'v1'
            ],
            'serialNumber' => CertificateSerialNumber::MAP,
            'signature' => AlgorithmIdentifier::MAP,
            'issuer' => Name::MAP,
            'validity' => Validity::MAP,
            'subject' => Name::MAP,
            'subjectPublicKeyInfo' => SubjectPublicKeyInfo::MAP,
            // implicit means that the T in the TLV structure is to be rewritten, regardless of the type
            'issuerUniqueID' => [
                'constant' => 1,
                'optional' => true,
                'implicit' => true
            ] + UniqueIdentifier::MAP,
            'subjectUniqueID' => [
                'constant' => 2,
                'optional' => true,
                'implicit' => true
            ] + UniqueIdentifier::MAP,
            // <http://tools.ietf.org/html/rfc2459#page-74> doesn't use the EXPLICIT keyword but if
            // it's not IMPLICIT, it's EXPLICIT
            'extensions' => [
                'constant' => 3,
                'optional' => true,
                'explicit' => true
            ] + Extensions::MAP
        ]
    ];
}
