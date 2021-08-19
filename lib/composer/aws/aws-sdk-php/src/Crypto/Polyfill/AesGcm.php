<?php
namespace Aws\Crypto\Polyfill;

use Aws\Exception\CryptoPolyfillException;
use InvalidArgumentException;
use RangeException;

/**
 * Class AesGcm
 *
 * This provides a polyfill for AES-GCM encryption/decryption, with caveats:
 *
 * 1. Only 96-bit nonces are supported.
 * 2. Only 128-bit authentication tags are supported. (i.e. non-truncated)
 *
 * Supports AES key sizes of 128-bit, 192-bit, and 256-bit.
 *
 * @package Aws\Crypto\Polyfill
 */
class AesGcm
{
    use NeedsTrait;

    /** @var Key $aesKey */
    private $aesKey;

    /** @var int $keySize */
    private $keySize;

    /** @var int $blockSize */
    protected $blockSize = 8192;

    /**
     * AesGcm constructor.
     *
     * @param Key $aesKey
     * @param int $keySize
     * @param int $blockSize
     *
     * @throws CryptoPolyfillException
     * @throws InvalidArgumentException
     * @throws RangeException
     */
    public function __construct(Key $aesKey, $keySize = 256, $blockSize = 8192)
    {
        /* Preconditions: */
        self::needs(
            \in_array($keySize, [128, 192, 256], true),
            "Key size must be 128, 192, or 256 bits; {$keySize} given",
            InvalidArgumentException::class
        );
        self::needs(
            \is_int($blockSize) && $blockSize > 0 && $blockSize <= PHP_INT_MAX,
            'Block size must be a positive integer.',
            RangeException::class
        );
        self::needs(
            $aesKey->length() << 3 === $keySize,
            'Incorrect key size; expected ' . $keySize . ' bits, got ' . ($aesKey->length() << 3) . ' bits.'
        );
        $this->aesKey = $aesKey;
        $this->keySize = $keySize;
    }

    /**
     * Encryption interface for AES-GCM
     *
     * @param string $plaintext  Message to be encrypted
     * @param string $nonce      Number to be used ONCE
     * @param Key $key           AES Key
     * @param string $aad        Additional authenticated data
     * @param string &$tag       Reference to variable to hold tag
     * @param int $keySize       Key size (bits)
     * @param int $blockSize     Block size (bytes) -- How much memory to buffer
     * @return string
     * @throws InvalidArgumentException
     */
    public static function encrypt(
        $plaintext,
        $nonce,
        Key $key,
        $aad,
        &$tag,
        $keySize = 256,
        $blockSize = 8192
    ) {
        self::needs(
            self::strlen($nonce) === 12,
            'Nonce must be exactly 12 bytes',
            InvalidArgumentException::class
        );

        $encryptor = new AesGcm($key, $keySize, $blockSize);
        list($aadLength, $gmac) = $encryptor->gmacInit($nonce, $aad);

        $ciphertext = \openssl_encrypt(
            $plaintext,
            "aes-{$encryptor->keySize}-ctr",
            $key->get(),
            OPENSSL_NO_PADDING | OPENSSL_RAW_DATA,
            $nonce . "\x00\x00\x00\x02"
        );

        /* Calculate auth tag in a streaming fashion to minimize memory usage: */
        $ciphertextLength = self::strlen($ciphertext);
        for ($i = 0; $i < $ciphertextLength; $i += $encryptor->blockSize) {
            $cBlock = new ByteArray(self::substr($ciphertext, $i, $encryptor->blockSize));
            $gmac->update($cBlock);
        }
        $tag = $gmac->finish($aadLength, $ciphertextLength)->toString();
        return $ciphertext;
    }

    /**
     * Decryption interface for AES-GCM
     *
     * @param string $ciphertext Ciphertext to decrypt
     * @param string $nonce      Number to be used ONCE
     * @param Key $key           AES key
     * @param string $aad        Additional authenticated data
     * @param string $tag        Authentication tag
     * @param int $keySize       Key size (bits)
     * @param int $blockSize     Block size (bytes) -- How much memory to buffer
     * @return string            Plaintext
     *
     * @throws CryptoPolyfillException
     * @throws InvalidArgumentException
     */
    public static function decrypt(
        $ciphertext,
        $nonce,
        Key $key,
        $aad,
        &$tag,
        $keySize = 256,
        $blockSize = 8192
    ) {
        /* Precondition: */
        self::needs(
            self::strlen($nonce) === 12,
            'Nonce must be exactly 12 bytes',
            InvalidArgumentException::class
        );

        $encryptor = new AesGcm($key, $keySize, $blockSize);
        list($aadLength, $gmac) = $encryptor->gmacInit($nonce, $aad);

        /* Calculate auth tag in a streaming fashion to minimize memory usage: */
        $ciphertextLength = self::strlen($ciphertext);
        for ($i = 0; $i < $ciphertextLength; $i += $encryptor->blockSize) {
            $cBlock = new ByteArray(self::substr($ciphertext, $i, $encryptor->blockSize));
            $gmac->update($cBlock);
        }

        /* Validate auth tag in constant-time: */
        $calc = $gmac->finish($aadLength, $ciphertextLength);
        $expected = new ByteArray($tag);
        self::needs($calc->equals($expected), 'Invalid authentication tag');

        /* Return plaintext if auth tag check succeeded: */
        return \openssl_decrypt(
            $ciphertext,
            "aes-{$encryptor->keySize}-ctr",
            $key->get(),
            OPENSSL_NO_PADDING | OPENSSL_RAW_DATA,
            $nonce . "\x00\x00\x00\x02"
        );
    }

    /**
     * Initialize a Gmac object with the nonce and this object's key.
     *
     * @param string $nonce     Must be exactly 12 bytes long.
     * @param string|null $aad
     * @return array
     */
    protected function gmacInit($nonce, $aad = null)
    {
        $gmac = new Gmac(
            $this->aesKey,
            $nonce . "\x00\x00\x00\x01",
            $this->keySize
        );
        $aadBlock = new ByteArray($aad);
        $aadLength = $aadBlock->count();
        $gmac->update($aadBlock);
        $gmac->flush();
        return [$aadLength, $gmac];
    }

    /**
     * Calculate the length of a string.
     *
     * Uses the appropriate PHP function without being brittle to
     * mbstring.func_overload.
     *
     * @param string $string
     * @return int
     */
    protected static function strlen($string)
    {
        if (\is_callable('\\mb_strlen')) {
            return (int) \mb_strlen($string, '8bit');
        }
        return (int) \strlen($string);
    }

    /**
     * Return a substring of the provided string.
     *
     * Uses the appropriate PHP function without being brittle to
     * mbstring.func_overload.
     *
     * @param string $string
     * @param int $offset
     * @param int|null $length
     * @return string
     */
    protected static function substr($string, $offset = 0, $length = null)
    {
        if (\is_callable('\\mb_substr')) {
            return \mb_substr($string, $offset, $length, '8bit');
        } elseif (!\is_null($length)) {
            return \substr($string, $offset, $length);
        }
        return \substr($string, $offset);
    }
}
