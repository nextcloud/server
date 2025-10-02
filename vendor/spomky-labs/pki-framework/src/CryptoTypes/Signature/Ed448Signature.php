<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\Signature;

use InvalidArgumentException;
use SpomkyLabs\Pki\ASN1\Type\Primitive\BitString;
use function mb_strlen;

/**
 * Implements Ed448 signature value.
 *
 * @todo Implement signature parsing
 *
 * @see https://tools.ietf.org/html/rfc8032#section-5.2.6
 */
final class Ed448Signature extends Signature
{
    /**
     * Signature value.
     */
    private readonly string $signature;

    private function __construct(string $signature)
    {
        if (mb_strlen($signature, '8bit') !== 114) {
            throw new InvalidArgumentException('Ed448 signature must be 114 octets.');
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
