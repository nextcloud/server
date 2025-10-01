<?php
namespace Aws\Crypto;

abstract class MaterialsProvider implements MaterialsProviderInterface
{
    private static $supportedKeySizes = [
        128 => true,
        192 => true,
        256 => true,
    ];

    /**
     * Returns if the requested size is supported by AES.
     *
     * @param int $keySize Size of the requested key in bits.
     *
     * @return bool
     */
    public static function isSupportedKeySize($keySize)
    {
        return isset(self::$supportedKeySizes[$keySize]);
    }

    /**
     * Performs further initialization of the MaterialsProvider based on the
     * data inside the MetadataEnvelope.
     *
     * @param MetadataEnvelope $envelope A storage envelope for encryption
     *                                   metadata to be read from.
     *
     * @return MaterialsProvider
     *
     * @throws \RuntimeException Thrown when there is an empty or improperly
     *                           formed materials description in the envelope.
     *
     * @internal
     */
    abstract public function fromDecryptionEnvelope(MetadataEnvelope $envelope);

    /**
     * Returns the material description for this Provider so it can be verified
     * by encryption mechanisms.
     *
     * @return string
     */
    abstract public function getMaterialsDescription();

    /**
     * Returns the wrap algorithm name for this Provider.
     *
     * @return string
     */
    abstract public function getWrapAlgorithmName();

    /**
     * Takes a content encryption key (CEK) and description to return an
     * encrypted key according to the Provider's specifications.
     *
     * @param string $unencryptedCek Key for use in encrypting other data
     *                               that itself needs to be encrypted by the
     *                               Provider.
     * @param string $materialDescription Material Description for use in
     *                                    encrypting the $cek.
     *
     * @return string
     */
    abstract public function encryptCek($unencryptedCek, $materialDescription);

    /**
     * Takes an encrypted content encryption key (CEK) and material description
     * for use decrypting the key according to the Provider's specifications.
     *
     * @param string $encryptedCek Encrypted key to be decrypted by the Provider
     *                             for use decrypting other data.
     * @param string $materialDescription Material Description for use in
     *                                    encrypting the $cek.
     *
     * @return string
     */
    abstract public function decryptCek($encryptedCek, $materialDescription);

    /**
     * @param string $keySize Length of a cipher key in bits for generating a
     *                        random content encryption key (CEK).
     *
     * @return string
     */
    public function generateCek($keySize)
    {
        return openssl_random_pseudo_bytes($keySize / 8);
    }

    /**
     * @param string $openSslName Cipher OpenSSL name to use for generating
     *                            an initialization vector.
     *
     * @return string
     */
    public function generateIv($openSslName)
    {
        return openssl_random_pseudo_bytes(
            openssl_cipher_iv_length($openSslName)
        );
    }
}
