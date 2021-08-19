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

use const \ZipStreamer\INT64_LOW_MAP;
use const \ZipStreamer\INT_MAX_32;

class Count64_64 extends Count64Base {
  private $value;

  public function getHiBytes() {
    return urShift($this->value, 32);
  }

  public function getLoBytes() {
    return $this->value & INT64_LOW_MAP;
  }

  public function _getValue() {
    return $this->value;
  }

  public function set($value) {
    if (is_int($value)) {
      if ($this->limit32Bit && INT_MAX_32 < $value) {
        throw new \OverFlowException(self::EXCEPTION_32BIT_OVERFLOW);
      }
      $this->value = $value;
    } else if (is_array($value) && 2 == sizeof($value)) {
      if ($this->limit32Bit && 0 !== $value[1]) {
        throw new \OverFlowException(self::EXCEPTION_32BIT_OVERFLOW);
      }
      $this->value = $value[1];
      $this->value = $this->value << 32;
      $this->value = $this->value + $value[0];
    } else if (is_object($value) && __CLASS__ == get_class($value)) {
      $value = $value->_getValue();
      if ($this->limit32Bit && INT_MAX_32 < $value) {
        throw new \OverFlowException(self::EXCEPTION_32BIT_OVERFLOW);
      }
      $this->value = $value;

    } else {
      throw new \InvalidArgumentException(self::EXCEPTION_SET_INVALID_ARGUMENT);
    }
    return $this;
  }

  public function add($value) {
    if (is_int($value)) {
      $sum = (int) ($this->value + $value);
    } else if (is_object($value) && __CLASS__ == get_class($value)) {
      $sum = (int) ($this->value + $value->_getValue());
    } else {
      throw new \InvalidArgumentException(self::EXCEPTION_ADD_INVALID_ARGUMENT);
    }
    if ($this->limit32Bit && INT_MAX_32 < $sum) {
      throw new \OverFlowException(self::EXCEPTION_32BIT_OVERFLOW);
    }
    $this->value = $sum;
    return $this;
  }
}
