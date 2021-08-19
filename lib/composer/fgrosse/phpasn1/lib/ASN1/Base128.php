<?php

namespace FG\ASN1;

use FG\Utility\BigInteger;
use InvalidArgumentException;

/**
 * A base-128 decoder.
 */
class Base128
{
    /**
     * @param int $value
     *
     * @return string
     */
    public static function encode($value)
    {
        $value = BigInteger::create($value);
        $octets = chr($value->modulus(0x80)->toInteger());

        $value = $value->shiftRight(7);
        while ($value->compare(0) > 0) {
            $octets .= chr(0x80 | $value->modulus(0x80)->toInteger());
            $value = $value->shiftRight(7);
        }

        return strrev($octets);
    }

    /**
     * @param string $octets
     *
     * @throws InvalidArgumentException if the given octets represent a malformed base-128 value or the decoded value would exceed the the maximum integer length
     *
     * @return int
     */
    public static function decode($octets)
    {
        $bitsPerOctet = 7;
        $value = BigInteger::create(0);
        $i = 0;

        while (true) {
            if (!isset($octets[$i])) {
                throw new InvalidArgumentException(sprintf('Malformed base-128 encoded value (0x%s).', strtoupper(bin2hex($octets)) ?: '0'));
            }

            $octet = ord($octets[$i++]);

            $l1 = $value->shiftLeft($bitsPerOctet);
            $r1 = $octet & 0x7f;
            $value = $l1->add($r1);

            if (0 === ($octet & 0x80)) {
                break;
            }
        }

        return (string)$value;
    }
}
