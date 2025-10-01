<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\Signature;

use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Primitive\BitString;
use SpomkyLabs\Pki\ASN1\Type\Primitive\Integer;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;

/**
 * Implements ECDSA signature value.
 *
 * ECDSA signature is represented as a `ECDSA-Sig-Value` ASN.1 type.
 *
 * @see https://tools.ietf.org/html/rfc3278#section-8.2
 */
final class ECSignature extends Signature
{
    private function __construct(
        private readonly string $r,
        private readonly string $s
    ) {
    }

    public static function create(string $r, string $s): self
    {
        return new self($r, $s);
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(Sequence $seq): self
    {
        $r = $seq->at(0)
            ->asInteger()
            ->number();
        $s = $seq->at(1)
            ->asInteger()
            ->number();
        return self::create($r, $s);
    }

    /**
     * Initialize from DER.
     */
    public static function fromDER(string $data): self
    {
        return self::fromASN1(UnspecifiedType::fromDER($data)->asSequence());
    }

    /**
     * Get the r-value.
     *
     * @return string Base 10 integer string
     */
    public function r(): string
    {
        return $this->r;
    }

    /**
     * Get the s-value.
     *
     * @return string Base 10 integer string
     */
    public function s(): string
    {
        return $this->s;
    }

    /**
     * Generate ASN.1 structure.
     */
    public function toASN1(): Sequence
    {
        return Sequence::create(Integer::create($this->r), Integer::create($this->s));
    }

    /**
     * Get DER encoding of the signature.
     */
    public function toDER(): string
    {
        return $this->toASN1()
            ->toDER();
    }

    public function bitString(): BitString
    {
        return BitString::create($this->toDER());
    }
}
