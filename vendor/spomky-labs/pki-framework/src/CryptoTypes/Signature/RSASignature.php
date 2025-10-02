<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\Signature;

use SpomkyLabs\Pki\ASN1\Type\Primitive\BitString;
use function strval;

/**
 * Implements RSA signature value.
 *
 * @todo Implement signature parsing
 *
 * @see https://tools.ietf.org/html/rfc2313#section-10
 */
final class RSASignature extends Signature
{
    /**
     * Signature value *S*.
     */
    private ?string $_signature = null;

    protected function __construct()
    {
    }

    /**
     * Initialize from RSA signature *S*.
     *
     * Signature value *S* is the result of last step in RSA signature process defined in PKCS #1.
     *
     * @see https://tools.ietf.org/html/rfc2313#section-10.1.4
     *
     * @param string $signature Signature bits
     *
     * @return self
     */
    public static function fromSignatureString(string $signature): Signature
    {
        $obj = new self();
        $obj->_signature = strval($signature);
        return $obj;
    }

    public function bitString(): BitString
    {
        return BitString::create($this->_signature);
    }
}
