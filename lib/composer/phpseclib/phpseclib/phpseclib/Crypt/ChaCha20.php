<?php

/**
 * Pure-PHP implementation of ChaCha20.
 *
 * PHP version 5
 *
 * @author    Jim Wigginton <terrafrost@php.net>
 * @copyright 2019 Jim Wigginton
 * @license   http://www.opensource.org/licenses/mit-license.html  MIT License
 * @link      http://phpseclib.sourceforge.net
 */

namespace phpseclib3\Crypt;

use phpseclib3\Exception\BadDecryptionException;
use phpseclib3\Exception\InsufficientSetupException;

/**
 * Pure-PHP implementation of ChaCha20.
 *
 * @author  Jim Wigginton <terrafrost@php.net>
 */
class ChaCha20 extends Salsa20
{
    /**
     * The OpenSSL specific name of the cipher
     *
     * @var string
     */
    protected $cipher_name_openssl = 'chacha20';

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
        switch ($engine) {
            case self::ENGINE_LIBSODIUM:
                // PHP 7.2.0 (30 Nov 2017) added support for libsodium

                // we could probably make it so that if $this->counter == 0 then the first block would be done with either OpenSSL
                // or PHP and then subsequent blocks would then be done with libsodium but idk - it's not a high priority atm

                // we could also make it so that if $this->counter == 0 and $this->continuousBuffer then do the first string
                // with libsodium and subsequent strings with openssl or pure-PHP but again not a high priority
                return function_exists('sodium_crypto_aead_chacha20poly1305_ietf_encrypt') &&
                       $this->key_length == 32 &&
                       (($this->usePoly1305 && !isset($this->poly1305Key) && $this->counter == 0) || $this->counter == 1) &&
                       !$this->continuousBuffer;
            case self::ENGINE_OPENSSL:
                // OpenSSL 1.1.0 (released 25 Aug 2016) added support for chacha20.
                // PHP didn't support OpenSSL 1.1.0 until 7.0.19 (11 May 2017)

                // if you attempt to provide openssl with a 128 bit key (as opposed to a 256 bit key) openssl will null
                // pad the key to 256 bits and still use the expansion constant for 256-bit keys. the fact that
                // openssl treats the IV as both the counter and nonce, however, let's us use openssl in continuous mode
                // whereas libsodium does not
                if ($this->key_length != 32) {
                    return false;
                }
        }

        return parent::isValidEngineHelper($engine);
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
        $this->setup();

        if ($this->engine == self::ENGINE_LIBSODIUM) {
            return $this->encrypt_with_libsodium($plaintext);
        }

        return parent::encrypt($plaintext);
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
        $this->setup();

        if ($this->engine == self::ENGINE_LIBSODIUM) {
            return $this->decrypt_with_libsodium($ciphertext);
        }

        return parent::decrypt($ciphertext);
    }

    /**
     * Encrypts a message with libsodium
     *
     * @see self::encrypt()
     * @param string $plaintext
     * @return string $text
     */
    private function encrypt_with_libsodium($plaintext)
    {
        $params = [$plaintext, $this->aad, $this->nonce, $this->key];
        $ciphertext = strlen($this->nonce) == 8 ?
            sodium_crypto_aead_chacha20poly1305_encrypt(...$params) :
            sodium_crypto_aead_chacha20poly1305_ietf_encrypt(...$params);
        if (!$this->usePoly1305) {
            return substr($ciphertext, 0, strlen($plaintext));
        }

        $newciphertext = substr($ciphertext, 0, strlen($plaintext));

        $this->newtag = $this->usingGeneratedPoly1305Key && strlen($this->nonce) == 12 ?
            substr($ciphertext, strlen($plaintext)) :
            $this->poly1305($newciphertext);

        return $newciphertext;
    }

    /**
     * Decrypts a message with libsodium
     *
     * @see self::decrypt()
     * @param string $ciphertext
     * @return string $text
     */
    private function decrypt_with_libsodium($ciphertext)
    {
        $params = [$ciphertext, $this->aad, $this->nonce, $this->key];

        if (isset($this->poly1305Key)) {
            if ($this->oldtag === false) {
                throw new InsufficientSetupException('Authentication Tag has not been set');
            }
            if ($this->usingGeneratedPoly1305Key && strlen($this->nonce) == 12) {
                $plaintext = sodium_crypto_aead_chacha20poly1305_ietf_decrypt(...$params);
                $this->oldtag = false;
                if ($plaintext === false) {
                    throw new BadDecryptionException('Derived authentication tag and supplied authentication tag do not match');
                }
                return $plaintext;
            }
            $newtag = $this->poly1305($ciphertext);
            if ($this->oldtag != substr($newtag, 0, strlen($this->oldtag))) {
                $this->oldtag = false;
                throw new BadDecryptionException('Derived authentication tag and supplied authentication tag do not match');
            }
            $this->oldtag = false;
        }

        $plaintext = strlen($this->nonce) == 8 ?
            sodium_crypto_aead_chacha20poly1305_encrypt(...$params) :
            sodium_crypto_aead_chacha20poly1305_ietf_encrypt(...$params);

        return substr($plaintext, 0, strlen($ciphertext));
    }

    /**
     * Sets the nonce.
     *
     * @param string $nonce
     */
    public function setNonce($nonce)
    {
        if (!is_string($nonce)) {
            throw new \UnexpectedValueException('The nonce should be a string');
        }

        /*
          from https://tools.ietf.org/html/rfc7539#page-7

          "Note also that the original ChaCha had a 64-bit nonce and 64-bit
           block count.  We have modified this here to be more consistent with
           recommendations in Section 3.2 of [RFC5116]."
         */
        switch (strlen($nonce)) {
            case 8:  // 64 bits
            case 12: // 96 bits
                break;
            default:
                throw new \LengthException('Nonce of size ' . strlen($nonce) . ' not supported by this algorithm. Only 64-bit nonces or 96-bit nonces are supported');
        }

        $this->nonce = $nonce;
        $this->changed = true;
        $this->setEngine();
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
            if ($this->engine == self::ENGINE_LIBSODIUM) {
                return;
            }
            $this->createPoly1305Key();
        }

        $key = $this->key;
        if (strlen($key) == 16) {
            $constant = 'expand 16-byte k';
            $key .= $key;
        } else {
            $constant = 'expand 32-byte k';
        }

        $this->p1 = $constant . $key;
        $this->p2 = $this->nonce;
        if (strlen($this->nonce) == 8) {
            $this->p2 = "\0\0\0\0" . $this->p2;
        }
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
        // in https://datatracker.ietf.org/doc/html/rfc7539#section-2.1 the addition,
        // xor'ing and rotation are all on the same line so i'm keeping it on the same
        // line here as well
        // @codingStandardsIgnoreStart
        $a+= $b; $d = self::leftRotate(intval($d) ^ intval($a), 16);
        $c+= $d; $b = self::leftRotate(intval($b) ^ intval($c), 12);
        $a+= $b; $d = self::leftRotate(intval($d) ^ intval($a), 8);
        $c+= $d; $b = self::leftRotate(intval($b) ^ intval($c), 7);
        // @codingStandardsIgnoreEnd
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
        static::quarterRound($x1, $x5, $x9, $x13);
        static::quarterRound($x2, $x6, $x10, $x14);
        static::quarterRound($x3, $x7, $x11, $x15);
        // rowRound
        static::quarterRound($x0, $x5, $x10, $x15);
        static::quarterRound($x1, $x6, $x11, $x12);
        static::quarterRound($x2, $x7, $x8, $x13);
        static::quarterRound($x3, $x4, $x9, $x14);
    }

    /**
     * The Salsa20 hash function function
     *
     * On my laptop this loop unrolled / function dereferenced version of parent::salsa20 encrypts 1mb of text in
     * 0.65s vs the 0.85s that it takes with the parent method.
     *
     * If we were free to assume that the host OS would always be 64-bits then the if condition in leftRotate could
     * be eliminated and we could knock this done to 0.60s.
     *
     * For comparison purposes, RC4 takes 0.16s and AES in CTR mode with the Eval engine takes 0.48s.
     * AES in CTR mode with the PHP engine takes 1.19s. Salsa20 / ChaCha20 do not benefit as much from the Eval
     * approach due to the fact that there are a lot less variables to de-reference, fewer loops to unroll, etc
     *
     * @param string $x
     */
    protected static function salsa20($x)
    {
        list(, $x0, $x1, $x2, $x3, $x4, $x5, $x6, $x7, $x8, $x9, $x10, $x11, $x12, $x13, $x14, $x15) = unpack('V*', $x);
        $z0 = $x0;
        $z1 = $x1;
        $z2 = $x2;
        $z3 = $x3;
        $z4 = $x4;
        $z5 = $x5;
        $z6 = $x6;
        $z7 = $x7;
        $z8 = $x8;
        $z9 = $x9;
        $z10 = $x10;
        $z11 = $x11;
        $z12 = $x12;
        $z13 = $x13;
        $z14 = $x14;
        $z15 = $x15;

        // @codingStandardsIgnoreStart
        // columnRound
        $x0+= $x4; $x12 = self::leftRotate(intval($x12) ^ intval($x0), 16);
        $x8+= $x12; $x4 = self::leftRotate(intval($x4) ^ intval($x8), 12);
        $x0+= $x4; $x12 = self::leftRotate(intval($x12) ^ intval($x0), 8);
        $x8+= $x12; $x4 = self::leftRotate(intval($x4) ^ intval($x8), 7);

        $x1+= $x5; $x13 = self::leftRotate(intval($x13) ^ intval($x1), 16);
        $x9+= $x13; $x5 = self::leftRotate(intval($x5) ^ intval($x9), 12);
        $x1+= $x5; $x13 = self::leftRotate(intval($x13) ^ intval($x1), 8);
        $x9+= $x13; $x5 = self::leftRotate(intval($x5) ^ intval($x9), 7);

        $x2+= $x6; $x14 = self::leftRotate(intval($x14) ^ intval($x2), 16);
        $x10+= $x14; $x6 = self::leftRotate(intval($x6) ^ intval($x10), 12);
        $x2+= $x6; $x14 = self::leftRotate(intval($x14) ^ intval($x2), 8);
        $x10+= $x14; $x6 = self::leftRotate(intval($x6) ^ intval($x10), 7);

        $x3+= $x7; $x15 = self::leftRotate(intval($x15) ^ intval($x3), 16);
        $x11+= $x15; $x7 = self::leftRotate(intval($x7) ^ intval($x11), 12);
        $x3+= $x7; $x15 = self::leftRotate(intval($x15) ^ intval($x3), 8);
        $x11+= $x15; $x7 = self::leftRotate(intval($x7) ^ intval($x11), 7);

        // rowRound
        $x0+= $x5; $x15 = self::leftRotate(intval($x15) ^ intval($x0), 16);
        $x10+= $x15; $x5 = self::leftRotate(intval($x5) ^ intval($x10), 12);
        $x0+= $x5; $x15 = self::leftRotate(intval($x15) ^ intval($x0), 8);
        $x10+= $x15; $x5 = self::leftRotate(intval($x5) ^ intval($x10), 7);

        $x1+= $x6; $x12 = self::leftRotate(intval($x12) ^ intval($x1), 16);
        $x11+= $x12; $x6 = self::leftRotate(intval($x6) ^ intval($x11), 12);
        $x1+= $x6; $x12 = self::leftRotate(intval($x12) ^ intval($x1), 8);
        $x11+= $x12; $x6 = self::leftRotate(intval($x6) ^ intval($x11), 7);

        $x2+= $x7; $x13 = self::leftRotate(intval($x13) ^ intval($x2), 16);
        $x8+= $x13; $x7 = self::leftRotate(intval($x7) ^ intval($x8), 12);
        $x2+= $x7; $x13 = self::leftRotate(intval($x13) ^ intval($x2), 8);
        $x8+= $x13; $x7 = self::leftRotate(intval($x7) ^ intval($x8), 7);

        $x3+= $x4; $x14 = self::leftRotate(intval($x14) ^ intval($x3), 16);
        $x9+= $x14; $x4 = self::leftRotate(intval($x4) ^ intval($x9), 12);
        $x3+= $x4; $x14 = self::leftRotate(intval($x14) ^ intval($x3), 8);
        $x9+= $x14; $x4 = self::leftRotate(intval($x4) ^ intval($x9), 7);

        // columnRound
        $x0+= $x4; $x12 = self::leftRotate(intval($x12) ^ intval($x0), 16);
        $x8+= $x12; $x4 = self::leftRotate(intval($x4) ^ intval($x8), 12);
        $x0+= $x4; $x12 = self::leftRotate(intval($x12) ^ intval($x0), 8);
        $x8+= $x12; $x4 = self::leftRotate(intval($x4) ^ intval($x8), 7);

        $x1+= $x5; $x13 = self::leftRotate(intval($x13) ^ intval($x1), 16);
        $x9+= $x13; $x5 = self::leftRotate(intval($x5) ^ intval($x9), 12);
        $x1+= $x5; $x13 = self::leftRotate(intval($x13) ^ intval($x1), 8);
        $x9+= $x13; $x5 = self::leftRotate(intval($x5) ^ intval($x9), 7);

        $x2+= $x6; $x14 = self::leftRotate(intval($x14) ^ intval($x2), 16);
        $x10+= $x14; $x6 = self::leftRotate(intval($x6) ^ intval($x10), 12);
        $x2+= $x6; $x14 = self::leftRotate(intval($x14) ^ intval($x2), 8);
        $x10+= $x14; $x6 = self::leftRotate(intval($x6) ^ intval($x10), 7);

        $x3+= $x7; $x15 = self::leftRotate(intval($x15) ^ intval($x3), 16);
        $x11+= $x15; $x7 = self::leftRotate(intval($x7) ^ intval($x11), 12);
        $x3+= $x7; $x15 = self::leftRotate(intval($x15) ^ intval($x3), 8);
        $x11+= $x15; $x7 = self::leftRotate(intval($x7) ^ intval($x11), 7);

        // rowRound
        $x0+= $x5; $x15 = self::leftRotate(intval($x15) ^ intval($x0), 16);
        $x10+= $x15; $x5 = self::leftRotate(intval($x5) ^ intval($x10), 12);
        $x0+= $x5; $x15 = self::leftRotate(intval($x15) ^ intval($x0), 8);
        $x10+= $x15; $x5 = self::leftRotate(intval($x5) ^ intval($x10), 7);

        $x1+= $x6; $x12 = self::leftRotate(intval($x12) ^ intval($x1), 16);
        $x11+= $x12; $x6 = self::leftRotate(intval($x6) ^ intval($x11), 12);
        $x1+= $x6; $x12 = self::leftRotate(intval($x12) ^ intval($x1), 8);
        $x11+= $x12; $x6 = self::leftRotate(intval($x6) ^ intval($x11), 7);

        $x2+= $x7; $x13 = self::leftRotate(intval($x13) ^ intval($x2), 16);
        $x8+= $x13; $x7 = self::leftRotate(intval($x7) ^ intval($x8), 12);
        $x2+= $x7; $x13 = self::leftRotate(intval($x13) ^ intval($x2), 8);
        $x8+= $x13; $x7 = self::leftRotate(intval($x7) ^ intval($x8), 7);

        $x3+= $x4; $x14 = self::leftRotate(intval($x14) ^ intval($x3), 16);
        $x9+= $x14; $x4 = self::leftRotate(intval($x4) ^ intval($x9), 12);
        $x3+= $x4; $x14 = self::leftRotate(intval($x14) ^ intval($x3), 8);
        $x9+= $x14; $x4 = self::leftRotate(intval($x4) ^ intval($x9), 7);

        // columnRound
        $x0+= $x4; $x12 = self::leftRotate(intval($x12) ^ intval($x0), 16);
        $x8+= $x12; $x4 = self::leftRotate(intval($x4) ^ intval($x8), 12);
        $x0+= $x4; $x12 = self::leftRotate(intval($x12) ^ intval($x0), 8);
        $x8+= $x12; $x4 = self::leftRotate(intval($x4) ^ intval($x8), 7);

        $x1+= $x5; $x13 = self::leftRotate(intval($x13) ^ intval($x1), 16);
        $x9+= $x13; $x5 = self::leftRotate(intval($x5) ^ intval($x9), 12);
        $x1+= $x5; $x13 = self::leftRotate(intval($x13) ^ intval($x1), 8);
        $x9+= $x13; $x5 = self::leftRotate(intval($x5) ^ intval($x9), 7);

        $x2+= $x6; $x14 = self::leftRotate(intval($x14) ^ intval($x2), 16);
        $x10+= $x14; $x6 = self::leftRotate(intval($x6) ^ intval($x10), 12);
        $x2+= $x6; $x14 = self::leftRotate(intval($x14) ^ intval($x2), 8);
        $x10+= $x14; $x6 = self::leftRotate(intval($x6) ^ intval($x10), 7);

        $x3+= $x7; $x15 = self::leftRotate(intval($x15) ^ intval($x3), 16);
        $x11+= $x15; $x7 = self::leftRotate(intval($x7) ^ intval($x11), 12);
        $x3+= $x7; $x15 = self::leftRotate(intval($x15) ^ intval($x3), 8);
        $x11+= $x15; $x7 = self::leftRotate(intval($x7) ^ intval($x11), 7);

        // rowRound
        $x0+= $x5; $x15 = self::leftRotate(intval($x15) ^ intval($x0), 16);
        $x10+= $x15; $x5 = self::leftRotate(intval($x5) ^ intval($x10), 12);
        $x0+= $x5; $x15 = self::leftRotate(intval($x15) ^ intval($x0), 8);
        $x10+= $x15; $x5 = self::leftRotate(intval($x5) ^ intval($x10), 7);

        $x1+= $x6; $x12 = self::leftRotate(intval($x12) ^ intval($x1), 16);
        $x11+= $x12; $x6 = self::leftRotate(intval($x6) ^ intval($x11), 12);
        $x1+= $x6; $x12 = self::leftRotate(intval($x12) ^ intval($x1), 8);
        $x11+= $x12; $x6 = self::leftRotate(intval($x6) ^ intval($x11), 7);

        $x2+= $x7; $x13 = self::leftRotate(intval($x13) ^ intval($x2), 16);
        $x8+= $x13; $x7 = self::leftRotate(intval($x7) ^ intval($x8), 12);
        $x2+= $x7; $x13 = self::leftRotate(intval($x13) ^ intval($x2), 8);
        $x8+= $x13; $x7 = self::leftRotate(intval($x7) ^ intval($x8), 7);

        $x3+= $x4; $x14 = self::leftRotate(intval($x14) ^ intval($x3), 16);
        $x9+= $x14; $x4 = self::leftRotate(intval($x4) ^ intval($x9), 12);
        $x3+= $x4; $x14 = self::leftRotate(intval($x14) ^ intval($x3), 8);
        $x9+= $x14; $x4 = self::leftRotate(intval($x4) ^ intval($x9), 7);

        // columnRound
        $x0+= $x4; $x12 = self::leftRotate(intval($x12) ^ intval($x0), 16);
        $x8+= $x12; $x4 = self::leftRotate(intval($x4) ^ intval($x8), 12);
        $x0+= $x4; $x12 = self::leftRotate(intval($x12) ^ intval($x0), 8);
        $x8+= $x12; $x4 = self::leftRotate(intval($x4) ^ intval($x8), 7);

        $x1+= $x5; $x13 = self::leftRotate(intval($x13) ^ intval($x1), 16);
        $x9+= $x13; $x5 = self::leftRotate(intval($x5) ^ intval($x9), 12);
        $x1+= $x5; $x13 = self::leftRotate(intval($x13) ^ intval($x1), 8);
        $x9+= $x13; $x5 = self::leftRotate(intval($x5) ^ intval($x9), 7);

        $x2+= $x6; $x14 = self::leftRotate(intval($x14) ^ intval($x2), 16);
        $x10+= $x14; $x6 = self::leftRotate(intval($x6) ^ intval($x10), 12);
        $x2+= $x6; $x14 = self::leftRotate(intval($x14) ^ intval($x2), 8);
        $x10+= $x14; $x6 = self::leftRotate(intval($x6) ^ intval($x10), 7);

        $x3+= $x7; $x15 = self::leftRotate(intval($x15) ^ intval($x3), 16);
        $x11+= $x15; $x7 = self::leftRotate(intval($x7) ^ intval($x11), 12);
        $x3+= $x7; $x15 = self::leftRotate(intval($x15) ^ intval($x3), 8);
        $x11+= $x15; $x7 = self::leftRotate(intval($x7) ^ intval($x11), 7);

        // rowRound
        $x0+= $x5; $x15 = self::leftRotate(intval($x15) ^ intval($x0), 16);
        $x10+= $x15; $x5 = self::leftRotate(intval($x5) ^ intval($x10), 12);
        $x0+= $x5; $x15 = self::leftRotate(intval($x15) ^ intval($x0), 8);
        $x10+= $x15; $x5 = self::leftRotate(intval($x5) ^ intval($x10), 7);

        $x1+= $x6; $x12 = self::leftRotate(intval($x12) ^ intval($x1), 16);
        $x11+= $x12; $x6 = self::leftRotate(intval($x6) ^ intval($x11), 12);
        $x1+= $x6; $x12 = self::leftRotate(intval($x12) ^ intval($x1), 8);
        $x11+= $x12; $x6 = self::leftRotate(intval($x6) ^ intval($x11), 7);

        $x2+= $x7; $x13 = self::leftRotate(intval($x13) ^ intval($x2), 16);
        $x8+= $x13; $x7 = self::leftRotate(intval($x7) ^ intval($x8), 12);
        $x2+= $x7; $x13 = self::leftRotate(intval($x13) ^ intval($x2), 8);
        $x8+= $x13; $x7 = self::leftRotate(intval($x7) ^ intval($x8), 7);

        $x3+= $x4; $x14 = self::leftRotate(intval($x14) ^ intval($x3), 16);
        $x9+= $x14; $x4 = self::leftRotate(intval($x4) ^ intval($x9), 12);
        $x3+= $x4; $x14 = self::leftRotate(intval($x14) ^ intval($x3), 8);
        $x9+= $x14; $x4 = self::leftRotate(intval($x4) ^ intval($x9), 7);

        // columnRound
        $x0+= $x4; $x12 = self::leftRotate(intval($x12) ^ intval($x0), 16);
        $x8+= $x12; $x4 = self::leftRotate(intval($x4) ^ intval($x8), 12);
        $x0+= $x4; $x12 = self::leftRotate(intval($x12) ^ intval($x0), 8);
        $x8+= $x12; $x4 = self::leftRotate(intval($x4) ^ intval($x8), 7);

        $x1+= $x5; $x13 = self::leftRotate(intval($x13) ^ intval($x1), 16);
        $x9+= $x13; $x5 = self::leftRotate(intval($x5) ^ intval($x9), 12);
        $x1+= $x5; $x13 = self::leftRotate(intval($x13) ^ intval($x1), 8);
        $x9+= $x13; $x5 = self::leftRotate(intval($x5) ^ intval($x9), 7);

        $x2+= $x6; $x14 = self::leftRotate(intval($x14) ^ intval($x2), 16);
        $x10+= $x14; $x6 = self::leftRotate(intval($x6) ^ intval($x10), 12);
        $x2+= $x6; $x14 = self::leftRotate(intval($x14) ^ intval($x2), 8);
        $x10+= $x14; $x6 = self::leftRotate(intval($x6) ^ intval($x10), 7);

        $x3+= $x7; $x15 = self::leftRotate(intval($x15) ^ intval($x3), 16);
        $x11+= $x15; $x7 = self::leftRotate(intval($x7) ^ intval($x11), 12);
        $x3+= $x7; $x15 = self::leftRotate(intval($x15) ^ intval($x3), 8);
        $x11+= $x15; $x7 = self::leftRotate(intval($x7) ^ intval($x11), 7);

        // rowRound
        $x0+= $x5; $x15 = self::leftRotate(intval($x15) ^ intval($x0), 16);
        $x10+= $x15; $x5 = self::leftRotate(intval($x5) ^ intval($x10), 12);
        $x0+= $x5; $x15 = self::leftRotate(intval($x15) ^ intval($x0), 8);
        $x10+= $x15; $x5 = self::leftRotate(intval($x5) ^ intval($x10), 7);

        $x1+= $x6; $x12 = self::leftRotate(intval($x12) ^ intval($x1), 16);
        $x11+= $x12; $x6 = self::leftRotate(intval($x6) ^ intval($x11), 12);
        $x1+= $x6; $x12 = self::leftRotate(intval($x12) ^ intval($x1), 8);
        $x11+= $x12; $x6 = self::leftRotate(intval($x6) ^ intval($x11), 7);

        $x2+= $x7; $x13 = self::leftRotate(intval($x13) ^ intval($x2), 16);
        $x8+= $x13; $x7 = self::leftRotate(intval($x7) ^ intval($x8), 12);
        $x2+= $x7; $x13 = self::leftRotate(intval($x13) ^ intval($x2), 8);
        $x8+= $x13; $x7 = self::leftRotate(intval($x7) ^ intval($x8), 7);

        $x3+= $x4; $x14 = self::leftRotate(intval($x14) ^ intval($x3), 16);
        $x9+= $x14; $x4 = self::leftRotate(intval($x4) ^ intval($x9), 12);
        $x3+= $x4; $x14 = self::leftRotate(intval($x14) ^ intval($x3), 8);
        $x9+= $x14; $x4 = self::leftRotate(intval($x4) ^ intval($x9), 7);

        // columnRound
        $x0+= $x4; $x12 = self::leftRotate(intval($x12) ^ intval($x0), 16);
        $x8+= $x12; $x4 = self::leftRotate(intval($x4) ^ intval($x8), 12);
        $x0+= $x4; $x12 = self::leftRotate(intval($x12) ^ intval($x0), 8);
        $x8+= $x12; $x4 = self::leftRotate(intval($x4) ^ intval($x8), 7);

        $x1+= $x5; $x13 = self::leftRotate(intval($x13) ^ intval($x1), 16);
        $x9+= $x13; $x5 = self::leftRotate(intval($x5) ^ intval($x9), 12);
        $x1+= $x5; $x13 = self::leftRotate(intval($x13) ^ intval($x1), 8);
        $x9+= $x13; $x5 = self::leftRotate(intval($x5) ^ intval($x9), 7);

        $x2+= $x6; $x14 = self::leftRotate(intval($x14) ^ intval($x2), 16);
        $x10+= $x14; $x6 = self::leftRotate(intval($x6) ^ intval($x10), 12);
        $x2+= $x6; $x14 = self::leftRotate(intval($x14) ^ intval($x2), 8);
        $x10+= $x14; $x6 = self::leftRotate(intval($x6) ^ intval($x10), 7);

        $x3+= $x7; $x15 = self::leftRotate(intval($x15) ^ intval($x3), 16);
        $x11+= $x15; $x7 = self::leftRotate(intval($x7) ^ intval($x11), 12);
        $x3+= $x7; $x15 = self::leftRotate(intval($x15) ^ intval($x3), 8);
        $x11+= $x15; $x7 = self::leftRotate(intval($x7) ^ intval($x11), 7);

        // rowRound
        $x0+= $x5; $x15 = self::leftRotate(intval($x15) ^ intval($x0), 16);
        $x10+= $x15; $x5 = self::leftRotate(intval($x5) ^ intval($x10), 12);
        $x0+= $x5; $x15 = self::leftRotate(intval($x15) ^ intval($x0), 8);
        $x10+= $x15; $x5 = self::leftRotate(intval($x5) ^ intval($x10), 7);

        $x1+= $x6; $x12 = self::leftRotate(intval($x12) ^ intval($x1), 16);
        $x11+= $x12; $x6 = self::leftRotate(intval($x6) ^ intval($x11), 12);
        $x1+= $x6; $x12 = self::leftRotate(intval($x12) ^ intval($x1), 8);
        $x11+= $x12; $x6 = self::leftRotate(intval($x6) ^ intval($x11), 7);

        $x2+= $x7; $x13 = self::leftRotate(intval($x13) ^ intval($x2), 16);
        $x8+= $x13; $x7 = self::leftRotate(intval($x7) ^ intval($x8), 12);
        $x2+= $x7; $x13 = self::leftRotate(intval($x13) ^ intval($x2), 8);
        $x8+= $x13; $x7 = self::leftRotate(intval($x7) ^ intval($x8), 7);

        $x3+= $x4; $x14 = self::leftRotate(intval($x14) ^ intval($x3), 16);
        $x9+= $x14; $x4 = self::leftRotate(intval($x4) ^ intval($x9), 12);
        $x3+= $x4; $x14 = self::leftRotate(intval($x14) ^ intval($x3), 8);
        $x9+= $x14; $x4 = self::leftRotate(intval($x4) ^ intval($x9), 7);

        // columnRound
        $x0+= $x4; $x12 = self::leftRotate(intval($x12) ^ intval($x0), 16);
        $x8+= $x12; $x4 = self::leftRotate(intval($x4) ^ intval($x8), 12);
        $x0+= $x4; $x12 = self::leftRotate(intval($x12) ^ intval($x0), 8);
        $x8+= $x12; $x4 = self::leftRotate(intval($x4) ^ intval($x8), 7);

        $x1+= $x5; $x13 = self::leftRotate(intval($x13) ^ intval($x1), 16);
        $x9+= $x13; $x5 = self::leftRotate(intval($x5) ^ intval($x9), 12);
        $x1+= $x5; $x13 = self::leftRotate(intval($x13) ^ intval($x1), 8);
        $x9+= $x13; $x5 = self::leftRotate(intval($x5) ^ intval($x9), 7);

        $x2+= $x6; $x14 = self::leftRotate(intval($x14) ^ intval($x2), 16);
        $x10+= $x14; $x6 = self::leftRotate(intval($x6) ^ intval($x10), 12);
        $x2+= $x6; $x14 = self::leftRotate(intval($x14) ^ intval($x2), 8);
        $x10+= $x14; $x6 = self::leftRotate(intval($x6) ^ intval($x10), 7);

        $x3+= $x7; $x15 = self::leftRotate(intval($x15) ^ intval($x3), 16);
        $x11+= $x15; $x7 = self::leftRotate(intval($x7) ^ intval($x11), 12);
        $x3+= $x7; $x15 = self::leftRotate(intval($x15) ^ intval($x3), 8);
        $x11+= $x15; $x7 = self::leftRotate(intval($x7) ^ intval($x11), 7);

        // rowRound
        $x0+= $x5; $x15 = self::leftRotate(intval($x15) ^ intval($x0), 16);
        $x10+= $x15; $x5 = self::leftRotate(intval($x5) ^ intval($x10), 12);
        $x0+= $x5; $x15 = self::leftRotate(intval($x15) ^ intval($x0), 8);
        $x10+= $x15; $x5 = self::leftRotate(intval($x5) ^ intval($x10), 7);

        $x1+= $x6; $x12 = self::leftRotate(intval($x12) ^ intval($x1), 16);
        $x11+= $x12; $x6 = self::leftRotate(intval($x6) ^ intval($x11), 12);
        $x1+= $x6; $x12 = self::leftRotate(intval($x12) ^ intval($x1), 8);
        $x11+= $x12; $x6 = self::leftRotate(intval($x6) ^ intval($x11), 7);

        $x2+= $x7; $x13 = self::leftRotate(intval($x13) ^ intval($x2), 16);
        $x8+= $x13; $x7 = self::leftRotate(intval($x7) ^ intval($x8), 12);
        $x2+= $x7; $x13 = self::leftRotate(intval($x13) ^ intval($x2), 8);
        $x8+= $x13; $x7 = self::leftRotate(intval($x7) ^ intval($x8), 7);

        $x3+= $x4; $x14 = self::leftRotate(intval($x14) ^ intval($x3), 16);
        $x9+= $x14; $x4 = self::leftRotate(intval($x4) ^ intval($x9), 12);
        $x3+= $x4; $x14 = self::leftRotate(intval($x14) ^ intval($x3), 8);
        $x9+= $x14; $x4 = self::leftRotate(intval($x4) ^ intval($x9), 7);

        // columnRound
        $x0+= $x4; $x12 = self::leftRotate(intval($x12) ^ intval($x0), 16);
        $x8+= $x12; $x4 = self::leftRotate(intval($x4) ^ intval($x8), 12);
        $x0+= $x4; $x12 = self::leftRotate(intval($x12) ^ intval($x0), 8);
        $x8+= $x12; $x4 = self::leftRotate(intval($x4) ^ intval($x8), 7);

        $x1+= $x5; $x13 = self::leftRotate(intval($x13) ^ intval($x1), 16);
        $x9+= $x13; $x5 = self::leftRotate(intval($x5) ^ intval($x9), 12);
        $x1+= $x5; $x13 = self::leftRotate(intval($x13) ^ intval($x1), 8);
        $x9+= $x13; $x5 = self::leftRotate(intval($x5) ^ intval($x9), 7);

        $x2+= $x6; $x14 = self::leftRotate(intval($x14) ^ intval($x2), 16);
        $x10+= $x14; $x6 = self::leftRotate(intval($x6) ^ intval($x10), 12);
        $x2+= $x6; $x14 = self::leftRotate(intval($x14) ^ intval($x2), 8);
        $x10+= $x14; $x6 = self::leftRotate(intval($x6) ^ intval($x10), 7);

        $x3+= $x7; $x15 = self::leftRotate(intval($x15) ^ intval($x3), 16);
        $x11+= $x15; $x7 = self::leftRotate(intval($x7) ^ intval($x11), 12);
        $x3+= $x7; $x15 = self::leftRotate(intval($x15) ^ intval($x3), 8);
        $x11+= $x15; $x7 = self::leftRotate(intval($x7) ^ intval($x11), 7);

        // rowRound
        $x0+= $x5; $x15 = self::leftRotate(intval($x15) ^ intval($x0), 16);
        $x10+= $x15; $x5 = self::leftRotate(intval($x5) ^ intval($x10), 12);
        $x0+= $x5; $x15 = self::leftRotate(intval($x15) ^ intval($x0), 8);
        $x10+= $x15; $x5 = self::leftRotate(intval($x5) ^ intval($x10), 7);

        $x1+= $x6; $x12 = self::leftRotate(intval($x12) ^ intval($x1), 16);
        $x11+= $x12; $x6 = self::leftRotate(intval($x6) ^ intval($x11), 12);
        $x1+= $x6; $x12 = self::leftRotate(intval($x12) ^ intval($x1), 8);
        $x11+= $x12; $x6 = self::leftRotate(intval($x6) ^ intval($x11), 7);

        $x2+= $x7; $x13 = self::leftRotate(intval($x13) ^ intval($x2), 16);
        $x8+= $x13; $x7 = self::leftRotate(intval($x7) ^ intval($x8), 12);
        $x2+= $x7; $x13 = self::leftRotate(intval($x13) ^ intval($x2), 8);
        $x8+= $x13; $x7 = self::leftRotate(intval($x7) ^ intval($x8), 7);

        $x3+= $x4; $x14 = self::leftRotate(intval($x14) ^ intval($x3), 16);
        $x9+= $x14; $x4 = self::leftRotate(intval($x4) ^ intval($x9), 12);
        $x3+= $x4; $x14 = self::leftRotate(intval($x14) ^ intval($x3), 8);
        $x9+= $x14; $x4 = self::leftRotate(intval($x4) ^ intval($x9), 7);

        // columnRound
        $x0+= $x4; $x12 = self::leftRotate(intval($x12) ^ intval($x0), 16);
        $x8+= $x12; $x4 = self::leftRotate(intval($x4) ^ intval($x8), 12);
        $x0+= $x4; $x12 = self::leftRotate(intval($x12) ^ intval($x0), 8);
        $x8+= $x12; $x4 = self::leftRotate(intval($x4) ^ intval($x8), 7);

        $x1+= $x5; $x13 = self::leftRotate(intval($x13) ^ intval($x1), 16);
        $x9+= $x13; $x5 = self::leftRotate(intval($x5) ^ intval($x9), 12);
        $x1+= $x5; $x13 = self::leftRotate(intval($x13) ^ intval($x1), 8);
        $x9+= $x13; $x5 = self::leftRotate(intval($x5) ^ intval($x9), 7);

        $x2+= $x6; $x14 = self::leftRotate(intval($x14) ^ intval($x2), 16);
        $x10+= $x14; $x6 = self::leftRotate(intval($x6) ^ intval($x10), 12);
        $x2+= $x6; $x14 = self::leftRotate(intval($x14) ^ intval($x2), 8);
        $x10+= $x14; $x6 = self::leftRotate(intval($x6) ^ intval($x10), 7);

        $x3+= $x7; $x15 = self::leftRotate(intval($x15) ^ intval($x3), 16);
        $x11+= $x15; $x7 = self::leftRotate(intval($x7) ^ intval($x11), 12);
        $x3+= $x7; $x15 = self::leftRotate(intval($x15) ^ intval($x3), 8);
        $x11+= $x15; $x7 = self::leftRotate(intval($x7) ^ intval($x11), 7);

        // rowRound
        $x0+= $x5; $x15 = self::leftRotate(intval($x15) ^ intval($x0), 16);
        $x10+= $x15; $x5 = self::leftRotate(intval($x5) ^ intval($x10), 12);
        $x0+= $x5; $x15 = self::leftRotate(intval($x15) ^ intval($x0), 8);
        $x10+= $x15; $x5 = self::leftRotate(intval($x5) ^ intval($x10), 7);

        $x1+= $x6; $x12 = self::leftRotate(intval($x12) ^ intval($x1), 16);
        $x11+= $x12; $x6 = self::leftRotate(intval($x6) ^ intval($x11), 12);
        $x1+= $x6; $x12 = self::leftRotate(intval($x12) ^ intval($x1), 8);
        $x11+= $x12; $x6 = self::leftRotate(intval($x6) ^ intval($x11), 7);

        $x2+= $x7; $x13 = self::leftRotate(intval($x13) ^ intval($x2), 16);
        $x8+= $x13; $x7 = self::leftRotate(intval($x7) ^ intval($x8), 12);
        $x2+= $x7; $x13 = self::leftRotate(intval($x13) ^ intval($x2), 8);
        $x8+= $x13; $x7 = self::leftRotate(intval($x7) ^ intval($x8), 7);

        $x3+= $x4; $x14 = self::leftRotate(intval($x14) ^ intval($x3), 16);
        $x9+= $x14; $x4 = self::leftRotate(intval($x4) ^ intval($x9), 12);
        $x3+= $x4; $x14 = self::leftRotate(intval($x14) ^ intval($x3), 8);
        $x9+= $x14; $x4 = self::leftRotate(intval($x4) ^ intval($x9), 7);

        // columnRound
        $x0+= $x4; $x12 = self::leftRotate(intval($x12) ^ intval($x0), 16);
        $x8+= $x12; $x4 = self::leftRotate(intval($x4) ^ intval($x8), 12);
        $x0+= $x4; $x12 = self::leftRotate(intval($x12) ^ intval($x0), 8);
        $x8+= $x12; $x4 = self::leftRotate(intval($x4) ^ intval($x8), 7);

        $x1+= $x5; $x13 = self::leftRotate(intval($x13) ^ intval($x1), 16);
        $x9+= $x13; $x5 = self::leftRotate(intval($x5) ^ intval($x9), 12);
        $x1+= $x5; $x13 = self::leftRotate(intval($x13) ^ intval($x1), 8);
        $x9+= $x13; $x5 = self::leftRotate(intval($x5) ^ intval($x9), 7);

        $x2+= $x6; $x14 = self::leftRotate(intval($x14) ^ intval($x2), 16);
        $x10+= $x14; $x6 = self::leftRotate(intval($x6) ^ intval($x10), 12);
        $x2+= $x6; $x14 = self::leftRotate(intval($x14) ^ intval($x2), 8);
        $x10+= $x14; $x6 = self::leftRotate(intval($x6) ^ intval($x10), 7);

        $x3+= $x7; $x15 = self::leftRotate(intval($x15) ^ intval($x3), 16);
        $x11+= $x15; $x7 = self::leftRotate(intval($x7) ^ intval($x11), 12);
        $x3+= $x7; $x15 = self::leftRotate(intval($x15) ^ intval($x3), 8);
        $x11+= $x15; $x7 = self::leftRotate(intval($x7) ^ intval($x11), 7);

        // rowRound
        $x0+= $x5; $x15 = self::leftRotate(intval($x15) ^ intval($x0), 16);
        $x10+= $x15; $x5 = self::leftRotate(intval($x5) ^ intval($x10), 12);
        $x0+= $x5; $x15 = self::leftRotate(intval($x15) ^ intval($x0), 8);
        $x10+= $x15; $x5 = self::leftRotate(intval($x5) ^ intval($x10), 7);

        $x1+= $x6; $x12 = self::leftRotate(intval($x12) ^ intval($x1), 16);
        $x11+= $x12; $x6 = self::leftRotate(intval($x6) ^ intval($x11), 12);
        $x1+= $x6; $x12 = self::leftRotate(intval($x12) ^ intval($x1), 8);
        $x11+= $x12; $x6 = self::leftRotate(intval($x6) ^ intval($x11), 7);

        $x2+= $x7; $x13 = self::leftRotate(intval($x13) ^ intval($x2), 16);
        $x8+= $x13; $x7 = self::leftRotate(intval($x7) ^ intval($x8), 12);
        $x2+= $x7; $x13 = self::leftRotate(intval($x13) ^ intval($x2), 8);
        $x8+= $x13; $x7 = self::leftRotate(intval($x7) ^ intval($x8), 7);

        $x3+= $x4; $x14 = self::leftRotate(intval($x14) ^ intval($x3), 16);
        $x9+= $x14; $x4 = self::leftRotate(intval($x4) ^ intval($x9), 12);
        $x3+= $x4; $x14 = self::leftRotate(intval($x14) ^ intval($x3), 8);
        $x9+= $x14; $x4 = self::leftRotate(intval($x4) ^ intval($x9), 7);
        // @codingStandardsIgnoreEnd

        $x0 += $z0;
        $x1 += $z1;
        $x2 += $z2;
        $x3 += $z3;
        $x4 += $z4;
        $x5 += $z5;
        $x6 += $z6;
        $x7 += $z7;
        $x8 += $z8;
        $x9 += $z9;
        $x10 += $z10;
        $x11 += $z11;
        $x12 += $z12;
        $x13 += $z13;
        $x14 += $z14;
        $x15 += $z15;

        return pack('V*', $x0, $x1, $x2, $x3, $x4, $x5, $x6, $x7, $x8, $x9, $x10, $x11, $x12, $x13, $x14, $x15);
    }
}
