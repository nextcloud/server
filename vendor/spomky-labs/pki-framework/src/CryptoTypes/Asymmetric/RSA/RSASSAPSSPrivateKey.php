<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\Asymmetric\RSA;

use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Primitive\Integer;
use SpomkyLabs\Pki\ASN1\Type\UnspecifiedType;
use SpomkyLabs\Pki\CryptoEncoding\PEM;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Asymmetric\RSAPSSSSAEncryptionAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Feature\AlgorithmIdentifierType;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\PrivateKey;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\PublicKey;
use UnexpectedValueException;

/**
 * Implements PKCS #1 RSASSAPSSPrivateKey ASN.1 type.
 *
 * @see https://datatracker.ietf.org/doc/html/rfc8017#section-8.1
 */
final class RSASSAPSSPrivateKey extends PrivateKey
{
    /**
     * @param string $modulus Modulus
     * @param string $publicExponent Public exponent
     * @param string $privateExponent Private exponent
     * @param string $prime1 First prime factor
     * @param string $prime2 Second prime factor
     * @param string $exponent1 First factor exponent
     * @param string $exponent2 Second factor exponent
     * @param string $coefficient CRT coefficient of the second factor
     */
    private function __construct(
        private readonly string $modulus,
        private readonly string $publicExponent,
        private readonly string $privateExponent,
        private readonly string $prime1,
        private readonly string $prime2,
        private readonly string $exponent1,
        private readonly string $exponent2,
        private readonly string $coefficient
    ) {
    }

    public static function create(
        string $n,
        string $e,
        string $d,
        string $p,
        string $q,
        string $dp,
        string $dq,
        string $qi
    ): self {
        return new self($n, $e, $d, $p, $q, $dp, $dq, $qi);
    }

    /**
     * Initialize from ASN.1.
     */
    public static function fromASN1(Sequence $seq): self
    {
        $version = $seq->at(0)
            ->asInteger()
            ->intNumber();
        if ($version !== 0) {
            throw new UnexpectedValueException('Version must be 0.');
        }
        // helper function get integer from given index
        $get_int = static fn ($idx) => $seq->at($idx)
            ->asInteger()
            ->number();
        $n = $get_int(1);
        $e = $get_int(2);
        $d = $get_int(3);
        $p = $get_int(4);
        $q = $get_int(5);
        $dp = $get_int(6);
        $dq = $get_int(7);
        $qi = $get_int(8);
        return self::create($n, $e, $d, $p, $q, $dp, $dq, $qi);
    }

    /**
     * Initialize from DER data.
     */
    public static function fromDER(string $data): self
    {
        return self::fromASN1(UnspecifiedType::fromDER($data)->asSequence());
    }

    /**
     * @see PrivateKey::fromPEM()
     */
    public static function fromPEM(PEM $pem): self
    {
        $pk = parent::fromPEM($pem);
        if (! ($pk instanceof self)) {
            throw new UnexpectedValueException('Not an RSA private key.');
        }
        return $pk;
    }

    /**
     * Get modulus.
     *
     * @return string Base 10 integer
     */
    public function modulus(): string
    {
        return $this->modulus;
    }

    /**
     * Get public exponent.
     *
     * @return string Base 10 integer
     */
    public function publicExponent(): string
    {
        return $this->publicExponent;
    }

    /**
     * Get private exponent.
     *
     * @return string Base 10 integer
     */
    public function privateExponent(): string
    {
        return $this->privateExponent;
    }

    /**
     * Get first prime factor.
     *
     * @return string Base 10 integer
     */
    public function prime1(): string
    {
        return $this->prime1;
    }

    /**
     * Get second prime factor.
     *
     * @return string Base 10 integer
     */
    public function prime2(): string
    {
        return $this->prime2;
    }

    /**
     * Get first factor exponent.
     *
     * @return string Base 10 integer
     */
    public function exponent1(): string
    {
        return $this->exponent1;
    }

    /**
     * Get second factor exponent.
     *
     * @return string Base 10 integer
     */
    public function exponent2(): string
    {
        return $this->exponent2;
    }

    /**
     * Get CRT coefficient of the second factor.
     *
     * @return string Base 10 integer
     */
    public function coefficient(): string
    {
        return $this->coefficient;
    }

    public function algorithmIdentifier(): AlgorithmIdentifierType
    {
        return RSAPSSSSAEncryptionAlgorithmIdentifier::create();
    }

    /**
     * @return RSAPublicKey
     */
    public function publicKey(): PublicKey
    {
        return RSAPublicKey::create($this->modulus, $this->publicExponent);
    }

    /**
     * Generate ASN.1 structure.
     */
    public function toASN1(): Sequence
    {
        return Sequence::create(
            Integer::create(0),
            Integer::create($this->modulus),
            Integer::create($this->publicExponent),
            Integer::create($this->privateExponent),
            Integer::create($this->prime1),
            Integer::create($this->prime2),
            Integer::create($this->exponent1),
            Integer::create($this->exponent2),
            Integer::create($this->coefficient)
        );
    }

    public function toDER(): string
    {
        return $this->toASN1()
            ->toDER();
    }

    public function toPEM(): PEM
    {
        return PEM::create(PEM::TYPE_PRIVATE_KEY, $this->toDER());
    }
}
