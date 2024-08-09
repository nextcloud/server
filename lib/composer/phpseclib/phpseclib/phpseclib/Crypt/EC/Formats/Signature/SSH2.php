<?php

/**
 * SSH2 Signature Handler
 *
 * PHP version 5
 *
 * Handles signatures in the format used by SSH2
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2016 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt\EC\Formats\Signature;

use phpseclib3\Common\Functions\Strings;
use phpseclib3\Math\BigInteger;

/**
 * SSH2 Signature Handler
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class SSH2
{
    /**
     * Loads a signature
     *
     * @param string $sig
     * @return mixed
     */
    public static function load($sig)
    {
        if (!is_string($sig)) {
            return false;
        }

        $result = Strings::unpackSSH2('ss', $sig);
        if ($result === false) {
            return false;
        }
        list($type, $blob) = $result;
        switch ($type) {
            // see https://tools.ietf.org/html/rfc5656#section-3.1.2
            case 'ecdsa-sha2-nistp256':
            case 'ecdsa-sha2-nistp384':
            case 'ecdsa-sha2-nistp521':
                break;
            default:
                return false;
        }

        $result = Strings::unpackSSH2('ii', $blob);
        if ($result === false) {
            return false;
        }

        return [
            'r' => $result[0],
            's' => $result[1]
        ];
    }

    /**
     * Returns a signature in the appropriate format
     *
     * @param \phpseclib3\Math\BigInteger $r
     * @param \phpseclib3\Math\BigInteger $s
     * @param string $curve
     * @return string
     */
    public static function save(BigInteger $r, BigInteger $s, $curve)
    {
        switch ($curve) {
            case 'secp256r1':
                $curve = 'nistp256';
                break;
            case 'secp384r1':
                $curve = 'nistp384';
                break;
            case 'secp521r1':
                $curve = 'nistp521';
                break;
            default:
                return false;
        }

        $blob = Strings::packSSH2('ii', $r, $s);

        return Strings::packSSH2('ss', 'ecdsa-sha2-' . $curve, $blob);
    }
}
