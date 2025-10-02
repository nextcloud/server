<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\CryptoTypes\Asymmetric\EC;

use Brick\Math\BigInteger;
use RangeException;
use RuntimeException;
use SpomkyLabs\Pki\ASN1\Type\Primitive\BitString;
use SpomkyLabs\Pki\ASN1\Type\Primitive\Integer;
use SpomkyLabs\Pki\ASN1\Type\Primitive\OctetString;
use function mb_strlen;

/**
 * Implements data type conversions from SEC 1: Elliptic Curve Cryptography.
 *
 * @see http://www.secg.org/sec1-v2.pdf
 */
final class ECConversion
{
    /**
     * Perform Bit-String-to-Octet-String Conversion.
     *
     * Defined in SEC 1 section 2.3.1.
     */
    public static function bitStringToOctetString(BitString $bs): OctetString
    {
        $str = $bs->string();
        if ($bs->unusedBits() !== 0) {
            // @todo pad string
            throw new RuntimeException('Unaligned bitstrings to supported');
        }
        return OctetString::create($str);
    }

    /**
     * Perform Octet-String-to-Bit-String Conversion.
     *
     * Defined in SEC 1 section 2.3.2.
     */
    public static function octetStringToBitString(OctetString $os): BitString
    {
        return BitString::create($os->string());
    }

    /**
     * Perform Integer-to-Octet-String Conversion.
     *
     * Defined in SEC 1 section 2.3.7.
     *
     * @param null|int $mlen Optional desired output length
     */
    public static function integerToOctetString(Integer $num, ?int $mlen = null): OctetString
    {
        $bigInteger = BigInteger::of($num->getValue());
        $str = $bigInteger->toBytes(false);
        if ($mlen !== null) {
            $len = mb_strlen($str, '8bit');
            if ($len > $mlen) {
                throw new RangeException('Number is too large.');
            }
            // pad with zeroes
            if ($len < $mlen) {
                $str = str_repeat("\0", $mlen - $len) . $str;
            }
        }
        return OctetString::create($str);
    }

    /**
     * Perform Octet-String-to-Integer Conversion.
     *
     * Defined in SEC 1 section 2.3.8.
     */
    public static function octetStringToInteger(OctetString $os): Integer
    {
        $num = BigInteger::fromBytes($os->string(), false);

        return Integer::create($num);
    }

    /**
     * Convert a base-10 number to octets.
     *
     * This is a convenicence method for integer <-> octet string conversion without the need for external ASN.1
     * dependencies.
     *
     * @param int|string $num Number in base-10
     * @param null|int $mlen Optional desired output length
     */
    public static function numberToOctets(int|string $num, ?int $mlen = null): string
    {
        return self::integerToOctetString(Integer::create($num), $mlen)->string();
    }

    /**
     * Convert octets to a base-10 number.
     *
     * This is a convenicence method for integer <-> octet string conversion without the need for external ASN.1
     * dependencies.
     *
     * @return string Number in base-10
     */
    public static function octetsToNumber(string $str): string
    {
        return self::octetStringToInteger(OctetString::create($str))->number();
    }
}
