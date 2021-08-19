<?php
namespace Aws\Crypto\Polyfill;

/**
 * Class Gmac
 *
 * @package Aws\Crypto\Polyfill
 */
class Gmac
{
    use NeedsTrait;

    const BLOCK_SIZE = 16;

    /** @var ByteArray $buf */
    protected $buf;

    /** @var int $bufLength */
    protected $bufLength = 0;

    /** @var ByteArray $h */
    protected $h;

    /** @var ByteArray $hf */
    protected $hf;

    /** @var Key $key */
    protected $key;

    /** @var ByteArray $x */
    protected $x;

    /**
     * Gmac constructor.
     *
     * @param Key $aesKey
     * @param string $nonce
     * @param int $keySize
     */
    public function __construct(Key $aesKey, $nonce, $keySize = 256)
    {
        $this->buf = new ByteArray(16);
        $this->h = new ByteArray(
            \openssl_encrypt(
                \str_repeat("\0", 16),
                "aes-{$keySize}-ecb",
                $aesKey->get(),
                OPENSSL_RAW_DATA | OPENSSL_NO_PADDING
            )
        );
        $this->key = $aesKey;
        $this->x = new ByteArray(16);
        $this->hf = new ByteArray(
            \openssl_encrypt(
                $nonce,
                "aes-{$keySize}-ecb",
                $aesKey->get(),
                OPENSSL_RAW_DATA | OPENSSL_NO_PADDING
            )
        );
    }

    /**
     * Update the object with some data.
     *
     * This method mutates this Gmac object.
     *
     * @param ByteArray $blocks
     * @return self
     */
    public function update(ByteArray $blocks)
    {
        if (($blocks->count() + $this->bufLength) < self::BLOCK_SIZE) {
            // Write to internal buffer until we reach enough to write.
            $this->buf->set($blocks, $this->bufLength);
            $this->bufLength += $blocks->count();
            return $this;
        }

        // Process internal buffer first.
        if ($this->bufLength > 0) {
            // 0 <= state.buf_len < BLOCK_SIZE is an invariant
            $tmp = new ByteArray(self::BLOCK_SIZE);
            $tmp->set($this->buf->slice(0, $this->bufLength));
            $remainingBlockLength = self::BLOCK_SIZE - $this->bufLength;
            $tmp->set($blocks->slice(0, $remainingBlockLength), $this->bufLength);
            $blocks = $blocks->slice($remainingBlockLength);
            $this->bufLength = 0;
            $this->x = $this->blockMultiply($this->x->exclusiveOr($tmp), $this->h);
        }

        // Process full blocks.
        $numBlocks = $blocks->count() >> 4;
        for ($i = 0; $i < $numBlocks; ++$i) {
            $tmp = $blocks->slice($i << 4, self::BLOCK_SIZE);
            $this->x = $this->blockMultiply($this->x->exclusiveOr($tmp), $this->h);
        }
        $last = $numBlocks << 4;

        // Zero-fill buffer
        for ($i = 0; $i < 16; ++$i) {
            $this->buf[$i] = 0;
        }
        // Feed leftover into buffer.
        if ($last < $blocks->count()) {
            $tmp = $blocks->slice($last);
            $this->buf->set($tmp);
            $this->bufLength += ($blocks->count() - $last);
        }
        return $this;
    }

    /**
     * Finish processing the authentication tag.
     *
     * This method mutates this Gmac object (effectively resetting it).
     *
     * @param int $aadLength
     * @param int $ciphertextLength
     * @return ByteArray
     */
    public function finish($aadLength, $ciphertextLength)
    {
        $lengthBlock = new ByteArray(16);
        $state = $this->flush();

        // AES-GCM expects bit lengths, not byte lengths.
        $lengthBlock->set(ByteArray::enc32be($aadLength >> 29), 0);
        $lengthBlock->set(ByteArray::enc32be($aadLength << 3), 4);
        $lengthBlock->set(ByteArray::enc32be($ciphertextLength >> 29), 8);
        $lengthBlock->set(ByteArray::enc32be($ciphertextLength << 3), 12);

        $state->update($lengthBlock);
        $output = $state->x->exclusiveOr($state->hf);

        // Zeroize the internal values as a best-effort.
        $state->buf->zeroize();
        $state->x->zeroize();
        $state->h->zeroize();
        $state->hf->zeroize();
        return $output;
    }

    /**
     * Get a specific bit from the provided array, at the given index.
     *
     * [01234567], 8+[01234567], 16+[01234567], ...
     *
     * @param ByteArray $x
     * @param int $i
     * @return int
     */
    protected function bit(ByteArray $x, $i)
    {
        $byte = $i >> 3;
        return ($x[$byte] >> ((7 - $i) & 7)) & 1;
    }

    /**
     * Galois Field Multiplication
     *
     * This function is the critical path that must be constant-time in order to
     * avoid timing side-channels against AES-GCM.
     *
     * The contents of each are always calculated, regardless of the branching
     * condition, to prevent another kind of timing leak.
     *
     * @param ByteArray $x
     * @param ByteArray $y
     * @return ByteArray
     */
    protected function blockMultiply(ByteArray $x, ByteArray $y)
    {
        static $fieldPolynomial = null;
        if (!$fieldPolynomial) {
            $fieldPolynomial = new ByteArray([
                0xe1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0
            ]);
        }
        self::needs($x->count() === 16, 'Argument 1 must be a ByteArray of exactly 16 bytes');
        self::needs($y->count() === 16, 'Argument 2 must be a ByteArray of exactly 16 bytes');

        $v = clone $y;
        $z = new ByteArray(16);

        for ($i = 0; $i < 128; ++$i) {
            // if ($b) $z = $z->exclusiveOr($v);
            $b = $this->bit($x, $i);
            $z = ByteArray::select(
                $b,
                $z->exclusiveOr($v),
                $z
            );

            // if ($b) $v = $v->exclusiveOr($fieldPolynomial);
            $b = $v[15] & 1;
            $v = $v->rshift();
            $v = ByteArray::select(
                $b,
                $v->exclusiveOr($fieldPolynomial),
                $v
            );
        }
        return $z;
    }

    /**
     * Finish processing any leftover bytes in the internal buffer.
     *
     * @return self
     */
    public function flush()
    {
        if ($this->bufLength !== 0) {
            $this->x = $this->blockMultiply(
                $this->x->exclusiveOr($this->buf),
                $this->h
            );
            $this->bufLength = 0;
        }
        return $this;
    }
}
