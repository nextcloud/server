<?php

/**
 * Pure-PHP PKCS#1 (v2.1) compliant implementation of RSA.
 *
 * PHP version 5
 *
 * Here's an example of how to encrypt and decrypt text with this library:
 * <code>
 * <?php
 *    include 'vendor/autoload.php';
 *
 *    $rsa = new \phpseclib\Crypt\RSA();
 *    extract($rsa->createKey());
 *
 *    $plaintext = 'terrafrost';
 *
 *    $rsa->loadKey($privatekey);
 *    $ciphertext = $rsa->encrypt($plaintext);
 *
 *    $rsa->loadKey($publickey);
 *    echo $rsa->decrypt($ciphertext);
 * ?>
 * </code>
 *
 * Here's an example of how to create signatures and verify signatures with this library:
 * <code>
 * <?php
 *    include 'vendor/autoload.php';
 *
 *    $rsa = new \phpseclib\Crypt\RSA();
 *    extract($rsa->createKey());
 *
 *    $plaintext = 'terrafrost';
 *
 *    $rsa->loadKey($privatekey);
 *    $signature = $rsa->sign($plaintext);
 *
 *    $rsa->loadKey($publickey);
 *    echo $rsa->verify($plaintext, $signature) ? 'verified' : 'unverified';
 * ?>
 * </code>
 *
 * @category  Crypt
 * @package   RSA
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2009 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib\Crypt;

use phpseclib\Math\BigInteger;

/**
 * Pure-PHP PKCS#1 compliant implementation of RSA.
 *
 * @package RSA
 * @author  Jim Wigginton <terrafrost@php.net>
 * @access  public
 */
class RSA
{
    /**#@+
     * @access public
     * @see self::encrypt()
     * @see self::decrypt()
     */
    /**
     * Use {@link http://en.wikipedia.org/wiki/Optimal_Asymmetric_Encryption_Padding Optimal Asymmetric Encryption Padding}
     * (OAEP) for encryption / decryption.
     *
     * Uses sha1 by default.
     *
     * @see self::setHash()
     * @see self::setMGFHash()
     */
    const ENCRYPTION_OAEP = 1;
    /**
     * Use PKCS#1 padding.
     *
     * Although self::ENCRYPTION_OAEP offers more security, including PKCS#1 padding is necessary for purposes of backwards
     * compatibility with protocols (like SSH-1) written before OAEP's introduction.
     */
    const ENCRYPTION_PKCS1 = 2;
    /**
     * Do not use any padding
     *
     * Although this method is not recommended it can none-the-less sometimes be useful if you're trying to decrypt some legacy
     * stuff, if you're trying to diagnose why an encrypted message isn't decrypting, etc.
     */
    const ENCRYPTION_NONE = 3;
    /**#@-*/

    /**#@+
     * @access public
     * @see self::sign()
     * @see self::verify()
     * @see self::setHash()
    */
    /**
     * Use the Probabilistic Signature Scheme for signing
     *
     * Uses sha1 by default.
     *
     * @see self::setSaltLength()
     * @see self::setMGFHash()
     */
    const SIGNATURE_PSS = 1;
    /**
     * Use the PKCS#1 scheme by default.
     *
     * Although self::SIGNATURE_PSS offers more security, including PKCS#1 signing is necessary for purposes of backwards
     * compatibility with protocols (like SSH-2) written before PSS's introduction.
     */
    const SIGNATURE_PKCS1 = 2;
    /**#@-*/

    /**#@+
     * @access private
     * @see \phpseclib\Crypt\RSA::createKey()
    */
    /**
     * ASN1 Integer
     */
    const ASN1_INTEGER = 2;
    /**
     * ASN1 Bit String
     */
    const ASN1_BITSTRING = 3;
    /**
     * ASN1 Octet String
     */
    const ASN1_OCTETSTRING = 4;
    /**
     * ASN1 Object Identifier
     */
    const ASN1_OBJECT = 6;
    /**
     * ASN1 Sequence (with the constucted bit set)
     */
    const ASN1_SEQUENCE = 48;
    /**#@-*/

    /**#@+
     * @access private
     * @see \phpseclib\Crypt\RSA::__construct()
    */
    /**
     * To use the pure-PHP implementation
     */
    const MODE_INTERNAL = 1;
    /**
     * To use the OpenSSL library
     *
     * (if enabled; otherwise, the internal implementation will be used)
     */
    const MODE_OPENSSL = 2;
    /**#@-*/

    /**#@+
     * @access public
     * @see \phpseclib\Crypt\RSA::createKey()
     * @see \phpseclib\Crypt\RSA::setPrivateKeyFormat()
    */
    /**
     * PKCS#1 formatted private key
     *
     * Used by OpenSSH
     */
    const PRIVATE_FORMAT_PKCS1 = 0;
    /**
     * PuTTY formatted private key
     */
    const PRIVATE_FORMAT_PUTTY = 1;
    /**
     * XML formatted private key
     */
    const PRIVATE_FORMAT_XML = 2;
    /**
     * PKCS#8 formatted private key
     */
    const PRIVATE_FORMAT_PKCS8 = 8;
    /**
     * OpenSSH formatted private key
     */
    const PRIVATE_FORMAT_OPENSSH = 9;
    /**#@-*/

    /**#@+
     * @access public
     * @see \phpseclib\Crypt\RSA::createKey()
     * @see \phpseclib\Crypt\RSA::setPublicKeyFormat()
    */
    /**
     * Raw public key
     *
     * An array containing two \phpseclib\Math\BigInteger objects.
     *
     * The exponent can be indexed with any of the following:
     *
     * 0, e, exponent, publicExponent
     *
     * The modulus can be indexed with any of the following:
     *
     * 1, n, modulo, modulus
     */
    const PUBLIC_FORMAT_RAW = 3;
    /**
     * PKCS#1 formatted public key (raw)
     *
     * Used by File/X509.php
     *
     * Has the following header:
     *
     * -----BEGIN RSA PUBLIC KEY-----
     *
     * Analogous to ssh-keygen's pem format (as specified by -m)
     */
    const PUBLIC_FORMAT_PKCS1 = 4;
    const PUBLIC_FORMAT_PKCS1_RAW = 4;
    /**
     * XML formatted public key
     */
    const PUBLIC_FORMAT_XML = 5;
    /**
     * OpenSSH formatted public key
     *
     * Place in $HOME/.ssh/authorized_keys
     */
    const PUBLIC_FORMAT_OPENSSH = 6;
    /**
     * PKCS#1 formatted public key (encapsulated)
     *
     * Used by PHP's openssl_public_encrypt() and openssl's rsautl (when -pubin is set)
     *
     * Has the following header:
     *
     * -----BEGIN PUBLIC KEY-----
     *
     * Analogous to ssh-keygen's pkcs8 format (as specified by -m). Although PKCS8
     * is specific to private keys it's basically creating a DER-encoded wrapper
     * for keys. This just extends that same concept to public keys (much like ssh-keygen)
     */
    const PUBLIC_FORMAT_PKCS8 = 7;
    /**#@-*/

    /**
     * Precomputed Zero
     *
     * @var \phpseclib\Math\BigInteger
     * @access private
     */
    var $zero;

    /**
     * Precomputed One
     *
     * @var \phpseclib\Math\BigInteger
     * @access private
     */
    var $one;

    /**
     * Private Key Format
     *
     * @var int
     * @access private
     */
    var $privateKeyFormat = self::PRIVATE_FORMAT_PKCS1;

    /**
     * Public Key Format
     *
     * @var int
     * @access public
     */
    var $publicKeyFormat = self::PUBLIC_FORMAT_PKCS8;

    /**
     * Modulus (ie. n)
     *
     * @var \phpseclib\Math\BigInteger
     * @access private
     */
    var $modulus;

    /**
     * Modulus length
     *
     * @var \phpseclib\Math\BigInteger
     * @access private
     */
    var $k;

    /**
     * Exponent (ie. e or d)
     *
     * @var \phpseclib\Math\BigInteger
     * @access private
     */
    var $exponent;

    /**
     * Primes for Chinese Remainder Theorem (ie. p and q)
     *
     * @var array
     * @access private
     */
    var $primes;

    /**
     * Exponents for Chinese Remainder Theorem (ie. dP and dQ)
     *
     * @var array
     * @access private
     */
    var $exponents;

    /**
     * Coefficients for Chinese Remainder Theorem (ie. qInv)
     *
     * @var array
     * @access private
     */
    var $coefficients;

    /**
     * Hash name
     *
     * @var string
     * @access private
     */
    var $hashName;

    /**
     * Hash function
     *
     * @var \phpseclib\Crypt\Hash
     * @access private
     */
    var $hash;

    /**
     * Length of hash function output
     *
     * @var int
     * @access private
     */
    var $hLen;

    /**
     * Length of salt
     *
     * @var int
     * @access private
     */
    var $sLen;

    /**
     * Hash function for the Mask Generation Function
     *
     * @var \phpseclib\Crypt\Hash
     * @access private
     */
    var $mgfHash;

    /**
     * Length of MGF hash function output
     *
     * @var int
     * @access private
     */
    var $mgfHLen;

    /**
     * Encryption mode
     *
     * @var int
     * @access private
     */
    var $encryptionMode = self::ENCRYPTION_OAEP;

    /**
     * Signature mode
     *
     * @var int
     * @access private
     */
    var $signatureMode = self::SIGNATURE_PSS;

    /**
     * Public Exponent
     *
     * @var mixed
     * @access private
     */
    var $publicExponent = false;

    /**
     * Password
     *
     * @var string
     * @access private
     */
    var $password = false;

    /**
     * Components
     *
     * For use with parsing XML formatted keys.  PHP's XML Parser functions use utilized - instead of PHP's DOM functions -
     * because PHP's XML Parser functions work on PHP4 whereas PHP's DOM functions - although surperior - don't.
     *
     * @see self::_start_element_handler()
     * @var array
     * @access private
     */
    var $components = array();

    /**
     * Current String
     *
     * For use with parsing XML formatted keys.
     *
     * @see self::_character_handler()
     * @see self::_stop_element_handler()
     * @var mixed
     * @access private
     */
    var $current;

    /**
     * OpenSSL configuration file name.
     *
     * Set to null to use system configuration file.
     * @see self::createKey()
     * @var mixed
     * @Access public
     */
    var $configFile;

    /**
     * Public key comment field.
     *
     * @var string
     * @access private
     */
    var $comment = 'phpseclib-generated-key';

    /**
     * The constructor
     *
     * If you want to make use of the openssl extension, you'll need to set the mode manually, yourself.  The reason
     * \phpseclib\Crypt\RSA doesn't do it is because OpenSSL doesn't fail gracefully.  openssl_pkey_new(), in particular, requires
     * openssl.cnf be present somewhere and, unfortunately, the only real way to find out is too late.
     *
     * @return \phpseclib\Crypt\RSA
     * @access public
     */
    function __construct()
    {
        $this->configFile = dirname(__FILE__) . '/../openssl.cnf';

        if (!defined('CRYPT_RSA_MODE')) {
            switch (true) {
                // Math/BigInteger's openssl requirements are a little less stringent than Crypt/RSA's. in particular,
                // Math/BigInteger doesn't require an openssl.cfg file whereas Crypt/RSA does. so if Math/BigInteger
                // can't use OpenSSL it can be pretty trivially assumed, then, that Crypt/RSA can't either.
                case defined('MATH_BIGINTEGER_OPENSSL_DISABLE'):
                    define('CRYPT_RSA_MODE', self::MODE_INTERNAL);
                    break;
                case function_exists('phpinfo') && extension_loaded('openssl') && file_exists($this->configFile):
                    // some versions of XAMPP have mismatched versions of OpenSSL which causes it not to work
                    $versions = array();

                    // avoid generating errors (even with suppression) when phpinfo() is disabled (common in production systems)
                    if (strpos(ini_get('disable_functions'), 'phpinfo') === false) {
                        ob_start();
                        @phpinfo();
                        $content = ob_get_contents();
                        ob_end_clean();

                        preg_match_all('#OpenSSL (Header|Library) Version(.*)#im', $content, $matches);

                        if (!empty($matches[1])) {
                            for ($i = 0; $i < count($matches[1]); $i++) {
                                $fullVersion = trim(str_replace('=>', '', strip_tags($matches[2][$i])));

                                // Remove letter part in OpenSSL version
                                if (!preg_match('/(\d+\.\d+\.\d+)/i', $fullVersion, $m)) {
                                    $versions[$matches[1][$i]] = $fullVersion;
                                } else {
                                    $versions[$matches[1][$i]] = $m[0];
                                }
                            }
                        }
                    }

                    // it doesn't appear that OpenSSL versions were reported upon until PHP 5.3+
                    switch (true) {
                        case !isset($versions['Header']):
                        case !isset($versions['Library']):
                        case $versions['Header'] == $versions['Library']:
                        case version_compare($versions['Header'], '1.0.0') >= 0 && version_compare($versions['Library'], '1.0.0') >= 0:
                            define('CRYPT_RSA_MODE', self::MODE_OPENSSL);
                            break;
                        default:
                            define('CRYPT_RSA_MODE', self::MODE_INTERNAL);
                            define('MATH_BIGINTEGER_OPENSSL_DISABLE', true);
                    }
                    break;
                default:
                    define('CRYPT_RSA_MODE', self::MODE_INTERNAL);
            }
        }

        $this->zero = new BigInteger();
        $this->one = new BigInteger(1);

        $this->hash = new Hash('sha1');
        $this->hLen = $this->hash->getLength();
        $this->hashName = 'sha1';
        $this->mgfHash = new Hash('sha1');
        $this->mgfHLen = $this->mgfHash->getLength();
    }

    /**
     * Create public / private key pair
     *
     * Returns an array with the following three elements:
     *  - 'privatekey': The private key.
     *  - 'publickey':  The public key.
     *  - 'partialkey': A partially computed key (if the execution time exceeded $timeout).
     *                  Will need to be passed back to \phpseclib\Crypt\RSA::createKey() as the third parameter for further processing.
     *
     * @access public
     * @param int $bits
     * @param int $timeout
     * @param array $partial
     */
    function createKey($bits = 1024, $timeout = false, $partial = array())
    {
        if (!defined('CRYPT_RSA_EXPONENT')) {
            // http://en.wikipedia.org/wiki/65537_%28number%29
            define('CRYPT_RSA_EXPONENT', '65537');
        }
        // per <http://cseweb.ucsd.edu/~hovav/dist/survey.pdf#page=5>, this number ought not result in primes smaller
        // than 256 bits. as a consequence if the key you're trying to create is 1024 bits and you've set CRYPT_RSA_SMALLEST_PRIME
        // to 384 bits then you're going to get a 384 bit prime and a 640 bit prime (384 + 1024 % 384). at least if
        // CRYPT_RSA_MODE is set to self::MODE_INTERNAL. if CRYPT_RSA_MODE is set to self::MODE_OPENSSL then
        // CRYPT_RSA_SMALLEST_PRIME is ignored (ie. multi-prime RSA support is more intended as a way to speed up RSA key
        // generation when there's a chance neither gmp nor OpenSSL are installed)
        if (!defined('CRYPT_RSA_SMALLEST_PRIME')) {
            define('CRYPT_RSA_SMALLEST_PRIME', 4096);
        }

        // OpenSSL uses 65537 as the exponent and requires RSA keys be 384 bits minimum
        if (CRYPT_RSA_MODE == self::MODE_OPENSSL && $bits >= 384 && CRYPT_RSA_EXPONENT == 65537) {
            $config = array();
            if (isset($this->configFile)) {
                $config['config'] = $this->configFile;
            }
            $rsa = openssl_pkey_new(array('private_key_bits' => $bits) + $config);
            openssl_pkey_export($rsa, $privatekey, null, $config);
            $publickey = openssl_pkey_get_details($rsa);
            $publickey = $publickey['key'];

            $privatekey = call_user_func_array(array($this, '_convertPrivateKey'), array_values($this->_parseKey($privatekey, self::PRIVATE_FORMAT_PKCS1)));
            $publickey = call_user_func_array(array($this, '_convertPublicKey'), array_values($this->_parseKey($publickey, self::PUBLIC_FORMAT_PKCS1)));

            // clear the buffer of error strings stemming from a minimalistic openssl.cnf
            // https://github.com/php/php-src/issues/11054 talks about other errors this'll pick up
            while (openssl_error_string() !== false) {
            }

            return array(
                'privatekey' => $privatekey,
                'publickey' => $publickey,
                'partialkey' => false
            );
        }

        static $e;
        if (!isset($e)) {
            $e = new BigInteger(CRYPT_RSA_EXPONENT);
        }

        extract($this->_generateMinMax($bits));
        $absoluteMin = $min;
        $temp = $bits >> 1; // divide by two to see how many bits P and Q would be
        if ($temp > CRYPT_RSA_SMALLEST_PRIME) {
            $num_primes = floor($bits / CRYPT_RSA_SMALLEST_PRIME);
            $temp = CRYPT_RSA_SMALLEST_PRIME;
        } else {
            $num_primes = 2;
        }
        extract($this->_generateMinMax($temp + $bits % $temp));
        $finalMax = $max;
        extract($this->_generateMinMax($temp));

        $generator = new BigInteger();

        $n = $this->one->copy();
        if (!empty($partial)) {
            extract(unserialize($partial));
        } else {
            $exponents = $coefficients = $primes = array();
            $lcm = array(
                'top' => $this->one->copy(),
                'bottom' => false
            );
        }

        $start = time();
        $i0 = count($primes) + 1;

        do {
            for ($i = $i0; $i <= $num_primes; $i++) {
                if ($timeout !== false) {
                    $timeout-= time() - $start;
                    $start = time();
                    if ($timeout <= 0) {
                        return array(
                            'privatekey' => '',
                            'publickey'  => '',
                            'partialkey' => serialize(array(
                                'primes' => $primes,
                                'coefficients' => $coefficients,
                                'lcm' => $lcm,
                                'exponents' => $exponents
                            ))
                        );
                    }
                }

                if ($i == $num_primes) {
                    list($min, $temp) = $absoluteMin->divide($n);
                    if (!$temp->equals($this->zero)) {
                        $min = $min->add($this->one); // ie. ceil()
                    }
                    $primes[$i] = $generator->randomPrime($min, $finalMax, $timeout);
                } else {
                    $primes[$i] = $generator->randomPrime($min, $max, $timeout);
                }

                if ($primes[$i] === false) { // if we've reached the timeout
                    if (count($primes) > 1) {
                        $partialkey = '';
                    } else {
                        array_pop($primes);
                        $partialkey = serialize(array(
                            'primes' => $primes,
                            'coefficients' => $coefficients,
                            'lcm' => $lcm,
                            'exponents' => $exponents
                        ));
                    }

                    return array(
                        'privatekey' => '',
                        'publickey'  => '',
                        'partialkey' => $partialkey
                    );
                }

                // the first coefficient is calculated differently from the rest
                // ie. instead of being $primes[1]->modInverse($primes[2]), it's $primes[2]->modInverse($primes[1])
                if ($i > 2) {
                    $coefficients[$i] = $n->modInverse($primes[$i]);
                }

                $n = $n->multiply($primes[$i]);

                $temp = $primes[$i]->subtract($this->one);

                // textbook RSA implementations use Euler's totient function instead of the least common multiple.
                // see http://en.wikipedia.org/wiki/Euler%27s_totient_function
                $lcm['top'] = $lcm['top']->multiply($temp);
                $lcm['bottom'] = $lcm['bottom'] === false ? $temp : $lcm['bottom']->gcd($temp);

                $exponents[$i] = $e->modInverse($temp);
            }

            list($temp) = $lcm['top']->divide($lcm['bottom']);
            $gcd = $temp->gcd($e);
            $i0 = 1;
        } while (!$gcd->equals($this->one));

        $d = $e->modInverse($temp);

        $coefficients[2] = $primes[2]->modInverse($primes[1]);

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

        return array(
            'privatekey' => $this->_convertPrivateKey($n, $e, $d, $primes, $exponents, $coefficients),
            'publickey'  => $this->_convertPublicKey($n, $e),
            'partialkey' => false
        );
    }

    /**
     * Convert a private key to the appropriate format.
     *
     * @access private
     * @see self::setPrivateKeyFormat()
     * @param Math_BigInteger $n
     * @param Math_BigInteger $e
     * @param Math_BigInteger $d
     * @param array<int,Math_BigInteger> $primes
     * @param array<int,Math_BigInteger> $exponents
     * @param array<int,Math_BigInteger> $coefficients
     * @return string
     */
    function _convertPrivateKey($n, $e, $d, $primes, $exponents, $coefficients)
    {
        $signed = $this->privateKeyFormat != self::PRIVATE_FORMAT_XML;
        $num_primes = count($primes);
        $raw = array(
            'version' => $num_primes == 2 ? chr(0) : chr(1), // two-prime vs. multi
            'modulus' => $n->toBytes($signed),
            'publicExponent' => $e->toBytes($signed),
            'privateExponent' => $d->toBytes($signed),
            'prime1' => $primes[1]->toBytes($signed),
            'prime2' => $primes[2]->toBytes($signed),
            'exponent1' => $exponents[1]->toBytes($signed),
            'exponent2' => $exponents[2]->toBytes($signed),
            'coefficient' => $coefficients[2]->toBytes($signed)
        );

        // if the format in question does not support multi-prime rsa and multi-prime rsa was used,
        // call _convertPublicKey() instead.
        switch ($this->privateKeyFormat) {
            case self::PRIVATE_FORMAT_XML:
                if ($num_primes != 2) {
                    return false;
                }
                return "<RSAKeyValue>\r\n" .
                       '  <Modulus>' . base64_encode($raw['modulus']) . "</Modulus>\r\n" .
                       '  <Exponent>' . base64_encode($raw['publicExponent']) . "</Exponent>\r\n" .
                       '  <P>' . base64_encode($raw['prime1']) . "</P>\r\n" .
                       '  <Q>' . base64_encode($raw['prime2']) . "</Q>\r\n" .
                       '  <DP>' . base64_encode($raw['exponent1']) . "</DP>\r\n" .
                       '  <DQ>' . base64_encode($raw['exponent2']) . "</DQ>\r\n" .
                       '  <InverseQ>' . base64_encode($raw['coefficient']) . "</InverseQ>\r\n" .
                       '  <D>' . base64_encode($raw['privateExponent']) . "</D>\r\n" .
                       '</RSAKeyValue>';
                break;
            case self::PRIVATE_FORMAT_PUTTY:
                if ($num_primes != 2) {
                    return false;
                }
                $key = "PuTTY-User-Key-File-2: ssh-rsa\r\nEncryption: ";
                $encryption = (!empty($this->password) || is_string($this->password)) ? 'aes256-cbc' : 'none';
                $key.= $encryption;
                $key.= "\r\nComment: " . $this->comment . "\r\n";
                $public = pack(
                    'Na*Na*Na*',
                    strlen('ssh-rsa'),
                    'ssh-rsa',
                    strlen($raw['publicExponent']),
                    $raw['publicExponent'],
                    strlen($raw['modulus']),
                    $raw['modulus']
                );
                $source = pack(
                    'Na*Na*Na*Na*',
                    strlen('ssh-rsa'),
                    'ssh-rsa',
                    strlen($encryption),
                    $encryption,
                    strlen($this->comment),
                    $this->comment,
                    strlen($public),
                    $public
                );
                $public = base64_encode($public);
                $key.= "Public-Lines: " . ((strlen($public) + 63) >> 6) . "\r\n";
                $key.= chunk_split($public, 64);
                $private = pack(
                    'Na*Na*Na*Na*',
                    strlen($raw['privateExponent']),
                    $raw['privateExponent'],
                    strlen($raw['prime1']),
                    $raw['prime1'],
                    strlen($raw['prime2']),
                    $raw['prime2'],
                    strlen($raw['coefficient']),
                    $raw['coefficient']
                );
                if (empty($this->password) && !is_string($this->password)) {
                    $source.= pack('Na*', strlen($private), $private);
                    $hashkey = 'putty-private-key-file-mac-key';
                } else {
                    $private.= Random::string(16 - (strlen($private) & 15));
                    $source.= pack('Na*', strlen($private), $private);
                    $sequence = 0;
                    $symkey = '';
                    while (strlen($symkey) < 32) {
                        $temp = pack('Na*', $sequence++, $this->password);
                        $symkey.= pack('H*', sha1($temp));
                    }
                    $symkey = substr($symkey, 0, 32);
                    $crypto = new AES();

                    $crypto->setKey($symkey);
                    $crypto->disablePadding();
                    $private = $crypto->encrypt($private);
                    $hashkey = 'putty-private-key-file-mac-key' . $this->password;
                }

                $private = base64_encode($private);
                $key.= 'Private-Lines: ' . ((strlen($private) + 63) >> 6) . "\r\n";
                $key.= chunk_split($private, 64);
                $hash = new Hash('sha1');
                $hash->setKey(pack('H*', sha1($hashkey)));
                $key.= 'Private-MAC: ' . bin2hex($hash->hash($source)) . "\r\n";

                return $key;
            case self::PRIVATE_FORMAT_OPENSSH:
                if ($num_primes != 2) {
                    return false;
                }
                $publicKey = pack('Na*Na*Na*', strlen('ssh-rsa'), 'ssh-rsa', strlen($raw['publicExponent']), $raw['publicExponent'], strlen($raw['modulus']), $raw['modulus']);
                $privateKey = pack(
                    'Na*Na*Na*Na*Na*Na*Na*',
                    strlen('ssh-rsa'),
                    'ssh-rsa',
                    strlen($raw['modulus']),
                    $raw['modulus'],
                    strlen($raw['publicExponent']),
                    $raw['publicExponent'],
                    strlen($raw['privateExponent']),
                    $raw['privateExponent'],
                    strlen($raw['coefficient']),
                    $raw['coefficient'],
                    strlen($raw['prime1']),
                    $raw['prime1'],
                    strlen($raw['prime2']),
                    $raw['prime2']
                );
                $checkint = Random::string(4);
                $paddedKey = pack(
                    'a*Na*',
                    $checkint . $checkint . $privateKey,
                    strlen($this->comment),
                    $this->comment
                );
                $paddingLength = (7 * strlen($paddedKey)) % 8;
                for ($i = 1; $i <= $paddingLength; $i++) {
                    $paddedKey.= chr($i);
                }
                $key = pack(
                    'Na*Na*Na*NNa*Na*',
                    strlen('none'),
                    'none',
                    strlen('none'),
                    'none',
                    0,
                    '',
                    1,
                    strlen($publicKey),
                    $publicKey,
                    strlen($paddedKey),
                    $paddedKey
                );
                $key = "openssh-key-v1\0$key";

                return "-----BEGIN OPENSSH PRIVATE KEY-----\n" .
                       chunk_split(base64_encode($key), 70, "\n") .
                       "-----END OPENSSH PRIVATE KEY-----\n";
            default: // eg. self::PRIVATE_FORMAT_PKCS1
                $components = array();
                foreach ($raw as $name => $value) {
                    $components[$name] = pack('Ca*a*', self::ASN1_INTEGER, $this->_encodeLength(strlen($value)), $value);
                }

                $RSAPrivateKey = implode('', $components);

                if ($num_primes > 2) {
                    $OtherPrimeInfos = '';
                    for ($i = 3; $i <= $num_primes; $i++) {
                        // OtherPrimeInfos ::= SEQUENCE SIZE(1..MAX) OF OtherPrimeInfo
                        //
                        // OtherPrimeInfo ::= SEQUENCE {
                        //     prime             INTEGER,  -- ri
                        //     exponent          INTEGER,  -- di
                        //     coefficient       INTEGER   -- ti
                        // }
                        $OtherPrimeInfo = pack('Ca*a*', self::ASN1_INTEGER, $this->_encodeLength(strlen($primes[$i]->toBytes(true))), $primes[$i]->toBytes(true));
                        $OtherPrimeInfo.= pack('Ca*a*', self::ASN1_INTEGER, $this->_encodeLength(strlen($exponents[$i]->toBytes(true))), $exponents[$i]->toBytes(true));
                        $OtherPrimeInfo.= pack('Ca*a*', self::ASN1_INTEGER, $this->_encodeLength(strlen($coefficients[$i]->toBytes(true))), $coefficients[$i]->toBytes(true));
                        $OtherPrimeInfos.= pack('Ca*a*', self::ASN1_SEQUENCE, $this->_encodeLength(strlen($OtherPrimeInfo)), $OtherPrimeInfo);
                    }
                    $RSAPrivateKey.= pack('Ca*a*', self::ASN1_SEQUENCE, $this->_encodeLength(strlen($OtherPrimeInfos)), $OtherPrimeInfos);
                }

                $RSAPrivateKey = pack('Ca*a*', self::ASN1_SEQUENCE, $this->_encodeLength(strlen($RSAPrivateKey)), $RSAPrivateKey);

                if ($this->privateKeyFormat == self::PRIVATE_FORMAT_PKCS8) {
                    $rsaOID = pack('H*', '300d06092a864886f70d0101010500'); // hex version of MA0GCSqGSIb3DQEBAQUA
                    $RSAPrivateKey = pack(
                        'Ca*a*Ca*a*',
                        self::ASN1_INTEGER,
                        "\01\00",
                        $rsaOID,
                        4,
                        $this->_encodeLength(strlen($RSAPrivateKey)),
                        $RSAPrivateKey
                    );
                    $RSAPrivateKey = pack('Ca*a*', self::ASN1_SEQUENCE, $this->_encodeLength(strlen($RSAPrivateKey)), $RSAPrivateKey);
                    if (!empty($this->password) || is_string($this->password)) {
                        $salt = Random::string(8);
                        $iterationCount = 2048;

                        $crypto = new DES();
                        $crypto->setPassword($this->password, 'pbkdf1', 'md5', $salt, $iterationCount);
                        $RSAPrivateKey = $crypto->encrypt($RSAPrivateKey);

                        $parameters = pack(
                            'Ca*a*Ca*N',
                            self::ASN1_OCTETSTRING,
                            $this->_encodeLength(strlen($salt)),
                            $salt,
                            self::ASN1_INTEGER,
                            $this->_encodeLength(4),
                            $iterationCount
                        );
                        $pbeWithMD5AndDES_CBC = "\x2a\x86\x48\x86\xf7\x0d\x01\x05\x03";

                        $encryptionAlgorithm = pack(
                            'Ca*a*Ca*a*',
                            self::ASN1_OBJECT,
                            $this->_encodeLength(strlen($pbeWithMD5AndDES_CBC)),
                            $pbeWithMD5AndDES_CBC,
                            self::ASN1_SEQUENCE,
                            $this->_encodeLength(strlen($parameters)),
                            $parameters
                        );

                        $RSAPrivateKey = pack(
                            'Ca*a*Ca*a*',
                            self::ASN1_SEQUENCE,
                            $this->_encodeLength(strlen($encryptionAlgorithm)),
                            $encryptionAlgorithm,
                            self::ASN1_OCTETSTRING,
                            $this->_encodeLength(strlen($RSAPrivateKey)),
                            $RSAPrivateKey
                        );

                        $RSAPrivateKey = pack('Ca*a*', self::ASN1_SEQUENCE, $this->_encodeLength(strlen($RSAPrivateKey)), $RSAPrivateKey);

                        $RSAPrivateKey = "-----BEGIN ENCRYPTED PRIVATE KEY-----\r\n" .
                                         chunk_split(base64_encode($RSAPrivateKey), 64) .
                                         '-----END ENCRYPTED PRIVATE KEY-----';
                    } else {
                        $RSAPrivateKey = "-----BEGIN PRIVATE KEY-----\r\n" .
                                         chunk_split(base64_encode($RSAPrivateKey), 64) .
                                         '-----END PRIVATE KEY-----';
                    }
                    return $RSAPrivateKey;
                }

                if (!empty($this->password) || is_string($this->password)) {
                    $iv = Random::string(8);
                    $symkey = pack('H*', md5($this->password . $iv)); // symkey is short for symmetric key
                    $symkey.= substr(pack('H*', md5($symkey . $this->password . $iv)), 0, 8);
                    $des = new TripleDES();
                    $des->setKey($symkey);
                    $des->setIV($iv);
                    $iv = strtoupper(bin2hex($iv));
                    $RSAPrivateKey = "-----BEGIN RSA PRIVATE KEY-----\r\n" .
                                     "Proc-Type: 4,ENCRYPTED\r\n" .
                                     "DEK-Info: DES-EDE3-CBC,$iv\r\n" .
                                     "\r\n" .
                                     chunk_split(base64_encode($des->encrypt($RSAPrivateKey)), 64) .
                                     '-----END RSA PRIVATE KEY-----';
                } else {
                    $RSAPrivateKey = "-----BEGIN RSA PRIVATE KEY-----\r\n" .
                                     chunk_split(base64_encode($RSAPrivateKey), 64) .
                                     '-----END RSA PRIVATE KEY-----';
                }

                return $RSAPrivateKey;
        }
    }

    /**
     * Convert a public key to the appropriate format
     *
     * @access private
     * @see self::setPublicKeyFormat()
     * @param Math_BigInteger $n
     * @param Math_BigInteger $e
     * @return string|array<string,Math_BigInteger>
     */
    function _convertPublicKey($n, $e)
    {
        $signed = $this->publicKeyFormat != self::PUBLIC_FORMAT_XML;

        $modulus = $n->toBytes($signed);
        $publicExponent = $e->toBytes($signed);

        switch ($this->publicKeyFormat) {
            case self::PUBLIC_FORMAT_RAW:
                return array('e' => $e->copy(), 'n' => $n->copy());
            case self::PUBLIC_FORMAT_XML:
                return "<RSAKeyValue>\r\n" .
                       '  <Modulus>' . base64_encode($modulus) . "</Modulus>\r\n" .
                       '  <Exponent>' . base64_encode($publicExponent) . "</Exponent>\r\n" .
                       '</RSAKeyValue>';
                break;
            case self::PUBLIC_FORMAT_OPENSSH:
                // from <http://tools.ietf.org/html/rfc4253#page-15>:
                // string    "ssh-rsa"
                // mpint     e
                // mpint     n
                $RSAPublicKey = pack('Na*Na*Na*', strlen('ssh-rsa'), 'ssh-rsa', strlen($publicExponent), $publicExponent, strlen($modulus), $modulus);
                $RSAPublicKey = 'ssh-rsa ' . base64_encode($RSAPublicKey) . ' ' . $this->comment;

                return $RSAPublicKey;
            default: // eg. self::PUBLIC_FORMAT_PKCS1_RAW or self::PUBLIC_FORMAT_PKCS1
                // from <http://tools.ietf.org/html/rfc3447#appendix-A.1.1>:
                // RSAPublicKey ::= SEQUENCE {
                //     modulus           INTEGER,  -- n
                //     publicExponent    INTEGER   -- e
                // }
                $components = array(
                    'modulus' => pack('Ca*a*', self::ASN1_INTEGER, $this->_encodeLength(strlen($modulus)), $modulus),
                    'publicExponent' => pack('Ca*a*', self::ASN1_INTEGER, $this->_encodeLength(strlen($publicExponent)), $publicExponent)
                );

                $RSAPublicKey = pack(
                    'Ca*a*a*',
                    self::ASN1_SEQUENCE,
                    $this->_encodeLength(strlen($components['modulus']) + strlen($components['publicExponent'])),
                    $components['modulus'],
                    $components['publicExponent']
                );

                if ($this->publicKeyFormat == self::PUBLIC_FORMAT_PKCS1_RAW) {
                    $RSAPublicKey = "-----BEGIN RSA PUBLIC KEY-----\r\n" .
                                    chunk_split(base64_encode($RSAPublicKey), 64) .
                                    '-----END RSA PUBLIC KEY-----';
                } else {
                    // sequence(oid(1.2.840.113549.1.1.1), null)) = rsaEncryption.
                    $rsaOID = pack('H*', '300d06092a864886f70d0101010500'); // hex version of MA0GCSqGSIb3DQEBAQUA
                    $RSAPublicKey = chr(0) . $RSAPublicKey;
                    $RSAPublicKey = chr(3) . $this->_encodeLength(strlen($RSAPublicKey)) . $RSAPublicKey;

                    $RSAPublicKey = pack(
                        'Ca*a*',
                        self::ASN1_SEQUENCE,
                        $this->_encodeLength(strlen($rsaOID . $RSAPublicKey)),
                        $rsaOID . $RSAPublicKey
                    );

                    $RSAPublicKey = "-----BEGIN PUBLIC KEY-----\r\n" .
                                     chunk_split(base64_encode($RSAPublicKey), 64) .
                                     '-----END PUBLIC KEY-----';
                }

                return $RSAPublicKey;
        }
    }

    /**
     * Break a public or private key down into its constituant components
     *
     * @access private
     * @see self::_convertPublicKey()
     * @see self::_convertPrivateKey()
     * @param string|array $key
     * @param int $type
     * @return array|bool
     */
    function _parseKey($key, $type)
    {
        if ($type != self::PUBLIC_FORMAT_RAW && !is_string($key)) {
            return false;
        }

        switch ($type) {
            case self::PUBLIC_FORMAT_RAW:
                if (!is_array($key)) {
                    return false;
                }
                $components = array();
                switch (true) {
                    case isset($key['e']):
                        $components['publicExponent'] = $key['e']->copy();
                        break;
                    case isset($key['exponent']):
                        $components['publicExponent'] = $key['exponent']->copy();
                        break;
                    case isset($key['publicExponent']):
                        $components['publicExponent'] = $key['publicExponent']->copy();
                        break;
                    case isset($key[0]):
                        $components['publicExponent'] = $key[0]->copy();
                }
                switch (true) {
                    case isset($key['n']):
                        $components['modulus'] = $key['n']->copy();
                        break;
                    case isset($key['modulo']):
                        $components['modulus'] = $key['modulo']->copy();
                        break;
                    case isset($key['modulus']):
                        $components['modulus'] = $key['modulus']->copy();
                        break;
                    case isset($key[1]):
                        $components['modulus'] = $key[1]->copy();
                }
                return isset($components['modulus']) && isset($components['publicExponent']) ? $components : false;
            case self::PRIVATE_FORMAT_PKCS1:
            case self::PRIVATE_FORMAT_PKCS8:
            case self::PUBLIC_FORMAT_PKCS1:
                /* Although PKCS#1 proposes a format that public and private keys can use, encrypting them is
                   "outside the scope" of PKCS#1.  PKCS#1 then refers you to PKCS#12 and PKCS#15 if you're wanting to
                   protect private keys, however, that's not what OpenSSL* does.  OpenSSL protects private keys by adding
                   two new "fields" to the key - DEK-Info and Proc-Type.  These fields are discussed here:

                   http://tools.ietf.org/html/rfc1421#section-4.6.1.1
                   http://tools.ietf.org/html/rfc1421#section-4.6.1.3

                   DES-EDE3-CBC as an algorithm, however, is not discussed anywhere, near as I can tell.
                   DES-CBC and DES-EDE are discussed in RFC1423, however, DES-EDE3-CBC isn't, nor is its key derivation
                   function.  As is, the definitive authority on this encoding scheme isn't the IETF but rather OpenSSL's
                   own implementation.  ie. the implementation *is* the standard and any bugs that may exist in that
                   implementation are part of the standard, as well.

                   * OpenSSL is the de facto standard.  It's utilized by OpenSSH and other projects */
                if (preg_match('#DEK-Info: (.+),(.+)#', $key, $matches)) {
                    $iv = pack('H*', trim($matches[2]));
                    $symkey = pack('H*', md5($this->password . substr($iv, 0, 8))); // symkey is short for symmetric key
                    $symkey.= pack('H*', md5($symkey . $this->password . substr($iv, 0, 8)));
                    // remove the Proc-Type / DEK-Info sections as they're no longer needed
                    $key = preg_replace('#^(?:Proc-Type|DEK-Info): .*#m', '', $key);
                    $ciphertext = $this->_extractBER($key);
                    if ($ciphertext === false) {
                        $ciphertext = $key;
                    }
                    switch ($matches[1]) {
                        case 'AES-256-CBC':
                            $crypto = new AES();
                            break;
                        case 'AES-128-CBC':
                            $symkey = substr($symkey, 0, 16);
                            $crypto = new AES();
                            break;
                        case 'DES-EDE3-CFB':
                            $crypto = new TripleDES(Base::MODE_CFB);
                            break;
                        case 'DES-EDE3-CBC':
                            $symkey = substr($symkey, 0, 24);
                            $crypto = new TripleDES();
                            break;
                        case 'DES-CBC':
                            $crypto = new DES();
                            break;
                        default:
                            return false;
                    }
                    $crypto->setKey($symkey);
                    $crypto->setIV($iv);
                    $decoded = $crypto->decrypt($ciphertext);
                } else {
                    $decoded = $this->_extractBER($key);
                }

                if ($decoded !== false) {
                    $key = $decoded;
                }

                $components = array();

                if (ord($this->_string_shift($key)) != self::ASN1_SEQUENCE) {
                    return false;
                }
                if ($this->_decodeLength($key) != strlen($key)) {
                    return false;
                }

                $tag = ord($this->_string_shift($key));
                /* intended for keys for which OpenSSL's asn1parse returns the following:

                    0:d=0  hl=4 l= 631 cons: SEQUENCE
                    4:d=1  hl=2 l=   1 prim:  INTEGER           :00
                    7:d=1  hl=2 l=  13 cons:  SEQUENCE
                    9:d=2  hl=2 l=   9 prim:   OBJECT            :rsaEncryption
                   20:d=2  hl=2 l=   0 prim:   NULL
                   22:d=1  hl=4 l= 609 prim:  OCTET STRING

                   ie. PKCS8 keys*/

                if ($tag == self::ASN1_INTEGER && substr($key, 0, 3) == "\x01\x00\x30") {
                    $this->_string_shift($key, 3);
                    $tag = self::ASN1_SEQUENCE;
                }

                if ($tag == self::ASN1_SEQUENCE) {
                    $temp = $this->_string_shift($key, $this->_decodeLength($key));
                    if (ord($this->_string_shift($temp)) != self::ASN1_OBJECT) {
                        return false;
                    }
                    $length = $this->_decodeLength($temp);
                    switch ($this->_string_shift($temp, $length)) {
                        case "\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01": // rsaEncryption
                        case "\x2A\x86\x48\x86\xF7\x0D\x01\x01\x0A": // rsaPSS
                            break;
                        case "\x2a\x86\x48\x86\xf7\x0d\x01\x05\x03": // pbeWithMD5AndDES-CBC
                            /*
                               PBEParameter ::= SEQUENCE {
                                   salt OCTET STRING (SIZE(8)),
                                   iterationCount INTEGER }
                            */
                            if (ord($this->_string_shift($temp)) != self::ASN1_SEQUENCE) {
                                return false;
                            }
                            if ($this->_decodeLength($temp) != strlen($temp)) {
                                return false;
                            }
                            $this->_string_shift($temp); // assume it's an octet string
                            $salt = $this->_string_shift($temp, $this->_decodeLength($temp));
                            if (ord($this->_string_shift($temp)) != self::ASN1_INTEGER) {
                                return false;
                            }
                            $this->_decodeLength($temp);
                            list(, $iterationCount) = unpack('N', str_pad($temp, 4, chr(0), STR_PAD_LEFT));
                            $this->_string_shift($key); // assume it's an octet string
                            $length = $this->_decodeLength($key);
                            if (strlen($key) != $length) {
                                return false;
                            }

                            $crypto = new DES();
                            $crypto->setPassword($this->password, 'pbkdf1', 'md5', $salt, $iterationCount);
                            $key = $crypto->decrypt($key);
                            if ($key === false) {
                                return false;
                            }
                            return $this->_parseKey($key, self::PRIVATE_FORMAT_PKCS1);
                        default:
                            return false;
                    }
                    /* intended for keys for which OpenSSL's asn1parse returns the following:

                        0:d=0  hl=4 l= 290 cons: SEQUENCE
                        4:d=1  hl=2 l=  13 cons:  SEQUENCE
                        6:d=2  hl=2 l=   9 prim:   OBJECT            :rsaEncryption
                       17:d=2  hl=2 l=   0 prim:   NULL
                       19:d=1  hl=4 l= 271 prim:  BIT STRING */
                    $tag = ord($this->_string_shift($key)); // skip over the BIT STRING / OCTET STRING tag
                    $this->_decodeLength($key); // skip over the BIT STRING / OCTET STRING length
                    // "The initial octet shall encode, as an unsigned binary integer wtih bit 1 as the least significant bit, the number of
                    //  unused bits in the final subsequent octet. The number shall be in the range zero to seven."
                    //  -- http://www.itu.int/ITU-T/studygroups/com17/languages/X.690-0207.pdf (section 8.6.2.2)
                    if ($tag == self::ASN1_BITSTRING) {
                        $this->_string_shift($key);
                    }
                    if (ord($this->_string_shift($key)) != self::ASN1_SEQUENCE) {
                        return false;
                    }
                    if ($this->_decodeLength($key) != strlen($key)) {
                        return false;
                    }
                    $tag = ord($this->_string_shift($key));
                }
                if ($tag != self::ASN1_INTEGER) {
                    return false;
                }

                $length = $this->_decodeLength($key);
                $temp = $this->_string_shift($key, $length);
                if (strlen($temp) != 1 || ord($temp) > 2) {
                    $components['modulus'] = new BigInteger($temp, 256);
                    $this->_string_shift($key); // skip over self::ASN1_INTEGER
                    $length = $this->_decodeLength($key);
                    $components[$type == self::PUBLIC_FORMAT_PKCS1 ? 'publicExponent' : 'privateExponent'] = new BigInteger($this->_string_shift($key, $length), 256);

                    return $components;
                }
                if (ord($this->_string_shift($key)) != self::ASN1_INTEGER) {
                    return false;
                }
                $length = $this->_decodeLength($key);
                $components['modulus'] = new BigInteger($this->_string_shift($key, $length), 256);
                $this->_string_shift($key);
                $length = $this->_decodeLength($key);
                $components['publicExponent'] = new BigInteger($this->_string_shift($key, $length), 256);
                $this->_string_shift($key);
                $length = $this->_decodeLength($key);
                $components['privateExponent'] = new BigInteger($this->_string_shift($key, $length), 256);
                $this->_string_shift($key);
                $length = $this->_decodeLength($key);
                $components['primes'] = array(1 => new BigInteger($this->_string_shift($key, $length), 256));
                $this->_string_shift($key);
                $length = $this->_decodeLength($key);
                $components['primes'][] = new BigInteger($this->_string_shift($key, $length), 256);
                $this->_string_shift($key);
                $length = $this->_decodeLength($key);
                $components['exponents'] = array(1 => new BigInteger($this->_string_shift($key, $length), 256));
                $this->_string_shift($key);
                $length = $this->_decodeLength($key);
                $components['exponents'][] = new BigInteger($this->_string_shift($key, $length), 256);
                $this->_string_shift($key);
                $length = $this->_decodeLength($key);
                $components['coefficients'] = array(2 => new BigInteger($this->_string_shift($key, $length), 256));

                if (!empty($key)) {
                    if (ord($this->_string_shift($key)) != self::ASN1_SEQUENCE) {
                        return false;
                    }
                    $this->_decodeLength($key);
                    while (!empty($key)) {
                        if (ord($this->_string_shift($key)) != self::ASN1_SEQUENCE) {
                            return false;
                        }
                        $this->_decodeLength($key);
                        $key = substr($key, 1);
                        $length = $this->_decodeLength($key);
                        $components['primes'][] = new BigInteger($this->_string_shift($key, $length), 256);
                        $this->_string_shift($key);
                        $length = $this->_decodeLength($key);
                        $components['exponents'][] = new BigInteger($this->_string_shift($key, $length), 256);
                        $this->_string_shift($key);
                        $length = $this->_decodeLength($key);
                        $components['coefficients'][] = new BigInteger($this->_string_shift($key, $length), 256);
                    }
                }

                return $components;
            case self::PUBLIC_FORMAT_OPENSSH:
                $parts = explode(' ', $key, 3);

                $key = isset($parts[1]) ? base64_decode($parts[1]) : false;
                if ($key === false) {
                    return false;
                }

                $comment = isset($parts[2]) ? $parts[2] : false;

                $cleanup = substr($key, 0, 11) == "\0\0\0\7ssh-rsa";

                if (strlen($key) <= 4) {
                    return false;
                }
                extract(unpack('Nlength', $this->_string_shift($key, 4)));
                $publicExponent = new BigInteger($this->_string_shift($key, $length), -256);
                if (strlen($key) <= 4) {
                    return false;
                }
                extract(unpack('Nlength', $this->_string_shift($key, 4)));
                $modulus = new BigInteger($this->_string_shift($key, $length), -256);

                if ($cleanup && strlen($key)) {
                    if (strlen($key) <= 4) {
                        return false;
                    }
                    extract(unpack('Nlength', $this->_string_shift($key, 4)));
                    $realModulus = new BigInteger($this->_string_shift($key, $length), -256);
                    return strlen($key) ? false : array(
                        'modulus' => $realModulus,
                        'publicExponent' => $modulus,
                        'comment' => $comment
                    );
                } else {
                    return strlen($key) ? false : array(
                        'modulus' => $modulus,
                        'publicExponent' => $publicExponent,
                        'comment' => $comment
                    );
                }
            // http://www.w3.org/TR/xmldsig-core/#sec-RSAKeyValue
            // http://en.wikipedia.org/wiki/XML_Signature
            case self::PRIVATE_FORMAT_XML:
            case self::PUBLIC_FORMAT_XML:
                if (!extension_loaded('xml')) {
                    return false;
                }

                $this->components = array();

                $xml = xml_parser_create('UTF-8');
                xml_set_object($xml, $this);
                xml_set_element_handler($xml, '_start_element_handler', '_stop_element_handler');
                xml_set_character_data_handler($xml, '_data_handler');
                // add <xml></xml> to account for "dangling" tags like <BitStrength>...</BitStrength> that are sometimes added
                if (!xml_parse($xml, '<xml>' . $key . '</xml>')) {
                    xml_parser_free($xml);
                    unset($xml);
                    return false;
                }

                xml_parser_free($xml);
                unset($xml);

                return isset($this->components['modulus']) && isset($this->components['publicExponent']) ? $this->components : false;
            // see PuTTY's SSHPUBK.C and https://tartarus.org/~simon/putty-snapshots/htmldoc/AppendixC.html
            case self::PRIVATE_FORMAT_PUTTY:
                $components = array();
                $key = preg_split('#\r\n|\r|\n#', $key);
                if ($this->_string_shift($key[0], strlen('PuTTY-User-Key-File-')) != 'PuTTY-User-Key-File-') {
                    return false;
                }
                $version = (int) $this->_string_shift($key[0], 3); // should be either "2: " or "3: 0" prior to int casting
                if ($version != 2 && $version != 3) {
                    return false;
                }
                $type = rtrim($key[0]);
                if ($type != 'ssh-rsa') {
                    return false;
                }
                $encryption = trim(preg_replace('#Encryption: (.+)#', '$1', $key[1]));
                $comment = trim(preg_replace('#Comment: (.+)#', '$1', $key[2]));

                $publicLength = trim(preg_replace('#Public-Lines: (\d+)#', '$1', $key[3]));
                $public = base64_decode(implode('', array_map('trim', array_slice($key, 4, $publicLength))));
                $public = substr($public, 11);
                extract(unpack('Nlength', $this->_string_shift($public, 4)));
                $components['publicExponent'] = new BigInteger($this->_string_shift($public, $length), -256);
                extract(unpack('Nlength', $this->_string_shift($public, 4)));
                $components['modulus'] = new BigInteger($this->_string_shift($public, $length), -256);

                $offset = $publicLength + 4;
                switch ($encryption) {
                    case 'aes256-cbc':
                        $crypto = new AES();
                        switch ($version) {
                            case 3:
                                if (!function_exists('sodium_crypto_pwhash')) {
                                    return false;
                                }
                                $flavour = trim(preg_replace('#Key-Derivation: (.*)#', '$1', $key[$offset++]));
                                switch ($flavour) {
                                    case 'Argon2i':
                                        $flavour = SODIUM_CRYPTO_PWHASH_ALG_ARGON2I13;
                                        break;
                                    case 'Argon2id':
                                        $flavour = SODIUM_CRYPTO_PWHASH_ALG_ARGON2ID13;
                                        break;
                                    default:
                                        return false;
                                }
                                $memory = trim(preg_replace('#Argon2-Memory: (\d+)#', '$1', $key[$offset++]));
                                $passes = trim(preg_replace('#Argon2-Passes: (\d+)#', '$1', $key[$offset++]));
                                $parallelism = trim(preg_replace('#Argon2-Parallelism: (\d+)#', '$1', $key[$offset++]));
                                $salt = pack('H*', trim(preg_replace('#Argon2-Salt: ([0-9a-f]+)#', '$1', $key[$offset++])));

                                $length = 80; // keylen + ivlen + mac_keylen
                                $temp = sodium_crypto_pwhash($length, $this->password, $salt, $passes, $memory << 10, $flavour);

                                $symkey = substr($temp, 0, 32);
                                $symiv = substr($temp, 32, 16);
                                break;
                            case 2:
                                $symkey = '';
                                $sequence = 0;
                                while (strlen($symkey) < 32) {
                                    $temp = pack('Na*', $sequence++, $this->password);
                                    $symkey.= pack('H*', sha1($temp));
                                }
                                $symkey = substr($symkey, 0, 32);
                                $symiv = str_repeat("\0", 16);
                        }
                }

                $privateLength = trim(preg_replace('#Private-Lines: (\d+)#', '$1', $key[$offset++]));
                $private = base64_decode(implode('', array_map('trim', array_slice($key, $offset, $privateLength))));

                if ($encryption != 'none') {
                    $crypto->setKey($symkey);
                    $crypto->setIV($symiv);
                    $crypto->disablePadding();
                    $private = $crypto->decrypt($private);
                    if ($private === false) {
                        return false;
                    }
                }

                extract(unpack('Nlength', $this->_string_shift($private, 4)));
                if (strlen($private) < $length) {
                    return false;
                }
                $components['privateExponent'] = new BigInteger($this->_string_shift($private, $length), -256);
                extract(unpack('Nlength', $this->_string_shift($private, 4)));
                if (strlen($private) < $length) {
                    return false;
                }
                $components['primes'] = array(1 => new BigInteger($this->_string_shift($private, $length), -256));
                extract(unpack('Nlength', $this->_string_shift($private, 4)));
                if (strlen($private) < $length) {
                    return false;
                }
                $components['primes'][] = new BigInteger($this->_string_shift($private, $length), -256);

                $temp = $components['primes'][1]->subtract($this->one);
                $components['exponents'] = array(1 => $components['publicExponent']->modInverse($temp));
                $temp = $components['primes'][2]->subtract($this->one);
                $components['exponents'][] = $components['publicExponent']->modInverse($temp);

                extract(unpack('Nlength', $this->_string_shift($private, 4)));
                if (strlen($private) < $length) {
                    return false;
                }
                $components['coefficients'] = array(2 => new BigInteger($this->_string_shift($private, $length), -256));

                return $components;
            case self::PRIVATE_FORMAT_OPENSSH:
                $components = array();
                $decoded = $this->_extractBER($key);
                $magic = $this->_string_shift($decoded, 15);
                if ($magic !== "openssh-key-v1\0") {
                    return false;
                }
                extract(unpack('Nlength', $this->_string_shift($decoded, 4)));
                if (strlen($decoded) < $length) {
                    return false;
                }
                $ciphername = $this->_string_shift($decoded, $length);
                extract(unpack('Nlength', $this->_string_shift($decoded, 4)));
                if (strlen($decoded) < $length) {
                    return false;
                }
                $kdfname = $this->_string_shift($decoded, $length);
                extract(unpack('Nlength', $this->_string_shift($decoded, 4)));
                if (strlen($decoded) < $length) {
                    return false;
                }
                $kdfoptions = $this->_string_shift($decoded, $length);
                extract(unpack('Nnumkeys', $this->_string_shift($decoded, 4)));
                if ($numkeys != 1 || ($ciphername != 'none' && $kdfname != 'bcrypt')) {
                    return false;
                }
                switch ($ciphername) {
                    case 'none':
                        break;
                    case 'aes256-ctr':
                        extract(unpack('Nlength', $this->_string_shift($kdfoptions, 4)));
                        if (strlen($kdfoptions) < $length) {
                            return false;
                        }
                        $salt = $this->_string_shift($kdfoptions, $length);
                        extract(unpack('Nrounds', $this->_string_shift($kdfoptions, 4)));
                        $crypto = new AES(AES::MODE_CTR);
                        $crypto->disablePadding();
                        if (!$crypto->setPassword($this->password, 'bcrypt', $salt, $rounds, 32)) {
                            return false;
                        }
                        break;
                    default:
                        return false;
                }
                extract(unpack('Nlength', $this->_string_shift($decoded, 4)));
                if (strlen($decoded) < $length) {
                    return false;
                }
                $publicKey = $this->_string_shift($decoded, $length);
                extract(unpack('Nlength', $this->_string_shift($decoded, 4)));
                if (strlen($decoded) < $length) {
                    return false;
                }

                if ($this->_string_shift($publicKey, 11) !== "\0\0\0\7ssh-rsa") {
                    return false;
                }

                $paddedKey = $this->_string_shift($decoded, $length);
                if (isset($crypto)) {
                    $paddedKey = $crypto->decrypt($paddedKey);
                }

                $checkint1 = $this->_string_shift($paddedKey, 4);
                $checkint2 = $this->_string_shift($paddedKey, 4);
                if (strlen($checkint1) != 4 || $checkint1 !== $checkint2) {
                    return false;
                }

                if ($this->_string_shift($paddedKey, 11) !== "\0\0\0\7ssh-rsa") {
                    return false;
                }

                $values = array(
                    &$components['modulus'],
                    &$components['publicExponent'],
                    &$components['privateExponent'],
                    &$components['coefficients'][2],
                    &$components['primes'][1],
                    &$components['primes'][2]
                );

                foreach ($values as &$value) {
                    extract(unpack('Nlength', $this->_string_shift($paddedKey, 4)));
                    if (strlen($paddedKey) < $length) {
                        return false;
                    }
                    $value = new BigInteger($this->_string_shift($paddedKey, $length), -256);
                }

                extract(unpack('Nlength', $this->_string_shift($paddedKey, 4)));
                if (strlen($paddedKey) < $length) {
                    return false;
                }
                $components['comment'] = $this->_string_shift($decoded, $length);

                $temp = $components['primes'][1]->subtract($this->one);
                $components['exponents'] = array(1 => $components['publicExponent']->modInverse($temp));
                $temp = $components['primes'][2]->subtract($this->one);
                $components['exponents'][] = $components['publicExponent']->modInverse($temp);

                return $components;
        }

        return false;
    }

    /**
     * Returns the key size
     *
     * More specifically, this returns the size of the modulo in bits.
     *
     * @access public
     * @return int
     */
    function getSize()
    {
        return !isset($this->modulus) ? 0 : strlen($this->modulus->toBits());
    }

    /**
     * Start Element Handler
     *
     * Called by xml_set_element_handler()
     *
     * @access private
     * @param resource $parser
     * @param string $name
     * @param array $attribs
     */
    function _start_element_handler($parser, $name, $attribs)
    {
        //$name = strtoupper($name);
        switch ($name) {
            case 'MODULUS':
                $this->current = &$this->components['modulus'];
                break;
            case 'EXPONENT':
                $this->current = &$this->components['publicExponent'];
                break;
            case 'P':
                $this->current = &$this->components['primes'][1];
                break;
            case 'Q':
                $this->current = &$this->components['primes'][2];
                break;
            case 'DP':
                $this->current = &$this->components['exponents'][1];
                break;
            case 'DQ':
                $this->current = &$this->components['exponents'][2];
                break;
            case 'INVERSEQ':
                $this->current = &$this->components['coefficients'][2];
                break;
            case 'D':
                $this->current = &$this->components['privateExponent'];
        }
        $this->current = '';
    }

    /**
     * Stop Element Handler
     *
     * Called by xml_set_element_handler()
     *
     * @access private
     * @param resource $parser
     * @param string $name
     */
    function _stop_element_handler($parser, $name)
    {
        if (isset($this->current)) {
            $this->current = new BigInteger(base64_decode($this->current), 256);
            unset($this->current);
        }
    }

    /**
     * Data Handler
     *
     * Called by xml_set_character_data_handler()
     *
     * @access private
     * @param resource $parser
     * @param string $data
     */
    function _data_handler($parser, $data)
    {
        if (!isset($this->current) || is_object($this->current)) {
            return;
        }
        $this->current.= trim($data);
    }

    /**
     * Loads a public or private key
     *
     * Returns true on success and false on failure (ie. an incorrect password was provided or the key was malformed)
     *
     * @access public
     * @param string|RSA|array $key
     * @param bool|int $type optional
     * @return bool
     */
    function loadKey($key, $type = false)
    {
        if ($key instanceof RSA) {
            $this->privateKeyFormat = $key->privateKeyFormat;
            $this->publicKeyFormat = $key->publicKeyFormat;
            $this->k = $key->k;
            $this->hLen = $key->hLen;
            $this->sLen = $key->sLen;
            $this->mgfHLen = $key->mgfHLen;
            $this->encryptionMode = $key->encryptionMode;
            $this->signatureMode = $key->signatureMode;
            $this->password = $key->password;
            $this->configFile = $key->configFile;
            $this->comment = $key->comment;

            if (is_object($key->hash)) {
                $this->hash = new Hash($key->hash->getHash());
            }
            if (is_object($key->mgfHash)) {
                $this->mgfHash = new Hash($key->mgfHash->getHash());
            }

            if (is_object($key->modulus)) {
                $this->modulus = $key->modulus->copy();
            }
            if (is_object($key->exponent)) {
                $this->exponent = $key->exponent->copy();
            }
            if (is_object($key->publicExponent)) {
                $this->publicExponent = $key->publicExponent->copy();
            }

            $this->primes = array();
            $this->exponents = array();
            $this->coefficients = array();

            foreach ($this->primes as $prime) {
                $this->primes[] = $prime->copy();
            }
            foreach ($this->exponents as $exponent) {
                $this->exponents[] = $exponent->copy();
            }
            foreach ($this->coefficients as $coefficient) {
                $this->coefficients[] = $coefficient->copy();
            }

            return true;
        }

        if ($type === false) {
            $types = array(
                self::PUBLIC_FORMAT_RAW,
                self::PRIVATE_FORMAT_PKCS1,
                self::PRIVATE_FORMAT_XML,
                self::PRIVATE_FORMAT_PUTTY,
                self::PUBLIC_FORMAT_OPENSSH,
                self::PRIVATE_FORMAT_OPENSSH
            );
            foreach ($types as $type) {
                $components = $this->_parseKey($key, $type);
                if ($components !== false) {
                    break;
                }
            }
        } else {
            $components = $this->_parseKey($key, $type);
        }

        if ($components === false) {
            $this->comment = null;
            $this->modulus = null;
            $this->k = null;
            $this->exponent = null;
            $this->primes = null;
            $this->exponents = null;
            $this->coefficients = null;
            $this->publicExponent = null;

            return false;
        }

        if (isset($components['comment']) && $components['comment'] !== false) {
            $this->comment = $components['comment'];
        }
        $this->modulus = $components['modulus'];
        $this->k = strlen($this->modulus->toBytes());
        $this->exponent = isset($components['privateExponent']) ? $components['privateExponent'] : $components['publicExponent'];
        if (isset($components['primes'])) {
            $this->primes = $components['primes'];
            $this->exponents = $components['exponents'];
            $this->coefficients = $components['coefficients'];
            $this->publicExponent = $components['publicExponent'];
        } else {
            $this->primes = array();
            $this->exponents = array();
            $this->coefficients = array();
            $this->publicExponent = false;
        }

        switch ($type) {
            case self::PUBLIC_FORMAT_OPENSSH:
            case self::PUBLIC_FORMAT_RAW:
                $this->setPublicKey();
                break;
            case self::PRIVATE_FORMAT_PKCS1:
                switch (true) {
                    case strpos($key, '-BEGIN PUBLIC KEY-') !== false:
                    case strpos($key, '-BEGIN RSA PUBLIC KEY-') !== false:
                        $this->setPublicKey();
                }
        }

        return true;
    }

    /**
     * Sets the password
     *
     * Private keys can be encrypted with a password.  To unset the password, pass in the empty string or false.
     * Or rather, pass in $password such that empty($password) && !is_string($password) is true.
     *
     * @see self::createKey()
     * @see self::loadKey()
     * @access public
     * @param string $password
     */
    function setPassword($password = false)
    {
        $this->password = $password;
    }

    /**
     * Defines the public key
     *
     * Some private key formats define the public exponent and some don't.  Those that don't define it are problematic when
     * used in certain contexts.  For example, in SSH-2, RSA authentication works by sending the public key along with a
     * message signed by the private key to the server.  The SSH-2 server looks the public key up in an index of public keys
     * and if it's present then proceeds to verify the signature.  Problem is, if your private key doesn't include the public
     * exponent this won't work unless you manually add the public exponent. phpseclib tries to guess if the key being used
     * is the public key but in the event that it guesses incorrectly you might still want to explicitly set the key as being
     * public.
     *
     * Do note that when a new key is loaded the index will be cleared.
     *
     * Returns true on success, false on failure
     *
     * @see self::getPublicKey()
     * @access public
     * @param string $key optional
     * @param int $type optional
     * @return bool
     */
    function setPublicKey($key = false, $type = false)
    {
        // if a public key has already been loaded return false
        if (!empty($this->publicExponent)) {
            return false;
        }

        if ($key === false && !empty($this->modulus)) {
            $this->publicExponent = $this->exponent;
            return true;
        }

        if ($type === false) {
            $types = array(
                self::PUBLIC_FORMAT_RAW,
                self::PUBLIC_FORMAT_PKCS1,
                self::PUBLIC_FORMAT_XML,
                self::PUBLIC_FORMAT_OPENSSH
            );
            foreach ($types as $type) {
                $components = $this->_parseKey($key, $type);
                if ($components !== false) {
                    break;
                }
            }
        } else {
            $components = $this->_parseKey($key, $type);
        }

        if ($components === false) {
            return false;
        }

        if (empty($this->modulus) || !$this->modulus->equals($components['modulus'])) {
            $this->modulus = $components['modulus'];
            $this->exponent = $this->publicExponent = $components['publicExponent'];
            return true;
        }

        $this->publicExponent = $components['publicExponent'];

        return true;
    }

    /**
     * Defines the private key
     *
     * If phpseclib guessed a private key was a public key and loaded it as such it might be desirable to force
     * phpseclib to treat the key as a private key. This function will do that.
     *
     * Do note that when a new key is loaded the index will be cleared.
     *
     * Returns true on success, false on failure
     *
     * @see self::getPublicKey()
     * @access public
     * @param string $key optional
     * @param int $type optional
     * @return bool
     */
    function setPrivateKey($key = false, $type = false)
    {
        if ($key === false && !empty($this->publicExponent)) {
            $this->publicExponent = false;
            return true;
        }

        $rsa = new RSA();
        if (!$rsa->loadKey($key, $type)) {
            return false;
        }
        $rsa->publicExponent = false;

        // don't overwrite the old key if the new key is invalid
        $this->loadKey($rsa);
        return true;
    }

    /**
     * Returns the public key
     *
     * The public key is only returned under two circumstances - if the private key had the public key embedded within it
     * or if the public key was set via setPublicKey().  If the currently loaded key is supposed to be the public key this
     * function won't return it since this library, for the most part, doesn't distinguish between public and private keys.
     *
     * @see self::getPublicKey()
     * @access public
     * @param int $type optional
     */
    function getPublicKey($type = self::PUBLIC_FORMAT_PKCS8)
    {
        if (empty($this->modulus) || empty($this->publicExponent)) {
            return false;
        }

        $oldFormat = $this->publicKeyFormat;
        $this->publicKeyFormat = $type;
        $temp = $this->_convertPublicKey($this->modulus, $this->publicExponent);
        $this->publicKeyFormat = $oldFormat;
        return $temp;
    }

    /**
     * Returns the public key's fingerprint
     *
     * The public key's fingerprint is returned, which is equivalent to running `ssh-keygen -lf rsa.pub`. If there is
     * no public key currently loaded, false is returned.
     * Example output (md5): "c1:b1:30:29:d7:b8:de:6c:97:77:10:d7:46:41:63:87" (as specified by RFC 4716)
     *
     * @access public
     * @param string $algorithm The hashing algorithm to be used. Valid options are 'md5' and 'sha256'. False is returned
     * for invalid values.
     * @return mixed
     */
    function getPublicKeyFingerprint($algorithm = 'md5')
    {
        if (empty($this->modulus) || empty($this->publicExponent)) {
            return false;
        }

        $modulus = $this->modulus->toBytes(true);
        $publicExponent = $this->publicExponent->toBytes(true);

        $RSAPublicKey = pack('Na*Na*Na*', strlen('ssh-rsa'), 'ssh-rsa', strlen($publicExponent), $publicExponent, strlen($modulus), $modulus);

        switch ($algorithm) {
            case 'sha256':
                $hash = new Hash('sha256');
                $base = base64_encode($hash->hash($RSAPublicKey));
                return substr($base, 0, strlen($base) - 1);
            case 'md5':
                return substr(chunk_split(md5($RSAPublicKey), 2, ':'), 0, -1);
            default:
                return false;
        }
    }

    /**
     * Returns the private key
     *
     * The private key is only returned if the currently loaded key contains the constituent prime numbers.
     *
     * @see self::getPublicKey()
     * @access public
     * @param int $type optional
     * @return mixed
     */
    function getPrivateKey($type = self::PUBLIC_FORMAT_PKCS1)
    {
        if (empty($this->primes)) {
            return false;
        }

        $oldFormat = $this->privateKeyFormat;
        $this->privateKeyFormat = $type;
        $temp = $this->_convertPrivateKey($this->modulus, $this->publicExponent, $this->exponent, $this->primes, $this->exponents, $this->coefficients);
        $this->privateKeyFormat = $oldFormat;
        return $temp;
    }

    /**
     * Returns a minimalistic private key
     *
     * Returns the private key without the prime number constituants.  Structurally identical to a public key that
     * hasn't been set as the public key
     *
     * @see self::getPrivateKey()
     * @access private
     * @param int $mode optional
     */
    function _getPrivatePublicKey($mode = self::PUBLIC_FORMAT_PKCS8)
    {
        if (empty($this->modulus) || empty($this->exponent)) {
            return false;
        }

        $oldFormat = $this->publicKeyFormat;
        $this->publicKeyFormat = $mode;
        $temp = $this->_convertPublicKey($this->modulus, $this->exponent);
        $this->publicKeyFormat = $oldFormat;
        return $temp;
    }

    /**
     *  __toString() magic method
     *
     * @access public
     * @return string
     */
    function __toString()
    {
        $key = $this->getPrivateKey($this->privateKeyFormat);
        if ($key !== false) {
            return $key;
        }
        $key = $this->_getPrivatePublicKey($this->publicKeyFormat);
        return $key !== false ? $key : '';
    }

    /**
     *  __clone() magic method
     *
     * @access public
     * @return Crypt_RSA
     */
    function __clone()
    {
        $key = new RSA();
        $key->loadKey($this);
        return $key;
    }

    /**
     * Generates the smallest and largest numbers requiring $bits bits
     *
     * @access private
     * @param int $bits
     * @return array
     */
    function _generateMinMax($bits)
    {
        $bytes = $bits >> 3;
        $min = str_repeat(chr(0), $bytes);
        $max = str_repeat(chr(0xFF), $bytes);
        $msb = $bits & 7;
        if ($msb) {
            $min = chr(1 << ($msb - 1)) . $min;
            $max = chr((1 << $msb) - 1) . $max;
        } else {
            $min[0] = chr(0x80);
        }

        return array(
            'min' => new BigInteger($min, 256),
            'max' => new BigInteger($max, 256)
        );
    }

    /**
     * DER-decode the length
     *
     * DER supports lengths up to (2**8)**127, however, we'll only support lengths up to (2**8)**4.  See
     * {@link http://itu.int/ITU-T/studygroups/com17/languages/X.690-0207.pdf#p=13 X.690 paragraph 8.1.3} for more information.
     *
     * @access private
     * @param string $string
     * @return int
     */
    function _decodeLength(&$string)
    {
        $length = ord($this->_string_shift($string));
        if ($length & 0x80) { // definite length, long form
            $length&= 0x7F;
            $temp = $this->_string_shift($string, $length);
            list(, $length) = unpack('N', substr(str_pad($temp, 4, chr(0), STR_PAD_LEFT), -4));
        }
        return $length;
    }

    /**
     * DER-encode the length
     *
     * DER supports lengths up to (2**8)**127, however, we'll only support lengths up to (2**8)**4.  See
     * {@link http://itu.int/ITU-T/studygroups/com17/languages/X.690-0207.pdf#p=13 X.690 paragraph 8.1.3} for more information.
     *
     * @access private
     * @param int $length
     * @return string
     */
    function _encodeLength($length)
    {
        if ($length <= 0x7F) {
            return chr($length);
        }

        $temp = ltrim(pack('N', $length), chr(0));
        return pack('Ca*', 0x80 | strlen($temp), $temp);
    }

    /**
     * String Shift
     *
     * Inspired by array_shift
     *
     * @param string $string
     * @param int $index
     * @return string
     * @access private
     */
    function _string_shift(&$string, $index = 1)
    {
        $substr = substr($string, 0, $index);
        $string = substr($string, $index);
        return $substr;
    }

    /**
     * Determines the private key format
     *
     * @see self::createKey()
     * @access public
     * @param int $format
     */
    function setPrivateKeyFormat($format)
    {
        $this->privateKeyFormat = $format;
    }

    /**
     * Determines the public key format
     *
     * @see self::createKey()
     * @access public
     * @param int $format
     */
    function setPublicKeyFormat($format)
    {
        $this->publicKeyFormat = $format;
    }

    /**
     * Determines which hashing function should be used
     *
     * Used with signature production / verification and (if the encryption mode is self::ENCRYPTION_OAEP) encryption and
     * decryption.  If $hash isn't supported, sha1 is used.
     *
     * @access public
     * @param string $hash
     */
    function setHash($hash)
    {
        // \phpseclib\Crypt\Hash supports algorithms that PKCS#1 doesn't support.  md5-96 and sha1-96, for example.
        switch ($hash) {
            case 'md2':
            case 'md5':
            case 'sha1':
            case 'sha256':
            case 'sha384':
            case 'sha512':
                $this->hash = new Hash($hash);
                $this->hashName = $hash;
                break;
            default:
                $this->hash = new Hash('sha1');
                $this->hashName = 'sha1';
        }
        $this->hLen = $this->hash->getLength();
    }

    /**
     * Determines which hashing function should be used for the mask generation function
     *
     * The mask generation function is used by self::ENCRYPTION_OAEP and self::SIGNATURE_PSS and although it's
     * best if Hash and MGFHash are set to the same thing this is not a requirement.
     *
     * @access public
     * @param string $hash
     */
    function setMGFHash($hash)
    {
        // \phpseclib\Crypt\Hash supports algorithms that PKCS#1 doesn't support.  md5-96 and sha1-96, for example.
        switch ($hash) {
            case 'md2':
            case 'md5':
            case 'sha1':
            case 'sha256':
            case 'sha384':
            case 'sha512':
                $this->mgfHash = new Hash($hash);
                break;
            default:
                $this->mgfHash = new Hash('sha1');
        }
        $this->mgfHLen = $this->mgfHash->getLength();
    }

    /**
     * Determines the salt length
     *
     * To quote from {@link http://tools.ietf.org/html/rfc3447#page-38 RFC3447#page-38}:
     *
     *    Typical salt lengths in octets are hLen (the length of the output
     *    of the hash function Hash) and 0.
     *
     * @access public
     * @param int $sLen
     */
    function setSaltLength($sLen)
    {
        $this->sLen = $sLen;
    }

    /**
     * Integer-to-Octet-String primitive
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-4.1 RFC3447#section-4.1}.
     *
     * @access private
     * @param \phpseclib\Math\BigInteger $x
     * @param int $xLen
     * @return string
     */
    function _i2osp($x, $xLen)
    {
        $x = $x->toBytes();
        if (strlen($x) > $xLen) {
            user_error('Integer too large');
            return false;
        }
        return str_pad($x, $xLen, chr(0), STR_PAD_LEFT);
    }

    /**
     * Octet-String-to-Integer primitive
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-4.2 RFC3447#section-4.2}.
     *
     * @access private
     * @param int|string|resource $x
     * @return \phpseclib\Math\BigInteger
     */
    function _os2ip($x)
    {
        return new BigInteger($x, 256);
    }

    /**
     * Exponentiate with or without Chinese Remainder Theorem
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-5.1.1 RFC3447#section-5.1.2}.
     *
     * @access private
     * @param \phpseclib\Math\BigInteger $x
     * @return \phpseclib\Math\BigInteger
     */
    function _exponentiate($x)
    {
        switch (true) {
            case empty($this->primes):
            case $this->primes[1]->equals($this->zero):
            case empty($this->coefficients):
            case $this->coefficients[2]->equals($this->zero):
            case empty($this->exponents):
            case $this->exponents[1]->equals($this->zero):
                return $x->modPow($this->exponent, $this->modulus);
        }

        $num_primes = count($this->primes);

        if (defined('CRYPT_RSA_DISABLE_BLINDING')) {
            $m_i = array(
                1 => $x->modPow($this->exponents[1], $this->primes[1]),
                2 => $x->modPow($this->exponents[2], $this->primes[2])
            );
            $h = $m_i[1]->subtract($m_i[2]);
            $h = $h->multiply($this->coefficients[2]);
            list(, $h) = $h->divide($this->primes[1]);
            $m = $m_i[2]->add($h->multiply($this->primes[2]));

            $r = $this->primes[1];
            for ($i = 3; $i <= $num_primes; $i++) {
                $m_i = $x->modPow($this->exponents[$i], $this->primes[$i]);

                $r = $r->multiply($this->primes[$i - 1]);

                $h = $m_i->subtract($m);
                $h = $h->multiply($this->coefficients[$i]);
                list(, $h) = $h->divide($this->primes[$i]);

                $m = $m->add($r->multiply($h));
            }
        } else {
            $smallest = $this->primes[1];
            for ($i = 2; $i <= $num_primes; $i++) {
                if ($smallest->compare($this->primes[$i]) > 0) {
                    $smallest = $this->primes[$i];
                }
            }

            $one = new BigInteger(1);

            $r = $one->random($one, $smallest->subtract($one));

            $m_i = array(
                1 => $this->_blind($x, $r, 1),
                2 => $this->_blind($x, $r, 2)
            );
            $h = $m_i[1]->subtract($m_i[2]);
            $h = $h->multiply($this->coefficients[2]);
            list(, $h) = $h->divide($this->primes[1]);
            $m = $m_i[2]->add($h->multiply($this->primes[2]));

            $r = $this->primes[1];
            for ($i = 3; $i <= $num_primes; $i++) {
                $m_i = $this->_blind($x, $r, $i);

                $r = $r->multiply($this->primes[$i - 1]);

                $h = $m_i->subtract($m);
                $h = $h->multiply($this->coefficients[$i]);
                list(, $h) = $h->divide($this->primes[$i]);

                $m = $m->add($r->multiply($h));
            }
        }

        return $m;
    }

    /**
     * Performs RSA Blinding
     *
     * Protects against timing attacks by employing RSA Blinding.
     * Returns $x->modPow($this->exponents[$i], $this->primes[$i])
     *
     * @access private
     * @param \phpseclib\Math\BigInteger $x
     * @param \phpseclib\Math\BigInteger $r
     * @param int $i
     * @return \phpseclib\Math\BigInteger
     */
    function _blind($x, $r, $i)
    {
        $x = $x->multiply($r->modPow($this->publicExponent, $this->primes[$i]));
        $x = $x->modPow($this->exponents[$i], $this->primes[$i]);

        $r = $r->modInverse($this->primes[$i]);
        $x = $x->multiply($r);
        list(, $x) = $x->divide($this->primes[$i]);

        return $x;
    }

    /**
     * Performs blinded RSA equality testing
     *
     * Protects against a particular type of timing attack described.
     *
     * See {@link http://codahale.com/a-lesson-in-timing-attacks/ A Lesson In Timing Attacks (or, Don't use MessageDigest.isEquals)}
     *
     * Thanks for the heads up singpolyma!
     *
     * @access private
     * @param string $x
     * @param string $y
     * @return bool
     */
    function _equals($x, $y)
    {
        if (function_exists('hash_equals')) {
            return hash_equals($x, $y);
        }

        if (strlen($x) != strlen($y)) {
            return false;
        }

        $result = "\0";
        $x^= $y;
        for ($i = 0; $i < strlen($x); $i++) {
            $result|= $x[$i];
        }

        return $result === "\0";
    }

    /**
     * RSAEP
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-5.1.1 RFC3447#section-5.1.1}.
     *
     * @access private
     * @param \phpseclib\Math\BigInteger $m
     * @return \phpseclib\Math\BigInteger
     */
    function _rsaep($m)
    {
        if ($m->compare($this->zero) < 0 || $m->compare($this->modulus) > 0) {
            user_error('Message representative out of range');
            return false;
        }
        return $this->_exponentiate($m);
    }

    /**
     * RSADP
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-5.1.2 RFC3447#section-5.1.2}.
     *
     * @access private
     * @param \phpseclib\Math\BigInteger $c
     * @return \phpseclib\Math\BigInteger
     */
    function _rsadp($c)
    {
        if ($c->compare($this->zero) < 0 || $c->compare($this->modulus) > 0) {
            user_error('Ciphertext representative out of range');
            return false;
        }
        return $this->_exponentiate($c);
    }

    /**
     * RSASP1
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-5.2.1 RFC3447#section-5.2.1}.
     *
     * @access private
     * @param \phpseclib\Math\BigInteger $m
     * @return \phpseclib\Math\BigInteger
     */
    function _rsasp1($m)
    {
        if ($m->compare($this->zero) < 0 || $m->compare($this->modulus) > 0) {
            user_error('Message representative out of range');
            return false;
        }
        return $this->_exponentiate($m);
    }

    /**
     * RSAVP1
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-5.2.2 RFC3447#section-5.2.2}.
     *
     * @access private
     * @param \phpseclib\Math\BigInteger $s
     * @return \phpseclib\Math\BigInteger
     */
    function _rsavp1($s)
    {
        if ($s->compare($this->zero) < 0 || $s->compare($this->modulus) > 0) {
            user_error('Signature representative out of range');
            return false;
        }
        return $this->_exponentiate($s);
    }

    /**
     * MGF1
     *
     * See {@link http://tools.ietf.org/html/rfc3447#appendix-B.2.1 RFC3447#appendix-B.2.1}.
     *
     * @access private
     * @param string $mgfSeed
     * @param int $maskLen
     * @return string
     */
    function _mgf1($mgfSeed, $maskLen)
    {
        // if $maskLen would yield strings larger than 4GB, PKCS#1 suggests a "Mask too long" error be output.

        $t = '';
        $count = ceil($maskLen / $this->mgfHLen);
        for ($i = 0; $i < $count; $i++) {
            $c = pack('N', $i);
            $t.= $this->mgfHash->hash($mgfSeed . $c);
        }

        return substr($t, 0, $maskLen);
    }

    /**
     * RSAES-OAEP-ENCRYPT
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-7.1.1 RFC3447#section-7.1.1} and
     * {http://en.wikipedia.org/wiki/Optimal_Asymmetric_Encryption_Padding OAES}.
     *
     * @access private
     * @param string $m
     * @param string $l
     * @return string
     */
    function _rsaes_oaep_encrypt($m, $l = '')
    {
        $mLen = strlen($m);

        // Length checking

        // if $l is larger than two million terrabytes and you're using sha1, PKCS#1 suggests a "Label too long" error
        // be output.

        if ($mLen > $this->k - 2 * $this->hLen - 2) {
            user_error('Message too long');
            return false;
        }

        // EME-OAEP encoding

        $lHash = $this->hash->hash($l);
        $ps = str_repeat(chr(0), $this->k - $mLen - 2 * $this->hLen - 2);
        $db = $lHash . $ps . chr(1) . $m;
        $seed = Random::string($this->hLen);
        $dbMask = $this->_mgf1($seed, $this->k - $this->hLen - 1);
        $maskedDB = $db ^ $dbMask;
        $seedMask = $this->_mgf1($maskedDB, $this->hLen);
        $maskedSeed = $seed ^ $seedMask;
        $em = chr(0) . $maskedSeed . $maskedDB;

        // RSA encryption

        $m = $this->_os2ip($em);
        $c = $this->_rsaep($m);
        $c = $this->_i2osp($c, $this->k);

        // Output the ciphertext C

        return $c;
    }

    /**
     * RSAES-OAEP-DECRYPT
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-7.1.2 RFC3447#section-7.1.2}.  The fact that the error
     * messages aren't distinguishable from one another hinders debugging, but, to quote from RFC3447#section-7.1.2:
     *
     *    Note.  Care must be taken to ensure that an opponent cannot
     *    distinguish the different error conditions in Step 3.g, whether by
     *    error message or timing, or, more generally, learn partial
     *    information about the encoded message EM.  Otherwise an opponent may
     *    be able to obtain useful information about the decryption of the
     *    ciphertext C, leading to a chosen-ciphertext attack such as the one
     *    observed by Manger [36].
     *
     * As for $l...  to quote from {@link http://tools.ietf.org/html/rfc3447#page-17 RFC3447#page-17}:
     *
     *    Both the encryption and the decryption operations of RSAES-OAEP take
     *    the value of a label L as input.  In this version of PKCS #1, L is
     *    the empty string; other uses of the label are outside the scope of
     *    this document.
     *
     * @access private
     * @param string $c
     * @param string $l
     * @return string
     */
    function _rsaes_oaep_decrypt($c, $l = '')
    {
        // Length checking

        // if $l is larger than two million terrabytes and you're using sha1, PKCS#1 suggests a "Label too long" error
        // be output.

        if (strlen($c) != $this->k || $this->k < 2 * $this->hLen + 2) {
            user_error('Decryption error');
            return false;
        }

        // RSA decryption

        $c = $this->_os2ip($c);
        $m = $this->_rsadp($c);
        if ($m === false) {
            user_error('Decryption error');
            return false;
        }
        $em = $this->_i2osp($m, $this->k);

        // EME-OAEP decoding

        $lHash = $this->hash->hash($l);
        $y = ord($em[0]);
        $maskedSeed = substr($em, 1, $this->hLen);
        $maskedDB = substr($em, $this->hLen + 1);
        $seedMask = $this->_mgf1($maskedDB, $this->hLen);
        $seed = $maskedSeed ^ $seedMask;
        $dbMask = $this->_mgf1($seed, $this->k - $this->hLen - 1);
        $db = $maskedDB ^ $dbMask;
        $lHash2 = substr($db, 0, $this->hLen);
        $m = substr($db, $this->hLen);
        $hashesMatch = $this->_equals($lHash, $lHash2);
        $leadingZeros = 1;
        $patternMatch = 0;
        $offset = 0;
        for ($i = 0; $i < strlen($m); $i++) {
            $patternMatch|= $leadingZeros & ($m[$i] === "\1");
            $leadingZeros&= $m[$i] === "\0";
            $offset+= $patternMatch ? 0 : 1;
        }

        // we do | instead of || to avoid https://en.wikipedia.org/wiki/Short-circuit_evaluation
        // to protect against timing attacks
        if (!$hashesMatch | !$patternMatch) {
            user_error('Decryption error');
            return false;
        }

        // Output the message M

        return substr($m, $offset + 1);
    }

    /**
     * Raw Encryption / Decryption
     *
     * Doesn't use padding and is not recommended.
     *
     * @access private
     * @param string $m
     * @return string
     */
    function _raw_encrypt($m)
    {
        $temp = $this->_os2ip($m);
        $temp = $this->_rsaep($temp);
        return  $this->_i2osp($temp, $this->k);
    }

    /**
     * RSAES-PKCS1-V1_5-ENCRYPT
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-7.2.1 RFC3447#section-7.2.1}.
     *
     * @access private
     * @param string $m
     * @return string
     */
    function _rsaes_pkcs1_v1_5_encrypt($m)
    {
        $mLen = strlen($m);

        // Length checking

        if ($mLen > $this->k - 11) {
            user_error('Message too long');
            return false;
        }

        // EME-PKCS1-v1_5 encoding

        $psLen = $this->k - $mLen - 3;
        $ps = '';
        while (strlen($ps) != $psLen) {
            $temp = Random::string($psLen - strlen($ps));
            $temp = str_replace("\x00", '', $temp);
            $ps.= $temp;
        }
        $type = 2;
        // see the comments of _rsaes_pkcs1_v1_5_decrypt() to understand why this is being done
        if (defined('CRYPT_RSA_PKCS15_COMPAT') && (!isset($this->publicExponent) || $this->exponent !== $this->publicExponent)) {
            $type = 1;
            // "The padding string PS shall consist of k-3-||D|| octets. ... for block type 01, they shall have value FF"
            $ps = str_repeat("\xFF", $psLen);
        }
        $em = chr(0) . chr($type) . $ps . chr(0) . $m;

        // RSA encryption
        $m = $this->_os2ip($em);
        $c = $this->_rsaep($m);
        $c = $this->_i2osp($c, $this->k);

        // Output the ciphertext C

        return $c;
    }

    /**
     * RSAES-PKCS1-V1_5-DECRYPT
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-7.2.2 RFC3447#section-7.2.2}.
     *
     * For compatibility purposes, this function departs slightly from the description given in RFC3447.
     * The reason being that RFC2313#section-8.1 (PKCS#1 v1.5) states that ciphertext's encrypted by the
     * private key should have the second byte set to either 0 or 1 and that ciphertext's encrypted by the
     * public key should have the second byte set to 2.  In RFC3447 (PKCS#1 v2.1), the second byte is supposed
     * to be 2 regardless of which key is used.  For compatibility purposes, we'll just check to make sure the
     * second byte is 2 or less.  If it is, we'll accept the decrypted string as valid.
     *
     * As a consequence of this, a private key encrypted ciphertext produced with \phpseclib\Crypt\RSA may not decrypt
     * with a strictly PKCS#1 v1.5 compliant RSA implementation.  Public key encrypted ciphertext's should but
     * not private key encrypted ciphertext's.
     *
     * @access private
     * @param string $c
     * @return string
     */
    function _rsaes_pkcs1_v1_5_decrypt($c)
    {
        // Length checking

        if (strlen($c) != $this->k) { // or if k < 11
            user_error('Decryption error');
            return false;
        }

        // RSA decryption

        $c = $this->_os2ip($c);
        $m = $this->_rsadp($c);

        if ($m === false) {
            user_error('Decryption error');
            return false;
        }
        $em = $this->_i2osp($m, $this->k);

        // EME-PKCS1-v1_5 decoding

        if (ord($em[0]) != 0 || ord($em[1]) > 2) {
            user_error('Decryption error');
            return false;
        }

        $ps = substr($em, 2, strpos($em, chr(0), 2) - 2);
        $m = substr($em, strlen($ps) + 3);

        if (strlen($ps) < 8) {
            user_error('Decryption error');
            return false;
        }

        // Output M

        return $m;
    }

    /**
     * EMSA-PSS-ENCODE
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-9.1.1 RFC3447#section-9.1.1}.
     *
     * @access private
     * @param string $m
     * @param int $emBits
     */
    function _emsa_pss_encode($m, $emBits)
    {
        // if $m is larger than two million terrabytes and you're using sha1, PKCS#1 suggests a "Label too long" error
        // be output.

        $emLen = ($emBits + 1) >> 3; // ie. ceil($emBits / 8)
        $sLen = $this->sLen !== null ? $this->sLen : $this->hLen;

        $mHash = $this->hash->hash($m);
        if ($emLen < $this->hLen + $sLen + 2) {
            user_error('Encoding error');
            return false;
        }

        $salt = Random::string($sLen);
        $m2 = "\0\0\0\0\0\0\0\0" . $mHash . $salt;
        $h = $this->hash->hash($m2);
        $ps = str_repeat(chr(0), $emLen - $sLen - $this->hLen - 2);
        $db = $ps . chr(1) . $salt;
        $dbMask = $this->_mgf1($h, $emLen - $this->hLen - 1);
        $maskedDB = $db ^ $dbMask;
        $maskedDB[0] = ~chr(0xFF << ($emBits & 7)) & $maskedDB[0];
        $em = $maskedDB . $h . chr(0xBC);

        return $em;
    }

    /**
     * EMSA-PSS-VERIFY
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-9.1.2 RFC3447#section-9.1.2}.
     *
     * @access private
     * @param string $m
     * @param string $em
     * @param int $emBits
     * @return string
     */
    function _emsa_pss_verify($m, $em, $emBits)
    {
        // if $m is larger than two million terrabytes and you're using sha1, PKCS#1 suggests a "Label too long" error
        // be output.

        $emLen = ($emBits + 7) >> 3; // ie. ceil($emBits / 8);
        $sLen = $this->sLen !== null ? $this->sLen : $this->hLen;

        $mHash = $this->hash->hash($m);
        if ($emLen < $this->hLen + $sLen + 2) {
            return false;
        }

        if ($em[strlen($em) - 1] != chr(0xBC)) {
            return false;
        }

        $maskedDB = substr($em, 0, -$this->hLen - 1);
        $h = substr($em, -$this->hLen - 1, $this->hLen);
        $temp = chr(0xFF << ($emBits & 7));
        if ((~$maskedDB[0] & $temp) != $temp) {
            return false;
        }
        $dbMask = $this->_mgf1($h, $emLen - $this->hLen - 1);
        $db = $maskedDB ^ $dbMask;
        $db[0] = ~chr(0xFF << ($emBits & 7)) & $db[0];
        $temp = $emLen - $this->hLen - $sLen - 2;
        if (substr($db, 0, $temp) != str_repeat(chr(0), $temp) || ord($db[$temp]) != 1) {
            return false;
        }
        $salt = substr($db, $temp + 1); // should be $sLen long
        $m2 = "\0\0\0\0\0\0\0\0" . $mHash . $salt;
        $h2 = $this->hash->hash($m2);
        return $this->_equals($h, $h2);
    }

    /**
     * RSASSA-PSS-SIGN
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-8.1.1 RFC3447#section-8.1.1}.
     *
     * @access private
     * @param string $m
     * @return string
     */
    function _rsassa_pss_sign($m)
    {
        // EMSA-PSS encoding

        $em = $this->_emsa_pss_encode($m, 8 * $this->k - 1);

        // RSA signature

        $m = $this->_os2ip($em);
        $s = $this->_rsasp1($m);
        $s = $this->_i2osp($s, $this->k);

        // Output the signature S

        return $s;
    }

    /**
     * RSASSA-PSS-VERIFY
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-8.1.2 RFC3447#section-8.1.2}.
     *
     * @access private
     * @param string $m
     * @param string $s
     * @return string
     */
    function _rsassa_pss_verify($m, $s)
    {
        // Length checking

        if (strlen($s) != $this->k) {
            user_error('Invalid signature');
            return false;
        }

        // RSA verification

        $modBits = strlen($this->modulus->toBits());

        $s2 = $this->_os2ip($s);
        $m2 = $this->_rsavp1($s2);
        if ($m2 === false) {
            user_error('Invalid signature');
            return false;
        }
        $em = $this->_i2osp($m2, $this->k);
        if ($em === false) {
            user_error('Invalid signature');
            return false;
        }

        // EMSA-PSS verification

        return $this->_emsa_pss_verify($m, $em, $modBits - 1);
    }

    /**
     * EMSA-PKCS1-V1_5-ENCODE
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-9.2 RFC3447#section-9.2}.
     *
     * @access private
     * @param string $m
     * @param int $emLen
     * @return string
     */
    function _emsa_pkcs1_v1_5_encode($m, $emLen)
    {
        $h = $this->hash->hash($m);
        if ($h === false) {
            return false;
        }

        // see http://tools.ietf.org/html/rfc3447#page-43
        switch ($this->hashName) {
            case 'md2':
                $t = pack('H*', '3020300c06082a864886f70d020205000410');
                break;
            case 'md5':
                $t = pack('H*', '3020300c06082a864886f70d020505000410');
                break;
            case 'sha1':
                $t = pack('H*', '3021300906052b0e03021a05000414');
                break;
            case 'sha256':
                $t = pack('H*', '3031300d060960864801650304020105000420');
                break;
            case 'sha384':
                $t = pack('H*', '3041300d060960864801650304020205000430');
                break;
            case 'sha512':
                $t = pack('H*', '3051300d060960864801650304020305000440');
        }
        $t.= $h;
        $tLen = strlen($t);

        if ($emLen < $tLen + 11) {
            user_error('Intended encoded message length too short');
            return false;
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
     * @access private
     * @param string $m
     * @param int $emLen
     * @return string
     */
    function _emsa_pkcs1_v1_5_encode_without_null($m, $emLen)
    {
        $h = $this->hash->hash($m);
        if ($h === false) {
            return false;
        }

        switch ($this->hashName) {
            case 'sha1':
                $t = pack('H*', '301f300706052b0e03021a0414');
                break;
            case 'sha256':
                $t = pack('H*', '302f300b06096086480165030402010420');
                break;
            case 'sha384':
                $t = pack('H*', '303f300b06096086480165030402020430');
                break;
            case 'sha512':
                $t = pack('H*', '304f300b06096086480165030402030440');
                break;
            default:
                return false;
        }
        $t.= $h;
        $tLen = strlen($t);

        if ($emLen < $tLen + 11) {
            user_error('Intended encoded message length too short');
            return false;
        }

        $ps = str_repeat(chr(0xFF), $emLen - $tLen - 3);

        $em = "\0\1$ps\0$t";

        return $em;
    }

    /**
     * RSASSA-PKCS1-V1_5-SIGN
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-8.2.1 RFC3447#section-8.2.1}.
     *
     * @access private
     * @param string $m
     * @return string
     */
    function _rsassa_pkcs1_v1_5_sign($m)
    {
        // EMSA-PKCS1-v1_5 encoding

        $em = $this->_emsa_pkcs1_v1_5_encode($m, $this->k);
        if ($em === false) {
            user_error('RSA modulus too short');
            return false;
        }

        // RSA signature

        $m = $this->_os2ip($em);
        $s = $this->_rsasp1($m);
        $s = $this->_i2osp($s, $this->k);

        // Output the signature S

        return $s;
    }

    /**
     * RSASSA-PKCS1-V1_5-VERIFY
     *
     * See {@link http://tools.ietf.org/html/rfc3447#section-8.2.2 RFC3447#section-8.2.2}.
     *
     * @access private
     * @param string $m
     * @param string $s
     * @return string
     */
    function _rsassa_pkcs1_v1_5_verify($m, $s)
    {
        // Length checking

        if (strlen($s) != $this->k) {
            user_error('Invalid signature');
            return false;
        }

        // RSA verification

        $s = $this->_os2ip($s);
        $m2 = $this->_rsavp1($s);
        if ($m2 === false) {
            user_error('Invalid signature');
            return false;
        }
        $em = $this->_i2osp($m2, $this->k);
        if ($em === false) {
            user_error('Invalid signature');
            return false;
        }

        // EMSA-PKCS1-v1_5 encoding

        $em2 = $this->_emsa_pkcs1_v1_5_encode($m, $this->k);
        $em3 = $this->_emsa_pkcs1_v1_5_encode_without_null($m, $this->k);

        if ($em2 === false && $em3 === false) {
            user_error('RSA modulus too short');
            return false;
        }

        // Compare

        return ($em2 !== false && $this->_equals($em, $em2)) ||
               ($em3 !== false && $this->_equals($em, $em3));
    }

    /**
     * Set Encryption Mode
     *
     * Valid values include self::ENCRYPTION_OAEP and self::ENCRYPTION_PKCS1.
     *
     * @access public
     * @param int $mode
     */
    function setEncryptionMode($mode)
    {
        $this->encryptionMode = $mode;
    }

    /**
     * Set Signature Mode
     *
     * Valid values include self::SIGNATURE_PSS and self::SIGNATURE_PKCS1
     *
     * @access public
     * @param int $mode
     */
    function setSignatureMode($mode)
    {
        $this->signatureMode = $mode;
    }

    /**
     * Set public key comment.
     *
     * @access public
     * @param string $comment
     */
    function setComment($comment)
    {
        $this->comment = $comment;
    }

    /**
     * Get public key comment.
     *
     * @access public
     * @return string
     */
    function getComment()
    {
        return $this->comment;
    }

    /**
     * Encryption
     *
     * Both self::ENCRYPTION_OAEP and self::ENCRYPTION_PKCS1 both place limits on how long $plaintext can be.
     * If $plaintext exceeds those limits it will be broken up so that it does and the resultant ciphertext's will
     * be concatenated together.
     *
     * @see self::decrypt()
     * @access public
     * @param string $plaintext
     * @return string
     */
    function encrypt($plaintext)
    {
        switch ($this->encryptionMode) {
            case self::ENCRYPTION_NONE:
                $plaintext = str_split($plaintext, $this->k);
                $ciphertext = '';
                foreach ($plaintext as $m) {
                    $ciphertext.= $this->_raw_encrypt($m);
                }
                return $ciphertext;
            case self::ENCRYPTION_PKCS1:
                $length = $this->k - 11;
                if ($length <= 0) {
                    return false;
                }

                $plaintext = str_split($plaintext, $length);
                $ciphertext = '';
                foreach ($plaintext as $m) {
                    $ciphertext.= $this->_rsaes_pkcs1_v1_5_encrypt($m);
                }
                return $ciphertext;
            //case self::ENCRYPTION_OAEP:
            default:
                $length = $this->k - 2 * $this->hLen - 2;
                if ($length <= 0) {
                    return false;
                }

                $plaintext = str_split($plaintext, $length);
                $ciphertext = '';
                foreach ($plaintext as $m) {
                    $ciphertext.= $this->_rsaes_oaep_encrypt($m);
                }
                return $ciphertext;
        }
    }

    /**
     * Decryption
     *
     * @see self::encrypt()
     * @access public
     * @param string $ciphertext
     * @return string
     */
    function decrypt($ciphertext)
    {
        if ($this->k <= 0) {
            return false;
        }

        $ciphertext = str_split($ciphertext, $this->k);
        $ciphertext[count($ciphertext) - 1] = str_pad($ciphertext[count($ciphertext) - 1], $this->k, chr(0), STR_PAD_LEFT);

        $plaintext = '';

        switch ($this->encryptionMode) {
            case self::ENCRYPTION_NONE:
                $decrypt = '_raw_encrypt';
                break;
            case self::ENCRYPTION_PKCS1:
                $decrypt = '_rsaes_pkcs1_v1_5_decrypt';
                break;
            //case self::ENCRYPTION_OAEP:
            default:
                $decrypt = '_rsaes_oaep_decrypt';
        }

        foreach ($ciphertext as $c) {
            $temp = $this->$decrypt($c);
            if ($temp === false) {
                return false;
            }
            $plaintext.= $temp;
        }

        return $plaintext;
    }

    /**
     * Create a signature
     *
     * @see self::verify()
     * @access public
     * @param string $message
     * @return string
     */
    function sign($message)
    {
        if (empty($this->modulus) || empty($this->exponent)) {
            return false;
        }

        switch ($this->signatureMode) {
            case self::SIGNATURE_PKCS1:
                return $this->_rsassa_pkcs1_v1_5_sign($message);
            //case self::SIGNATURE_PSS:
            default:
                return $this->_rsassa_pss_sign($message);
        }
    }

    /**
     * Verifies a signature
     *
     * @see self::sign()
     * @access public
     * @param string $message
     * @param string $signature
     * @return bool
     */
    function verify($message, $signature)
    {
        if (empty($this->modulus) || empty($this->exponent)) {
            return false;
        }

        switch ($this->signatureMode) {
            case self::SIGNATURE_PKCS1:
                return $this->_rsassa_pkcs1_v1_5_verify($message, $signature);
            //case self::SIGNATURE_PSS:
            default:
                return $this->_rsassa_pss_verify($message, $signature);
        }
    }

    /**
     * Extract raw BER from Base64 encoding
     *
     * @access private
     * @param string $str
     * @return string
     */
    function _extractBER($str)
    {
        /* X.509 certs are assumed to be base64 encoded but sometimes they'll have additional things in them
         * above and beyond the ceritificate.
         * ie. some may have the following preceding the -----BEGIN CERTIFICATE----- line:
         *
         * Bag Attributes
         *     localKeyID: 01 00 00 00
         * subject=/O=organization/OU=org unit/CN=common name
         * issuer=/O=organization/CN=common name
         */
        $temp = preg_replace('#.*?^-+[^-]+-+[\r\n ]*$#ms', '', $str, 1);
        // remove the -----BEGIN CERTIFICATE----- and -----END CERTIFICATE----- stuff
        $temp = preg_replace('#-+[^-]+-+#', '', $temp);
        // remove new lines
        $temp = str_replace(array("\r", "\n", ' '), '', $temp);
        $temp = preg_match('#^[a-zA-Z\d/+]*={0,2}$#', $temp) ? base64_decode($temp) : false;
        return $temp != false ? $temp : $str;
    }
}
