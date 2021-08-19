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
use Brick\Math\BigInteger;
use FG\ASN1\Universal\BitString;
use FG\ASN1\Universal\Integer;
use FG\ASN1\Universal\NullObject;
use FG\ASN1\Universal\ObjectIdentifier;
use FG\ASN1\Universal\Sequence;
use InvalidArgumentException;

class RsaKey extends Key
{
    public const DATA_N = -1;
    public const DATA_E = -2;
    public const DATA_D = -3;
    public const DATA_P = -4;
    public const DATA_Q = -5;
    public const DATA_DP = -6;
    public const DATA_DQ = -7;
    public const DATA_QI = -8;
    public const DATA_OTHER = -9;
    public const DATA_RI = -10;
    public const DATA_DI = -11;
    public const DATA_TI = -12;

    public function __construct(array $data)
    {
        parent::__construct($data);
        Assertion::eq($data[self::TYPE], self::TYPE_RSA, 'Invalid RSA key. The key type does not correspond to a RSA key');
        Assertion::keyExists($data, self::DATA_N, 'Invalid RSA key. The modulus is missing');
        Assertion::keyExists($data, self::DATA_E, 'Invalid RSA key. The exponent is missing');
    }

    public function n(): string
    {
        return $this->get(self::DATA_N);
    }

    public function e(): string
    {
        return $this->get(self::DATA_E);
    }

    public function d(): string
    {
        Assertion::true($this->isPrivate(), 'The key is not private.');

        return $this->get(self::DATA_D);
    }

    public function p(): string
    {
        Assertion::true($this->isPrivate(), 'The key is not private.');

        return $this->get(self::DATA_P);
    }

    public function q(): string
    {
        Assertion::true($this->isPrivate(), 'The key is not private.');

        return $this->get(self::DATA_Q);
    }

    public function dP(): string
    {
        Assertion::true($this->isPrivate(), 'The key is not private.');

        return $this->get(self::DATA_DP);
    }

    public function dQ(): string
    {
        Assertion::true($this->isPrivate(), 'The key is not private.');

        return $this->get(self::DATA_DQ);
    }

    public function QInv(): string
    {
        Assertion::true($this->isPrivate(), 'The key is not private.');

        return $this->get(self::DATA_QI);
    }

    public function other(): array
    {
        Assertion::true($this->isPrivate(), 'The key is not private.');

        return $this->get(self::DATA_OTHER);
    }

    public function rI(): string
    {
        Assertion::true($this->isPrivate(), 'The key is not private.');

        return $this->get(self::DATA_RI);
    }

    public function dI(): string
    {
        Assertion::true($this->isPrivate(), 'The key is not private.');

        return $this->get(self::DATA_DI);
    }

    public function tI(): string
    {
        Assertion::true($this->isPrivate(), 'The key is not private.');

        return $this->get(self::DATA_TI);
    }

    public function hasPrimes(): bool
    {
        return $this->has(self::DATA_P) && $this->has(self::DATA_Q);
    }

    public function primes(): array
    {
        return [
            $this->p(),
            $this->q(),
        ];
    }

    public function hasExponents(): bool
    {
        return $this->has(self::DATA_DP) && $this->has(self::DATA_DQ);
    }

    public function exponents(): array
    {
        return [
            $this->dP(),
            $this->dQ(),
        ];
    }

    public function hasCoefficient(): bool
    {
        return $this->has(self::DATA_QI);
    }

    public function isPublic(): bool
    {
        return !$this->isPrivate();
    }

    public function isPrivate(): bool
    {
        return array_key_exists(self::DATA_D, $this->getData());
    }

    public function asPem(): string
    {
        Assertion::false($this->isPrivate(), 'Unsupported for private keys.');
        $bitSring = new Sequence(
            new Integer($this->fromBase64ToInteger($this->n())),
            new Integer($this->fromBase64ToInteger($this->e()))
        );

        $der = new Sequence(
            new Sequence(
                new ObjectIdentifier('1.2.840.113549.1.1.1'),
                new NullObject()
            ),
            new BitString(bin2hex($bitSring->getBinary()))
        );

        return $this->pem('PUBLIC KEY', $der->getBinary());
    }

    private function fromBase64ToInteger(string $value): string
    {
        $data = unpack('H*', $value);
        if (false === $data) {
            throw new InvalidArgumentException('Unable to convert to an integer');
        }

        $hex = current($data);

        return BigInteger::fromBase($hex, 16)->toBase(10);
    }

    private function pem(string $type, string $der): string
    {
        return sprintf("-----BEGIN %s-----\n", mb_strtoupper($type)).
            chunk_split(base64_encode($der), 64, "\n").
            sprintf("-----END %s-----\n", mb_strtoupper($type));
    }
}
