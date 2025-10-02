<?php

declare(strict_types=1);

namespace Cose\Key;

use InvalidArgumentException;
use SpomkyLabs\Pki\ASN1\Type\Constructed\Sequence;
use SpomkyLabs\Pki\ASN1\Type\Primitive\BitString;
use SpomkyLabs\Pki\ASN1\Type\Primitive\Integer;
use SpomkyLabs\Pki\ASN1\Type\Primitive\ObjectIdentifier;
use SpomkyLabs\Pki\ASN1\Type\Primitive\OctetString;
use SpomkyLabs\Pki\ASN1\Type\Tagged\ExplicitlyTaggedType;
use function array_key_exists;
use function in_array;
use function is_int;

/**
 * @final
 * @see \Cose\Tests\Key\Ec2KeyTest
 */
class Ec2Key extends Key
{
    final public const CURVE_P256 = 1;

    final public const CURVE_P256K = 8;

    final public const CURVE_P384 = 2;

    final public const CURVE_P521 = 3;

    final public const CURVE_NAME_P256 = 'P-256';

    final public const CURVE_NAME_P256K = 'P-256K';

    final public const CURVE_NAME_P384 = 'P-384';

    final public const CURVE_NAME_P521 = 'P-521';

    final public const DATA_CURVE = -1;

    final public const DATA_X = -2;

    final public const DATA_Y = -3;

    final public const DATA_D = -4;

    private const SUPPORTED_CURVES_INT = [self::CURVE_P256, self::CURVE_P256K, self::CURVE_P384, self::CURVE_P521];

    private const SUPPORTED_CURVES_NAMES = [
        self::CURVE_NAME_P256,
        self::CURVE_NAME_P256K,
        self::CURVE_NAME_P384,
        self::CURVE_NAME_P521,
    ];

    private const NAMED_CURVE_OID = [
        self::CURVE_P256 => '1.2.840.10045.3.1.7',
        // NIST P-256 / secp256r1
        self::CURVE_P256K => '1.3.132.0.10',
        // NIST P-256K / secp256k1
        self::CURVE_P384 => '1.3.132.0.34',
        // NIST P-384 / secp384r1
        self::CURVE_P521 => '1.3.132.0.35',
        // NIST P-521 / secp521r1
    ];

    private const CURVE_KEY_LENGTH = [
        self::CURVE_P256 => 32,
        self::CURVE_P256K => 32,
        self::CURVE_P384 => 48,
        self::CURVE_P521 => 66,
        self::CURVE_NAME_P256 => 32,
        self::CURVE_NAME_P256K => 32,
        self::CURVE_NAME_P384 => 48,
        self::CURVE_NAME_P521 => 66,
    ];

    /**
     * @param array<int|string, mixed> $data
     */
    public function __construct(array $data)
    {
        foreach ([self::DATA_CURVE, self::TYPE] as $key) {
            if (is_numeric($data[$key])) {
                $data[$key] = (int) $data[$key];
            }
        }
        parent::__construct($data);
        if ($data[self::TYPE] !== self::TYPE_EC2 && $data[self::TYPE] !== self::TYPE_NAME_EC2) {
            throw new InvalidArgumentException('Invalid EC2 key. The key type does not correspond to an EC2 key');
        }
        if (! isset($data[self::DATA_CURVE], $data[self::DATA_X], $data[self::DATA_Y])) {
            throw new InvalidArgumentException('Invalid EC2 key. The curve or the "x/y" coordinates are missing');
        }
        if (mb_strlen((string) $data[self::DATA_X], '8bit') !== self::CURVE_KEY_LENGTH[$data[self::DATA_CURVE]]) {
            throw new InvalidArgumentException('Invalid length for x coordinate');
        }
        if (mb_strlen((string) $data[self::DATA_Y], '8bit') !== self::CURVE_KEY_LENGTH[$data[self::DATA_CURVE]]) {
            throw new InvalidArgumentException('Invalid length for y coordinate');
        }
        if (is_int($data[self::DATA_CURVE])) {
            if (! in_array($data[self::DATA_CURVE], self::SUPPORTED_CURVES_INT, true)) {
                throw new InvalidArgumentException('The curve is not supported');
            }
        } elseif (! in_array($data[self::DATA_CURVE], self::SUPPORTED_CURVES_NAMES, true)) {
            throw new InvalidArgumentException('The curve is not supported');
        }
    }

    /**
     * @param array<int|string, mixed> $data
     */
    public static function create(array $data): self
    {
        return new self($data);
    }

    public function toPublic(): self
    {
        $data = $this->getData();
        unset($data[self::DATA_D]);

        return new self($data);
    }

    public function x(): string
    {
        return $this->get(self::DATA_X);
    }

    public function y(): string
    {
        return $this->get(self::DATA_Y);
    }

    public function isPrivate(): bool
    {
        return array_key_exists(self::DATA_D, $this->getData());
    }

    public function d(): string
    {
        if (! $this->isPrivate()) {
            throw new InvalidArgumentException('The key is not private.');
        }
        return $this->get(self::DATA_D);
    }

    public function curve(): int|string
    {
        return $this->get(self::DATA_CURVE);
    }

    public function asPEM(): string
    {
        if ($this->isPrivate()) {
            $der = Sequence::create(
                Integer::create(1),
                OctetString::create($this->d()),
                ExplicitlyTaggedType::create(0, ObjectIdentifier::create($this->getCurveOid())),
                ExplicitlyTaggedType::create(1, BitString::create($this->getUncompressedCoordinates())),
            );

            return $this->pem('EC PRIVATE KEY', $der->toDER());
        }

        $der = Sequence::create(
            Sequence::create(
                ObjectIdentifier::create('1.2.840.10045.2.1'),
                ObjectIdentifier::create($this->getCurveOid())
            ),
            BitString::create($this->getUncompressedCoordinates())
        );

        return $this->pem('PUBLIC KEY', $der->toDER());
    }

    public function getUncompressedCoordinates(): string
    {
        return "\x04" . $this->x() . $this->y();
    }

    private function getCurveOid(): string
    {
        return self::NAMED_CURVE_OID[$this->curve()];
    }

    private function pem(string $type, string $der): string
    {
        return sprintf("-----BEGIN %s-----\n", mb_strtoupper($type)) .
            chunk_split(base64_encode($der), 64, "\n") .
            sprintf("-----END %s-----\n", mb_strtoupper($type));
    }
}
