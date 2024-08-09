<?php

/**
 * JSON Web Key (RFC7517 / RFC8037) Formatted EC Handler
 *
 * PHP version 5
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2015 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt\EC\Formats\Keys;

use phpseclib3\Common\Functions\Strings;
use phpseclib3\Crypt\Common\Formats\Keys\JWK as Progenitor;
use phpseclib3\Crypt\EC\BaseCurves\Base as BaseCurve;
use phpseclib3\Crypt\EC\BaseCurves\TwistedEdwards as TwistedEdwardsCurve;
use phpseclib3\Crypt\EC\Curves\Ed25519;
use phpseclib3\Crypt\EC\Curves\secp256k1;
use phpseclib3\Crypt\EC\Curves\secp256r1;
use phpseclib3\Crypt\EC\Curves\secp384r1;
use phpseclib3\Crypt\EC\Curves\secp521r1;
use phpseclib3\Exception\UnsupportedCurveException;
use phpseclib3\Math\BigInteger;

/**
 * JWK Formatted EC Handler
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class JWK extends Progenitor
{
    use Common;

    /**
     * Break a public or private key down into its constituent components
     *
     * @param string $key
     * @param string $password optional
     * @return array
     */
    public static function load($key, $password = '')
    {
        $key = parent::load($key, $password);

        switch ($key->kty) {
            case 'EC':
                switch ($key->crv) {
                    case 'P-256':
                    case 'P-384':
                    case 'P-521':
                    case 'secp256k1':
                        break;
                    default:
                        throw new UnsupportedCurveException('Only P-256, P-384, P-521 and secp256k1 curves are accepted (' . $key->crv . ' provided)');
                }
                break;
            case 'OKP':
                switch ($key->crv) {
                    case 'Ed25519':
                    case 'Ed448':
                        break;
                    default:
                        throw new UnsupportedCurveException('Only Ed25519 and Ed448 curves are accepted (' . $key->crv . ' provided)');
                }
                break;
            default:
                throw new \Exception('Only EC and OKP JWK keys are supported');
        }

        $curve = '\phpseclib3\Crypt\EC\Curves\\' . str_replace('P-', 'nistp', $key->crv);
        $curve = new $curve();

        if ($curve instanceof TwistedEdwardsCurve) {
            $QA = self::extractPoint(Strings::base64url_decode($key->x), $curve);
            if (!isset($key->d)) {
                return compact('curve', 'QA');
            }
            $arr = $curve->extractSecret(Strings::base64url_decode($key->d));
            return compact('curve', 'QA') + $arr;
        }

        $QA = [
            $curve->convertInteger(new BigInteger(Strings::base64url_decode($key->x), 256)),
            $curve->convertInteger(new BigInteger(Strings::base64url_decode($key->y), 256))
        ];

        if (!$curve->verifyPoint($QA)) {
            throw new \RuntimeException('Unable to verify that point exists on curve');
        }

        if (!isset($key->d)) {
            return compact('curve', 'QA');
        }

        $dA = new BigInteger(Strings::base64url_decode($key->d), 256);

        $curve->rangeCheck($dA);

        return compact('curve', 'dA', 'QA');
    }

    /**
     * Returns the alias that corresponds to a curve
     *
     * @return string
     */
    private static function getAlias(BaseCurve $curve)
    {
        switch (true) {
            case $curve instanceof secp256r1:
                return 'P-256';
            case $curve instanceof secp384r1:
                return 'P-384';
            case $curve instanceof secp521r1:
                return 'P-521';
            case $curve instanceof secp256k1:
                return 'secp256k1';
        }

        $reflect = new \ReflectionClass($curve);
        $curveName = $reflect->isFinal() ?
            $reflect->getParentClass()->getShortName() :
            $reflect->getShortName();
        throw new UnsupportedCurveException("$curveName is not a supported curve");
    }

    /**
     * Return the array superstructure for an EC public key
     *
     * @param \phpseclib3\Crypt\EC\BaseCurves\Base $curve
     * @param \phpseclib3\Math\Common\FiniteField\Integer[] $publicKey
     * @return array
     */
    private static function savePublicKeyHelper(BaseCurve $curve, array $publicKey)
    {
        if ($curve instanceof TwistedEdwardsCurve) {
            return [
                'kty' => 'OKP',
                'crv' => $curve instanceof Ed25519 ? 'Ed25519' : 'Ed448',
                'x' => Strings::base64url_encode($curve->encodePoint($publicKey))
            ];
        }

        return [
            'kty' => 'EC',
            'crv' => self::getAlias($curve),
            'x' => Strings::base64url_encode($publicKey[0]->toBytes()),
            'y' => Strings::base64url_encode($publicKey[1]->toBytes())
        ];
    }

    /**
     * Convert an EC public key to the appropriate format
     *
     * @param \phpseclib3\Crypt\EC\BaseCurves\Base $curve
     * @param \phpseclib3\Math\Common\FiniteField\Integer[] $publicKey
     * @param array $options optional
     * @return string
     */
    public static function savePublicKey(BaseCurve $curve, array $publicKey, array $options = [])
    {
        $key = self::savePublicKeyHelper($curve, $publicKey);

        return self::wrapKey($key, $options);
    }

    /**
     * Convert a private key to the appropriate format.
     *
     * @param \phpseclib3\Math\BigInteger $privateKey
     * @param \phpseclib3\Crypt\EC\Curves\Ed25519 $curve
     * @param \phpseclib3\Math\Common\FiniteField\Integer[] $publicKey
     * @param string $secret optional
     * @param string $password optional
     * @param array $options optional
     * @return string
     */
    public static function savePrivateKey(BigInteger $privateKey, BaseCurve $curve, array $publicKey, $secret = null, $password = '', array $options = [])
    {
        $key = self::savePublicKeyHelper($curve, $publicKey);
        $key['d'] = $curve instanceof TwistedEdwardsCurve ? $secret : $privateKey->toBytes();
        $key['d'] = Strings::base64url_encode($key['d']);

        return self::wrapKey($key, $options);
    }
}
