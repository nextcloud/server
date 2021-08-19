<?php

namespace Doctrine\Common\Cache\Psr6;

use DateInterval;
use DateTime;
use DateTimeInterface;
use Psr\Cache\CacheItemInterface;
use TypeError;

use function get_class;
use function gettype;
use function is_int;
use function is_object;
use function microtime;
use function sprintf;

final class CacheItem implements CacheItemInterface
{
    /** @var string */
    private $key;
    /** @var mixed */
    private $value;
    /** @var bool */
    private $isHit;
    /** @var float|null */
    private $expiry;

    /**
     * @internal
     *
     * @param mixed $data
     */
    public function __construct(string $key, $data, bool $isHit)
    {
        $this->key   = $key;
        $this->value = $data;
        $this->isHit = $isHit;
    }

    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * {@inheritDoc}
     *
     * @return mixed
     */
    public function get()
    {
        return $this->value;
    }

    public function isHit(): bool
    {
        return $this->isHit;
    }

    /**
     * {@inheritDoc}
     */
    public function set($value): self
    {
        $this->value = $value;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function expiresAt($expiration): self
    {
        if ($expiration === null) {
            $this->expiry = null;
        } elseif ($expiration instanceof DateTimeInterface) {
            $this->expiry = (float) $expiration->format('U.u');
        } else {
            throw new TypeError(sprintf(
                'Expected $expiration to be an instance of DateTimeInterface or null, got %s',
                is_object($expiration) ? get_class($expiration) : gettype($expiration)
            ));
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function expiresAfter($time): self
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
                is_object($time) ? get_class($time) : gettype($time)
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
