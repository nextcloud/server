<?php
/**
 * Class that holds compression type and level constants.
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
 * @author André Rothe <arothe@zks.uni-leipzig.de>
 * @copyright Copyright (C) 2013-2015 Nicolai Ehemann and contributors
 * @license GNU GPL
 * @version 1.0
 */
namespace ZipStreamer;

class COMPR {
  // compression method
  const STORE = 0x0000; //  0 - The file is stored (no compression)
  const DEFLATE = 0x0008; //  8 - The file is deflated

  // compression level (for deflate compression)
  const NONE = 0;
  const NORMAL = 1;
  const MAXIMUM = 2;
  const SUPERFAST = 3;
}
