<?php

declare(strict_types=1);

namespace Cose\Key;

use Brick\Math\BigInteger;
use InvalidArgumentException;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\PublicKeyInfo;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\RSA\RSAPrivateKey;
use SpomkyLabs\Pki\CryptoTypes\Asymmetric\RSA\RSAPublicKey;
use function array_key_exists;
use function in_array;

/**
 * @final
 * @see \Cose\Tests\Key\RsaKeyTest
 */
class RsaKey extends Key
{
    final public const DATA_N = -1;

    final public const DATA_E = -2;

    final public const DATA_D = -3;

    final public const DATA_P = -4;

    final public const DATA_Q = -5;

    final public const DATA_DP = -6;

    final public const DATA_DQ = -7;

    final public const DATA_QI = -8;

    final public const DATA_OTHER = -9;

    final public const DATA_RI = -10;

    final public const DATA_DI = -11;

    final public const DATA_TI = -12;

    /**
     * @param array<int|string, mixed> $data
     */
    public function __construct(array $data)
    {
        foreach ([self::TYPE] as $key) {
            if (is_numeric($data[$key])) {
                $data[$key] = (int) $data[$key];
            }
        }
        parent::__construct($data);
        if ($data[self::TYPE] !== self::TYPE_RSA && $data[self::TYPE] !== self::TYPE_NAME_RSA) {
            throw new InvalidArgumentException('Invalid RSA key. The key type does not correspond to a RSA key');
        }
        if (! isset($data[self::DATA_N], $data[self::DATA_E])) {
            throw new InvalidArgumentException('Invalid RSA key. The modulus or the exponent is missing');
        }
    }

    /**
     * @param array<int|string, mixed> $data
     */
    public static function create(array $data): self
    {
        return new self($data);
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
        $this->checkKeyIsPrivate();

        return $this->get(self::DATA_D);
    }

    public function p(): string
    {
        $this->checkKeyIsPrivate();

        return $this->get(self::DATA_P);
    }

    public function q(): string
    {
        $this->checkKeyIsPrivate();

        return $this->get(self::DATA_Q);
    }

    public function dP(): string
    {
        $this->checkKeyIsPrivate();

        return $this->get(self::DATA_DP);
    }

    public function dQ(): string
    {
        $this->checkKeyIsPrivate();

        return $this->get(self::DATA_DQ);
    }

    public function QInv(): string
    {
        $this->checkKeyIsPrivate();

        return $this->get(self::DATA_QI);
    }

    /**
     * @return array<mixed>
     */
    public function other(): array
    {
        $this->checkKeyIsPrivate();

        return $this->get(self::DATA_OTHER);
    }

    public function rI(): string
    {
        $this->checkKeyIsPrivate();

        return $this->get(self::DATA_RI);
    }

    public function dI(): string
    {
        $this->checkKeyIsPrivate();

        return $this->get(self::DATA_DI);
    }

    public function tI(): string
    {
        $this->checkKeyIsPrivate();

        return $this->get(self::DATA_TI);
    }

    public function hasPrimes(): bool
    {
        return $this->has(self::DATA_P) && $this->has(self::DATA_Q);
    }

    /**
     * @return string[]
     */
    public function primes(): array
    {
        return [$this->p(), $this->q()];
    }

    public function hasExponents(): bool
    {
        return $this->has(self::DATA_DP) && $this->has(self::DATA_DQ);
    }

    /**
     * @return string[]
     */
    public function exponents(): array
    {
        return [$this->dP(), $this->dQ()];
    }

    public function hasCoefficient(): bool
    {
        return $this->has(self::DATA_QI);
    }

    public function isPublic(): bool
    {
        return ! $this->isPrivate();
    }

    public function isPrivate(): bool
    {
        return array_key_exists(self::DATA_D, $this->getData());
    }

    public function asPem(): string
    {
        if ($this->isPrivate()) {
            $privateKey = RSAPrivateKey::create(
                $this->binaryToBigInteger($this->n()),
                $this->binaryToBigInteger($this->e()),
                $this->binaryToBigInteger($this->d()),
                $this->binaryToBigInteger($this->p()),
                $this->binaryToBigInteger($this->q()),
                $this->binaryToBigInteger($this->dP()),
                $this->binaryToBigInteger($this->dQ()),
                $this->binaryToBigInteger($this->QInv())
            );

            return $privateKey->toPEM()
                ->string();
        }

        $publicKey = RSAPublicKey::create(
            $this->binaryToBigInteger($this->n()),
            $this->binaryToBigInteger($this->e())
        );
        $rsaKey = PublicKeyInfo::fromPublicKey($publicKey);

        return $rsaKey->toPEM()
            ->string();
    }

    public function toPublic(): static
    {
        $toBeRemoved = [
            self::DATA_D,
            self::DATA_P,
            self::DATA_Q,
            self::DATA_DP,
            self::DATA_DQ,
            self::DATA_QI,
            self::DATA_OTHER,
            self::DATA_RI,
            self::DATA_DI,
            self::DATA_TI,
        ];
        $data = $this->getData();
        foreach ($data as $k => $v) {
            if (in_array($k, $toBeRemoved, true)) {
                unset($data[$k]);
            }
        }

        return new static($data);
    }

    private function checkKeyIsPrivate(): void
    {
        if (! $this->isPrivate()) {
            throw new InvalidArgumentException('The key is not private.');
        }
    }

    private function binaryToBigInteger(string $data): string
    {
        $res = unpack('H*', $data);
        $res = current($res);

        return BigInteger::fromBase($res, 16)->toBase(10);
    }
}
