<?php
/**
 * Simple class to support some very basic operations on 64 bit intergers
 * on 32 bit machines.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Nicolai Ehemann <en@enlightened.de>
 * @copyright Copyright (C) 2013-2014 Nicolai Ehemann and contributors
 * @license GNU GPL
 */
namespace ZipStreamer\Lib;

class Count64_32 extends Count64Base {
  private $loBytes;
  private $hiBytes;

  public function getHiBytes() {
    return $this->hiBytes;
  }

  public function getLoBytes() {
    return $this->loBytes;
  }

  public function _getValue() {
    return array($this->hiBytes, $this->loBytes);
  }

  public function set($value) {
    if (is_int($value)) {
      $this->loBytes = $value;
      $this->hiBytes = 0;
    } else if (is_array($value) && 2 == sizeof($value)) {
      $this->loBytes = $value[0];
      if ($this->limit32Bit && 0 !== $value[1]) {
        throw new \OverflowException(self::EXCEPTION_32BIT_OVERFLOW);
      }
      $this->hiBytes = $value[1];
    } else if (is_object($value) && __CLASS__ == get_class($value)) {
      $value = $value->_getValue();
          if ($this->limit32Bit && 0 !== $value[0]) {
        throw new \OverflowException(self::EXCEPTION_32BIT_OVERFLOW);
      }
      $this->hiBytes = $value[0];
      $this->loBytes = $value[1];
    } else {
      throw new \InvalidArgumentException(self::EXCEPTION_SET_INVALID_ARGUMENT);
    }
    return $this;
  }

  public function add($value) {
    if (is_int($value)) {
      $sum = (int) ($this->loBytes + $value);
      // overflow!
      if (($this->loBytes > -1 && $sum < $this->loBytes && $sum > -1)
        || ($this->loBytes < 0 && ($sum < $this->loBytes || $sum > -1))) {
        if ($this->limit32Bit) {
          throw new \OverflowException(self::EXCEPTION_32BIT_OVERFLOW);
        }
        $this->hiBytes = (int) ($this->hiBytes + 1);
      }
      $this->loBytes = $sum;
    } else if (is_object($value) && __CLASS__ == get_class($value)) {
      $value = $value->_getValue();
      $sum = (int) ($this->loBytes + $value[1]);
      if (($this->loBytes > -1 && $sum < $this->loBytes && $sum > -1)
        || ($this->loBytes < 0 && ($sum < $this->loBytes || $sum > -1))) {
        if ($this->limit32Bit) {
          throw new \OverflowException(self::EXCEPTION_32BIT_OVERFLOW);
        }
        $this->hiBytes = (int) ($this->hiBytes + 1);
      }
      $this->loBytes = $sum;
      if ($this->limit32Bit && 0 !== $value[0]) {
        throw new \OverflowException(self::EXCEPTION_32BIT_OVERFLOW);
      }
      $this->hiBytes = (int) ($this->hiBytes + $value[0]);
    } else {
      throw new \InvalidArgumentException(self::EXCEPTION_ADD_INVALID_ARGUMENT);
    }
    return $this;
  }
}
