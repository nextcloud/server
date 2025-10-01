<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Cipher;

use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\SpecificAlgorithmIdentifier;
use UnexpectedValueException;
use function mb_strlen;

/**
 * Base class for cipher algorithm identifiers.
 */
abstract class CipherAlgorithmIdentifier extends SpecificAlgorithmIdentifier
{
    protected function __construct(
        string $oid,
        protected string $initializationVector
    ) {
        $this->_checkIVSize($initializationVector);
        parent::__construct($oid);
    }

    /**
     * Get key size in bytes.
     */
    abstract public function keySize(): int;

    /**
     * Get the initialization vector size in bytes.
     */
    abstract public function ivSize(): int;

    /**
     * Get initialization vector.
     */
    public function initializationVector(): string
    {
        return $this->initializationVector;
    }

    /**
     * Get copy of the object with given initialization vector.
     *
     * @param string $iv Initialization vector or null to remove
     */
    public function withInitializationVector(string $iv): self
    {
        $this->_checkIVSize($iv);
        $obj = clone $this;
        $obj->initializationVector = $iv;
        return $obj;
    }

    /**
     * Check that initialization vector size is valid for the cipher.
     */
    protected function _checkIVSize(string $iv): void
    {
        if (mb_strlen($iv, '8bit') !== $this->ivSize()) {
            throw new UnexpectedValueException('Invalid IV size.');
        }
    }
}
