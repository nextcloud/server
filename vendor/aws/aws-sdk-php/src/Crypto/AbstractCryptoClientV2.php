<?php
namespace Aws\Crypto;

use Aws\Crypto\Cipher\CipherMethod;
use GuzzleHttp\Psr7\Stream;

/**
 * @internal
 */
abstract class AbstractCryptoClientV2
{
    public static $supportedCiphers = ['gcm'];

    public static $supportedKeyWraps = [
        KmsMaterialsProviderV2::WRAP_ALGORITHM_NAME
    ];

    public static $supportedSecurityProfiles = ['V2', 'V2_AND_LEGACY'];

    public static $legacySecurityProfiles = ['V2_AND_LEGACY'];

    /**
     * Returns if the passed cipher name is supported for encryption by the SDK.
     *
     * @param string $cipherName The name of a cipher to verify is registered.
     *
     * @return bool If the cipher passed is in our supported list.
     */
    public static function isSupportedCipher($cipherName)
    {
        return in_array($cipherName, self::$supportedCiphers, true);
    }

    /**
     * Returns an identifier recognizable by `openssl_*` functions, such as
     * `aes-256-gcm`
     *
     * @param string $cipherName Name of the cipher being used for encrypting
     *                           or decrypting.
     * @param int $keySize Size of the encryption key, in bits, that will be
     *                     used.
     *
     * @return string
     */
    abstract protected function getCipherOpenSslName($cipherName, $keySize);

    /**
     * Constructs a CipherMethod for the given name, initialized with the other
     * data passed for use in encrypting or decrypting.
     *
     * @param string $cipherName Name of the cipher to generate for encrypting.
     * @param string $iv Base Initialization Vector for the cipher.
     * @param int $keySize Size of the encryption key, in bits, that will be
     *                     used.
     *
     * @return CipherMethod
     *
     * @internal
     */
    abstract protected function buildCipherMethod($cipherName, $iv, $keySize);

    /**
     * Performs a reverse lookup to get the openssl_* cipher name from the
     * AESName passed in from the MetadataEnvelope.
     *
     * @param $aesName
     *
     * @return string
     *
     * @internal
     */
    abstract protected function getCipherFromAesName($aesName);

    /**
     * Dependency to provide an interface for building an encryption stream for
     * data given cipher details, metadata, and materials to do so.
     *
     * @param Stream $plaintext Plain-text data to be encrypted using the
     *                          materials, algorithm, and data provided.
     * @param array $options Options for use in encryption.
     * @param MaterialsProviderV2 $provider A provider to supply and encrypt
     *                                      materials used in encryption.
     * @param MetadataEnvelope $envelope A storage envelope for encryption
     *                                   metadata to be added to.
     *
     * @return AesStreamInterface
     *
     * @internal
     */
    abstract public function encrypt(
        Stream $plaintext,
        array $options,
        MaterialsProviderV2 $provider,
        MetadataEnvelope $envelope
    );

    /**
     * Dependency to provide an interface for building a decryption stream for
     * cipher text given metadata and materials to do so.
     *
     * @param string $cipherText Plain-text data to be decrypted using the
     *                           materials, algorithm, and data provided.
     * @param MaterialsProviderInterface $provider A provider to supply and encrypt
     *                                             materials used in encryption.
     * @param MetadataEnvelope $envelope A storage envelope for encryption
     *                                   metadata to be read from.
     * @param array $options Options used for decryption.
     *
     * @return AesStreamInterface
     *
     * @internal
     */
    abstract public function decrypt(
        $cipherText,
        MaterialsProviderInterfaceV2 $provider,
        MetadataEnvelope $envelope,
        array $options = []
    );
}
