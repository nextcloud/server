<?php
namespace Aws\Crypto;

use Aws\Exception\CryptoException;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\LimitStream;
use Psr\Http\Message\StreamInterface;

trait DecryptionTraitV2
{
    /**
     * Dependency to reverse lookup the openssl_* cipher name from the AESName
     * in the MetadataEnvelope.
     *
     * @param $aesName
     *
     * @return string
     *
     * @internal
     */
    abstract protected function getCipherFromAesName($aesName);

    /**
     * Dependency to generate a CipherMethod from a set of inputs for loading
     * in to an AesDecryptingStream.
     *
     * @param string $cipherName Name of the cipher to generate for decrypting.
     * @param string $iv Base Initialization Vector for the cipher.
     * @param int $keySize Size of the encryption key, in bits, that will be
     *                     used.
     *
     * @return Cipher\CipherMethod
     *
     * @internal
     */
    abstract protected function buildCipherMethod($cipherName, $iv, $keySize);

    /**
     * Builds an AesStreamInterface using cipher options loaded from the
     * MetadataEnvelope and MaterialsProvider. Can decrypt data from both the
     * legacy and V2 encryption client workflows.
     *
     * @param string $cipherText Plain-text data to be encrypted using the
     *                           materials, algorithm, and data provided.
     * @param MaterialsProviderInterfaceV2 $provider A provider to supply and encrypt
     *                                             materials used in encryption.
     * @param MetadataEnvelope $envelope A storage envelope for encryption
     *                                   metadata to be read from.
     * @param array $options Options used for decryption.
     *
     * @return AesStreamInterface
     *
     * @throws \InvalidArgumentException Thrown when a value in $cipherOptions
     *                                   is not valid.
     *
     * @internal
     */
    public function decrypt(
        $cipherText,
        MaterialsProviderInterfaceV2 $provider,
        MetadataEnvelope $envelope,
        array $options = []
    ) {
        $options['@CipherOptions'] = !empty($options['@CipherOptions'])
            ? $options['@CipherOptions']
            : [];
        $options['@CipherOptions']['Iv'] = base64_decode(
            $envelope[MetadataEnvelope::IV_HEADER]
        );

        $options['@CipherOptions']['TagLength'] =
            $envelope[MetadataEnvelope::CRYPTO_TAG_LENGTH_HEADER] / 8;

        $cek = $provider->decryptCek(
            base64_decode(
                $envelope[MetadataEnvelope::CONTENT_KEY_V2_HEADER]
            ),
            json_decode(
                $envelope[MetadataEnvelope::MATERIALS_DESCRIPTION_HEADER],
                true
            ),
            $options
        );
        $options['@CipherOptions']['KeySize'] = strlen($cek) * 8;
        $options['@CipherOptions']['Cipher'] = $this->getCipherFromAesName(
            $envelope[MetadataEnvelope::CONTENT_CRYPTO_SCHEME_HEADER]
        );

        $this->validateOptionsAndEnvelope($options, $envelope);

        $decryptionStream = $this->getDecryptingStream(
            $cipherText,
            $cek,
            $options['@CipherOptions']
        );
        unset($cek);

        return $decryptionStream;
    }

    private function getTagFromCiphertextStream(
        StreamInterface $cipherText,
        $tagLength
    ) {
        $cipherTextSize = $cipherText->getSize();
        if ($cipherTextSize == null || $cipherTextSize <= 0) {
            throw new \RuntimeException('Cannot decrypt a stream of unknown'
                . ' size.');
        }
        return (string) new LimitStream(
            $cipherText,
            $tagLength,
            $cipherTextSize - $tagLength
        );
    }

    private function getStrippedCiphertextStream(
        StreamInterface $cipherText,
        $tagLength
    ) {
        $cipherTextSize = $cipherText->getSize();
        if ($cipherTextSize == null || $cipherTextSize <= 0) {
            throw new \RuntimeException('Cannot decrypt a stream of unknown'
                . ' size.');
        }
        return new LimitStream(
            $cipherText,
            $cipherTextSize - $tagLength,
            0
        );
    }

    private function validateOptionsAndEnvelope($options, $envelope)
    {
        $allowedCiphers = AbstractCryptoClientV2::$supportedCiphers;
        $allowedKeywraps = AbstractCryptoClientV2::$supportedKeyWraps;
        if ($options['@SecurityProfile'] == 'V2_AND_LEGACY') {
            $allowedCiphers = array_unique(array_merge(
                $allowedCiphers,
                AbstractCryptoClient::$supportedCiphers
            ));
            $allowedKeywraps = array_unique(array_merge(
                $allowedKeywraps,
                AbstractCryptoClient::$supportedKeyWraps
            ));
        }

        $v1SchemaException = new CryptoException("The requested object is encrypted"
            . " with V1 encryption schemas that have been disabled by"
            . " client configuration @SecurityProfile=V2. Retry with"
            . " V2_AND_LEGACY enabled or reencrypt the object.");

        if (!in_array($options['@CipherOptions']['Cipher'], $allowedCiphers)) {
            if (in_array($options['@CipherOptions']['Cipher'], AbstractCryptoClient::$supportedCiphers)) {
                throw $v1SchemaException;
            }
            throw new CryptoException("The requested object is encrypted with"
                . " the cipher '{$options['@CipherOptions']['Cipher']}', which is not"
                . " supported for decryption with the selected security profile."
                . " This profile allows decryption with: "
                . implode(", ", $allowedCiphers));
        }
        if (!in_array(
            $envelope[MetadataEnvelope::KEY_WRAP_ALGORITHM_HEADER],
            $allowedKeywraps
        )) {
            if (in_array(
                $envelope[MetadataEnvelope::KEY_WRAP_ALGORITHM_HEADER],
                AbstractCryptoClient::$supportedKeyWraps)
            ) {
                throw $v1SchemaException;
            }
            throw new CryptoException("The requested object is encrypted with"
                . " the keywrap schema '{$envelope[MetadataEnvelope::KEY_WRAP_ALGORITHM_HEADER]}',"
                . " which is not supported for decryption with the current security"
                . " profile.");
        }

        $matdesc = json_decode(
            $envelope[MetadataEnvelope::MATERIALS_DESCRIPTION_HEADER],
            true
        );
        if (isset($matdesc['aws:x-amz-cek-alg'])
            && $envelope[MetadataEnvelope::CONTENT_CRYPTO_SCHEME_HEADER] !==
                $matdesc['aws:x-amz-cek-alg']
        ) {
            throw new CryptoException("There is a mismatch in specified content"
                . " encryption algrithm between the materials description value"
                . " and the metadata envelope value: {$matdesc['aws:x-amz-cek-alg']}"
                . " vs. {$envelope[MetadataEnvelope::CONTENT_CRYPTO_SCHEME_HEADER]}.");
        }
    }

    /**
     * Generates a stream that wraps the cipher text with the proper cipher and
     * uses the content encryption key (CEK) to decrypt the data when read.
     *
     * @param string $cipherText Plain-text data to be encrypted using the
     *                           materials, algorithm, and data provided.
     * @param string $cek A content encryption key for use by the stream for
     *                    encrypting the plaintext data.
     * @param array $cipherOptions Options for use in determining the cipher to
     *                             be used for encrypting data.
     *
     * @return AesStreamInterface
     *
     * @internal
     */
    protected function getDecryptingStream(
        $cipherText,
        $cek,
        $cipherOptions
    ) {
        $cipherTextStream = Psr7\Utils::streamFor($cipherText);
        switch ($cipherOptions['Cipher']) {
            case 'gcm':
                $cipherOptions['Tag'] = $this->getTagFromCiphertextStream(
                        $cipherTextStream,
                        $cipherOptions['TagLength']
                    );

                return new AesGcmDecryptingStream(
                    $this->getStrippedCiphertextStream(
                        $cipherTextStream,
                        $cipherOptions['TagLength']
                    ),
                    $cek,
                    $cipherOptions['Iv'],
                    $cipherOptions['Tag'],
                    $cipherOptions['Aad'] = isset($cipherOptions['Aad'])
                        ? $cipherOptions['Aad']
                        : null,
                    $cipherOptions['TagLength'] ?: null,
                    $cipherOptions['KeySize']
                );
            default:
                $cipherMethod = $this->buildCipherMethod(
                    $cipherOptions['Cipher'],
                    $cipherOptions['Iv'],
                    $cipherOptions['KeySize']
                );
                return new AesDecryptingStream(
                    $cipherTextStream,
                    $cek,
                    $cipherMethod
                );
        }
    }
}
