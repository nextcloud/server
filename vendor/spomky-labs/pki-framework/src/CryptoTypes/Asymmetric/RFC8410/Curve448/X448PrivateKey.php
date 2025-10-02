<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\Asymmetric\RFC8410\Curve448;

use LogicException;
use SpomkyLabs\Pki\ASN1\Type\Primitive\OctetString;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Asymmetric\X448AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Feature\AlgorithmIdentifierType;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\PublicKey;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\RFC8410\RFC8410PrivateKey;
use UnexpectedValueException;
use function mb_strlen;

/**
 * Implements an intermediary class to store X448 private key.
 *
 * @see https://tools.ietf.org/html/rfc8410
 */
final class X448PrivateKey extends RFC8410PrivateKey
{
    /**
     * @param string $private_key Private key data
     * @param null|string $public_key Public key data
     */
    protected function __construct(string $private_key, ?string $public_key = null)
    {
        if (mb_strlen($private_key, '8bit') !== 56) {
            throw new UnexpectedValueException('X448 private key must be exactly 56 bytes.');
        }
        if (isset($public_key) && mb_strlen($public_key, '8bit') !== 56) {
            throw new UnexpectedValueException('X448 public key must be exactly 56 bytes.');
        }
        parent::__construct($private_key, $public_key);
    }

    public static function create(string $private_key, ?string $public_key = null): self
    {
        return new self($private_key, $public_key);
    }

    /**
     * Initialize from `CurvePrivateKey` OctetString.
     *
     * @param OctetString $str Private key data wrapped into OctetString
     * @param null|string $public_key Optional public key data
     */
    public static function fromOctetString(OctetString $str, ?string $public_key = null): self
    {
        return self::create($str->string(), $public_key);
    }

    public function algorithmIdentifier(): AlgorithmIdentifierType
    {
        return X448AlgorithmIdentifier::create();
    }

    public function publicKey(): PublicKey
    {
        if (! $this->hasPublicKey()) {
            throw new LogicException('Public key not set.');
        }
        return X448PublicKey::create($this->_publicKeyData);
    }
}
