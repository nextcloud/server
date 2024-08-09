<?php

/**
 * InvalidityDate
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
 * InvalidityDate
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class InvalidityDate
{
    const MAP = ['type' => ASN1::TYPE_GENERALIZED_TIME];
}
