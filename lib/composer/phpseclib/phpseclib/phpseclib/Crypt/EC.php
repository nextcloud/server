<?php

/**
 * Pure-PHP implementation of EC.
 *
 * PHP version 5
 *
 * Here's an example of how to create signatures and verify signatures with this library:
 * <code>
 * <?php
 * include 'vendor/autoload.php';
 *
 * $private = \phpseclib3\Crypt\EC::createKey('secp256k1');
 * $public = $private->getPublicKey();
 *
 * $plaintext = 'terrafrost';
 *
 * $signature = $private->sign($plaintext);
 *
 * echo $public->verify($plaintext, $signature) ? 'verified' : 'unverified';
 * ?>
 * </code>
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2016 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt;

use phpseclib3\Crypt\Common\AsymmetricKey;
use phpseclib3\Crypt\EC\BaseCurves\Montgomery as MontgomeryCurve;
use phpseclib3\Crypt\EC\BaseCurves\TwistedEdwards as TwistedEdwardsCurve;
use phpseclib3\Crypt\EC\Curves\Curve25519;
use phpseclib3\Crypt\EC\Curves\Ed25519;
use phpseclib3\Crypt\EC\Curves\Ed448;
use phpseclib3\Crypt\EC\Formats\Keys\PKCS1;
use phpseclib3\Crypt\EC\Parameters;
use phpseclib3\Crypt\EC\PrivateKey;
use phpseclib3\Crypt\EC\PublicKey;
use phpseclib3\Exception\UnsupportedAlgorithmException;
use phpseclib3\Exception\UnsupportedCurveException;
use phpseclib3\Exception\UnsupportedOperationException;
use phpseclib3\File\ASN1;
use phpseclib3\File\ASN1\Maps\ECParameters;
use phpseclib3\Math\BigInteger;

/**
 * Pure-PHP implementation of EC.
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class EC extends AsymmetricKey
{
    /**
     * Algorithm Name
     *
     * @var string
     */
    const ALGORITHM = 'EC';

    /**
     * Public Key QA
     *
     * @var object[]
     */
    protected $QA;

    /**
     * Curve
     *
     * @var \phpseclib3\Crypt\EC\BaseCurves\Base
     */
    protected $curve;

    /**
     * Signature Format
     *
     * @var string
     */
    protected $format;

    /**
     * Signature Format (Short)
     *
     * @var string
     */
    protected $shortFormat;

    /**
     * Curve Name
     *
     * @var string
     */
    private $curveName;

    /**
     * Curve Order
     *
     * Used for deterministic ECDSA
     *
     * @var \phpseclib3\Math\BigInteger
     */
    protected $q;

    /**
     * Alias for the private key
     *
     * Used for deterministic ECDSA. AsymmetricKey expects $x. I don't like x because
     * with x you have x * the base point yielding an (x, y)-coordinate that is the
     * public key. But the x is different depending on which side of the equal sign
     * you're on. It's less ambiguous if you do dA * base point = (x, y)-coordinate.
     *
     * @var \phpseclib3\Math\BigInteger
     */
    protected $x;

    /**
     * Context
     *
     * @var string
     */
    protected $context;

    /**
     * Signature Format
     *
     * @var string
     */
    protected $sigFormat;

    /**
     * Create public / private key pair.
     *
     * @param string $curve
     * @return \phpseclib3\Crypt\EC\PrivateKey
     */
    public static function createKey($curve)
    {
        self::initialize_static_variables();

        $class = new \ReflectionClass(static::class);
        if ($class->isFinal()) {
            throw new \RuntimeException('createKey() should not be called from final classes (' . static::class . ')');
        }

        if (!isset(self::$engines['PHP'])) {
            self::useBestEngine();
        }

        $curve = strtolower($curve);
        if (self::$engines['libsodium'] && $curve == 'ed25519' && function_exists('sodium_crypto_sign_keypair')) {
            $kp = sodium_crypto_sign_keypair();

            $privatekey = EC::loadFormat('libsodium', sodium_crypto_sign_secretkey($kp));
            //$publickey = EC::loadFormat('libsodium', sodium_crypto_sign_publickey($kp));

            $privatekey->curveName = 'Ed25519';
            //$publickey->curveName = $curve;

            return $privatekey;
        }

        $privatekey = new PrivateKey();

        $curveName = $curve;
        if (preg_match('#(?:^curve|^ed)\d+$#', $curveName)) {
            $curveName = ucfirst($curveName);
        } elseif (substr($curveName, 0, 10) == 'brainpoolp') {
            $curveName = 'brainpoolP' . substr($curveName, 10);
        }
        $curve = '\phpseclib3\Crypt\EC\Curves\\' . $curveName;

        if (!class_exists($curve)) {
            throw new UnsupportedCurveException('Named Curve of ' . $curveName . ' is not supported');
        }

        $reflect = new \ReflectionClass($curve);
        $curveName = $reflect->isFinal() ?
            $reflect->getParentClass()->getShortName() :
            $reflect->getShortName();

        $curve = new $curve();
        if ($curve instanceof TwistedEdwardsCurve) {
            $arr = $curve->extractSecret(Random::string($curve instanceof Ed448 ? 57 : 32));
            $privatekey->dA = $dA = $arr['dA'];
            $privatekey->secret = $arr['secret'];
        } else {
            $privatekey->dA = $dA = $curve->createRandomMultiplier();
        }
        if ($curve instanceof Curve25519 && self::$engines['libsodium']) {
            //$r = pack('H*', '0900000000000000000000000000000000000000000000000000000000000000');
            //$QA = sodium_crypto_scalarmult($dA->toBytes(), $r);
            $QA = sodium_crypto_box_publickey_from_secretkey($dA->toBytes());
            $privatekey->QA = [$curve->convertInteger(new BigInteger(strrev($QA), 256))];
        } else {
            $privatekey->QA = $curve->multiplyPoint($curve->getBasePoint(), $dA);
        }
        $privatekey->curve = $curve;

        //$publickey = clone $privatekey;
        //unset($publickey->dA);
        //unset($publickey->x);

        $privatekey->curveName = $curveName;
        //$publickey->curveName = $curveName;

        if ($privatekey->curve instanceof TwistedEdwardsCurve) {
            return $privatekey->withHash($curve::HASH);
        }

        return $privatekey;
    }

    /**
     * OnLoad Handler
     *
     * @return bool
     */
    protected static function onLoad(array $components)
    {
        if (!isset(self::$engines['PHP'])) {
            self::useBestEngine();
        }

        if (!isset($components['dA']) && !isset($components['QA'])) {
            $new = new Parameters();
            $new->curve = $components['curve'];
            return $new;
        }

        $new = isset($components['dA']) ?
            new PrivateKey() :
            new PublicKey();
        $new->curve = $components['curve'];
        $new->QA = $components['QA'];

        if (isset($components['dA'])) {
            $new->dA = $components['dA'];
            $new->secret = $components['secret'];
        }

        if ($new->curve instanceof TwistedEdwardsCurve) {
            return $new->withHash($components['curve']::HASH);
        }

        return $new;
    }

    /**
     * Constructor
     *
     * PublicKey and PrivateKey objects can only be created from abstract RSA class
     */
    protected function __construct()
    {
        $this->sigFormat = self::validatePlugin('Signature', 'ASN1');
        $this->shortFormat = 'ASN1';

        parent::__construct();
    }

    /**
     * Returns the curve
     *
     * Returns a string if it's a named curve, an array if not
     *
     * @return string|array
     */
    public function getCurve()
    {
        if ($this->curveName) {
            return $this->curveName;
        }

        if ($this->curve instanceof MontgomeryCurve) {
            $this->curveName = $this->curve instanceof Curve25519 ? 'Curve25519' : 'Curve448';
            return $this->curveName;
        }

        if ($this->curve instanceof TwistedEdwardsCurve) {
            $this->curveName = $this->curve instanceof Ed25519 ? 'Ed25519' : 'Ed448';
            return $this->curveName;
        }

        $params = $this->getParameters()->toString('PKCS8', ['namedCurve' => true]);
        $decoded = ASN1::extractBER($params);
        $decoded = ASN1::decodeBER($decoded);
        $decoded = ASN1::asn1map($decoded[0], ECParameters::MAP);
        if (isset($decoded['namedCurve'])) {
            $this->curveName = $decoded['namedCurve'];
            return $decoded['namedCurve'];
        }

        if (!$namedCurves) {
            PKCS1::useSpecifiedCurve();
        }

        return $decoded;
    }

    /**
     * Returns the key size
     *
     * Quoting https://tools.ietf.org/html/rfc5656#section-2,
     *
     * "The size of a set of elliptic curve domain parameters on a prime
     *  curve is defined as the number of bits in the binary representation
     *  of the field order, commonly denoted by p.  Size on a
     *  characteristic-2 curve is defined as the number of bits in the binary
     *  representation of the field, commonly denoted by m.  A set of
     *  elliptic curve domain parameters defines a group of order n generated
     *  by a base point P"
     *
     * @return int
     */
    public function getLength()
    {
        return $this->curve->getLength();
    }

    /**
     * Returns the current engine being used
     *
     * @see self::useInternalEngine()
     * @see self::useBestEngine()
     * @return string
     */
    public function getEngine()
    {
        if (!isset(self::$engines['PHP'])) {
            self::useBestEngine();
        }
        if ($this->curve instanceof TwistedEdwardsCurve) {
            return $this->curve instanceof Ed25519 && self::$engines['libsodium'] && !isset($this->context) ?
                'libsodium' : 'PHP';
        }

        return self::$engines['OpenSSL'] && in_array($this->hash->getHash(), openssl_get_md_methods()) ?
            'OpenSSL' : 'PHP';
    }

    /**
     * Returns the public key coordinates as a string
     *
     * Used by ECDH
     *
     * @return string
     */
    public function getEncodedCoordinates()
    {
        if ($this->curve instanceof MontgomeryCurve) {
            return strrev($this->QA[0]->toBytes(true));
        }
        if ($this->curve instanceof TwistedEdwardsCurve) {
            return $this->curve->encodePoint($this->QA);
        }
        return "\4" . $this->QA[0]->toBytes(true) . $this->QA[1]->toBytes(true);
    }

    /**
     * Returns the parameters
     *
     * @see self::getPublicKey()
     * @param string $type optional
     * @return mixed
     */
    public function getParameters($type = 'PKCS1')
    {
        $type = self::validatePlugin('Keys', $type, 'saveParameters');

        $key = $type::saveParameters($this->curve);

        return EC::load($key, 'PKCS1')
            ->withHash($this->hash->getHash())
            ->withSignatureFormat($this->shortFormat);
    }

    /**
     * Determines the signature padding mode
     *
     * Valid values are: ASN1, SSH2, Raw
     *
     * @param string $format
     */
    public function withSignatureFormat($format)
    {
        if ($this->curve instanceof MontgomeryCurve) {
            throw new UnsupportedOperationException('Montgomery Curves cannot be used to create signatures');
        }

        $new = clone $this;
        $new->shortFormat = $format;
        $new->sigFormat = self::validatePlugin('Signature', $format);
        return $new;
    }

    /**
     * Returns the signature format currently being used
     *
     */
    public function getSignatureFormat()
    {
        return $this->shortFormat;
    }

    /**
     * Sets the context
     *
     * Used by Ed25519 / Ed448.
     *
     * @see self::sign()
     * @see self::verify()
     * @param string $context optional
     */
    public function withContext($context = null)
    {
        if (!$this->curve instanceof TwistedEdwardsCurve) {
            throw new UnsupportedCurveException('Only Ed25519 and Ed448 support contexts');
        }

        $new = clone $this;
        if (!isset($context)) {
            $new->context = null;
            return $new;
        }
        if (!is_string($context)) {
            throw new \InvalidArgumentException('setContext expects a string');
        }
        if (strlen($context) > 255) {
            throw new \LengthException('The context is supposed to be, at most, 255 bytes long');
        }
        $new->context = $context;
        return $new;
    }

    /**
     * Returns the signature format currently being used
     *
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * Determines which hashing function should be used
     *
     * @param string $hash
     */
    public function withHash($hash)
    {
        if ($this->curve instanceof MontgomeryCurve) {
            throw new UnsupportedOperationException('Montgomery Curves cannot be used to create signatures');
        }
        if ($this->curve instanceof Ed25519 && $hash != 'sha512') {
            throw new UnsupportedAlgorithmException('Ed25519 only supports sha512 as a hash');
        }
        if ($this->curve instanceof Ed448 && $hash != 'shake256-912') {
            throw new UnsupportedAlgorithmException('Ed448 only supports shake256 with a length of 114 bytes');
        }

        return parent::withHash($hash);
    }

    /**
     * __toString() magic method
     *
     * @return string
     */
    public function __toString()
    {
        if ($this->curve instanceof MontgomeryCurve) {
            return '';
        }

        return parent::__toString();
    }
}
