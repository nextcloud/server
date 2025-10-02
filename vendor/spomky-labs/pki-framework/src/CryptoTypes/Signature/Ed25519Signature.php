<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\Signature;

use InvalidArgumentException;
use SpomkyLabs\Pki\ASN1\Type\Primitive\BitString;
use function mb_strlen;

/**
 * Implements Ed25519 signature value.
 *
 * @todo Implement signature parsing
 *
 * @see https://tools.ietf.org/html/rfc8032#section-5.1.6
 */
final class Ed25519Signature extends Signature
{
    /**
     * Signature value.
     */
    private readonly string $signature;

    private function __construct(string $signature)
    {
        if (mb_strlen($signature, '8bit') !== 64) {
            throw new InvalidArgumentException('Ed25519 signature must be 64 octets.');
        }
        $this->signature = $signature;
    }

    public static function create(string $signature): self
    {
        return new self($signature);
    }

    public function bitString(): BitString
    {
        return BitString::create($this->signature);
    }
}
