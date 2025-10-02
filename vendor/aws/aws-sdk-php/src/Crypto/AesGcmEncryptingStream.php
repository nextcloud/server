<?php
namespace Aws\Crypto;

use GuzzleHttp\Psr7;
use GuzzleHttp\Psr7\StreamDecoratorTrait;
use Psr\Http\Message\StreamInterface;

/**
 * @internal Represents a stream of data to be gcm encrypted.
 */
class AesGcmEncryptingStream implements AesStreamInterface, AesStreamInterfaceV2
{
    use StreamDecoratorTrait;

    private $aad;

    private $initializationVector;

    private $key;

    private $keySize;

    private $plaintext;

    private $tag = '';

    private $tagLength;

    /**
     * @var StreamInterface
     */
    private $stream;

    /**
     * Same as non-static 'getAesName' method, allowing calls in a static
     * context.
     *
     * @return string
     */
    public static function getStaticAesName()
    {
        return 'AES/GCM/NoPadding';
    }

    /**
     * @param StreamInterface $plaintext
     * @param string $key
     * @param string $initializationVector
     * @param string $aad
     * @param int $tagLength
     * @param int $keySize
     */
    public function __construct(
        StreamInterface $plaintext,
        $key,
        $initializationVector,
        $aad = '',
        $tagLength = 16,
        $keySize = 256
    ) {

        $this->plaintext = $plaintext;
        $this->key = $key;
        $this->initializationVector = $initializationVector;
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

    /**
     * Same as static method and retained for backwards compatibility
     *
     * @return string
     */
    public function getAesName()
    {
        return self::getStaticAesName();
    }

    public function getCurrentIv()
    {
        return $this->initializationVector;
    }

    public function createStream()
    {
        return Psr7\Utils::streamFor(\openssl_encrypt(
            (string)$this->plaintext,
            $this->getOpenSslName(),
            $this->key,
            OPENSSL_RAW_DATA,
            $this->initializationVector,
            $this->tag,
            $this->aad,
            $this->tagLength
        ));
    }

    /**
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    public function isWritable(): bool
    {
        return false;
    }
}
