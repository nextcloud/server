<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier;

use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Asymmetric\ECPublicKeyAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Asymmetric\Ed25519AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Asymmetric\Ed448AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Asymmetric\RSAEncryptionAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Asymmetric\X25519AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Asymmetric\X448AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Cipher\AES128CBCAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Cipher\AES192CBCAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Cipher\AES256CBCAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Cipher\DESCBCAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Cipher\DESEDE3CBCAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Cipher\RC2CBCAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Hash\HMACWithSHA1AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Hash\HMACWithSHA224AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Hash\HMACWithSHA256AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Hash\HMACWithSHA384AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Hash\HMACWithSHA512AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Hash\MD5AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Hash\SHA1AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Hash\SHA224AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Hash\SHA256AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Hash\SHA384AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Hash\SHA512AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Signature\ECDSAWithSHA1AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Signature\ECDSAWithSHA224AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Signature\ECDSAWithSHA256AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Signature\ECDSAWithSHA384AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Signature\ECDSAWithSHA512AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Signature\MD2WithRSAEncryptionAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Signature\MD4WithRSAEncryptionAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Signature\MD5WithRSAEncryptionAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Signature\SHA1WithRSAEncryptionAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Signature\SHA224WithRSAEncryptionAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Signature\SHA256WithRSAEncryptionAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Signature\SHA384WithRSAEncryptionAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Signature\SHA512WithRSAEncryptionAlgorithmIdentifier;
use function array_key_exists;

/**
 * Factory class to parse AlgorithmIdentifier ASN.1 types to specific algorithm identifier objects.
 *
 * Additional providers may be added to the process to support algorithm identifiers that are implemented in external
 * libraries.
 */
final class AlgorithmIdentifierFactory
{
    /**
     * Mapping for algorithm identifiers provided by this library.
     *
     * @internal
     *
     * @var array<string, string>
     */
    private const MAP_OID_TO_CLASS = [
        AlgorithmIdentifier::OID_RSA_ENCRYPTION => RSAEncryptionAlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_EC_PUBLIC_KEY => ECPublicKeyAlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_X25519 => X25519AlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_X448 => X448AlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_ED25519 => Ed25519AlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_ED448 => Ed448AlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_DES_CBC => DESCBCAlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_DES_EDE3_CBC => DESEDE3CBCAlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_RC2_CBC => RC2CBCAlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_AES_128_CBC => AES128CBCAlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_AES_192_CBC => AES192CBCAlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_AES_256_CBC => AES256CBCAlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_HMAC_WITH_SHA1 => HMACWithSHA1AlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_HMAC_WITH_SHA224 => HMACWithSHA224AlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_HMAC_WITH_SHA256 => HMACWithSHA256AlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_HMAC_WITH_SHA384 => HMACWithSHA384AlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_HMAC_WITH_SHA512 => HMACWithSHA512AlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_MD5 => MD5AlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_SHA1 => SHA1AlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_SHA224 => SHA224AlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_SHA256 => SHA256AlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_SHA384 => SHA384AlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_SHA512 => SHA512AlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_MD2_WITH_RSA_ENCRYPTION => MD2WithRSAEncryptionAlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_MD4_WITH_RSA_ENCRYPTION => MD4WithRSAEncryptionAlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_MD5_WITH_RSA_ENCRYPTION => MD5WithRSAEncryptionAlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_SHA1_WITH_RSA_ENCRYPTION => SHA1WithRSAEncryptionAlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_SHA224_WITH_RSA_ENCRYPTION => SHA224WithRSAEncryptionAlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_SHA256_WITH_RSA_ENCRYPTION => SHA256WithRSAEncryptionAlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_SHA384_WITH_RSA_ENCRYPTION => SHA384WithRSAEncryptionAlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_SHA512_WITH_RSA_ENCRYPTION => SHA512WithRSAEncryptionAlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_ECDSA_WITH_SHA1 => ECDSAWithSHA1AlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_ECDSA_WITH_SHA224 => ECDSAWithSHA224AlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_ECDSA_WITH_SHA256 => ECDSAWithSHA256AlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_ECDSA_WITH_SHA384 => ECDSAWithSHA384AlgorithmIdentifier::class,
        AlgorithmIdentifier::OID_ECDSA_WITH_SHA512 => ECDSAWithSHA512AlgorithmIdentifier::class,
    ];

    /**
     * Additional algorithm identifier providers.
     *
     * @var AlgorithmIdentifierProvider[]
     */
    private readonly array $_additionalProviders;

    /**
     * @param AlgorithmIdentifierProvider ...$providers Additional providers
     */
    private function __construct(AlgorithmIdentifierProvider ...$providers)
    {
        $this->_additionalProviders = $providers;
    }

    public static function create(AlgorithmIdentifierProvider ...$providers): self
    {
        return new self(...$providers);
    }

    /**
     * Get the name of a class that implements algorithm identifier for given OID.
     *
     * @param string $oid Object identifier in dotted format
     *
     * @return null|string Fully qualified class name or null if not supported
     */
    public function getClass(string $oid): ?string
    {
        // if OID is provided by this factory
        if (array_key_exists($oid, self::MAP_OID_TO_CLASS)) {
            return self::MAP_OID_TO_CLASS[$oid];
        }
        // try additional providers
        foreach ($this->_additionalProviders as $provider) {
            if ($provider->supportsOID($oid)) {
                return $provider->getClassByOID($oid);
            }
        }
        return null;
    }

    /**
     * Parse AlgorithmIdentifier from an ASN.1 sequence.
     */
    public function parse(Sequence $seq): AlgorithmIdentifier
    {
        $oid = $seq->at(0)
            ->asObjectIdentifier()
            ->oid();
        $params = $seq->has(1) ? $seq->at(1) : null;
        $cls = $this->getClass($oid);
        if ($cls !== null) {
            return $cls::fromASN1Params($params);
        }

        return GenericAlgorithmIdentifier::create($oid, $params);
    }
}
