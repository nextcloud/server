<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\Asymmetric\RFC8410;

use LogicException;
use SpomkyLabs\Pki\ASN1\Type\Primitive\BitString;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\PublicKey;

/**
 * Implements an intermediary object to store a public key using Curve25519 or Curve448 as defined by RFC 8410.
 *
 * Public keys described in RFC 8410 may only be encoded as `SubjectPublicKeyInfo`.
 *
 * @see https://tools.ietf.org/html/rfc8410
 */
abstract class RFC8410PublicKey extends PublicKey
{
    /**
     * @param string $publicKey Public key data
     */
    protected function __construct(
        private readonly string $publicKey
    ) {
    }

    public function toDER(): string
    {
        throw new LogicException("RFC 8410 public key doesn't have a DER encoding.");
    }

    public function subjectPublicKey(): BitString
    {
        return BitString::create($this->publicKey);
    }
}
