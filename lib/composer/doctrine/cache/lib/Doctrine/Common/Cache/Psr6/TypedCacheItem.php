<?php

namespace Doctrine\Common\Cache\Psr6;

use DateInterval;
use DateTime;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;
use TypeError;

use function get_debug_type;
use function is_int;
use function microtime;
use function sprintf;

final class TypedCacheItem implements CacheItemInterface
{
    private ?float $expiry = null;

    /**
     * @internal
     */
    public function __construct(
        private string $key,
        private mixed $value,
        private bool $isHit,
    ) {
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function get(): mixed
    {
        return $this->value;
    }

    public function isHit(): bool
    {
        return $this->isHit;
    }

    public function set(mixed $value): static
    {
        $this->value = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function expiresAt($expiration): static
    {
        if ($expiration === null) {
            $this->expiry = null;
        } elseif ($expiration instanceof DateTimeInterface) {
            $this->expiry = (float) $expiration->format('U.u');
        } else {
            throw new TypeError(sprintf(
                'Expected $expiration to be an instance of DateTimeInterface or null, got %s',
                get_debug_type($expiration)
            ));
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function expiresAfter($time): static
    {
        if ($time === null) {
            $this->expiry = null;
        } elseif ($time instanceof DateInterval) {
            $this->expiry = microtime(true) + DateTime::createFromFormat('U', 0)->add($time)->format('U.u');
        } elseif (is_int($time)) {
            $this->expiry = $time + microtime(true);
        } else {
            throw new TypeError(sprintf(
                'Expected $time to be either an integer, an instance of DateInterval or null, got %s',
                get_debug_type($time)
            ));
        }

        return $this;
    }

    /**
     * @internal
     */
    public function getExpiry(): ?float
    {
        return $this->expiry;
    }
}
