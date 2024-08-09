<?php

/**
 * libsodium Key Handler
 *
 * Different NaCl implementations store the key differently.
 * https://blog.mozilla.org/warner/2011/11/29/ed25519-keys/ elaborates.
 * libsodium appears to use the same format as SUPERCOP.
 *
 * PHP version 5
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2015 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt\EC\Formats\Keys;

use phpseclib3\Crypt\EC\Curves\Ed25519;
use phpseclib3\Exception\UnsupportedFormatException;
use phpseclib3\Math\BigInteger;

/**
 * libsodium Key Handler
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class libsodium
{
    use Common;

    /**
     * Is invisible flag
     *
     */
    const IS_INVISIBLE = true;

    /**
     * Break a public or private key down into its constituent components
     *
     * @param string $key
     * @param string $password optional
     * @return array
     */
    public static function load($key, $password = '')
    {
        switch (strlen($key)) {
            case 32:
                $public = $key;
                break;
            case 64:
                $private = substr($key, 0, 32);
                $public = substr($key, -32);
                break;
            case 96:
                $public = substr($key, -32);
                if (substr($key, 32, 32) != $public) {
                    throw new \RuntimeException('Keys with 96 bytes should have the 2nd and 3rd set of 32 bytes match');
                }
                $private = substr($key, 0, 32);
                break;
            default:
                throw new \RuntimeException('libsodium keys need to either be 32 bytes long, 64 bytes long or 96 bytes long');
        }

        $curve = new Ed25519();
        $components = ['curve' => $curve];
        if (isset($private)) {
            $arr = $curve->extractSecret($private);
            $components['dA'] = $arr['dA'];
            $components['secret'] = $arr['secret'];
        }
        $components['QA'] = isset($public) ?
            self::extractPoint($public, $curve) :
            $curve->multiplyPoint($curve->getBasePoint(), $components['dA']);

        return $components;
    }

    /**
     * Convert an EC public key to the appropriate format
     *
     * @param \phpseclib3\Crypt\EC\Curves\Ed25519 $curve
     * @param \phpseclib3\Math\Common\FiniteField\Integer[] $publicKey
     * @return string
     */
    public static function savePublicKey(Ed25519 $curve, array $publicKey)
    {
        return $curve->encodePoint($publicKey);
    }

    /**
     * Convert a private key to the appropriate format.
     *
     * @param \phpseclib3\Math\BigInteger $privateKey
     * @param \phpseclib3\Crypt\EC\Curves\Ed25519 $curve
     * @param \phpseclib3\Math\Common\FiniteField\Integer[] $publicKey
     * @param string $secret optional
     * @param string $password optional
     * @return string
     */
    public static function savePrivateKey(BigInteger $privateKey, Ed25519 $curve, array $publicKey, $secret = null, $password = '')
    {
        if (!isset($secret)) {
            throw new \RuntimeException('Private Key does not have a secret set');
        }
        if (strlen($secret) != 32) {
            throw new \RuntimeException('Private Key secret is not of the correct length');
        }
        if (!empty($password) && is_string($password)) {
            throw new UnsupportedFormatException('libsodium private keys do not support encryption');
        }
        return $secret . $curve->encodePoint($publicKey);
    }
}
