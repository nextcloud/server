<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Cipher;

/**
 * Base class for block cipher algorithm identifiers.
 */
abstract class BlockCipherAlgorithmIdentifier extends CipherAlgorithmIdentifier
{
    /**
     * Get block size in bytes.
     */
    abstract public function blockSize(): int;
}
