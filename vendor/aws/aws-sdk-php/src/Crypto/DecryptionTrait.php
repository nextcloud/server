<?php
namespace Aws\Crypto;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\LimitStream;
use Psr\Http\Message\StreamInterface;

trait DecryptionTrait
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
     * @param MaterialsProviderInterface $provider A provider to supply and encrypt
     *                                             materials used in encryption.
     * @param MetadataEnvelope $envelope A storage envelope for encryption
     *                                   metadata to be read from.
     * @param array $cipherOptions Additional verification options.
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
        MaterialsProviderInterface $provider,
        MetadataEnvelope $envelope,
        array $cipherOptions = []
    ) {
        $cipherOptions['Iv'] = base64_decode(
            $envelope[MetadataEnvelope::IV_HEADER]
        );

        $cipherOptions['TagLength'] =
            $envelope[MetadataEnvelope::CRYPTO_TAG_LENGTH_HEADER] / 8;

        $cek = $provider->decryptCek(
            base64_decode(
                $envelope[MetadataEnvelope::CONTENT_KEY_V2_HEADER]
            ),
            json_decode(
                $envelope[MetadataEnvelope::MATERIALS_DESCRIPTION_HEADER],
                true
            )
        );
        $cipherOptions['KeySize'] = strlen($cek) * 8;
        $cipherOptions['Cipher'] = $this->getCipherFromAesName(
            $envelope[MetadataEnvelope::CONTENT_CRYPTO_SCHEME_HEADER]
        );

        $decryptionStream = $this->getDecryptingStream(
            $cipherText,
            $cek,
            $cipherOptions
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
                        : '',
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
