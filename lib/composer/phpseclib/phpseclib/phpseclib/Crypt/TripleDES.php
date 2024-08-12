<?php

/**
 * Pure-PHP implementation of Triple DES.
 *
 * Uses mcrypt, if available, and an internal implementation, otherwise.  Operates in the EDE3 mode (encrypt-decrypt-encrypt).
 *
 * PHP version 5
 *
 * Here's a short example of how to use this library:
 * <code>
 * <?php
 *    include 'vendor/autoload.php';
 *
 *    $des = new \phpseclib3\Crypt\TripleDES('ctr');
 *
 *    $des->setKey('abcdefghijklmnopqrstuvwx');
 *
 *    $size = 10 * 1024;
 *    $plaintext = '';
 *    for ($i = 0; $i < $size; $i++) {
 *        $plaintext.= 'a';
 *    }
 *
 *    echo $des->decrypt($des->encrypt($plaintext));
 * ?>
 * </code>
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2007 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt;

/**
 * Pure-PHP implementation of Triple DES.
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
class TripleDES extends DES
{
    /**
     * Encrypt / decrypt using inner chaining
     *
     * Inner chaining is used by SSH-1 and is generally considered to be less secure then outer chaining (self::MODE_CBC3).
     */
    const MODE_3CBC = -2;

    /**
     * Encrypt / decrypt using outer chaining
     *
     * Outer chaining is used by SSH-2 and when the mode is set to \phpseclib3\Crypt\Common\BlockCipher::MODE_CBC.
     */
    const MODE_CBC3 = self::MODE_CBC;

    /**
     * Key Length (in bytes)
     *
     * @see \phpseclib3\Crypt\TripleDES::setKeyLength()
     * @var int
     */
    protected $key_length = 24;

    /**
     * The mcrypt specific name of the cipher
     *
     * @see \phpseclib3\Crypt\DES::cipher_name_mcrypt
     * @see \phpseclib3\Crypt\Common\SymmetricKey::cipher_name_mcrypt
     * @var string
     */
    protected $cipher_name_mcrypt = 'tripledes';

    /**
     * Optimizing value while CFB-encrypting
     *
     * @see \phpseclib3\Crypt\Common\SymmetricKey::cfb_init_len
     * @var int
     */
    protected $cfb_init_len = 750;

    /**
     * max possible size of $key
     *
     * @see self::setKey()
     * @see \phpseclib3\Crypt\DES::setKey()
     * @var string
     */
    protected $key_length_max = 24;

    /**
     * Internal flag whether using self::MODE_3CBC or not
     *
     * @var bool
     */
    private $mode_3cbc;

    /**
     * The \phpseclib3\Crypt\DES objects
     *
     * Used only if $mode_3cbc === true
     *
     * @var array
     */
    private $des;

    /**
     * Default Constructor.
     *
     * Determines whether or not the mcrypt or OpenSSL extensions should be used.
     *
     * $mode could be:
     *
     * - ecb
     *
     * - cbc
     *
     * - ctr
     *
     * - cfb
     *
     * - ofb
     *
     * - 3cbc
     *
     * - cbc3 (same as cbc)
     *
     * @see \phpseclib3\Crypt\DES::__construct()
     * @see \phpseclib3\Crypt\Common\SymmetricKey::__construct()
     * @param string $mode
     */
    public function __construct($mode)
    {
        switch (strtolower($mode)) {
            // In case of self::MODE_3CBC, we init as CRYPT_DES_MODE_CBC
            // and additional flag us internally as 3CBC
            case '3cbc':
                parent::__construct('cbc');
                $this->mode_3cbc = true;

                // This three $des'es will do the 3CBC work (if $key > 64bits)
                $this->des = [
                    new DES('cbc'),
                    new DES('cbc'),
                    new DES('cbc'),
                ];

                // we're going to be doing the padding, ourselves, so disable it in the \phpseclib3\Crypt\DES objects
                $this->des[0]->disablePadding();
                $this->des[1]->disablePadding();
                $this->des[2]->disablePadding();
                break;
            case 'cbc3':
                $mode = 'cbc';
                // fall-through
            // If not 3CBC, we init as usual
            default:
                parent::__construct($mode);

                if ($this->mode == self::MODE_STREAM) {
                    throw new BadModeException('Block ciphers cannot be ran in stream mode');
                }
        }
    }

    /**
     * Test for engine validity
     *
     * This is mainly just a wrapper to set things up for \phpseclib3\Crypt\Common\SymmetricKey::isValidEngine()
     *
     * @see \phpseclib3\Crypt\Common\SymmetricKey::__construct()
     * @param int $engine
     * @return bool
     */
    protected function isValidEngineHelper($engine)
    {
        if ($engine == self::ENGINE_OPENSSL) {
            $this->cipher_name_openssl_ecb = 'des-ede3';
            $mode = $this->openssl_translate_mode();
            $this->cipher_name_openssl = $mode == 'ecb' ? 'des-ede3' : 'des-ede3-' . $mode;
        }

        return parent::isValidEngineHelper($engine);
    }

    /**
     * Sets the initialization vector.
     *
     * SetIV is not required when \phpseclib3\Crypt\Common\SymmetricKey::MODE_ECB is being used.
     *
     * @see \phpseclib3\Crypt\Common\SymmetricKey::setIV()
     * @param string $iv
     */
    public function setIV($iv)
    {
        parent::setIV($iv);
        if ($this->mode_3cbc) {
            $this->des[0]->setIV($iv);
            $this->des[1]->setIV($iv);
            $this->des[2]->setIV($iv);
        }
    }

    /**
     * Sets the key length.
     *
     * Valid key lengths are 128 and 192 bits.
     *
     * If you want to use a 64-bit key use DES.php
     *
     * @see \phpseclib3\Crypt\Common\SymmetricKey:setKeyLength()
     * @throws \LengthException if the key length is invalid
     * @param int $length
     */
    public function setKeyLength($length)
    {
        switch ($length) {
            case 128:
            case 192:
                break;
            default:
                throw new \LengthException('Key size of ' . $length . ' bits is not supported by this algorithm. Only keys of sizes 128 or 192 bits are supported');
        }

        parent::setKeyLength($length);
    }

    /**
     * Sets the key.
     *
     * Triple DES can use 128-bit (eg. strlen($key) == 16) or 192-bit (eg. strlen($key) == 24) keys.
     *
     * DES also requires that every eighth bit be a parity bit, however, we'll ignore that.
     *
     * @see \phpseclib3\Crypt\DES::setKey()
     * @see \phpseclib3\Crypt\Common\SymmetricKey::setKey()
     * @throws \LengthException if the key length is invalid
     * @param string $key
     */
    public function setKey($key)
    {
        if ($this->explicit_key_length !== false && strlen($key) != $this->explicit_key_length) {
            throw new \LengthException('Key length has already been set to ' . $this->explicit_key_length . ' bytes and this key is ' . strlen($key) . ' bytes');
        }

        switch (strlen($key)) {
            case 16:
                $key .= substr($key, 0, 8);
                break;
            case 24:
                break;
            default:
                throw new \LengthException('Key of size ' . strlen($key) . ' not supported by this algorithm. Only keys of sizes 16 or 24 are supported');
        }

        // copied from self::setKey()
        $this->key = $key;
        $this->key_length = strlen($key);
        $this->changed = $this->nonIVChanged = true;
        $this->setEngine();

        if ($this->mode_3cbc) {
            $this->des[0]->setKey(substr($key, 0, 8));
            $this->des[1]->setKey(substr($key, 8, 8));
            $this->des[2]->setKey(substr($key, 16, 8));
        }
    }

    /**
     * Encrypts a message.
     *
     * @see \phpseclib3\Crypt\Common\SymmetricKey::encrypt()
     * @param string $plaintext
     * @return string $cipertext
     */
    public function encrypt($plaintext)
    {
        // parent::en/decrypt() is able to do all the work for all modes and keylengths,
        // except for: self::MODE_3CBC (inner chaining CBC) with a key > 64bits

        // if the key is smaller then 8, do what we'd normally do
        if ($this->mode_3cbc && strlen($this->key) > 8) {
            return $this->des[2]->encrypt(
                $this->des[1]->decrypt(
                    $this->des[0]->encrypt(
                        $this->pad($plaintext)
                    )
                )
            );
        }

        return parent::encrypt($plaintext);
    }

    /**
     * Decrypts a message.
     *
     * @see \phpseclib3\Crypt\Common\SymmetricKey::decrypt()
     * @param string $ciphertext
     * @return string $plaintext
     */
    public function decrypt($ciphertext)
    {
        if ($this->mode_3cbc && strlen($this->key) > 8) {
            return $this->unpad(
                $this->des[0]->decrypt(
                    $this->des[1]->encrypt(
                        $this->des[2]->decrypt(
                            str_pad($ciphertext, (strlen($ciphertext) + 7) & 0xFFFFFFF8, "\0")
                        )
                    )
                )
            );
        }

        return parent::decrypt($ciphertext);
    }

    /**
     * Treat consecutive "packets" as if they are a continuous buffer.
     *
     * Say you have a 16-byte plaintext $plaintext.  Using the default behavior, the two following code snippets
     * will yield different outputs:
     *
     * <code>
     *    echo $des->encrypt(substr($plaintext, 0, 8));
     *    echo $des->encrypt(substr($plaintext, 8, 8));
     * </code>
     * <code>
     *    echo $des->encrypt($plaintext);
     * </code>
     *
     * The solution is to enable the continuous buffer.  Although this will resolve the above discrepancy, it creates
     * another, as demonstrated with the following:
     *
     * <code>
     *    $des->encrypt(substr($plaintext, 0, 8));
     *    echo $des->decrypt($des->encrypt(substr($plaintext, 8, 8)));
     * </code>
     * <code>
     *    echo $des->decrypt($des->encrypt(substr($plaintext, 8, 8)));
     * </code>
     *
     * With the continuous buffer disabled, these would yield the same output.  With it enabled, they yield different
     * outputs.  The reason is due to the fact that the initialization vector's change after every encryption /
     * decryption round when the continuous buffer is enabled.  When it's disabled, they remain constant.
     *
     * Put another way, when the continuous buffer is enabled, the state of the \phpseclib3\Crypt\DES() object changes after each
     * encryption / decryption round, whereas otherwise, it'd remain constant.  For this reason, it's recommended that
     * continuous buffers not be used.  They do offer better security and are, in fact, sometimes required (SSH uses them),
     * however, they are also less intuitive and more likely to cause you problems.
     *
     * @see \phpseclib3\Crypt\Common\SymmetricKey::enableContinuousBuffer()
     * @see self::disableContinuousBuffer()
     */
    public function enableContinuousBuffer()
    {
        parent::enableContinuousBuffer();
        if ($this->mode_3cbc) {
            $this->des[0]->enableContinuousBuffer();
            $this->des[1]->enableContinuousBuffer();
            $this->des[2]->enableContinuousBuffer();
        }
    }

    /**
     * Treat consecutive packets as if they are a discontinuous buffer.
     *
     * The default behavior.
     *
     * @see \phpseclib3\Crypt\Common\SymmetricKey::disableContinuousBuffer()
     * @see self::enableContinuousBuffer()
     */
    public function disableContinuousBuffer()
    {
        parent::disableContinuousBuffer();
        if ($this->mode_3cbc) {
            $this->des[0]->disableContinuousBuffer();
            $this->des[1]->disableContinuousBuffer();
            $this->des[2]->disableContinuousBuffer();
        }
    }

    /**
     * Creates the key schedule
     *
     * @see \phpseclib3\Crypt\DES::setupKey()
     * @see \phpseclib3\Crypt\Common\SymmetricKey::setupKey()
     */
    protected function setupKey()
    {
        switch (true) {
            // if $key <= 64bits we configure our internal pure-php cipher engine
            // to act as regular [1]DES, not as 3DES. mcrypt.so::tripledes does the same.
            case strlen($this->key) <= 8:
                $this->des_rounds = 1;
                break;

            // otherwise, if $key > 64bits, we configure our engine to work as 3DES.
            default:
                $this->des_rounds = 3;

                // (only) if 3CBC is used we have, of course, to setup the $des[0-2] keys also separately.
                if ($this->mode_3cbc) {
                    $this->des[0]->setupKey();
                    $this->des[1]->setupKey();
                    $this->des[2]->setupKey();

                    // because $des[0-2] will, now, do all the work we can return here
                    // not need unnecessary stress parent::setupKey() with our, now unused, $key.
                    return;
                }
        }
        // setup our key
        parent::setupKey();
    }

    /**
     * Sets the internal crypt engine
     *
     * @see \phpseclib3\Crypt\Common\SymmetricKey::__construct()
     * @see \phpseclib3\Crypt\Common\SymmetricKey::setPreferredEngine()
     * @param int $engine
     */
    public function setPreferredEngine($engine)
    {
        if ($this->mode_3cbc) {
            $this->des[0]->setPreferredEngine($engine);
            $this->des[1]->setPreferredEngine($engine);
            $this->des[2]->setPreferredEngine($engine);
        }

        parent::setPreferredEngine($engine);
    }
}
