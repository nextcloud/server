<?php
namespace Aws\Crypto;

use Aws\Exception\CryptoException;
use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;

/**
 * @internal Represents a stream of data to be gcm decrypted.
 */
class AesGcmDecryptingStream implements AesStreamInterface
{
    use StreamDecoratorTrait;

    private $aad;

    private $initializationVector;

    private $key;

    private $keySize;

    private $cipherText;

    private $tag;

    private $tagLength;

    /**
     * @var StreamInterface
     */
    private $stream;

    /**
     * @param StreamInterface $cipherText
     * @param string $key
     * @param string $initializationVector
     * @param string $tag
     * @param string $aad
     * @param int $tagLength
     * @param int $keySize
     */
    public function __construct(
        StreamInterface $cipherText,
        $key,
        $initializationVector,
        $tag,
        $aad = '',
        $tagLength = 128,
        $keySize = 256
    ) {
        $this->cipherText = $cipherText;
        $this->key = $key;
        $this->initializationVector = $initializationVector;
        $this->tag = $tag;
        $this->aad = $aad;
        $this->tagLength = $tagLength;
        $this->keySize = $keySize;
        // unsetting the property forces the first access to go through
        // __get().
        unset($this->stream);
    }

    public function getOpenSslName()
    {
        return "aes-{$this->keySize}-gcm";
    }

    public function getAesName()
    {
        return 'AES/GCM/NoPadding';
    }

    public function getCurrentIv()
    {
        return $this->initializationVector;
    }

    public function createStream()
    {

        $result = \openssl_decrypt(
            (string)$this->cipherText,
            $this->getOpenSslName(),
            $this->key,
            OPENSSL_RAW_DATA,
            $this->initializationVector,
            $this->tag,
            $this->aad
        );
        if ($result === false) {
            throw new CryptoException('The requested object could not be '
            . 'decrypted due to an invalid authentication tag.');
        }
        return Psr7\Utils::streamFor($result);

    }

    public function isWritable(): bool
    {
        return false;
    }
}
