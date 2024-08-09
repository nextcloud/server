<?php

/**
 * PuTTY Formatted DSA Key Handler
 *
 * puttygen does not generate DSA keys with an N of anything other than 160, however,
 * it can still load them and convert them. PuTTY will load them, too, but SSH servers
 * won't accept them. Since PuTTY formatted keys are primarily used with SSH this makes
 * keys with N > 160 kinda useless, hence this handlers not supporting such keys.
 *
 * PHP version 5
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2015 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt\DSA\Formats\Keys;

use phpseclib3\Common\Functions\Strings;
use phpseclib3\Crypt\Common\Formats\Keys\PuTTY as Progenitor;
use phpseclib3\Math\BigInteger;

/**
 * PuTTY Formatted DSA Key Handler
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class PuTTY extends Progenitor
{
    /**
     * Public Handler
     *
     * @var string
     */
    const PUBLIC_HANDLER = 'phpseclib3\Crypt\DSA\Formats\Keys\OpenSSH';

    /**
     * Algorithm Identifier
     *
     * @var array
     */
    protected static $types = ['ssh-dss'];

    /**
     * Break a public or private key down into its constituent components
     *
     * @param string $key
     * @param string $password optional
     * @return array
     */
    public static function load($key, $password = '')
    {
        $components = parent::load($key, $password);
        if (!isset($components['private'])) {
            return $components;
        }
        extract($components);
        unset($components['public'], $components['private']);

        list($p, $q, $g, $y) = Strings::unpackSSH2('iiii', $public);
        list($x) = Strings::unpackSSH2('i', $private);

        return compact('p', 'q', 'g', 'y', 'x', 'comment');
    }

    /**
     * Convert a private key to the appropriate format.
     *
     * @param \phpseclib3\Math\BigInteger $p
     * @param \phpseclib3\Math\BigInteger $q
     * @param \phpseclib3\Math\BigInteger $g
     * @param \phpseclib3\Math\BigInteger $y
     * @param \phpseclib3\Math\BigInteger $x
     * @param string $password optional
     * @param array $options optional
     * @return string
     */
    public static function savePrivateKey(BigInteger $p, BigInteger $q, BigInteger $g, BigInteger $y, BigInteger $x, $password = false, array $options = [])
    {
        if ($q->getLength() != 160) {
            throw new \InvalidArgumentException('SSH only supports keys with an N (length of Group Order q) of 160');
        }

        $public = Strings::packSSH2('iiii', $p, $q, $g, $y);
        $private = Strings::packSSH2('i', $x);

        return self::wrapPrivateKey($public, $private, 'ssh-dss', $password, $options);
    }

    /**
     * Convert a public key to the appropriate format
     *
     * @param \phpseclib3\Math\BigInteger $p
     * @param \phpseclib3\Math\BigInteger $q
     * @param \phpseclib3\Math\BigInteger $g
     * @param \phpseclib3\Math\BigInteger $y
     * @return string
     */
    public static function savePublicKey(BigInteger $p, BigInteger $q, BigInteger $g, BigInteger $y)
    {
        if ($q->getLength() != 160) {
            throw new \InvalidArgumentException('SSH only supports keys with an N (length of Group Order q) of 160');
        }

        return self::wrapPublicKey(Strings::packSSH2('iiii', $p, $q, $g, $y), 'ssh-dss');
    }
}
