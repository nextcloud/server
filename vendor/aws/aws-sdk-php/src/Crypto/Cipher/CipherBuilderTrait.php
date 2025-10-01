<?php
namespace Aws\Crypto\Cipher;

use Aws\Exception\CryptoException;

trait CipherBuilderTrait
{
    /**
     * Returns an identifier recognizable by `openssl_*` functions, such as
     * `aes-256-cbc` or `aes-128-ctr`.
     *
     * @param string $cipherName Name of the cipher being used for encrypting
     *                           or decrypting.
     * @param int $keySize Size of the encryption key, in bits, that will be
     *                     used.
     *
     * @return string
     */
    protected function getCipherOpenSslName($cipherName, $keySize)
    {
        return "aes-{$keySize}-{$cipherName}";
    }

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
    protected function buildCipherMethod($cipherName, $iv, $keySize)
    {
        switch ($cipherName) {
            case 'cbc':
                return new Cbc(
                    $iv,
                    $keySize
                );
            default:
                return null;
        }
    }

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
    protected function getCipherFromAesName($aesName)
    {
        switch ($aesName) {
            case 'AES/GCM/NoPadding':
                return 'gcm';
            case 'AES/CBC/PKCS5Padding':
                return 'cbc';
            default:
                throw new CryptoException('Unrecognized or unsupported'
                    . ' AESName for reverse lookup.');
        }
    }
}