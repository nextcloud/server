<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\X509\GeneralName;

use UnexpectedValueException;
use function array_slice;
use function count;

final class IPv6Address extends IPAddress
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
        $words = unpack('n*', $octets);
        $words = $words === false ? [] : $words;
        switch (count($words)) {
            case 8:
                $ip = self::wordsToIPv6String($words);
                break;
            case 16:
                $ip = self::wordsToIPv6String(array_slice($words, 0, 8));
                $mask = self::wordsToIPv6String(array_slice($words, 8, 8));
                break;
            default:
                throw new UnexpectedValueException('Invalid IPv6 octet length.');
        }
        return self::create($ip, $mask);
    }

    /**
     * Convert an array of 16 bit words to an IPv6 string representation.
     *
     * @param int[] $words
     */
    protected static function wordsToIPv6String(array $words): string
    {
        $groups = array_map(static fn ($word) => sprintf('%04x', $word), $words);
        return implode(':', $groups);
    }

    protected function octets(): string
    {
        $words = array_map('hexdec', explode(':', $this->ip));
        if (isset($this->mask)) {
            $words = array_merge($words, array_map('hexdec', explode(':', $this->mask)));
        }
        return pack('n*', ...$words);
    }
}
