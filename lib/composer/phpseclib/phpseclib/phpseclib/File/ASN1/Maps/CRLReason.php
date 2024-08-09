<?php

/**
 * CRLReason
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
 * CRLReason
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class CRLReason
{
    const MAP = [
        'type' => ASN1::TYPE_ENUMERATED,
        'mapping' => [
            'unspecified',
            'keyCompromise',
            'cACompromise',
            'affiliationChanged',
            'superseded',
            'cessationOfOperation',
            'certificateHold',
            // Value 7 is not used.
            8 => 'removeFromCRL',
            'privilegeWithdrawn',
            'aACompromise'
        ]
    ];
}
