<?php
namespace Aws\Crypto\Polyfill;

/**
 * Class ByteArray
 * @package Aws\Crypto\Polyfill
 */
class ByteArray extends \SplFixedArray
{
    use NeedsTrait;

    /**
     * ByteArray constructor.
     *
     * @param int|string|int[] $size
     *     If you pass in an integer, it creates a ByteArray of that size.
     *     If you pass in a string or array, it converts it to an array of
     *       integers between 0 and 255.
     * @throws \InvalidArgumentException
     */
    public function __construct($size = 0)
    {
        $arr = null;
        // Integer? This behaves just like SplFixedArray.
        if (\is_array($size)) {
            // Array? We need to pass the count to parent::__construct() then populate
            $arr = $size;
            $size = \count($arr);
        } elseif (\is_string($size)) {
            // We need to avoid mbstring.func_overload
            if (\is_callable('\\mb_str_split')) {
                $tmp = \mb_str_split($size, 1, '8bit');
            } else {
                $tmp = \str_split($size, 1);
            }
            // Let's convert each character to an 8-bit integer and store in $arr
            $arr = [];
            if (!empty($tmp)) {
                foreach ($tmp as $t) {
                    if (strlen($t) < 1) {
                        continue;
                    }
                    $arr []= \unpack('C', $t)[1] & 0xff;
                }
            }
            $size = \count($arr);
        } elseif ($size instanceof ByteArray) {
            $arr = $size->toArray();
            $size = $size->count();
        } elseif (!\is_int($size)) {
            throw new \InvalidArgumentException(
                'Argument must be an integer, string, or array of integers.'
            );
        }

        parent::__construct($size);

        if (!empty($arr)) {
            // Populate this object with values from constructor argument
            foreach ($arr as $i => $v) {
                $this->offsetSet($i, $v);
            }
        } else {
            // Initialize to zero.
            for ($i = 0; $i < $size; ++$i) {
                $this->offsetSet($i, 0);
            }
        }
    }

    /**
     * Encode an integer into a byte array. 32-bit (unsigned), big endian byte order.
     *
     * @param int $num
     * @return self
     */
    public static function enc32be($num)
    {
        return new ByteArray(\pack('N', $num));
    }

    /**
     * @param ByteArray $other
     * @return bool
     */
    public function equals(ByteArray $other)
    {
        if ($this->count() !== $other->count()) {
            return false;
        }
        $d = 0;
        for ($i = $this->count() - 1; $i >= 0; --$i) {
            $d |= $this[$i] ^ $other[$i];
        }
        return $d === 0;
    }

    /**
     * @param ByteArray $array
     * @return ByteArray
     */
    public function exclusiveOr(ByteArray $array)
    {
        self::needs(
            $this->count() === $array->count(),
            'Both ByteArrays must be equal size for exclusiveOr()'
        );
        $out = clone $this;
        for ($i = 0; $i < $this->count(); ++$i) {
            $out[$i] = $array[$i] ^ $out[$i];
        }
        return $out;
    }

    /**
     * Returns a new ByteArray incremented by 1 (big endian byte order).
     *
     * @param int $increase
     * @return self
     */
    public function getIncremented($increase = 1)
    {
        $clone = clone $this;
        $index = $clone->count();
        while ($index > 0) {
            --$index;
            $tmp = ($clone[$index] + $increase) & PHP_INT_MAX;
            $clone[$index] = $tmp & 0xff;
            $increase = $tmp >> 8;
        }
        return $clone;
    }

    /**
     * Sets a value. See SplFixedArray for more.
     *
     * @param int $index
     * @param int $newval
     * @return void
     */
    public function offsetSet($index, $newval)
    {
        parent::offsetSet($index, $newval & 0xff);
    }

    /**
     * Return a copy of this ByteArray, bitshifted to the right by 1.
     * Used in Gmac.
     *
     * @return self
     */
    public function rshift()
    {
        $out = clone $this;
        for ($j = $this->count() - 1; $j > 0; --$j) {
            $out[$j] = (($out[$j - 1] & 1) << 7) | ($out[$j] >> 1);
        }
        $out[0] >>= 1;
        return $out;
    }

    /**
     * Constant-time conditional select. This is meant to read like a ternary operator.
     *
     * $z = ByteArray::select(1, $x, $y); // $z is equal to $x
     * $z = ByteArray::select(0, $x, $y); // $z is equal to $y
     *
     * @param int $select
     * @param ByteArray $left
     * @param ByteArray $right
     * @return ByteArray
     */
    public static function select($select, ByteArray $left, ByteArray $right)
    {
        self::needs(
            $left->count() === $right->count(),
            'Both ByteArrays must be equal size for select()'
        );
        $rightLength = $right->count();
        $out = clone $right;
        $mask = (-($select & 1)) & 0xff;
        for ($i = 0; $i < $rightLength;  $i++) {
            $out[$i] = $out[$i] ^ (($left[$i] ^ $right[$i]) & $mask);
        }
        return $out;
    }

    /**
     * Overwrite values of this ByteArray based on a separate ByteArray, with
     * a given starting offset and length.
     *
     * See JavaScript's Uint8Array.set() for more information.
     *
     * @param ByteArray $input
     * @param int $offset
     * @param int|null $length
     * @return self
     */
    public function set(ByteArray $input, $offset = 0, $length = null)
    {
        self::needs(
            is_int($offset) && $offset >= 0,
            'Offset must be a positive integer or zero'
        );
        if (is_null($length)) {
            $length = $input->count();
        }

        $i = 0; $j = $offset;
        while ($i < $length && $j < $this->count()) {
            $this[$j] = $input[$i];
            ++$i;
            ++$j;
        }
        return $this;
    }

    /**
     * Returns a slice of this ByteArray.
     *
     * @param int $start
     * @param null $length
     * @return self
     */
    public function slice($start = 0, $length = null)
    {
        return new ByteArray(\array_slice($this->toArray(), $start, $length));
    }

    /**
     * Mutates the current state and sets all values to zero.
     *
     * @return void
     */
    public function zeroize()
    {
        for ($i = $this->count() - 1; $i >= 0; --$i) {
            $this->offsetSet($i, 0);
        }
    }

    /**
     * Converts the ByteArray to a raw binary string.
     *
     * @return string
     */
    public function toString()
    {
        $count = $this->count();
        if ($count === 0) {
            return '';
        }
        $args = $this->toArray();
        \array_unshift($args, \str_repeat('C', $count));
        // constant-time, PHP <5.6 equivalent to pack('C*', ...$args);
        return \call_user_func_array('\\pack', $args);
    }
}
