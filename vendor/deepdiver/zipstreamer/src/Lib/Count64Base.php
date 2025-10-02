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

abstract class Count64Base {
  protected $limit32Bit = False;

  public function __construct($value = 0, $limit32Bit = False) {
    $this->limit32Bit = $limit32Bit;
    $this->set($value);
  }

  abstract public function set($value);
  abstract public function add($value);
  abstract public function getHiBytes();
  abstract public function getLoBytes();
  abstract public function _getValue();

  const EXCEPTION_SET_INVALID_ARGUMENT = "Count64 object can only be set() to integer or Count64 values";
  const EXCEPTION_ADD_INVALID_ARGUMENT = "Count64 object can only be add()ed integer or Count64 values";
  const EXCEPTION_32BIT_OVERFLOW = "Count64 object limited to 32 bit (overflow)";
}
