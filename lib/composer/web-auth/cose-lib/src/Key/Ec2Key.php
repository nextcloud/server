<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2020 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Cose\Key;

use function array_key_exists;
use Assert\Assertion;
use FG\ASN1\ExplicitlyTaggedObject;
use FG\ASN1\Universal\BitString;
use FG\ASN1\Universal\Integer;
use FG\ASN1\Universal\ObjectIdentifier;
use FG\ASN1\Universal\OctetString;
use FG\ASN1\Universal\Sequence;

class Ec2Key extends Key
{
    public const CURVE_P256 = 1;
    public const CURVE_P256K = 8;
    public const CURVE_P384 = 2;
    public const CURVE_P521 = 3;

    public const DATA_CURVE = -1;
    public const DATA_X = -2;
    public const DATA_Y = -3;
    public const DATA_D = -4;

    private const SUPPORTED_CURVES = [
        self::CURVE_P256,
        self::CURVE_P256K,
        self::CURVE_P384,
        self::CURVE_P521,
    ];

    private const NAMED_CURVE_OID = [
        self::CURVE_P256 => '1.2.840.10045.3.1.7', // NIST P-256 / secp256r1
        self::CURVE_P256K => '1.3.132.0.10', // NIST P-256K / secp256k1
        self::CURVE_P384 => '1.3.132.0.34', // NIST P-384 / secp384r1
        self::CURVE_P521 => '1.3.132.0.35', // NIST P-521 / secp521r1
    ];

    private const CURVE_KEY_LENGTH = [
        self::CURVE_P256 => 32,
        self::CURVE_P256K => 32,
        self::CURVE_P384 => 48,
        self::CURVE_P521 => 66,
    ];

    public function __construct(array $data)
    {
        parent::__construct($data);
        Assertion::eq($data[self::TYPE], self::TYPE_EC2, 'Invalid EC2 key. The key type does not correspond to an EC2 key');
        Assertion::keyExists($data, self::DATA_CURVE, 'Invalid EC2 key. The curve is missing');
        Assertion::keyExists($data, self::DATA_X, 'Invalid EC2 key. The x coordinate is missing');
        Assertion::keyExists($data, self::DATA_Y, 'Invalid EC2 key. The y coordinate is missing');
        Assertion::length($data[self::DATA_X], self::CURVE_KEY_LENGTH[$data[self::DATA_CURVE]], 'Invalid length for x coordinate', null, '8bit');
        Assertion::length($data[self::DATA_Y], self::CURVE_KEY_LENGTH[$data[self::DATA_CURVE]], 'Invalid length for y coordinate', null, '8bit');
        Assertion::inArray((int) $data[self::DATA_CURVE], self::SUPPORTED_CURVES, 'The curve is not supported');
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
        Assertion::true($this->isPrivate(), 'The key is not private');

        return $this->get(self::DATA_D);
    }

    public function curve(): int
    {
        return (int) $this->get(self::DATA_CURVE);
    }

    public function asPEM(): string
    {
        if ($this->isPrivate()) {
            $der = new Sequence(
                new Integer(1),
                new OctetString(bin2hex($this->d())),
                new ExplicitlyTaggedObject(0, new ObjectIdentifier($this->getCurveOid())),
                new ExplicitlyTaggedObject(1, new BitString(bin2hex($this->getUncompressedCoordinates())))
            );

            return $this->pem('EC PRIVATE KEY', $der->getBinary());
        }

        $der = new Sequence(
            new Sequence(
                new ObjectIdentifier('1.2.840.10045.2.1'),
                new ObjectIdentifier($this->getCurveOid())
            ),
            new BitString(bin2hex($this->getUncompressedCoordinates()))
        );

        return $this->pem('PUBLIC KEY', $der->getBinary());
    }

    public function getUncompressedCoordinates(): string
    {
        return "\x04".$this->x().$this->y();
    }

    private function getCurveOid(): string
    {
        return self::NAMED_CURVE_OID[$this->curve()];
    }

    private function pem(string $type, string $der): string
    {
        return sprintf("-----BEGIN %s-----\n", mb_strtoupper($type)).
            chunk_split(base64_encode($der), 64, "\n").
            sprintf("-----END %s-----\n", mb_strtoupper($type));
    }
}
