<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\GeneralName;

use UnexpectedValueException;
use function array_slice;
use function count;

final class IPv4Address extends IPAddress
{
    public static function create(string $ip, ?string $mask = null): self
    {
        return new self($ip, $mask);
    }

    /**
     * Initialize from octets.
     */
    public static function fromOctets(string $octets): self
    {
        $mask = null;
        $bytes = unpack('C*', $octets);
        $bytes = $bytes === false ? [] : $bytes;
        switch (count($bytes)) {
            case 4:
                $ip = implode('.', $bytes);
                break;
            case 8:
                $ip = implode('.', array_slice($bytes, 0, 4));
                $mask = implode('.', array_slice($bytes, 4, 4));
                break;
            default:
                throw new UnexpectedValueException('Invalid IPv4 octet length.');
        }
        return self::create($ip, $mask);
    }

    protected function octets(): string
    {
        $bytes = array_map('intval', explode('.', $this->ip));
        if (isset($this->mask)) {
            $bytes = array_merge($bytes, array_map('intval', explode('.', $this->mask)));
        }
        return pack('C*', ...$bytes);
    }
}
