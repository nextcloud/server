<?php
namespace Aws\Crypto;

abstract class MaterialsProviderV2 implements MaterialsProviderInterfaceV2
{
    private static $supportedKeySizes = [
        128 => true,
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
     * Returns the wrap algorithm name for this Provider.
     *
     * @return string
     */
    abstract public function getWrapAlgorithmName();

    /**
     * Takes an encrypted content encryption key (CEK) and material description
     * for use decrypting the key according to the Provider's specifications.
     *
     * @param string $encryptedCek Encrypted key to be decrypted by the Provider
     *                             for use decrypting other data.
     * @param string $materialDescription Material Description for use in
     *                                    decrypting the CEK.
     * @param string $options Options for use in decrypting the CEK.
     *
     * @return string
     */
    abstract public function decryptCek($encryptedCek, $materialDescription, $options);

    /**
     * @param string $keySize Length of a cipher key in bits for generating a
     *                        random content encryption key (CEK).
     * @param array $context Context map needed for key encryption
     * @param array $options Additional options to be used in CEK generation
     *
     * @return array
     */
    abstract public function generateCek($keySize, $context, $options);

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
