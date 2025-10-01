<?php

declare(strict_types=1);

namespace SpomkyLabs\Pki\ASN1\Type\Primitive;

use Brick\Math\BigInteger;
use LogicException;
use RangeException;
use SpomkyLabs\Pki\ASN1\Component\Identifier;
use SpomkyLabs\Pki\ASN1\Component\Length;
use SpomkyLabs\Pki\ASN1\Element;
use SpomkyLabs\Pki\ASN1\Exception\DecodeException;
use SpomkyLabs\Pki\ASN1\Feature\ElementBase;
use SpomkyLabs\Pki\ASN1\Type\PrimitiveType;
use SpomkyLabs\Pki\ASN1\Type\UniversalClass;
use SpomkyLabs\Pki\ASN1\Util\BigInt;
use Stringable;
use UnexpectedValueException;
use function chr;
use function count;
use function in_array;
use function mb_strlen;
use function ord;
use const INF;

/**
 * Implements *REAL* type.
 */
final class Real extends Element implements Stringable
{
    use UniversalClass;
    use PrimitiveType;

    /**
     * Regex pattern to parse NR1 form number.
     *
     * @var string
     */
    final public const NR1_REGEX = '/^\s*' .
    '(?<s>[+\-])?' .    // sign
    '(?<i>\d+)' .       // integer
    '$/';

    /**
     * Regex pattern to parse NR2 form number.
     *
     * @var string
     */
    final public const NR2_REGEX = '/^\s*' .
    '(?<s>[+\-])?' .                            // sign
    '(?<d>(?:\d+[\.,]\d*)|(?:\d*[\.,]\d+))' .   // decimal number
    '$/';

    /**
     * Regex pattern to parse NR3 form number.
     *
     * @var string
     */
    final public const NR3_REGEX = '/^\s*' .
    '(?<ms>[+\-])?' .                           // mantissa sign
    '(?<m>(?:\d+[\.,]\d*)|(?:\d*[\.,]\d+))' .   // mantissa
    '[Ee](?<es>[+\-])?' .                       // exponent sign
    '(?<e>\d+)' .                               // exponent
    '$/';

    /**
     * Regex pattern to parse PHP exponent number format.
     *
     * @see http://php.net/manual/en/language.types.float.php
     *
     * @var string
     */
    final public const PHP_EXPONENT_DNUM = '/^' .
    '(?<ms>[+\-])?' .               // sign
    '(?<m>' .
    '\d+' .                     // LNUM
    '|' .
    '(?:\d*\.\d+|\d+\.\d*)' .   // DNUM
    ')[eE]' .
    '(?<es>[+\-])?(?<e>\d+)' .      // exponent
    '$/';

    /**
     * Exponent when value is positive or negative infinite.
     *
     * @var int
     */
    final public const INF_EXPONENT = 2047;

    /**
     * Exponent bias for IEEE 754 double precision float.
     *
     * @var int
     */
    final public const EXP_BIAS = -1023;

    /**
     * Signed integer mantissa.
     */
    private readonly BigInt $_mantissa;

    /**
     * Signed integer exponent.
     */
    private readonly BigInt $_exponent;

    /**
     * Abstract value base.
     *
     * Must be 2 or 10.
     */
    private readonly int $_base;

    /**
     * Whether to encode strictly in DER.
     */
    private bool $_strictDer;

    /**
     * Number as a native float.
     *
     * @internal Lazily initialized
     */
    private ?float $_float = null;

    /**
     * @param BigInteger|int|string $mantissa Integer mantissa
     * @param BigInteger|int|string $exponent Integer exponent
     * @param int $base Base, 2 or 10
     */
    private function __construct(BigInteger|int|string $mantissa, BigInteger|int|string $exponent, int $base = 10)
    {
        if ($base !== 10 && $base !== 2) {
            throw new UnexpectedValueException('Base must be 2 or 10.');
        }
        parent::__construct(self::TYPE_REAL);
        $this->_strictDer = true;
        $this->_mantissa = BigInt::create($mantissa);
        $this->_exponent = BigInt::create($exponent);
        $this->_base = $base;
    }

    public function __toString(): string
    {
        return sprintf('%g', $this->floatVal());
    }

    public static function create(
        BigInteger|int|string $mantissa,
        BigInteger|int|string $exponent,
        int $base = 10
    ): self {
        return new self($mantissa, $exponent, $base);
    }

    /**
     * Create base 2 real number from float.
     */
    public static function fromFloat(float $number): self
    {
        if (is_infinite($number)) {
            return self::fromInfinite($number);
        }
        if (is_nan($number)) {
            throw new UnexpectedValueException('NaN values not supported.');
        }
        [$m, $e] = self::parse754Double(pack('E', $number));
        return self::create($m, $e, 2);
    }

    /**
     * Create base 10 real number from string.
     *
     * @param string $number Real number in base-10 textual form
     */
    public static function fromString(string $number): self
    {
        [$m, $e] = self::parseString($number);
        return self::create($m, $e, 10);
    }

    /**
     * Get self with strict DER flag set or unset.
     *
     * @param bool $strict whether to encode strictly in DER
     */
    public function withStrictDER(bool $strict): self
    {
        $obj = clone $this;
        $obj->_strictDer = $strict;
        return $obj;
    }

    /**
     * Get the mantissa.
     */
    public function mantissa(): BigInt
    {
        return $this->_mantissa;
    }

    /**
     * Get the exponent.
     */
    public function exponent(): BigInt
    {
        return $this->_exponent;
    }

    /**
     * Get the base.
     */
    public function base(): int
    {
        return $this->_base;
    }

    /**
     * Get number as a float.
     */
    public function floatVal(): float
    {
        if (! isset($this->_float)) {
            $m = $this->_mantissa->toInt();
            $e = $this->_exponent->toInt();
            $this->_float = (float) ($m * $this->_base ** $e);
        }
        return $this->_float;
    }

    /**
     * Get number as a NR3 form string conforming to DER rules.
     */
    public function nr3Val(): string
    {
        // convert to base 10
        if ($this->_base === 2) {
            [$m, $e] = self::parseString(sprintf('%15E', $this->floatVal()));
        } else {
            $m = $this->_mantissa->getValue();
            $e = $this->_exponent->getValue();
        }
        $zero = BigInteger::of(0);
        $ten = BigInteger::of(10);

        // shift trailing zeroes from the mantissa to the exponent
        // (X.690 07-2002, section 11.3.2.4)
        while (! $m->isEqualTo($zero) && $m->mod($ten)->isEqualTo($zero)) {
            $m = $m->dividedBy($ten);
            $e = $e->plus(1);
        }
        // if exponent is zero, it must be prefixed with a "+" sign
        // (X.690 07-2002, section 11.3.2.6)
        if ($e->isEqualTo(0)) {
            $es = '+';
        } else {
            $es = $e->isLessThan(0) ? '-' : '';
        }
        return sprintf('%s.E%s%s', $m->toBase(10), $es, $e->abs()->toBase(10));
    }

    protected function encodedAsDER(): string
    {
        $infExponent = BigInteger::of(self::INF_EXPONENT);
        if ($this->_exponent->getValue()->isEqualTo($infExponent)) {
            return $this->encodeSpecial();
        }
        // if the real value is the value zero, there shall be no contents
        // octets in the encoding. (X.690 07-2002, section 8.5.2)
        if ($this->_mantissa->getValue()->toBase(10) === '0') {
            return '';
        }
        if ($this->_base === 10) {
            return $this->encodeDecimal();
        }
        return $this->encodeBinary();
    }

    /**
     * Encode in binary format.
     */
    protected function encodeBinary(): string
    {
        /** @var BigInteger $m */
        /** @var BigInteger $e */
        /** @var int $sign */
        [$base, $sign, $m, $e] = $this->prepareBinaryEncoding();
        $zero = BigInteger::of(0);
        $byte = 0x80;
        if ($sign < 0) {
            $byte |= 0x40;
        }
        // normalization: mantissa must be 0 or odd
        if ($base === 2) {
            // while last bit is zero
            while ($m->isGreaterThan(0) && $m->and(0x01)->isEqualTo($zero)) {
                $m = $m->shiftedRight(1);
                $e = $e->plus(1);
            }
        } elseif ($base === 8) {
            $byte |= 0x10;
            // while last 3 bits are zero
            while ($m->isGreaterThan(0) && $m->and(0x07)->isEqualTo($zero)) {
                $m = $m->shiftedRight(3);
                $e = $e->plus(1);
            }
        } else { // base === 16
            $byte |= 0x20;
            // while last 4 bits are zero
            while ($m->isGreaterThan(0) && $m->and(0x0f)->isEqualTo($zero)) {
                $m = $m->shiftedRight(4);
                $e = $e->plus(1);
            }
        }
        // scale factor
        $scale = 0;
        while ($m->isGreaterThan(0) && $m->and(0x01)->isEqualTo($zero)) {
            $m = $m->shiftedRight(1);
            ++$scale;
        }
        $byte |= ($scale & 0x03) << 2;
        // encode exponent
        $exp_bytes = (BigInt::create($e))->signedOctets();
        $exp_len = mb_strlen($exp_bytes, '8bit');
        if ($exp_len > 0xff) {
            throw new RangeException('Exponent encoding is too long.');
        }
        if ($exp_len <= 3) {
            $byte |= ($exp_len - 1) & 0x03;
            $bytes = chr($byte);
        } else {
            $byte |= 0x03;
            $bytes = chr($byte) . chr($exp_len);
        }
        $bytes .= $exp_bytes;
        // encode mantissa
        $bytes .= (BigInt::create($m))->unsignedOctets();
        return $bytes;
    }

    /**
     * Encode in decimal format.
     */
    protected function encodeDecimal(): string
    {
        // encode in NR3 decimal encoding
        return chr(0x03) . $this->nr3Val();
    }

    /**
     * Encode special value.
     */
    protected function encodeSpecial(): string
    {
        return match ($this->_mantissa->toInt()) {
            1 => chr(0x40),
            -1 => chr(0x41),
            default => throw new LogicException('Invalid special value.'),
        };
    }

    protected static function decodeFromDER(Identifier $identifier, string $data, int &$offset): ElementBase
    {
        $idx = $offset;
        $length = Length::expectFromDER($data, $idx)->intLength();
        // if length is zero, value is zero (spec 8.5.2)
        if ($length === 0) {
            $obj = self::create(0, 0, 10);
        } else {
            $bytes = mb_substr($data, $idx, $length, '8bit');
            $byte = ord($bytes[0]);
            if ((0x80 & $byte) !== 0) { // bit 8 = 1
                $obj = self::decodeBinaryEncoding($bytes);
            } elseif ($byte >> 6 === 0x00) { // bit 8 = 0, bit 7 = 0
                $obj = self::decodeDecimalEncoding($bytes);
            } else { // bit 8 = 0, bit 7 = 1
                $obj = self::decodeSpecialRealValue($bytes);
            }
        }
        $offset = $idx + $length;
        return $obj;
    }

    /**
     * Decode binary encoding.
     */
    protected static function decodeBinaryEncoding(string $data): self
    {
        $byte = ord($data[0]);
        // bit 7 is set if mantissa is negative
        $neg = (bool) (0x40 & $byte);
        $base = match (($byte >> 4) & 0x03) {
            0b00 => 2,
            0b01 => 8,
            0b10 => 16,
            default => throw new DecodeException('Reserved REAL binary encoding base not supported.'),
        };
        // scaling factor in bits 4 and 3
        $scale = ($byte >> 2) & 0x03;
        $idx = 1;
        // content length in bits 2 and 1
        $len = ($byte & 0x03) + 1;
        // if both bits are set, the next octet encodes the length
        if ($len > 3) {
            if (mb_strlen($data, '8bit') < 2) {
                throw new DecodeException('Unexpected end of data while decoding REAL exponent length.');
            }
            $len = ord($data[1]);
            $idx = 2;
        }
        if (mb_strlen($data, '8bit') < $idx + $len) {
            throw new DecodeException('Unexpected end of data while decoding REAL exponent.');
        }
        // decode exponent
        $octets = mb_substr($data, $idx, $len, '8bit');
        $exp = BigInt::fromSignedOctets($octets)->getValue();
        if ($base === 8) {
            $exp = $exp->multipliedBy(3);
        } elseif ($base === 16) {
            $exp = $exp->multipliedBy(4);
        }
        if (mb_strlen($data, '8bit') <= $idx + $len) {
            throw new DecodeException('Unexpected end of data while decoding REAL mantissa.');
        }
        // decode mantissa
        $octets = mb_substr($data, $idx + $len, null, '8bit');
        $n = BigInt::fromUnsignedOctets($octets)->getValue();
        $n = $n->multipliedBy(2 ** $scale);
        if ($neg) {
            $n = $n->negated();
        }
        return self::create($n, $exp, 2);
    }

    /**
     * Decode decimal encoding.
     */
    protected static function decodeDecimalEncoding(string $data): self
    {
        $nr = ord($data[0]) & 0x3f;
        if (! in_array($nr, [1, 2, 3], true)) {
            throw new DecodeException('Unsupported decimal encoding form.');
        }
        $str = mb_substr($data, 1, null, '8bit');
        return self::fromString($str);
    }

    /**
     * Decode special encoding.
     */
    protected static function decodeSpecialRealValue(string $data): self
    {
        if (mb_strlen($data, '8bit') !== 1) {
            throw new DecodeException('SpecialRealValue must have one content octet.');
        }
        $byte = ord($data[0]);
        if ($byte === 0x40) {   // positive infinity
            return self::fromInfinite(INF);
        }
        if ($byte === 0x41) {   // negative infinity
            return self::fromInfinite(-INF);
        }
        throw new DecodeException('Invalid SpecialRealValue encoding.');
    }

    /**
     * Prepare value for binary encoding.
     *
     * @return array<int|BigInteger> (int) base, (int) sign, (BigInteger) mantissa and (BigInteger) exponent
     */
    protected function prepareBinaryEncoding(): array
    {
        $base = 2;
        $m = $this->_mantissa->getValue();
        $ms = $m->getSign();
        $m = BigInteger::of($m->abs());
        $e = $this->_exponent->getValue();
        $es = $e->getSign();
        $e = BigInteger::of($e->abs());
        $zero = BigInteger::of(0);
        $three = BigInteger::of(3);
        $four = BigInteger::of(4);
        // DER uses only base 2 binary encoding
        if (! $this->_strictDer) {
            if ($e->mod($four)->isEqualTo($zero)) {
                $base = 16;
                $e = $e->dividedBy(4);
            } elseif ($e->mod($three)->isEqualTo($zero)) {
                $base = 8;
                $e = $e->dividedBy(3);
            }
        }
        return [$base, $ms, $m, $e->multipliedBy($es)];
    }

    /**
     * Initialize from INF or -INF.
     */
    private static function fromInfinite(float $inf): self
    {
        return self::create($inf === -INF ? -1 : 1, self::INF_EXPONENT, 2);
    }

    /**
     * Parse IEEE 754 big endian formatted double precision float to base 2 mantissa and exponent.
     *
     * @param string $octets 64 bits
     *
     * @return BigInteger[] Tuple of mantissa and exponent
     */
    private static function parse754Double(string $octets): array
    {
        $n = BigInteger::fromBytes($octets, false);
        // sign bit
        $neg = $n->testBit(63);
        // 11 bits of biased exponent
        $exponentMask = BigInteger::fromBase('7ff0000000000000', 16);
        $exp = $n->and($exponentMask)
            ->shiftedRight(52)
            ->plus(self::EXP_BIAS);

        // 52 bits of mantissa
        $mantissaMask = BigInteger::fromBase('fffffffffffff', 16);
        $man = $n->and($mantissaMask);
        // zero, ASN.1 doesn't differentiate -0 from +0
        $zero = BigInteger::of(0);
        if ($exp->isEqualTo(self::EXP_BIAS) && $man->isEqualTo($zero)) {
            return [BigInteger::of(0), BigInteger::of(0)];
        }
        // denormalized value, shift binary point
        if ($exp->isEqualTo(self::EXP_BIAS)) {
            $exp = $exp->plus(1);
        } // normalized value, insert implicit leading one before the binary point
        else {
            $man = $man->or(BigInteger::of(1)->shiftedLeft(52));
        }

        // find the last fraction bit that is set
        $last = 0;
        while (! $man->testBit($last) && $last !== 52) {
            $last++;
        }

        $bits_for_fraction = 52 - $last;
        // adjust mantissa and exponent so that we have integer values
        $man = $man->shiftedRight($last);
        $exp = $exp->minus($bits_for_fraction);
        // negate mantissa if number was negative
        if ($neg) {
            $man = $man->negated();
        }
        return [$man, $exp];
    }

    /**
     * Parse textual REAL number to base 10 mantissa and exponent.
     *
     * @param string $str Number
     *
     * @return BigInteger[] Tuple of mantissa and exponent
     */
    private static function parseString(string $str): array
    {
        // PHP exponent format
        if (preg_match(self::PHP_EXPONENT_DNUM, $str, $match) === 1) {
            [$m, $e] = self::parsePHPExponentMatch($match);
        } // NR3 format
        elseif (preg_match(self::NR3_REGEX, $str, $match) === 1) {
            [$m, $e] = self::parseNR3Match($match);
        } // NR2 format
        elseif (preg_match(self::NR2_REGEX, $str, $match) === 1) {
            [$m, $e] = self::parseNR2Match($match);
        } // NR1 format
        elseif (preg_match(self::NR1_REGEX, $str, $match) === 1) {
            [$m, $e] = self::parseNR1Match($match);
        } // invalid number
        else {
            throw new UnexpectedValueException("{$str} could not be parsed to REAL.");
        }
        // normalize so that mantissa has no trailing zeroes
        $zero = BigInteger::of(0);
        $ten = BigInteger::of(10);
        while (! $m->isEqualTo($zero) && $m->mod($ten)->isEqualTo($zero)) {
            $m = $m->dividedBy($ten);
            $e = $e->plus(1);
        }
        return [$m, $e];
    }

    /**
     * Parse PHP form float to base 10 mantissa and exponent.
     *
     * @param array<string> $match Regexp match
     *
     * @return BigInteger[] Tuple of mantissa and exponent
     */
    private static function parsePHPExponentMatch(array $match): array
    {
        // mantissa sign
        $ms = $match['ms'] === '-' ? -1 : 1;
        $m_parts = explode('.', $match['m']);
        // integer part of the mantissa
        $int = ltrim($m_parts[0], '0');
        // exponent sign
        $es = $match['es'] === '-' ? -1 : 1;
        // signed exponent
        $e = BigInteger::of($match['e'])->multipliedBy($es);
        // if mantissa had fractional part
        if (count($m_parts) === 2) {
            $frac = rtrim($m_parts[1], '0');
            $e = $e->minus(mb_strlen($frac, '8bit'));
            $int .= $frac;
        }
        $m = BigInteger::of($int)->multipliedBy($ms);
        return [$m, $e];
    }

    /**
     * Parse NR3 form number to base 10 mantissa and exponent.
     *
     * @param array<string> $match Regexp match
     *
     * @return BigInteger[] Tuple of mantissa and exponent
     */
    private static function parseNR3Match(array $match): array
    {
        // mantissa sign
        $ms = $match['ms'] === '-' ? -1 : 1;
        // explode mantissa to integer and fraction parts
        [$int, $frac] = explode('.', str_replace(',', '.', $match['m']));
        $int = ltrim($int, '0');
        $frac = rtrim($frac, '0');
        // exponent sign
        $es = $match['es'] === '-' ? -1 : 1;
        // signed exponent
        $e = BigInteger::of($match['e'])->multipliedBy($es);
        // shift exponent by the number of base 10 fractions
        $e = $e->minus(mb_strlen($frac, '8bit'));
        // insert fractions to integer part and produce signed mantissa
        $int .= $frac;
        if ($int === '') {
            $int = '0';
        }
        $m = BigInteger::of($int)->multipliedBy($ms);
        return [$m, $e];
    }

    /**
     * Parse NR2 form number to base 10 mantissa and exponent.
     *
     * @param array<string> $match Regexp match
     *
     * @return BigInteger[] Tuple of mantissa and exponent
     */
    private static function parseNR2Match(array $match): array
    {
        $sign = $match['s'] === '-' ? -1 : 1;
        // explode decimal number to integer and fraction parts
        [$int, $frac] = explode('.', str_replace(',', '.', $match['d']));
        $int = ltrim($int, '0');
        $frac = rtrim($frac, '0');
        // shift exponent by the number of base 10 fractions
        $e = BigInteger::of(0);
        $e = $e->minus(mb_strlen($frac, '8bit'));
        // insert fractions to integer part and produce signed mantissa
        $int .= $frac;
        if ($int === '') {
            $int = '0';
        }
        $m = BigInteger::of($int)->multipliedBy($sign);
        return [$m, $e];
    }

    /**
     * Parse NR1 form number to base 10 mantissa and exponent.
     *
     * @param array<string> $match Regexp match
     *
     * @return BigInteger[] Tuple of mantissa and exponent
     */
    private static function parseNR1Match(array $match): array
    {
        $sign = $match['s'] === '-' ? -1 : 1;
        $int = ltrim($match['i'], '0');
        if ($int === '') {
            $int = '0';
        }
        $m = BigInteger::of($int)->multipliedBy($sign);
        return [$m, BigInteger::of(0)];
    }
}
