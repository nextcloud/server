<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\Utility;

/**
 * Class BigIntegerGmp
 * Integer representation of big numbers using the GMP extension to perform operations.
 * @package FG\Utility
 * @internal
 */
class BigIntegerGmp extends BigInteger
{
    /**
     * Resource handle.
     * @var \GMP
     */
    protected $_rh;

    public function __clone()
    {
        $this->_rh = gmp_add($this->_rh, 0);
    }

    protected function _fromString($str)
    {
        $this->_rh = gmp_init($str, 10);
    }

    protected function _fromInteger($integer)
    {
        $this->_rh = gmp_init($integer, 10);
    }

    public function __toString()
    {
        return gmp_strval($this->_rh, 10);
    }

    public function toInteger()
    {
        if ($this->compare(PHP_INT_MAX) > 0 || $this->compare(PHP_INT_MIN) < 0) {
            throw new \OverflowException(sprintf('Can not represent %s as integer.', $this));
        }
        return gmp_intval($this->_rh);
    }

    public function isNegative()
    {
        return gmp_sign($this->_rh) === -1;
    }

    protected function _unwrap($number)
    {
        if ($number instanceof self) {
            return $number->_rh;
        }
        return $number;
    }

    public function compare($number)
    {
        return gmp_cmp($this->_rh, $this->_unwrap($number));
    }

    public function add($b)
    {
        $ret = new self();
        $ret->_rh = gmp_add($this->_rh, $this->_unwrap($b));
        return $ret;
    }

    public function subtract($b)
    {
        $ret = new self();
        $ret->_rh = gmp_sub($this->_rh, $this->_unwrap($b));
        return $ret;
    }

    public function multiply($b)
    {
        $ret = new self();
        $ret->_rh = gmp_mul($this->_rh, $this->_unwrap($b));
        return $ret;
    }

    public function modulus($b)
    {
        $ret = new self();
        $ret->_rh = gmp_mod($this->_rh, $this->_unwrap($b));
        return $ret;
    }

    public function toPower($b)
    {
        if ($b instanceof self) {
            // gmp_pow accepts just an integer
            if ($b->compare(PHP_INT_MAX) > 0) {
                throw new \UnexpectedValueException('Unable to raise to power greater than PHP_INT_MAX.');
            }
            $b = gmp_intval($b->_rh);
        }
        $ret = new self();
        $ret->_rh = gmp_pow($this->_rh, $b);
        return $ret;
    }

    public function shiftRight($bits=8)
    {
        $ret = new self();
        $ret->_rh = gmp_div($this->_rh, gmp_pow(2, $bits));
        return $ret;
    }

    public function shiftLeft($bits=8)
    {
        $ret = new self();
        $ret->_rh = gmp_mul($this->_rh, gmp_pow(2, $bits));
        return $ret;
    }

    public function absoluteValue()
    {
        $ret = new self();
        $ret->_rh = gmp_abs($this->_rh);
        return $ret;
    }
}
