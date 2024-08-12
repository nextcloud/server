<?php

/**
 * Pure-PHP implementation of Salsa20.
 *
 * PHP version 5
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2019 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt;

use phpseclib3\Common\Functions\Strings;
use phpseclib3\Crypt\Common\StreamCipher;
use phpseclib3\Exception\BadDecryptionException;
use phpseclib3\Exception\InsufficientSetupException;

/**
 * Pure-PHP implementation of Salsa20.
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
class Salsa20 extends StreamCipher
{
    /**
     * Part 1 of the state
     *
     * @var string|false
     */
    protected $p1 = false;

    /**
     * Part 2 of the state
     *
     * @var string|false
     */
    protected $p2 = false;

    /**
     * Key Length (in bytes)
     *
     * @var int
     */
    protected $key_length = 32; // = 256 bits

    /**
     * @see \phpseclib3\Crypt\Salsa20::crypt()
     */
    const ENCRYPT = 0;

    /**
     * @see \phpseclib3\Crypt\Salsa20::crypt()
     */
    const DECRYPT = 1;

    /**
     * Encryption buffer for continuous mode
     *
     * @var array
     */
    protected $enbuffer;

    /**
     * Decryption buffer for continuous mode
     *
     * @var array
     */
    protected $debuffer;

    /**
     * Counter
     *
     * @var int
     */
    protected $counter = 0;

    /**
     * Using Generated Poly1305 Key
     *
     * @var boolean
     */
    protected $usingGeneratedPoly1305Key = false;

    /**
     * Salsa20 uses a nonce
     *
     * @return bool
     */
    public function usesNonce()
    {
        return true;
    }

    /**
     * Sets the key.
     *
     * @param string $key
     * @throws \LengthException if the key length isn't supported
     */
    public function setKey($key)
    {
        switch (strlen($key)) {
            case 16:
            case 32:
                break;
            default:
                throw new \LengthException('Key of size ' . strlen($key) . ' not supported by this algorithm. Only keys of sizes 16 or 32 are supported');
        }

        parent::setKey($key);
    }

    /**
     * Sets the nonce.
     *
     * @param string $nonce
     */
    public function setNonce($nonce)
    {
        if (strlen($nonce) != 8) {
            throw new \LengthException('Nonce of size ' . strlen($key) . ' not supported by this algorithm. Only an 64-bit nonce is supported');
        }

        $this->nonce = $nonce;
        $this->changed = true;
        $this->setEngine();
    }

    /**
     * Sets the counter.
     *
     * @param int $counter
     */
    public function setCounter($counter)
    {
        $this->counter = $counter;
        $this->setEngine();
    }

    /**
     * Creates a Poly1305 key using the method discussed in RFC8439
     *
     * See https://tools.ietf.org/html/rfc8439#section-2.6.1
     */
    protected function createPoly1305Key()
    {
        if ($this->nonce === false) {
            throw new InsufficientSetupException('No nonce has been defined');
        }

        if ($this->key === false) {
            throw new InsufficientSetupException('No key has been defined');
        }

        $c = clone $this;
        $c->setCounter(0);
        $c->usePoly1305 = false;
        $block = $c->encrypt(str_repeat("\0", 256));
        $this->setPoly1305Key(substr($block, 0, 32));

        if ($this->counter == 0) {
            $this->counter++;
        }
    }

    /**
     * Setup the self::ENGINE_INTERNAL $engine
     *
     * (re)init, if necessary, the internal cipher $engine
     *
     * _setup() will be called each time if $changed === true
     * typically this happens when using one or more of following public methods:
     *
     * - setKey()
     *
     * - setNonce()
     *
     * - First run of encrypt() / decrypt() with no init-settings
     *
     * @see self::setKey()
     * @see self::setNonce()
     * @see self::disableContinuousBuffer()
     */
    protected function setup()
    {
        if (!$this->changed) {
            return;
        }

        $this->enbuffer = $this->debuffer = ['ciphertext' => '', 'counter' => $this->counter];

        $this->changed = $this->nonIVChanged = false;

        if ($this->nonce === false) {
            throw new InsufficientSetupException('No nonce has been defined');
        }

        if ($this->key === false) {
            throw new InsufficientSetupException('No key has been defined');
        }

        if ($this->usePoly1305 && !isset($this->poly1305Key)) {
            $this->usingGeneratedPoly1305Key = true;
            $this->createPoly1305Key();
        }

        $key = $this->key;
        if (strlen($key) == 16) {
            $constant = 'expand 16-byte k';
            $key .= $key;
        } else {
            $constant = 'expand 32-byte k';
        }

        $this->p1 = substr($constant, 0, 4) .
                    substr($key, 0, 16) .
                    substr($constant, 4, 4) .
                    $this->nonce .
                    "\0\0\0\0";
        $this->p2 = substr($constant, 8, 4) .
                    substr($key, 16, 16) .
                    substr($constant, 12, 4);
    }

    /**
     * Setup the key (expansion)
     */
    protected function setupKey()
    {
        // Salsa20 does not utilize this method
    }

    /**
     * Encrypts a message.
     *
     * @see \phpseclib3\Crypt\Common\SymmetricKey::decrypt()
     * @see self::crypt()
     * @param string $plaintext
     * @return string $ciphertext
     */
    public function encrypt($plaintext)
    {
        $ciphertext = $this->crypt($plaintext, self::ENCRYPT);
        if (isset($this->poly1305Key)) {
            $this->newtag = $this->poly1305($ciphertext);
        }
        return $ciphertext;
    }

    /**
     * Decrypts a message.
     *
     * $this->decrypt($this->encrypt($plaintext)) == $this->encrypt($this->encrypt($plaintext)).
     * At least if the continuous buffer is disabled.
     *
     * @see \phpseclib3\Crypt\Common\SymmetricKey::encrypt()
     * @see self::crypt()
     * @param string $ciphertext
     * @return string $plaintext
     */
    public function decrypt($ciphertext)
    {
        if (isset($this->poly1305Key)) {
            if ($this->oldtag === false) {
                throw new InsufficientSetupException('Authentication Tag has not been set');
            }
            $newtag = $this->poly1305($ciphertext);
            if ($this->oldtag != substr($newtag, 0, strlen($this->oldtag))) {
                $this->oldtag = false;
                throw new BadDecryptionException('Derived authentication tag and supplied authentication tag do not match');
            }
            $this->oldtag = false;
        }

        return $this->crypt($ciphertext, self::DECRYPT);
    }

    /**
     * Encrypts a block
     *
     * @param string $in
     */
    protected function encryptBlock($in)
    {
        // Salsa20 does not utilize this method
    }

    /**
     * Decrypts a block
     *
     * @param string $in
     */
    protected function decryptBlock($in)
    {
        // Salsa20 does not utilize this method
    }

    /**
     * Encrypts or decrypts a message.
     *
     * @see self::encrypt()
     * @see self::decrypt()
     * @param string $text
     * @param int $mode
     * @return string $text
     */
    private function crypt($text, $mode)
    {
        $this->setup();
        if (!$this->continuousBuffer) {
            if ($this->engine == self::ENGINE_OPENSSL) {
                $iv = pack('V', $this->counter) . $this->p2;
                return openssl_encrypt(
                    $text,
                    $this->cipher_name_openssl,
                    $this->key,
                    OPENSSL_RAW_DATA,
                    $iv
                );
            }
            $i = $this->counter;
            $blocks = str_split($text, 64);
            foreach ($blocks as &$block) {
                $block ^= static::salsa20($this->p1 . pack('V', $i++) . $this->p2);
            }

            return implode('', $blocks);
        }

        if ($mode == self::ENCRYPT) {
            $buffer = &$this->enbuffer;
        } else {
            $buffer = &$this->debuffer;
        }
        if (!strlen($buffer['ciphertext'])) {
            $ciphertext = '';
        } else {
            $ciphertext = $text ^ Strings::shift($buffer['ciphertext'], strlen($text));
            $text = substr($text, strlen($ciphertext));
            if (!strlen($text)) {
                return $ciphertext;
            }
        }

        $overflow = strlen($text) % 64; // & 0x3F
        if ($overflow) {
            $text2 = Strings::pop($text, $overflow);
            if ($this->engine == self::ENGINE_OPENSSL) {
                $iv = pack('V', $buffer['counter']) . $this->p2;
                // at this point $text should be a multiple of 64
                $buffer['counter'] += (strlen($text) >> 6) + 1; // ie. divide by 64
                $encrypted = openssl_encrypt(
                    $text . str_repeat("\0", 64),
                    $this->cipher_name_openssl,
                    $this->key,
                    OPENSSL_RAW_DATA,
                    $iv
                );
                $temp = Strings::pop($encrypted, 64);
            } else {
                $blocks = str_split($text, 64);
                if (strlen($text)) {
                    foreach ($blocks as &$block) {
                        $block ^= static::salsa20($this->p1 . pack('V', $buffer['counter']++) . $this->p2);
                    }
                }
                $encrypted = implode('', $blocks);
                $temp = static::salsa20($this->p1 . pack('V', $buffer['counter']++) . $this->p2);
            }
            $ciphertext .= $encrypted . ($text2 ^ $temp);
            $buffer['ciphertext'] = substr($temp, $overflow);
        } elseif (!strlen($buffer['ciphertext'])) {
            if ($this->engine == self::ENGINE_OPENSSL) {
                $iv = pack('V', $buffer['counter']) . $this->p2;
                $buffer['counter'] += (strlen($text) >> 6);
                $ciphertext .= openssl_encrypt(
                    $text,
                    $this->cipher_name_openssl,
                    $this->key,
                    OPENSSL_RAW_DATA,
                    $iv
                );
            } else {
                $blocks = str_split($text, 64);
                foreach ($blocks as &$block) {
                    $block ^= static::salsa20($this->p1 . pack('V', $buffer['counter']++) . $this->p2);
                }
                $ciphertext .= implode('', $blocks);
            }
        }

        return $ciphertext;
    }

    /**
     * Left Rotate
     *
     * @param int $x
     * @param int $n
     * @return int
     */
    protected static function leftRotate($x, $n)
    {
        if (PHP_INT_SIZE == 8) {
            $r1 = $x << $n;
            $r1 &= 0xFFFFFFFF;
            $r2 = ($x & 0xFFFFFFFF) >> (32 - $n);
        } else {
            $x = (int) $x;
            $r1 = $x << $n;
            $r2 = $x >> (32 - $n);
            $r2 &= (1 << $n) - 1;
        }
        return $r1 | $r2;
    }

    /**
     * The quarterround function
     *
     * @param int $a
     * @param int $b
     * @param int $c
     * @param int $d
     */
    protected static function quarterRound(&$a, &$b, &$c, &$d)
    {
        $b ^= self::leftRotate($a + $d, 7);
        $c ^= self::leftRotate($b + $a, 9);
        $d ^= self::leftRotate($c + $b, 13);
        $a ^= self::leftRotate($d + $c, 18);
    }

    /**
     * The doubleround function
     *
     * @param int $x0 (by reference)
     * @param int $x1 (by reference)
     * @param int $x2 (by reference)
     * @param int $x3 (by reference)
     * @param int $x4 (by reference)
     * @param int $x5 (by reference)
     * @param int $x6 (by reference)
     * @param int $x7 (by reference)
     * @param int $x8 (by reference)
     * @param int $x9 (by reference)
     * @param int $x10 (by reference)
     * @param int $x11 (by reference)
     * @param int $x12 (by reference)
     * @param int $x13 (by reference)
     * @param int $x14 (by reference)
     * @param int $x15 (by reference)
     */
    protected static function doubleRound(&$x0, &$x1, &$x2, &$x3, &$x4, &$x5, &$x6, &$x7, &$x8, &$x9, &$x10, &$x11, &$x12, &$x13, &$x14, &$x15)
    {
        // columnRound
        static::quarterRound($x0, $x4, $x8, $x12);
        static::quarterRound($x5, $x9, $x13, $x1);
        static::quarterRound($x10, $x14, $x2, $x6);
        static::quarterRound($x15, $x3, $x7, $x11);
        // rowRound
        static::quarterRound($x0, $x1, $x2, $x3);
        static::quarterRound($x5, $x6, $x7, $x4);
        static::quarterRound($x10, $x11, $x8, $x9);
        static::quarterRound($x15, $x12, $x13, $x14);
    }

    /**
     * The Salsa20 hash function function
     *
     * @param string $x
     */
    protected static function salsa20($x)
    {
        $z = $x = unpack('V*', $x);
        for ($i = 0; $i < 10; $i++) {
            static::doubleRound($z[1], $z[2], $z[3], $z[4], $z[5], $z[6], $z[7], $z[8], $z[9], $z[10], $z[11], $z[12], $z[13], $z[14], $z[15], $z[16]);
        }

        for ($i = 1; $i <= 16; $i++) {
            $x[$i] += $z[$i];
        }

        return pack('V*', ...$x);
    }

    /**
     * Calculates Poly1305 MAC
     *
     * @see self::decrypt()
     * @see self::encrypt()
     * @param string $ciphertext
     * @return string
     */
    protected function poly1305($ciphertext)
    {
        if (!$this->usingGeneratedPoly1305Key) {
            return parent::poly1305($this->aad . $ciphertext);
        } else {
            /*
            sodium_crypto_aead_chacha20poly1305_encrypt does not calculate the poly1305 tag
            the same way sodium_crypto_aead_chacha20poly1305_ietf_encrypt does. you can see
            how the latter encrypts it in Salsa20::encrypt(). here's how the former encrypts
            it:

            $this->newtag = $this->poly1305(
                $this->aad .
                pack('V', strlen($this->aad)) . "\0\0\0\0" .
                $ciphertext .
                pack('V', strlen($ciphertext)) . "\0\0\0\0"
            );

            phpseclib opts to use the IETF construction, even when the nonce is 64-bits
            instead of 96-bits
            */
            return parent::poly1305(
                self::nullPad128($this->aad) .
                self::nullPad128($ciphertext) .
                pack('V', strlen($this->aad)) . "\0\0\0\0" .
                pack('V', strlen($ciphertext)) . "\0\0\0\0"
            );
        }
    }
}
