<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\Asymmetric\EC;

use InvalidArgumentException;
use LogicException;
use RuntimeException;
use SpomkyLabs\Pki\ASN1\Type\Primitive\BitString;
use SpomkyLabs\Pki\ASN1\Type\Primitive\Integer;
use SpomkyLabs\Pki\ASN1\Type\Primitive\OctetString;
use SpomkyLabs\Pki\CryptoEncoding\PEM;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\AlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Asymmetric\ECPublicKeyAlgorithmIdentifier;
use SpomkyLabs\Pki\CryptoTypes\AlgorithmIdentifier\Feature\AlgorithmIdentifierType;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\PublicKey;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\PublicKeyInfo;
use UnexpectedValueException;
use function array_key_exists;
use function in_array;
use function mb_strlen;
use function ord;

/**
 * Implements elliptic curve public key type as specified by RFC 5480.
 *
 * @see https://tools.ietf.org/html/rfc5480#section-2.2
 */
final class ECPublicKey extends PublicKey
{
    /**
     * Elliptic curve public key.
     */
    private readonly string $ecPoint;

    /**
     * @param string $ecPoint ECPoint
     * @param null|string $namedCurve Named curve OID
     */
    private function __construct(
        string $ecPoint,
        private readonly ?string $namedCurve
    ) {
        // first octet must be 0x04 for uncompressed form, and 0x02 or 0x03
        // for compressed form.
        if (($ecPoint === '') || ! in_array(ord($ecPoint[0]), [2, 3, 4], true)) {
            throw new InvalidArgumentException('Invalid ECPoint.');
        }
        $this->ecPoint = $ecPoint;
    }

    public static function create(string $ecPoint, ?string $namedCurve = null): self
    {
        return new self($ecPoint, $namedCurve);
    }

    /**
     * Initialize from curve point coordinates.
     *
     * @param int|string $x X coordinate as a base10 number
     * @param int|string $y Y coordinate as a base10 number
     * @param null|string $named_curve Named curve OID
     * @param null|int $bits Size of *p* in bits
     */
    public static function fromCoordinates(
        int|string $x,
        int|string $y,
        ?string $named_curve = null,
        ?int $bits = null
    ): self {
        // if bitsize is not explicitly set, check from supported curves
        if (! isset($bits) && isset($named_curve)) {
            $bits = self::_curveSize($named_curve);
        }
        $mlen = null;
        if (isset($bits)) {
            $mlen = (int) ceil($bits / 8);
        }
        $x_os = ECConversion::integerToOctetString(Integer::create($x), $mlen)->string();
        $y_os = ECConversion::integerToOctetString(Integer::create($y), $mlen)->string();
        $ec_point = "\x4{$x_os}{$y_os}";
        return self::create($ec_point, $named_curve);
    }

    /**
     * @see PublicKey::fromPEM()
     */
    public static function fromPEM(PEM $pem): self
    {
        if ($pem->type() !== PEM::TYPE_PUBLIC_KEY) {
            throw new UnexpectedValueException('Not a public key.');
        }
        $pki = PublicKeyInfo::fromDER($pem->data());
        $algo = $pki->algorithmIdentifier();
        if ($algo->oid() !== AlgorithmIdentifier::OID_EC_PUBLIC_KEY
            || ! ($algo instanceof ECPublicKeyAlgorithmIdentifier)) {
            throw new UnexpectedValueException('Not an elliptic curve key.');
        }
        // ECPoint is directly mapped into public key data
        return self::create($pki->publicKeyData()->string(), $algo->namedCurve());
    }

    /**
     * Get ECPoint value.
     */
    public function ECPoint(): string
    {
        return $this->ecPoint;
    }

    /**
     * Get curve point coordinates.
     *
     * @return string[] Tuple of X and Y coordinates as base-10 numbers
     */
    public function curvePoint(): array
    {
        return array_map(static fn ($str) => ECConversion::octetsToNumber($str), $this->curvePointOctets());
    }

    /**
     * Get curve point coordinates in octet string representation.
     *
     * @return string[] tuple of X and Y field elements as a string
     */
    public function curvePointOctets(): array
    {
        if ($this->isCompressed()) {
            throw new RuntimeException('EC point compression not supported.');
        }
        $str = mb_substr($this->ecPoint, 1, null, '8bit');
        $length = (int) floor(mb_strlen($str, '8bit') / 2);
        if ($length < 1) {
            throw new RuntimeException('Invalid EC point.');
        }
        [$x, $y] = mb_str_split($str, $length, '8bit');
        return [$x, $y];
    }

    /**
     * Whether ECPoint is in compressed form.
     */
    public function isCompressed(): bool
    {
        $c = ord($this->ecPoint[0]);
        return $c !== 4;
    }

    /**
     * Whether named curve is present.
     */
    public function hasNamedCurve(): bool
    {
        return isset($this->namedCurve);
    }

    /**
     * Get named curve OID.
     */
    public function namedCurve(): string
    {
        if (! $this->hasNamedCurve()) {
            throw new LogicException('namedCurve not set.');
        }
        return $this->namedCurve;
    }

    public function algorithmIdentifier(): AlgorithmIdentifierType
    {
        return ECPublicKeyAlgorithmIdentifier::create($this->namedCurve());
    }

    /**
     * Generate ASN.1 element.
     */
    public function toASN1(): OctetString
    {
        return OctetString::create($this->ecPoint);
    }

    public function toDER(): string
    {
        return $this->toASN1()
            ->toDER();
    }

    /**
     * @see https://tools.ietf.org/html/rfc5480#section-2.2
     */
    public function subjectPublicKey(): BitString
    {
        // ECPoint is directly mapped to subjectPublicKey
        return BitString::create($this->ecPoint);
    }

    /**
     * Get the curve size *p* in bits.
     *
     * @param string $oid Curve OID
     */
    private static function _curveSize(string $oid): ?int
    {
        if (! array_key_exists($oid, ECPublicKeyAlgorithmIdentifier::MAP_CURVE_TO_SIZE)) {
            return null;
        }
        return ECPublicKeyAlgorithmIdentifier::MAP_CURVE_TO_SIZE[$oid];
    }
}
