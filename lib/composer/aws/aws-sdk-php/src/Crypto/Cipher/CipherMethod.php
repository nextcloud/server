<?php
namespace Aws\Crypto\Cipher;

interface CipherMethod
{
    /**
     * Returns an identifier recognizable by `openssl_*` functions, such as
     * `aes-256-cbc` or `aes-128-ctr`.
     *
     * @return string
     */
    public function getOpenSslName();

    /**
     * Returns an AES recognizable name, such as 'AES/GCM/NoPadding'.
     *
     * @return string
     */
    public function getAesName();

    /**
     * Returns the IV that should be used to initialize the next block in
     * encrypt or decrypt.
     *
     * @return string
     */
    public function getCurrentIv();

    /**
     * Indicates whether the cipher method used with this IV requires padding
     * the final block to make sure the plaintext is evenly divisible by the
     * block size.
     *
     * @return boolean
     */
    public function requiresPadding();

    /**
     * Adjust the return of this::getCurrentIv to reflect a seek performed on
     * the encryption stream using this IV object.
     *
     * @param int $offset
     * @param int $whence
     *
     * @throws LogicException   Thrown if the requested seek is not supported by
     *                          this IV implementation. For example, a CBC IV
     *                          only supports a full rewind ($offset === 0 &&
     *                          $whence === SEEK_SET)
     */
    public function seek($offset, $whence = SEEK_SET);

    /**
     * Take account of the last cipher text block to adjust the return of
     * this::getCurrentIv
     *
     * @param string $cipherTextBlock
     */
    public function update($cipherTextBlock);
}
