<?php
namespace Aws\Crypto;

use GuzzleHttp\Psr7\StreamDecoratorTrait;
use \LogicException;
use Psr\Http\Message\StreamInterface;
use Aws\Crypto\Cipher\CipherMethod;

/**
 * @internal Represents a stream of data to be decrypted with passed cipher.
 */
class AesDecryptingStream implements AesStreamInterface
{
    const BLOCK_SIZE = 16; // 128 bits

    use StreamDecoratorTrait;

    /**
     * @var string
     */
    private $buffer = '';

    /**
     * @var CipherMethod
     */
    private $cipherMethod;

    /**
     * @var string
     */
    private $key;

    /**
     * @var StreamInterface
     */
    private $stream;

    /**
     * @param StreamInterface $cipherText
     * @param string $key
     * @param CipherMethod $cipherMethod
     */
    public function __construct(
        StreamInterface $cipherText,
        $key,
        CipherMethod $cipherMethod
    ) {
        $this->stream = $cipherText;
        $this->key = $key;
        $this->cipherMethod = clone $cipherMethod;
    }

    public function getOpenSslName()
    {
        return $this->cipherMethod->getOpenSslName();
    }

    public function getAesName()
    {
        return $this->cipherMethod->getAesName();
    }

    public function getCurrentIv()
    {
        return $this->cipherMethod->getCurrentIv();
    }

    public function getSize()
    {
        $plainTextSize = $this->stream->getSize();

        if ($this->cipherMethod->requiresPadding()) {
            // PKCS7 padding requires that between 1 and self::BLOCK_SIZE be
            // added to the plaintext to make it an even number of blocks. The
            // plaintext is between strlen($cipherText) - self::BLOCK_SIZE and
            // strlen($cipherText) - 1
            return null;
        }

        return $plainTextSize;
    }

    public function isWritable()
    {
        return false;
    }

    public function read($length)
    {
        if ($length > strlen($this->buffer)) {
            $this->buffer .= $this->decryptBlock(
                self::BLOCK_SIZE * ceil(($length - strlen($this->buffer)) / self::BLOCK_SIZE)
            );
        }

        $data = substr($this->buffer, 0, $length);
        $this->buffer = substr($this->buffer, $length);

        return $data ? $data : '';
    }

    public function seek($offset, $whence = SEEK_SET)
    {
        if ($offset === 0 && $whence === SEEK_SET) {
            $this->buffer = '';
            $this->cipherMethod->seek(0, SEEK_SET);
            $this->stream->seek(0, SEEK_SET);
        } else {
            throw new LogicException('AES encryption streams only support being'
                . ' rewound, not arbitrary seeking.');
        }
    }

    private function decryptBlock($length)
    {
        if ($this->stream->eof()) {
            return '';
        }

        $cipherText = '';
        do {
            $cipherText .= $this->stream->read($length - strlen($cipherText));
        } while (strlen($cipherText) < $length && !$this->stream->eof());

        $options = OPENSSL_RAW_DATA;
        if (!$this->stream->eof()
            && $this->stream->getSize() !== $this->stream->tell()
        ) {
            $options |= OPENSSL_ZERO_PADDING;
        }

        $plaintext = openssl_decrypt(
            $cipherText,
            $this->cipherMethod->getOpenSslName(),
            $this->key,
            $options,
            $this->cipherMethod->getCurrentIv()
        );

        $this->cipherMethod->update($cipherText);

        return $plaintext;
    }
}
