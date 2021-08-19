<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\Utility;

/**
 * Class BigIntegerBcmath
 * Integer representation of big numbers using the bcmath library to perform large operations.
 * @package FG\Utility
 * @internal
 */
class BigIntegerBcmath extends BigInteger
{
    protected $_str;

    public function __clone()
    {
        // nothing needed to copy
    }

    protected function _fromString($str)
    {
        $this->_str = (string)$str;
    }

    protected function _fromInteger($integer)
    {
        $this->_str = (string)$integer;
    }

    public function __toString()
    {
        return $this->_str;
    }

    public function toInteger()
    {
        if ($this->compare(PHP_INT_MAX) > 0 || $this->compare(PHP_INT_MIN) < 0) {
            throw new \OverflowException(sprintf('Can not represent %s as integer.', $this->_str));
        }
        return (int)$this->_str;
    }

    public function isNegative()
    {
        return bccomp($this->_str, '0', 0) < 0;
    }

    protected function _unwrap($number)
    {
        if ($number instanceof self) {
            return $number->_str;
        }
        return $number;
    }

    public function compare($number)
    {
        return bccomp($this->_str, $this->_unwrap($number), 0);
    }

    public function add($b)
    {
        $ret = new self();
        $ret->_str = bcadd($this->_str, $this->_unwrap($b), 0);
        return $ret;
    }

    public function subtract($b)
    {
        $ret = new self();
        $ret->_str = bcsub($this->_str, $this->_unwrap($b), 0);
        return $ret;
    }

    public function multiply($b)
    {
        $ret = new self();
        $ret->_str = bcmul($this->_str, $this->_unwrap($b), 0);
        return $ret;
    }

    public function modulus($b)
    {
        $ret = new self();
        if ($this->isNegative()) {
            // bcmod handles negative numbers differently
            $b = $this->_unwrap($b);
            $ret->_str = bcsub($b, bcmod(bcsub('0', $this->_str, 0), $b), 0);
        }
        else {
            $ret->_str = bcmod($this->_str, $this->_unwrap($b));
        }
        return $ret;
    }

    public function toPower($b)
    {
        $ret = new self();
        $ret->_str = bcpow($this->_str, $this->_unwrap($b), 0);
        return $ret;
    }

    public function shiftRight($bits = 8)
    {
        $ret = new self();
        $ret->_str = bcdiv($this->_str, bcpow('2', $bits));
        return $ret;
    }

    public function shiftLeft($bits = 8) {
        $ret = new self();
        $ret->_str = bcmul($this->_str, bcpow('2', $bits));
        return $ret;
    }

    public function absoluteValue()
    {
        $ret = new self();
        if (-1 === bccomp($this->_str, '0', 0)) {
            $ret->_str = bcsub('0', $this->_str, 0);
        }
        else {
            $ret->_str = $this->_str;
        }
        return $ret;
    }
}
