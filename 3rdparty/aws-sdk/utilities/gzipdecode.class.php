<?php
/*
 * Copyright 2011-2012 Amazon.com, Inc. or its affiliates. All Rights Reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License").
 * You may not use this file except in compliance with the License.
 * A copy of the License is located at
 *
 *  http://aws.amazon.com/apache2.0
 *
 * or in the "license" file accompanying this file. This file is distributed
 * on an "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either
 * express or implied. See the License for the specific language governing
 * permissions and limitations under the License.
 */

/**
 * SimplePie
 *
 * A PHP-Based RSS and Atom Feed Framework.
 * Takes the hard work out of managing a complete RSS/Atom solution.
 *
 * Copyright (c) 2004-2010, Ryan Parman, Geoffrey Sneddon, Ryan McCue, and contributors
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification, are
 * permitted provided that the following conditions are met:
 *
 * 	* Redistributions of source code must retain the above copyright notice, this list of
 * 	  conditions and the following disclaimer.
 *
 * 	* Redistributions in binary form must reproduce the above copyright notice, this list
 * 	  of conditions and the following disclaimer in the documentation and/or other materials
 * 	  provided with the distribution.
 *
 * 	* Neither the name of the SimplePie Team nor the names of its contributors may be used
 * 	  to endorse or promote products derived from this software without specific prior
 * 	  written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS
 * OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS
 * AND CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR
 * OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package SimplePie
 * @version 1.3-dev
 * @copyright 2004-2010 Ryan Parman, Geoffrey Sneddon, Ryan McCue
 * @author Ryan Parman
 * @author Geoffrey Sneddon
 * @author Ryan McCue
 * @link http://simplepie.org/ SimplePie
 * @license http://www.opensource.org/licenses/bsd-license.php BSD License
 * @todo phpDoc comments
 */


/*%******************************************************************************************%*/
// CLASS

/**
 * Handles a variety of primary and edge cases around gzip/deflate decoding in PHP.
 *
 * @version 2011.02.21
 * @license See the included NOTICE.md file for more information.
 * @copyright See the included NOTICE.md file for more information.
 * @link http://aws.amazon.com/php/ PHP Developer Center
 * @link https://github.com/simplepie/simplepie/blob/master/SimplePie/gzdecode.php SimplePie_gzdecode
 */
class CFGzipDecode
{
	/**
	 * Compressed data
	 *
	 * @access private
	 * @see gzdecode::$data
	 */
	public $compressed_data;

	/**
	 * Size of compressed data
	 *
	 * @access private
	 */
	public $compressed_size;

	/**
	 * Minimum size of a valid gzip string
	 *
	 * @access private
	 */
	public $min_compressed_size = 18;

	/**
	 * Current position of pointer
	 *
	 * @access private
	 */
	public $position = 0;

	/**
	 * Flags (FLG)
	 *
	 * @access private
	 */
	public $flags;

	/**
	 * Uncompressed data
	 *
	 * @access public
	 * @see gzdecode::$compressed_data
	 */
	public $data;

	/**
	 * Modified time
	 *
	 * @access public
	 */
	public $MTIME;

	/**
	 * Extra Flags
	 *
	 * @access public
	 */
	public $XFL;

	/**
	 * Operating System
	 *
	 * @access public
	 */
	public $OS;

	/**
	 * Subfield ID 1
	 *
	 * @access public
	 * @see gzdecode::$extra_field
	 * @see gzdecode::$SI2
	 */
	public $SI1;

	/**
	 * Subfield ID 2
	 *
	 * @access public
	 * @see gzdecode::$extra_field
	 * @see gzdecode::$SI1
	 */
	public $SI2;

	/**
	 * Extra field content
	 *
	 * @access public
	 * @see gzdecode::$SI1
	 * @see gzdecode::$SI2
	 */
	public $extra_field;

	/**
	 * Original filename
	 *
	 * @access public
	 */
	public $filename;

	/**
	 * Human readable comment
	 *
	 * @access public
	 */
	public $comment;

	/**
	 * Don't allow anything to be set
	 *
	 * @access public
	 */
	public function __set($name, $value)
	{
		trigger_error("Cannot write property $name", E_USER_ERROR);
	}

	/**
	 * Set the compressed string and related properties
	 *
	 * @access public
	 */
	public function __construct($data)
	{
		$this->compressed_data = $data;
		$this->compressed_size = strlen($data);
	}

	/**
	 * Decode the GZIP stream
	 *
	 * @access public
	 */
	public function parse()
	{
		if ($this->compressed_size >= $this->min_compressed_size)
		{
			// Check ID1, ID2, and CM
			if (substr($this->compressed_data, 0, 3) !== "\x1F\x8B\x08")
			{
				return false;
			}

			// Get the FLG (FLaGs)
			$this->flags = ord($this->compressed_data[3]);

			// FLG bits above (1 << 4) are reserved
			if ($this->flags > 0x1F)
			{
				return false;
			}

			// Advance the pointer after the above
			$this->position += 4;

			// MTIME
			$mtime = substr($this->compressed_data, $this->position, 4);
			// Reverse the string if we're on a big-endian arch because l is the only signed long and is machine endianness
			if (current(unpack('S', "\x00\x01")) === 1)
			{
				$mtime = strrev($mtime);
			}
			$this->MTIME = current(unpack('l', $mtime));
			$this->position += 4;

			// Get the XFL (eXtra FLags)
			$this->XFL = ord($this->compressed_data[$this->position++]);

			// Get the OS (Operating System)
			$this->OS = ord($this->compressed_data[$this->position++]);

			// Parse the FEXTRA
			if ($this->flags & 4)
			{
				// Read subfield IDs
				$this->SI1 = $this->compressed_data[$this->position++];
				$this->SI2 = $this->compressed_data[$this->position++];

				// SI2 set to zero is reserved for future use
				if ($this->SI2 === "\x00")
				{
					return false;
				}

				// Get the length of the extra field
				$len = current(unpack('v', substr($this->compressed_data, $this->position, 2)));
				$position += 2;

				// Check the length of the string is still valid
				$this->min_compressed_size += $len + 4;
				if ($this->compressed_size >= $this->min_compressed_size)
				{
					// Set the extra field to the given data
					$this->extra_field = substr($this->compressed_data, $this->position, $len);
					$this->position += $len;
				}
				else
				{
					return false;
				}
			}

			// Parse the FNAME
			if ($this->flags & 8)
			{
				// Get the length of the filename
				$len = strcspn($this->compressed_data, "\x00", $this->position);

				// Check the length of the string is still valid
				$this->min_compressed_size += $len + 1;
				if ($this->compressed_size >= $this->min_compressed_size)
				{
					// Set the original filename to the given string
					$this->filename = substr($this->compressed_data, $this->position, $len);
					$this->position += $len + 1;
				}
				else
				{
					return false;
				}
			}

			// Parse the FCOMMENT
			if ($this->flags & 16)
			{
				// Get the length of the comment
				$len = strcspn($this->compressed_data, "\x00", $this->position);

				// Check the length of the string is still valid
				$this->min_compressed_size += $len + 1;
				if ($this->compressed_size >= $this->min_compressed_size)
				{
					// Set the original comment to the given string
					$this->comment = substr($this->compressed_data, $this->position, $len);
					$this->position += $len + 1;
				}
				else
				{
					return false;
				}
			}

			// Parse the FHCRC
			if ($this->flags & 2)
			{
				// Check the length of the string is still valid
				$this->min_compressed_size += $len + 2;
				if ($this->compressed_size >= $this->min_compressed_size)
				{
					// Read the CRC
					$crc = current(unpack('v', substr($this->compressed_data, $this->position, 2)));

					// Check the CRC matches
					if ((crc32(substr($this->compressed_data, 0, $this->position)) & 0xFFFF) === $crc)
					{
						$this->position += 2;
					}
					else
					{
						return false;
					}
				}
				else
				{
					return false;
				}
			}

			// Decompress the actual data
			if (($this->data = gzinflate(substr($this->compressed_data, $this->position, -8))) === false)
			{
				return false;
			}
			else
			{
				$this->position = $this->compressed_size - 8;
			}

			// Check CRC of data
			$crc = current(unpack('V', substr($this->compressed_data, $this->position, 4)));
			$this->position += 4;
			/*if (extension_loaded('hash') && sprintf('%u', current(unpack('V', hash('crc32b', $this->data)))) !== sprintf('%u', $crc))
			{
				return false;
			}*/

			// Check ISIZE of data
			$isize = current(unpack('V', substr($this->compressed_data, $this->position, 4)));
			$this->position += 4;
			if (sprintf('%u', strlen($this->data) & 0xFFFFFFFF) !== sprintf('%u', $isize))
			{
				return false;
			}

			// Wow, against all odds, we've actually got a valid gzip string
			return true;
		}
		else
		{
			return false;
		}
	}
}
