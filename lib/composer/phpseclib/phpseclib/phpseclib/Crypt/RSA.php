<?php

/**
 * Pure-PHP PKCS#1 (v2.1) compliant implementation of RSA.
 *
 * PHP version 5
 *
 * Here's an example of how to encrypt and decrypt text with this library:
 * <code>
 * <?php
 * include 'vendor/autoload.php';
 *
 * $private = \phpseclib3\Crypt\RSA::createKey();
 * $public = $private->getPublicKey();
 *
 * $plaintext = 'terrafrost';
 *
 * $ciphertext = $public->encrypt($plaintext);
 *
 * echo $private->decrypt($ciphertext);
 * ?>
 * </code>
 *
 * Here's an example of how to create signatures and verify signatures with this library:
 * <code>
 * <?php
 * include 'vendor/autoload.php';
 *
 * $private = \phpseclib3\Crypt\RSA::createKey();
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
 * One thing to consider when using this: so phpseclib uses PSS mode by default.
 * Technically, id-RSASSA-PSS has a different key format than rsaEncryption. So
 * should phpseclib save to the id-RSASSA-PSS format by default or the
 * rsaEncryption format? For stand-alone keys I figure rsaEncryption is better
 * because SSH doesn't use PSS and idk how many SSH servers would be able to
 * decode an id-RSASSA-PSS key. For X.509 certificates the id-RSASSA-PSS
 * format is used by default (unless you change it up to use PKCS1 instead)
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2009 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt;

use phpseclib3\Crypt\Common\AsymmetricKey;
use phpseclib3\Crypt\RSA\Formats\Keys\PSS;
use phpseclib3\Crypt\RSA\PrivateKey;
use phpseclib3\Crypt\RSA\PublicKey;
use phpseclib3\Exception\InconsistentSetupException;
use phpseclib3\Exception\UnsupportedAlgorithmException;
use phpseclib3\Math\BigInteger;

/**
 * Pure-PHP PKCS#1 compliant implementation of RSA.
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class RSA extends AsymmetricKey
{
    /**
     * Algorithm Name
     *
     * @var string
     */
    const ALGORITHM = 'RSA';

    /**
     * Use {@link http://en.wikipedia.org/wiki/Optimal_Asymmetric_Encryption_Padding Optimal Asymmetric Encryption Padding}
     * (OAEP) for encryption / decryption.
     *
     * Uses sha256 by default
     *
     * @see self::setHash()
     * @see self::setMGFHash()
     * @see self::encrypt()
     * @see self::decrypt()
     */
    const ENCRYPTION_OAEP = 1;

    /**
     * Use PKCS#1 padding.
     *
     * Although self::PADDING_OAEP / self::PADDING_PSS  offers more security, including PKCS#1 padding is necessary for purposes of backwards
     * compatibility with protocols (like SSH-1) written before OAEP's introduction.
     *
     * @see self::encrypt()
     * @see self::decrypt()
     */
    const ENCRYPTION_PKCS1 = 2;

    /**
     * Do not use any padding
     *
     * Although this method is not recommended it can none-the-less sometimes be useful if you're trying to decrypt some legacy
     * stuff, if you're trying to diagnose why an encrypted message isn't decrypting, etc.
     *
     * @see self::encrypt()
     * @see self::decrypt()
     */
    const ENCRYPTION_NONE = 4;

    /**
     * Use the Probabilistic Signature Scheme for signing
     *
     * Uses sha256 and 0 as the salt length
     *
     * @see self::setSaltLength()
     * @see self::setMGFHash()
     * @see self::setHash()
     * @see self::sign()
     * @see self::verify()
     * @see self::setHash()
     */
    const SIGNATURE_PSS = 16;

    /**
     * Use a relaxed version of PKCS#1 padding for signature verification
     *
     * @see self::sign()
     * @see self::verify()
     * @see self::setHash()
     */
    const SIGNATURE_RELAXED_PKCS1 = 32;

    /**
     * Use PKCS#1 padding for signature verification
     *
     * @see self::sign()
     * @see self::verify()
     * @see self::setHash()
     */
    const SIGNATURE_PKCS1 = 64;

    /**
     * Encryption padding mode
     *
     * @var int
     */
    protected $encryptionPadding = self::ENCRYPTION_OAEP;

    /**
     * Signature padding mode
     *
     * @var int
     */
    protected $signaturePadding = self::SIGNATURE_PSS;

    /**
     * Length of hash function output
     *
     * @var int
     */
    protected $hLen;

    /**
     * Length of salt
     *
     * @var int
     */
    protected $sLen;

    /**
     * Label
     *
     * @var string
     */
    protected $label = '';

    /**
     * Hash function for the Mask Generation Function
     *
     * @var \phpseclib3\Crypt\Hash
     */
    protected $mgfHash;

    /**
     * Length of MGF hash function output
     *
     * @var int
     */
    protected $mgfHLen;

    /**
     * Modulus (ie. n)
     *
     * @var \phpseclib3\Math\BigInteger
     */
    protected $modulus;

    /**
     * Modulus length
     *
     * @var \phpseclib3\Math\BigInteger
     */
    protected $k;

    /**
     * Exponent (ie. e or d)
     *
     * @var \phpseclib3\Math\BigInteger
     */
    protected $exponent;

    /**
     * Default public exponent
     *
     * @var int
     * @link http://en.wikipedia.org/wiki/65537_%28number%29
     */
    private static $defaultExponent = 65537;

    /**
     * Enable Blinding?
     *
     * @var bool
     */
    protected static $enableBlinding = true;

    /**
     * OpenSSL configuration file name.
     *
     * @see self::createKey()
     * @var ?string
     */
    protected static $configFile;

    /**
     * Smallest Prime
     *
     * Per <http://cseweb.ucsd.edu/~hovav/dist/survey.pdf#page=5>, this number ought not result in primes smaller
     * than 256 bits. As a consequence if the key you're trying to create is 1024 bits and you've set smallestPrime
     * to 384 bits then you're going to get a 384 bit prime and a 640 bit prime (384 + 1024 % 384). At least if
     * engine is set to self::ENGINE_INTERNAL. If Engine is set to self::ENGINE_OPENSSL then smallest Prime is
     * ignored (ie. multi-prime RSA support is more intended as a way to speed up RSA key generation when there's
     * a chance neither gmp nor OpenSSL are installed)
     *
     * @var int
     */
    private static $smallestPrime = 4096;

    /**
     * Public Exponent
     *
     * @var \phpseclib3\Math\BigInteger
     */
    protected $publicExponent;

    /**
     * Sets the public exponent for key generation
     *
     * This will be 65537 unless changed.
     *
     * @param int $val
     */
    public static function setExponent($val)
    {
        self::$defaultExponent = $val;
    }

    /**
     * Sets the smallest prime number in bits. Used for key generation
     *
     * This will be 4096 unless changed.
     *
     * @param int $val
     */
    public static function setSmallestPrime($val)
    {
        self::$smallestPrime = $val;
    }

    /**
     * Sets the OpenSSL config file path
     *
     * Set to the empty string to use the default config file
     *
     * @param string $val
     */
    public static function setOpenSSLConfigPath($val)
    {
        self::$configFile = $val;
    }

    /**
     * Create a private key
     *
     * The public key can be extracted from the private key
     *
     * @return RSA\PrivateKey
     * @param int $bits
     */
    public static function createKey($bits = 2048)
    {
        self::initialize_static_variables();

        $class = new \ReflectionClass(static::class);
        if ($class->isFinal()) {
            throw new \RuntimeException('createKey() should not be called from final classes (' . static::class . ')');
        }

        $regSize = $bits >> 1; // divide by two to see how many bits P and Q would be
        if ($regSize > self::$smallestPrime) {
            $num_primes = floor($bits / self::$smallestPrime);
            $regSize = self::$smallestPrime;
        } else {
            $num_primes = 2;
        }

        if ($num_primes == 2 && $bits >= 384 && self::$defaultExponent == 65537) {
            if (!isset(self::$engines['PHP'])) {
                self::useBestEngine();
            }

            // OpenSSL uses 65537 as the exponent and requires RSA keys be 384 bits minimum
            if (self::$engines['OpenSSL']) {
                $config = [];
                if (self::$configFile) {
                    $config['config'] = self::$configFile;
                }
                $rsa = openssl_pkey_new(['private_key_bits' => $bits] + $config);
                openssl_pkey_export($rsa, $privatekeystr, null, $config);

                // clear the buffer of error strings stemming from a minimalistic openssl.cnf
                // https://github.com/php/php-src/issues/11054 talks about other errors this'll pick up
                while (openssl_error_string() !== false) {
                }

                return RSA::load($privatekeystr);
            }
        }

        static $e;
        if (!isset($e)) {
            $e = new BigInteger(self::$defaultExponent);
        }

        $n = clone self::$one;
        $exponents = $coefficients = $primes = [];
        $lcm = [
            'top' => clone self::$one,
            'bottom' => false
        ];

        do {
            for ($i = 1; $i <= $num_primes; $i++) {
                if ($i != $num_primes) {
                    $primes[$i] = BigInteger::randomPrime($regSize);
                } else {
                    extract(BigInteger::minMaxBits($bits));
                    /** @var BigInteger $min
                     *  @var BigInteger $max
                     */
                    list($min) = $min->divide($n);
                    $min = $min->add(self::$one);
                    list($max) = $max->divide($n);
                    $primes[$i] = BigInteger::randomRangePrime($min, $max);
                }

                // the first coefficient is calculated differently from the rest
                // ie. instead of being $primes[1]->modInverse($primes[2]), it's $primes[2]->modInverse($primes[1])
                if ($i > 2) {
                    $coefficients[$i] = $n->modInverse($primes[$i]);
                }

                $n = $n->multiply($primes[$i]);

                $temp = $primes[$i]->subtract(self::$one);

                // textbook RSA implementations use Euler's totient function instead of the least common multiple.
                // see http://en.wikipedia.org/wiki/Euler%27s_totient_function
                $lcm['top'] = $lcm['top']->multiply($temp);
                $lcm['bottom'] = $lcm['bottom'] === false ? $temp : $lcm['bottom']->gcd($temp);
            }

            list($temp) = $lcm['top']->divide($lcm['bottom']);
            $gcd = $temp->gcd($e);
            $i0 = 1;
        } while (!$gcd->equals(self::$one));

        $coefficients[2] = $primes[2]->modInverse($primes[1]);

        $d = $e->modInverse($temp);

        foreach ($primes as $i => $prime) {
            $temp = $prime->subtract(self::$one);
            $exponents[$i] = $e->modInverse($temp);
        }

        // from <http://tools.ietf.org/html/rfc3447#appendix-A.1.2>:
        // RSAPrivateKey ::= SEQUENCE {
        //     version           Version,
        //     modulus           INTEGER,  -- n
        //     publicExponent    INTEGER,  -- e
        //     privateExponent   INTEGER,  -- d
        //     prime1            INTEGER,  -- p
        //     prime2            INTEGER,  -- q
        //     exponent1         INTEGER,  -- d mod (p-1)
        //     exponent2         INTEGER,  -- d mod (q-1)
        //     coefficient       INTEGER,  -- (inverse of q) mod p
        //     otherPrimeInfos   OtherPrimeInfos OPTIONAL
        // }
        $privatekey = new PrivateKey();
        $privatekey->modulus = $n;
        $privatekey->k = $bits >> 3;
        $privatekey->publicExponent = $e;
        $privatekey->exponent = $d;
        $privatekey->primes = $primes;
        $privatekey->exponents = $exponents;
        $privatekey->coefficients = $coefficients;

        /*
        $publickey = new PublicKey;
        $publickey->modulus = $n;
        $publickey->k = $bits >> 3;
        $publickey->exponent = $e;
        $publickey->publicExponent = $e;
        $publickey->isPublic = true;
        */

        return $privatekey;
    }

    /**
     * OnLoad Handler
     *
     * @return bool
     */
    protected static function onLoad(array $components)
    {
        $key = $components['isPublicKey'] ?
            new PublicKey() :
            new PrivateKey();

        $key->modulus = $components['modulus'];
        $key->publicExponent = $components['publicExponent'];
        $key->k = $key->modulus->getLengthInBytes();

        if ($components['isPublicKey'] || !isset($components['privateExponent'])) {
            $key->exponent = $key->publicExponent;
        } else {
            $key->privateExponent = $components['privateExponent'];
            $key->exponent = $key->privateExponent;
            $key->primes = $components['primes'];
            $key->exponents = $components['exponents'];
            $key->coefficients = $components['coefficients'];
        }

        if ($components['format'] == PSS::class) {
            // in the X509 world RSA keys are assumed to use PKCS1 padding by default. only if the key is
            // explicitly a PSS key is the use of PSS assumed. phpseclib does not work like this. phpseclib
            // uses PSS padding by default. it assumes the more secure method by default and altho it provides
            // for the less secure PKCS1 method you have to go out of your way to use it. this is consistent
            // with the latest trends in crypto. libsodium (NaCl) is actually a little more extreme in that
            // not only does it defaults to the most secure methods - it doesn't even let you choose less
            // secure methods
            //$key = $key->withPadding(self::SIGNATURE_PSS);
            if (isset($components['hash'])) {
                $key = $key->withHash($components['hash']);
            }
            if (isset($components['MGFHash'])) {
                $key = $key->withMGFHash($components['MGFHash']);
            }
            if (isset($components['saltLength'])) {
                $key = $key->withSaltLength($components['saltLength']);
            }
        }

        return $key;
    }

    /**
     * Initialize static variables
     */
    protected static function initialize_static_variables()
    {
        if (!isset(self::$configFile)) {
            self::$configFile = dirname(__FILE__) . '/../openssl.cnf';
        }

        parent::initialize_static_variables();
    }

    /**
     * Constructor
     *
     * PublicKey and PrivateKey objects can only be created from abstract RSA class
     */
    protected function __construct()
    {
        parent::__construct();

        $this->hLen = $this->hash->getLengthInBytes();
        $this->mgfHash = new Hash('sha256');
        $this->mgfHLen = $this->mgfHash->getLengthInBytes();
    }

    /**
     * Integer-to-Octet-String primitive
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-4.1 RFC3447#section-4.1}.
     *
     * @param bool|\phpseclib3\Math\BigInteger $x
     * @param int $xLen
     * @return bool|string
     */
    protected function i2osp($x, $xLen)
    {
        if ($x === false) {
            return false;
        }
        $x = $x->toBytes();
        if (strlen($x) > $xLen) {
            throw new \OutOfRangeException('Resultant string length out of range');
        }
        return str_pad($x, $xLen, chr(0), STR_PAD_LEFT);
    }

    /**
     * Octet-String-to-Integer primitive
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-4.2 RFC3447#section-4.2}.
     *
     * @param string $x
     * @return \phpseclib3\Math\BigInteger
     */
    protected function os2ip($x)
    {
        return new BigInteger($x, 256);
    }

    /**
     * EMSA-PKCS1-V1_5-ENCODE
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-9.2 RFC3447#section-9.2}.
     *
     * @param string $m
     * @param int $emLen
     * @throws \LengthException if the intended encoded message length is too short
     * @return string
     */
    protected function emsa_pkcs1_v1_5_encode($m, $emLen)
    {
        $h = $this->hash->hash($m);

        // see http://tools.ietf.org/html/rfc3447#page-43
        switch ($this->hash->getHash()) {
            case 'md2':
                $t = "\x30\x20\x30\x0c\x06\x08\x2a\x86\x48\x86\xf7\x0d\x02\x02\x05\x00\x04\x10";
                break;
            case 'md5':
                $t = "\x30\x20\x30\x0c\x06\x08\x2a\x86\x48\x86\xf7\x0d\x02\x05\x05\x00\x04\x10";
                break;
            case 'sha1':
                $t = "\x30\x21\x30\x09\x06\x05\x2b\x0e\x03\x02\x1a\x05\x00\x04\x14";
                break;
            case 'sha256':
                $t = "\x30\x31\x30\x0d\x06\x09\x60\x86\x48\x01\x65\x03\x04\x02\x01\x05\x00\x04\x20";
                break;
            case 'sha384':
                $t = "\x30\x41\x30\x0d\x06\x09\x60\x86\x48\x01\x65\x03\x04\x02\x02\x05\x00\x04\x30";
                break;
            case 'sha512':
                $t = "\x30\x51\x30\x0d\x06\x09\x60\x86\x48\x01\x65\x03\x04\x02\x03\x05\x00\x04\x40";
                break;
            // from https://www.emc.com/collateral/white-papers/h11300-pkcs-1v2-2-rsa-cryptography-standard-wp.pdf#page=40
            case 'sha224':
                $t = "\x30\x2d\x30\x0d\x06\x09\x60\x86\x48\x01\x65\x03\x04\x02\x04\x05\x00\x04\x1c";
                break;
            case 'sha512/224':
                $t = "\x30\x2d\x30\x0d\x06\x09\x60\x86\x48\x01\x65\x03\x04\x02\x05\x05\x00\x04\x1c";
                break;
            case 'sha512/256':
                $t = "\x30\x31\x30\x0d\x06\x09\x60\x86\x48\x01\x65\x03\x04\x02\x06\x05\x00\x04\x20";
        }
        $t .= $h;
        $tLen = strlen($t);

        if ($emLen < $tLen + 11) {
            throw new \LengthException('Intended encoded message length too short');
        }

        $ps = str_repeat(chr(0xFF), $emLen - $tLen - 3);

        $em = "\0\1$ps\0$t";

        return $em;
    }

    /**
     * EMSA-PKCS1-V1_5-ENCODE (without NULL)
     *
     * Quoting https://tools.ietf.org/html/rfc8017#page-65,
     *
     * "The parameters field associated with id-sha1, id-sha224, id-sha256,
     *  id-sha384, id-sha512, id-sha512/224, and id-sha512/256 should
     *  generally be omitted, but if present, it shall have a value of type
     *  NULL"
     *
     * @param string $m
     * @param int $emLen
     * @return string
     */
    protected function emsa_pkcs1_v1_5_encode_without_null($m, $emLen)
    {
        $h = $this->hash->hash($m);

        // see http://tools.ietf.org/html/rfc3447#page-43
        switch ($this->hash->getHash()) {
            case 'sha1':
                $t = "\x30\x1f\x30\x07\x06\x05\x2b\x0e\x03\x02\x1a\x04\x14";
                break;
            case 'sha256':
                $t = "\x30\x2f\x30\x0b\x06\x09\x60\x86\x48\x01\x65\x03\x04\x02\x01\x04\x20";
                break;
            case 'sha384':
                $t = "\x30\x3f\x30\x0b\x06\x09\x60\x86\x48\x01\x65\x03\x04\x02\x02\x04\x30";
                break;
            case 'sha512':
                $t = "\x30\x4f\x30\x0b\x06\x09\x60\x86\x48\x01\x65\x03\x04\x02\x03\x04\x40";
                break;
            // from https://www.emc.com/collateral/white-papers/h11300-pkcs-1v2-2-rsa-cryptography-standard-wp.pdf#page=40
            case 'sha224':
                $t = "\x30\x2b\x30\x0b\x06\x09\x60\x86\x48\x01\x65\x03\x04\x02\x04\x04\x1c";
                break;
            case 'sha512/224':
                $t = "\x30\x2b\x30\x0b\x06\x09\x60\x86\x48\x01\x65\x03\x04\x02\x05\x04\x1c";
                break;
            case 'sha512/256':
                $t = "\x30\x2f\x30\x0b\x06\x09\x60\x86\x48\x01\x65\x03\x04\x02\x06\x04\x20";
                break;
            default:
                throw new UnsupportedAlgorithmException('md2 and md5 require NULLs');
        }
        $t .= $h;
        $tLen = strlen($t);

        if ($emLen < $tLen + 11) {
            throw new \LengthException('Intended encoded message length too short');
        }

        $ps = str_repeat(chr(0xFF), $emLen - $tLen - 3);

        $em = "\0\1$ps\0$t";

        return $em;
    }

    /**
     * MGF1
     *
     * See {@link http://tools.ietf.org/html/rfc3447#appendix-B.2.1 RFC3447#appendix-B.2.1}.
     *
     * @param string $mgfSeed
     * @param int $maskLen
     * @return string
     */
    protected function mgf1($mgfSeed, $maskLen)
    {
        // if $maskLen would yield strings larger than 4GB, PKCS#1 suggests a "Mask too long" error be output.

        $t = '';
        $count = ceil($maskLen / $this->mgfHLen);
        for ($i = 0; $i < $count; $i++) {
            $c = pack('N', $i);
            $t .= $this->mgfHash->hash($mgfSeed . $c);
        }

        return substr($t, 0, $maskLen);
    }

    /**
     * Returns the key size
     *
     * More specifically, this returns the size of the modulo in bits.
     *
     * @return int
     */
    public function getLength()
    {
        return !isset($this->modulus) ? 0 : $this->modulus->getLength();
    }

    /**
     * Determines which hashing function should be used
     *
     * Used with signature production / verification and (if the encryption mode is self::PADDING_OAEP) encryption and
     * decryption.
     *
     * @param string $hash
     */
    public function withHash($hash)
    {
        $new = clone $this;

        // \phpseclib3\Crypt\Hash supports algorithms that PKCS#1 doesn't support.  md5-96 and sha1-96, for example.
        switch (strtolower($hash)) {
            case 'md2':
            case 'md5':
            case 'sha1':
            case 'sha256':
            case 'sha384':
            case 'sha512':
            case 'sha224':
            case 'sha512/224':
            case 'sha512/256':
                $new->hash = new Hash($hash);
                break;
            default:
                throw new UnsupportedAlgorithmException(
                    'The only supported hash algorithms are: md2, md5, sha1, sha256, sha384, sha512, sha224, sha512/224, sha512/256'
                );
        }
        $new->hLen = $new->hash->getLengthInBytes();

        return $new;
    }

    /**
     * Determines which hashing function should be used for the mask generation function
     *
     * The mask generation function is used by self::PADDING_OAEP and self::PADDING_PSS and although it's
     * best if Hash and MGFHash are set to the same thing this is not a requirement.
     *
     * @param string $hash
     */
    public function withMGFHash($hash)
    {
        $new = clone $this;

        // \phpseclib3\Crypt\Hash supports algorithms that PKCS#1 doesn't support.  md5-96 and sha1-96, for example.
        switch (strtolower($hash)) {
            case 'md2':
            case 'md5':
            case 'sha1':
            case 'sha256':
            case 'sha384':
            case 'sha512':
            case 'sha224':
            case 'sha512/224':
            case 'sha512/256':
                $new->mgfHash = new Hash($hash);
                break;
            default:
                throw new UnsupportedAlgorithmException(
                    'The only supported hash algorithms are: md2, md5, sha1, sha256, sha384, sha512, sha224, sha512/224, sha512/256'
                );
        }
        $new->mgfHLen = $new->mgfHash->getLengthInBytes();

        return $new;
    }

    /**
     * Returns the MGF hash algorithm currently being used
     *
     */
    public function getMGFHash()
    {
        return clone $this->mgfHash;
    }

    /**
     * Determines the salt length
     *
     * Used by RSA::PADDING_PSS
     *
     * To quote from {@link http://tools.ietf.org/html/rfc3447#page-38 RFC3447#page-38}:
     *
     *    Typical salt lengths in octets are hLen (the length of the output
     *    of the hash function Hash) and 0.
     *
     * @param int $sLen
     */
    public function withSaltLength($sLen)
    {
        $new = clone $this;
        $new->sLen = $sLen;
        return $new;
    }

    /**
     * Returns the salt length currently being used
     *
     */
    public function getSaltLength()
    {
        return $this->sLen !== null ? $this->sLen : $this->hLen;
    }

    /**
     * Determines the label
     *
     * Used by RSA::PADDING_OAEP
     *
     * To quote from {@link http://tools.ietf.org/html/rfc3447#page-17 RFC3447#page-17}:
     *
     *    Both the encryption and the decryption operations of RSAES-OAEP take
     *    the value of a label L as input.  In this version of PKCS #1, L is
     *    the empty string; other uses of the label are outside the scope of
     *    this document.
     *
     * @param string $label
     */
    public function withLabel($label)
    {
        $new = clone $this;
        $new->label = $label;
        return $new;
    }

    /**
     * Returns the label currently being used
     *
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Determines the padding modes
     *
     * Example: $key->withPadding(RSA::ENCRYPTION_PKCS1 | RSA::SIGNATURE_PKCS1);
     *
     * @param int $padding
     */
    public function withPadding($padding)
    {
        $masks = [
            self::ENCRYPTION_OAEP,
            self::ENCRYPTION_PKCS1,
            self::ENCRYPTION_NONE
        ];
        $encryptedCount = 0;
        $selected = 0;
        foreach ($masks as $mask) {
            if ($padding & $mask) {
                $selected = $mask;
                $encryptedCount++;
            }
        }
        if ($encryptedCount > 1) {
            throw new InconsistentSetupException('Multiple encryption padding modes have been selected; at most only one should be selected');
        }
        $encryptionPadding = $selected;

        $masks = [
            self::SIGNATURE_PSS,
            self::SIGNATURE_RELAXED_PKCS1,
            self::SIGNATURE_PKCS1
        ];
        $signatureCount = 0;
        $selected = 0;
        foreach ($masks as $mask) {
            if ($padding & $mask) {
                $selected = $mask;
                $signatureCount++;
            }
        }
        if ($signatureCount > 1) {
            throw new InconsistentSetupException('Multiple signature padding modes have been selected; at most only one should be selected');
        }
        $signaturePadding = $selected;

        $new = clone $this;
        if ($encryptedCount) {
            $new->encryptionPadding = $encryptionPadding;
        }
        if ($signatureCount) {
            $new->signaturePadding = $signaturePadding;
        }
        return $new;
    }

    /**
     * Returns the padding currently being used
     *
     */
    public function getPadding()
    {
        return $this->signaturePadding | $this->encryptionPadding;
    }

    /**
     * Returns the current engine being used
     *
     * OpenSSL is only used in this class (and it's subclasses) for key generation
     * Even then it depends on the parameters you're using. It's not used for
     * multi-prime RSA nor is it used if the key length is outside of the range
     * supported by OpenSSL
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
        return self::$engines['OpenSSL'] && self::$defaultExponent == 65537 ?
            'OpenSSL' :
            'PHP';
    }

    /**
     * Enable RSA Blinding
     *
     */
    public static function enableBlinding()
    {
        static::$enableBlinding = true;
    }

    /**
     * Disable RSA Blinding
     *
     */
    public static function disableBlinding()
    {
        static::$enableBlinding = false;
    }
}
