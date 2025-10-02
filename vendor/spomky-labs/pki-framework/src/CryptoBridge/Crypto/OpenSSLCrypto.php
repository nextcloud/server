<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoBridge\Crypto;

use RuntimeException;
use SpomkyLabs\Pki\CryptoBridge\Crypto;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Cipher\BlockCipherAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Cipher\CipherAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Cipher\RC2CBCAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Feature\SignatureAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\PrivateKeyInfo;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\PublicKeyInfo;
use SpomkyLabs\Pki\CryptoTypes\Signature\Signature;
use UnexpectedValueException;
use function array_key_exists;
use function mb_strlen;
use const OPENSSL_ALGO_MD4;
use const OPENSSL_ALGO_MD5;
use const OPENSSL_ALGO_SHA1;
use const OPENSSL_ALGO_SHA224;
use const OPENSSL_ALGO_SHA256;
use const OPENSSL_ALGO_SHA384;
use const OPENSSL_ALGO_SHA512;
use const OPENSSL_RAW_DATA;
use const OPENSSL_ZERO_PADDING;

/**
 * Crypto engine using OpenSSL extension.
 */
final class OpenSSLCrypto extends Crypto
{
    /**
     * Mapping from algorithm OID to OpenSSL signature method identifier.
     *
     * @internal
     *
     * @var array<string, int>
     */
    private const MAP_DIGEST_OID = [
        AlgorithmIdentifier::OID_MD4_WITH_RSA_ENCRYPTION => OPENSSL_ALGO_MD4,
        AlgorithmIdentifier::OID_MD5_WITH_RSA_ENCRYPTION => OPENSSL_ALGO_MD5,
        AlgorithmIdentifier::OID_SHA1_WITH_RSA_ENCRYPTION => OPENSSL_ALGO_SHA1,
        AlgorithmIdentifier::OID_SHA224_WITH_RSA_ENCRYPTION => OPENSSL_ALGO_SHA224,
        AlgorithmIdentifier::OID_SHA256_WITH_RSA_ENCRYPTION => OPENSSL_ALGO_SHA256,
        AlgorithmIdentifier::OID_SHA384_WITH_RSA_ENCRYPTION => OPENSSL_ALGO_SHA384,
        AlgorithmIdentifier::OID_SHA512_WITH_RSA_ENCRYPTION => OPENSSL_ALGO_SHA512,
        AlgorithmIdentifier::OID_ECDSA_WITH_SHA1 => OPENSSL_ALGO_SHA1,
        AlgorithmIdentifier::OID_ECDSA_WITH_SHA224 => OPENSSL_ALGO_SHA224,
        AlgorithmIdentifier::OID_ECDSA_WITH_SHA256 => OPENSSL_ALGO_SHA256,
        AlgorithmIdentifier::OID_ECDSA_WITH_SHA384 => OPENSSL_ALGO_SHA384,
        AlgorithmIdentifier::OID_ECDSA_WITH_SHA512 => OPENSSL_ALGO_SHA512,
    ];

    /**
     * Mapping from algorithm OID to OpenSSL cipher method name.
     *
     * @internal
     *
     * @var array<string, string>
     */
    private const MAP_CIPHER_OID = [
        AlgorithmIdentifier::OID_DES_CBC => 'des-cbc',
        AlgorithmIdentifier::OID_DES_EDE3_CBC => 'des-ede3-cbc',
        AlgorithmIdentifier::OID_AES_128_CBC => 'aes-128-cbc',
        AlgorithmIdentifier::OID_AES_192_CBC => 'aes-192-cbc',
        AlgorithmIdentifier::OID_AES_256_CBC => 'aes-256-cbc',
    ];

    public function sign(
        string $data,
        PrivateKeyInfo $privkey_info,
        SignatureAlgorithmIdentifier $algo
    ): Signature {
        $this->_checkSignatureAlgoAndKey($algo, $privkey_info->algorithmIdentifier());
        $result = openssl_sign($data, $signature, (string) $privkey_info->toPEM(), $this->_algoToDigest($algo));
        if ($result === false) {
            throw new RuntimeException('openssl_sign() failed: ' . $this->_getLastError());
        }
        return Signature::fromSignatureData($signature, $algo);
    }

    public function verify(
        string $data,
        Signature $signature,
        PublicKeyInfo $pubkey_info,
        SignatureAlgorithmIdentifier $algo
    ): bool {
        $this->_checkSignatureAlgoAndKey($algo, $pubkey_info->algorithmIdentifier());
        $result = openssl_verify(
            $data,
            $signature->bitString()
                ->string(),
            (string) $pubkey_info->toPEM(),
            $this->_algoToDigest($algo)
        );
        if ($result === -1) {
            throw new RuntimeException('openssl_verify() failed: ' . $this->_getLastError());
        }
        return $result === 1;
    }

    public function encrypt(string $data, string $key, CipherAlgorithmIdentifier $algo): string
    {
        $this->_checkCipherKeySize($algo, $key);
        $iv = $algo->initializationVector();
        $result = openssl_encrypt(
            $data,
            $this->_algoToCipher($algo),
            $key,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $iv
        );
        if ($result === false) {
            throw new RuntimeException('openssl_encrypt() failed: ' . $this->_getLastError());
        }
        return $result;
    }

    public function decrypt(string $data, string $key, CipherAlgorithmIdentifier $algo): string
    {
        $this->_checkCipherKeySize($algo, $key);
        $iv = $algo->initializationVector();
        $result = openssl_decrypt(
            $data,
            $this->_algoToCipher($algo),
            $key,
            OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING,
            $iv
        );
        if ($result === false) {
            throw new RuntimeException('openssl_decrypt() failed: ' . $this->_getLastError());
        }
        return $result;
    }

    /**
     * Validate cipher algorithm key size.
     */
    protected function _checkCipherKeySize(CipherAlgorithmIdentifier $algo, string $key): void
    {
        if ($algo instanceof BlockCipherAlgorithmIdentifier) {
            if (mb_strlen($key, '8bit') !== $algo->keySize()) {
                throw new UnexpectedValueException(
                    sprintf(
                        'Key length for %s must be %d, %d given.',
                        $algo->name(),
                        $algo->keySize(),
                        mb_strlen($key, '8bit')
                    )
                );
            }
        }
    }

    /**
     * Get last OpenSSL error message.
     */
    protected function _getLastError(): ?string
    {
        // pump error message queue
        $msg = null;
        while (false !== ($err = openssl_error_string())) {
            $msg = $err;
        }
        return $msg;
    }

    /**
     * Check that given signature algorithm supports key of given type.
     *
     * @param SignatureAlgorithmIdentifier $sig_algo Signature algorithm
     * @param AlgorithmIdentifier $key_algo Key algorithm
     */
    protected function _checkSignatureAlgoAndKey(
        SignatureAlgorithmIdentifier $sig_algo,
        AlgorithmIdentifier $key_algo
    ): void {
        if (! $sig_algo->supportsKeyAlgorithm($key_algo)) {
            throw new UnexpectedValueException(
                sprintf(
                    'Signature algorithm %s does not support key algorithm %s.',
                    $sig_algo->name(),
                    $key_algo->name()
                )
            );
        }
    }

    /**
     * Get OpenSSL digest method for given signature algorithm identifier.
     */
    protected function _algoToDigest(SignatureAlgorithmIdentifier $algo): int
    {
        $oid = $algo->oid();
        if (! array_key_exists($oid, self::MAP_DIGEST_OID)) {
            throw new UnexpectedValueException(sprintf('Digest method %s not supported.', $algo->name()));
        }
        return self::MAP_DIGEST_OID[$oid];
    }

    /**
     * Get OpenSSL cipher method for given cipher algorithm identifier.
     */
    protected function _algoToCipher(CipherAlgorithmIdentifier $algo): string
    {
        $oid = $algo->oid();
        if (array_key_exists($oid, self::MAP_CIPHER_OID)) {
            return self::MAP_CIPHER_OID[$oid];
        }
        if ($oid === AlgorithmIdentifier::OID_RC2_CBC) {
            if (! $algo instanceof RC2CBCAlgorithmIdentifier) {
                throw new UnexpectedValueException('Not an RC2-CBC algorithm.');
            }
            return $this->_rc2AlgoToCipher($algo);
        }
        throw new UnexpectedValueException(sprintf('Cipher method %s not supported.', $algo->name()));
    }

    /**
     * Get OpenSSL cipher method for given RC2 algorithm identifier.
     */
    protected function _rc2AlgoToCipher(RC2CBCAlgorithmIdentifier $algo): string
    {
        return match ($algo->effectiveKeyBits()) {
            128 => 'rc2-cbc',
            64 => 'rc2-64-cbc',
            40 => 'rc2-40-cbc',
            default => throw new UnexpectedValueException($algo->effectiveKeyBits() . ' bit RC2 not supported.'),
        };
    }
}
