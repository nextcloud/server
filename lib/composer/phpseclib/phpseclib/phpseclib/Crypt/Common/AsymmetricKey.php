<?php

/**
 * Base Class for all asymmetric key ciphers
 *
 * PHP version 5
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2016 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt\Common;

use phpseclib3\Crypt\DSA;
use phpseclib3\Crypt\Hash;
use phpseclib3\Crypt\RSA;
use phpseclib3\Exception\NoKeyLoadedException;
use phpseclib3\Exception\UnsupportedFormatException;
use phpseclib3\Math\BigInteger;

/**
 * Base Class for all asymmetric cipher classes
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
abstract class AsymmetricKey
{
    /**
     * Precomputed Zero
     *
     * @var \phpseclib3\Math\BigInteger
     */
    protected static $zero;

    /**
     * Precomputed One
     *
     * @var \phpseclib3\Math\BigInteger
     */
    protected static $one;

    /**
     * Format of the loaded key
     *
     * @var string
     */
    protected $format;

    /**
     * Hash function
     *
     * @var \phpseclib3\Crypt\Hash
     */
    protected $hash;

    /**
     * HMAC function
     *
     * @var \phpseclib3\Crypt\Hash
     */
    private $hmac;

    /**
     * Supported plugins (lower case)
     *
     * @see self::initialize_static_variables()
     * @var array
     */
    private static $plugins = [];

    /**
     * Invisible plugins
     *
     * @see self::initialize_static_variables()
     * @var array
     */
    private static $invisiblePlugins = [];

    /**
     * Available Engines
     *
     * @var boolean[]
     */
    protected static $engines = [];

    /**
     * Key Comment
     *
     * @var null|string
     */
    private $comment;

    /**
     * @param string $type
     * @return array|string
     */
    abstract public function toString($type, array $options = []);

    /**
     * The constructor
     */
    protected function __construct()
    {
        self::initialize_static_variables();

        $this->hash = new Hash('sha256');
        $this->hmac = new Hash('sha256');
    }

    /**
     * Initialize static variables
     */
    protected static function initialize_static_variables()
    {
        if (!isset(self::$zero)) {
            self::$zero = new BigInteger(0);
            self::$one = new BigInteger(1);
        }

        self::loadPlugins('Keys');
        if (static::ALGORITHM != 'RSA' && static::ALGORITHM != 'DH') {
            self::loadPlugins('Signature');
        }
    }

    /**
     * Load the key
     *
     * @param string $key
     * @param string $password optional
     * @return \phpseclib3\Crypt\Common\PublicKey|\phpseclib3\Crypt\Common\PrivateKey
     */
    public static function load($key, $password = false)
    {
        self::initialize_static_variables();

        $class = new \ReflectionClass(static::class);
        if ($class->isFinal()) {
            throw new \RuntimeException('load() should not be called from final classes (' . static::class . ')');
        }

        $components = false;
        foreach (self::$plugins[static::ALGORITHM]['Keys'] as $format) {
            if (isset(self::$invisiblePlugins[static::ALGORITHM]) && in_array($format, self::$invisiblePlugins[static::ALGORITHM])) {
                continue;
            }
            try {
                $components = $format::load($key, $password);
            } catch (\Exception $e) {
                $components = false;
            }
            if ($components !== false) {
                break;
            }
        }

        if ($components === false) {
            throw new NoKeyLoadedException('Unable to read key');
        }

        $components['format'] = $format;
        $components['secret'] = isset($components['secret']) ? $components['secret'] : '';
        $comment = isset($components['comment']) ? $components['comment'] : null;
        $new = static::onLoad($components);
        $new->format = $format;
        $new->comment = $comment;
        return $new instanceof PrivateKey ?
            $new->withPassword($password) :
            $new;
    }

    /**
     * Loads a private key
     *
     * @return PrivateKey
     * @param string|array $key
     * @param string $password optional
     */
    public static function loadPrivateKey($key, $password = '')
    {
        $key = self::load($key, $password);
        if (!$key instanceof PrivateKey) {
            throw new NoKeyLoadedException('The key that was loaded was not a private key');
        }
        return $key;
    }

    /**
     * Loads a public key
     *
     * @return PublicKey
     * @param string|array $key
     */
    public static function loadPublicKey($key)
    {
        $key = self::load($key);
        if (!$key instanceof PublicKey) {
            throw new NoKeyLoadedException('The key that was loaded was not a public key');
        }
        return $key;
    }

    /**
     * Loads parameters
     *
     * @return AsymmetricKey
     * @param string|array $key
     */
    public static function loadParameters($key)
    {
        $key = self::load($key);
        if (!$key instanceof PrivateKey && !$key instanceof PublicKey) {
            throw new NoKeyLoadedException('The key that was loaded was not a parameter');
        }
        return $key;
    }

    /**
     * Load the key, assuming a specific format
     *
     * @param string $type
     * @param string $key
     * @param string $password optional
     * @return static
     */
    public static function loadFormat($type, $key, $password = false)
    {
        self::initialize_static_variables();

        $components = false;
        $format = strtolower($type);
        if (isset(self::$plugins[static::ALGORITHM]['Keys'][$format])) {
            $format = self::$plugins[static::ALGORITHM]['Keys'][$format];
            $components = $format::load($key, $password);
        }

        if ($components === false) {
            throw new NoKeyLoadedException('Unable to read key');
        }

        $components['format'] = $format;
        $components['secret'] = isset($components['secret']) ? $components['secret'] : '';

        $new = static::onLoad($components);
        $new->format = $format;
        return $new instanceof PrivateKey ?
            $new->withPassword($password) :
            $new;
    }

    /**
     * Loads a private key
     *
     * @return PrivateKey
     * @param string $type
     * @param string $key
     * @param string $password optional
     */
    public static function loadPrivateKeyFormat($type, $key, $password = false)
    {
        $key = self::loadFormat($type, $key, $password);
        if (!$key instanceof PrivateKey) {
            throw new NoKeyLoadedException('The key that was loaded was not a private key');
        }
        return $key;
    }

    /**
     * Loads a public key
     *
     * @return PublicKey
     * @param string $type
     * @param string $key
     */
    public static function loadPublicKeyFormat($type, $key)
    {
        $key = self::loadFormat($type, $key);
        if (!$key instanceof PublicKey) {
            throw new NoKeyLoadedException('The key that was loaded was not a public key');
        }
        return $key;
    }

    /**
     * Loads parameters
     *
     * @return AsymmetricKey
     * @param string $type
     * @param string|array $key
     */
    public static function loadParametersFormat($type, $key)
    {
        $key = self::loadFormat($type, $key);
        if (!$key instanceof PrivateKey && !$key instanceof PublicKey) {
            throw new NoKeyLoadedException('The key that was loaded was not a parameter');
        }
        return $key;
    }

    /**
     * Validate Plugin
     *
     * @param string $format
     * @param string $type
     * @param string $method optional
     * @return mixed
     */
    protected static function validatePlugin($format, $type, $method = null)
    {
        $type = strtolower($type);
        if (!isset(self::$plugins[static::ALGORITHM][$format][$type])) {
            throw new UnsupportedFormatException("$type is not a supported format");
        }
        $type = self::$plugins[static::ALGORITHM][$format][$type];
        if (isset($method) && !method_exists($type, $method)) {
            throw new UnsupportedFormatException("$type does not implement $method");
        }

        return $type;
    }

    /**
     * Load Plugins
     *
     * @param string $format
     */
    private static function loadPlugins($format)
    {
        if (!isset(self::$plugins[static::ALGORITHM][$format])) {
            self::$plugins[static::ALGORITHM][$format] = [];
            foreach (new \DirectoryIterator(__DIR__ . '/../' . static::ALGORITHM . '/Formats/' . $format . '/') as $file) {
                if ($file->getExtension() != 'php') {
                    continue;
                }
                $name = $file->getBasename('.php');
                if ($name[0] == '.') {
                    continue;
                }
                $type = 'phpseclib3\Crypt\\' . static::ALGORITHM . '\\Formats\\' . $format . '\\' . $name;
                $reflect = new \ReflectionClass($type);
                if ($reflect->isTrait()) {
                    continue;
                }
                self::$plugins[static::ALGORITHM][$format][strtolower($name)] = $type;
                if ($reflect->hasConstant('IS_INVISIBLE')) {
                    self::$invisiblePlugins[static::ALGORITHM][] = $type;
                }
            }
        }
    }

    /**
     * Returns a list of supported formats.
     *
     * @return array
     */
    public static function getSupportedKeyFormats()
    {
        self::initialize_static_variables();

        return self::$plugins[static::ALGORITHM]['Keys'];
    }

    /**
     * Add a fileformat plugin
     *
     * The plugin needs to either already be loaded or be auto-loadable.
     * Loading a plugin whose shortname overwrite an existing shortname will overwrite the old plugin.
     *
     * @see self::load()
     * @param string $fullname
     * @return bool
     */
    public static function addFileFormat($fullname)
    {
        self::initialize_static_variables();

        if (class_exists($fullname)) {
            $meta = new \ReflectionClass($fullname);
            $shortname = $meta->getShortName();
            self::$plugins[static::ALGORITHM]['Keys'][strtolower($shortname)] = $fullname;
            if ($meta->hasConstant('IS_INVISIBLE')) {
                self::$invisiblePlugins[static::ALGORITHM][] = strtolower($shortname);
            }
        }
    }

    /**
     * Returns the format of the loaded key.
     *
     * If the key that was loaded wasn't in a valid or if the key was auto-generated
     * with RSA::createKey() then this will throw an exception.
     *
     * @see self::load()
     * @return mixed
     */
    public function getLoadedFormat()
    {
        if (empty($this->format)) {
            throw new NoKeyLoadedException('This key was created with createKey - it was not loaded with load. Therefore there is no "loaded format"');
        }

        $meta = new \ReflectionClass($this->format);
        return $meta->getShortName();
    }

    /**
     * Returns the key's comment
     *
     * Not all key formats support comments. If you want to set a comment use toString()
     *
     * @return null|string
     */
    public function getComment()
    {
        return $this->comment;
    }

    /**
     * Tests engine validity
     *
     */
    public static function useBestEngine()
    {
        static::$engines = [
            'PHP' => true,
            'OpenSSL' => extension_loaded('openssl'),
            // this test can be satisfied by either of the following:
            // http://php.net/manual/en/book.sodium.php
            // https://github.com/paragonie/sodium_compat
            'libsodium' => function_exists('sodium_crypto_sign_keypair')
        ];

        return static::$engines;
    }

    /**
     * Flag to use internal engine only (useful for unit testing)
     *
     */
    public static function useInternalEngine()
    {
        static::$engines = [
            'PHP' => true,
            'OpenSSL' => false,
            'libsodium' => false
        ];
    }

    /**
     * __toString() magic method
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString('PKCS8');
    }

    /**
     * Determines which hashing function should be used
     *
     * @param string $hash
     */
    public function withHash($hash)
    {
        $new = clone $this;

        $new->hash = new Hash($hash);
        $new->hmac = new Hash($hash);

        return $new;
    }

    /**
     * Returns the hash algorithm currently being used
     *
     */
    public function getHash()
    {
        return clone $this->hash;
    }

    /**
     * Compute the pseudorandom k for signature generation,
     * using the process specified for deterministic DSA.
     *
     * @param string $h1
     * @return string
     */
    protected function computek($h1)
    {
        $v = str_repeat("\1", strlen($h1));

        $k = str_repeat("\0", strlen($h1));

        $x = $this->int2octets($this->x);
        $h1 = $this->bits2octets($h1);

        $this->hmac->setKey($k);
        $k = $this->hmac->hash($v . "\0" . $x . $h1);
        $this->hmac->setKey($k);
        $v = $this->hmac->hash($v);
        $k = $this->hmac->hash($v . "\1" . $x . $h1);
        $this->hmac->setKey($k);
        $v = $this->hmac->hash($v);

        $qlen = $this->q->getLengthInBytes();

        while (true) {
            $t = '';
            while (strlen($t) < $qlen) {
                $v = $this->hmac->hash($v);
                $t = $t . $v;
            }
            $k = $this->bits2int($t);

            if (!$k->equals(self::$zero) && $k->compare($this->q) < 0) {
                break;
            }
            $k = $this->hmac->hash($v . "\0");
            $this->hmac->setKey($k);
            $v = $this->hmac->hash($v);
        }

        return $k;
    }

    /**
     * Integer to Octet String
     *
     * @param \phpseclib3\Math\BigInteger $v
     * @return string
     */
    private function int2octets($v)
    {
        $out = $v->toBytes();
        $rolen = $this->q->getLengthInBytes();
        if (strlen($out) < $rolen) {
            return str_pad($out, $rolen, "\0", STR_PAD_LEFT);
        } elseif (strlen($out) > $rolen) {
            return substr($out, -$rolen);
        } else {
            return $out;
        }
    }

    /**
     * Bit String to Integer
     *
     * @param string $in
     * @return \phpseclib3\Math\BigInteger
     */
    protected function bits2int($in)
    {
        $v = new BigInteger($in, 256);
        $vlen = strlen($in) << 3;
        $qlen = $this->q->getLength();
        if ($vlen > $qlen) {
            return $v->bitwise_rightShift($vlen - $qlen);
        }
        return $v;
    }

    /**
     * Bit String to Octet String
     *
     * @param string $in
     * @return string
     */
    private function bits2octets($in)
    {
        $z1 = $this->bits2int($in);
        $z2 = $z1->subtract($this->q);
        return $z2->compare(self::$zero) < 0 ?
            $this->int2octets($z1) :
            $this->int2octets($z2);
    }
}
