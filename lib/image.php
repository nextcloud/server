<?php

/**
* ownCloud
*
* @author Thomas Tanghus
* @copyright 2011 Thomas Tanghus <thomas@tanghus.net>
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/
/**
 * Class for basic image manipulation
 */
class OC_Image {
	protected $resource = false; // tmp resource.
	protected $imagetype = IMAGETYPE_PNG; // Default to png if file type isn't evident.
	protected $bit_depth = 24;
	protected $filepath = null;

	/**
	* @brief Get mime type for an image file.
	* @param $filepath The path to a local image file.
	* @returns string The mime type if the it could be determined, otherwise an empty string.
	*/
	static public function getMimeTypeForFile($filepath) {
		// exif_imagetype throws "read error!" if file is less than 12 byte
		if (filesize($filepath) > 11) {
			$imagetype = exif_imagetype($filepath);
		}
		else {
			$imagetype = false;
		}
		return $imagetype ? image_type_to_mime_type($imagetype) : '';
	}

	/**
	* @brief Constructor.
	* @param $imageref The path to a local file, a base64 encoded string or a resource created by an imagecreate* function.
	* @returns bool False on error
	*/
	public function __construct($imageref = null) {
		//OC_Log::write('core',__METHOD__.'(): start', OC_Log::DEBUG);
		if(!extension_loaded('gd') || !function_exists('gd_info')) {
			OC_Log::write('core', __METHOD__.'(): GD module not installed', OC_Log::ERROR);
			return false;
		}
		if(!is_null($imageref)) {
			$this->load($imageref);
		}
	}

	/**
	* @brief Determine whether the object contains an image resource.
	* @returns bool
	*/
	public function valid() { // apparently you can't name a method 'empty'...
		return is_resource($this->resource);
	}

	/**
	* @brief Returns the MIME type of the image or an empty string if no image is loaded.
	* @returns int
	*/
	public function mimeType() {
		return $this->valid() ? image_type_to_mime_type($this->imagetype) : '';
	}

	/**
	* @brief Returns the width of the image or -1 if no image is loaded.
	* @returns int
	*/
	public function width() {
		return $this->valid() ? imagesx($this->resource) : -1;
	}

	/**
	* @brief Returns the height of the image or -1 if no image is loaded.
	* @returns int
	*/
	public function height() {
		return $this->valid() ? imagesy($this->resource) : -1;
	}

	/**
	* @brief Returns the width when the image orientation is top-left.
	* @returns int
	*/
	public function widthTopLeft() {
		$o = $this->getOrientation();
		OC_Log::write('core', 'OC_Image->widthTopLeft() Orientation: '.$o, OC_Log::DEBUG);
		switch($o) {
			case -1:
			case 1:
			case 2: // Not tested
			case 3:
			case 4: // Not tested
				return $this->width();
				break;
			case 5: // Not tested
			case 6:
			case 7: // Not tested
			case 8:
				return $this->height();
				break;
		}
		return $this->width();
	}

	/**
	* @brief Returns the height when the image orientation is top-left.
	* @returns int
	*/
	public function heightTopLeft() {
		$o = $this->getOrientation();
		OC_Log::write('core', 'OC_Image->heightTopLeft() Orientation: '.$o, OC_Log::DEBUG);
		switch($o) {
			case -1:
			case 1:
			case 2: // Not tested
			case 3:
			case 4: // Not tested
				return $this->height();
				break;
			case 5: // Not tested
			case 6:
			case 7: // Not tested
			case 8:
				return $this->width();
				break;
		}
		return $this->height();
	}

	/**
	* @brief Outputs the image.
	* @returns bool
	*/
	public function show() {
		header('Content-Type: '.$this->mimeType());
		return $this->_output();
	}

	/**
	* @brief Saves the image.
	* @returns bool
	*/

	public function save($filepath=null) {
		if($filepath === null && $this->filepath === null) {
			OC_Log::write('core', __METHOD__.'(): called with no path.', OC_Log::ERROR);
			return false;
		} elseif($filepath === null && $this->filepath !== null) {
			$filepath = $this->filepath;
		}
		return $this->_output($filepath);
	}

	/**
	* @brief Outputs/saves the image.
	*/
	private function _output($filepath=null) {
		if($filepath) {
			if (!file_exists(dirname($filepath)))
				mkdir(dirname($filepath), 0777, true);
			if(!is_writable(dirname($filepath))) {
				OC_Log::write('core',
					__METHOD__.'(): Directory \''.dirname($filepath).'\' is not writable.',
					OC_Log::ERROR);
				return false;
			} elseif(is_writable(dirname($filepath)) && file_exists($filepath) && !is_writable($filepath)) {
				OC_Log::write('core', __METHOD__.'(): File \''.$filepath.'\' is not writable.', OC_Log::ERROR);
				return false;
			}
		}
		if (!$this->valid()) {
			return false;
		}

		$retval = false;
		switch($this->imagetype) {
			case IMAGETYPE_GIF:
				$retval = imagegif($this->resource, $filepath);
				break;
			case IMAGETYPE_JPEG:
				$retval = imagejpeg($this->resource, $filepath);
				break;
			case IMAGETYPE_PNG:
				$retval = imagepng($this->resource, $filepath);
				break;
			case IMAGETYPE_XBM:
				$retval = imagexbm($this->resource, $filepath);
				break;
			case IMAGETYPE_WBMP:
				$retval = imagewbmp($this->resource, $filepath);
				break;
			case IMAGETYPE_BMP:
				$retval = imagebmp($this->resource, $filepath, $this->bit_depth);
				break;
			default:
				$retval = imagepng($this->resource, $filepath);
		}
		return $retval;
	}

	/**
	* @brief Prints the image when called as $image().
	*/
	public function __invoke() {
		return $this->show();
	}

	/**
	* @returns Returns the image resource in any.
	*/
	public function resource() {
		return $this->resource;
	}

	/**
	* @returns Returns the raw image data.
	*/
	function data() {
		ob_start();
		$res = imagepng($this->resource);
		if (!$res) {
			OC_Log::write('core', 'OC_Image->data. Error getting image data.', OC_Log::ERROR);
		}
		return ob_get_clean();
	}

	/**
	* @returns Returns a base64 encoded string suitable for embedding in a VCard.
	*/
	function __toString() {
		return base64_encode($this->data());
	}

	/**
	* (I'm open for suggestions on better method name ;)
	* @brief Get the orientation based on EXIF data.
	* @returns The orientation or -1 if no EXIF data is available.
	*/
	public function getOrientation() {
		if(!is_callable('exif_read_data')) {
			OC_Log::write('core', 'OC_Image->fixOrientation() Exif module not enabled.', OC_Log::DEBUG);
			return -1;
		}
		if(!$this->valid()) {
			OC_Log::write('core', 'OC_Image->fixOrientation() No image loaded.', OC_Log::DEBUG);
			return -1;
		}
		if(is_null($this->filepath) || !is_readable($this->filepath)) {
			OC_Log::write('core', 'OC_Image->fixOrientation() No readable file path set.', OC_Log::DEBUG);
			return -1;
		}
		$exif = @exif_read_data($this->filepath, 'IFD0');
		if(!$exif) {
			return -1;
		}
		if(!isset($exif['Orientation'])) {
			return -1;
		}
		return $exif['Orientation'];
	}

	/**
	* (I'm open for suggestions on better method name ;)
	* @brief Fixes orientation based on EXIF data.
	* @returns bool.
	*/
	public function fixOrientation() {
		$o = $this->getOrientation();
		OC_Log::write('core', 'OC_Image->fixOrientation() Orientation: '.$o, OC_Log::DEBUG);
		$rotate = 0;
		$flip = false;
		switch($o) {
			case -1:
				return false; //Nothing to fix
				break;
			case 1:
				$rotate = 0;
				$flip = false;
				break;
			case 2: // Not tested
				$rotate = 0;
				$flip = true;
				break;
			case 3:
				$rotate = 180;
				$flip = false;
				break;
			case 4: // Not tested
				$rotate = 180;
				$flip = true;
				break;
			case 5: // Not tested
				$rotate = 90;
				$flip = true;
				break;
			case 6:
				//$rotate = 90;
				$rotate = 270;
				$flip = false;
				break;
			case 7: // Not tested
				$rotate = 270;
				$flip = true;
				break;
			case 8:
				$rotate = 90;
				$flip = false;
				break;
		}
		if($rotate) {
			$res = imagerotate($this->resource, $rotate, -1);
			if($res) {
				if(imagealphablending($res, true)) {
					if(imagesavealpha($res, true)) {
						imagedestroy($this->resource);
						$this->resource = $res;
						return true;
					} else {
						OC_Log::write('core', 'OC_Image->fixOrientation() Error during alphasaving.', OC_Log::DEBUG);
						return false;
					}
				} else {
					OC_Log::write('core', 'OC_Image->fixOrientation() Error during alphablending.', OC_Log::DEBUG);
					return false;
				}
			} else {
				OC_Log::write('core', 'OC_Image->fixOrientation() Error during oriention fixing.', OC_Log::DEBUG);
				return false;
			}
		}
	}

	/**
	* @brief Loads an image from a local file, a base64 encoded string or a resource created by an imagecreate* function.
	* @param $imageref The path to a local file, a base64 encoded string or a resource created by an imagecreate* function or a file resource (file handle	).
	* @returns An image resource or false on error
	*/
	public function load($imageref) {
		if(is_resource($imageref)) {
			if(get_resource_type($imageref) == 'gd') {
				$this->resource = $imageref;
				return $this->resource;
			} elseif(in_array(get_resource_type($imageref), array('file', 'stream'))) {
				return $this->loadFromFileHandle($imageref);
			}
		} elseif($this->loadFromFile($imageref) !== false) {
			return $this->resource;
		} elseif($this->loadFromBase64($imageref) !== false) {
			return $this->resource;
		} elseif($this->loadFromData($imageref) !== false) {
			return $this->resource;
		} else {
			OC_Log::write('core', __METHOD__.'(): couldn\'t load anything. Giving up!', OC_Log::DEBUG);
			return false;
		}
	}

	/**
	* @brief Loads an image from an open file handle.
	* It is the responsibility of the caller to position the pointer at the correct place and to close the handle again.
	* @param $handle
	* @returns An image resource or false on error
	*/
	public function loadFromFileHandle($handle) {
		OC_Log::write('core', __METHOD__.'(): Trying', OC_Log::DEBUG);
		$contents = stream_get_contents($handle);
		if($this->loadFromData($contents)) {
			return $this->resource;
		}
	}

	/**
	* @brief Loads an image from a local file.
	* @param $imageref The path to a local file.
	* @returns An image resource or false on error
	*/
	public function loadFromFile($imagepath=false) {
		// exif_imagetype throws "read error!" if file is less than 12 byte
		if(!is_file($imagepath) || !file_exists($imagepath) || filesize($imagepath) < 12 || !is_readable($imagepath)) {
			// Debug output disabled because this method is tried before loadFromBase64?
			OC_Log::write('core', 'OC_Image->loadFromFile, couldn\'t load: '.$imagepath, OC_Log::DEBUG);
			return false;
		}
		$itype = exif_imagetype($imagepath);
		switch($itype) {
			case IMAGETYPE_GIF:
				if (imagetypes() & IMG_GIF) {
					$this->resource = imagecreatefromgif($imagepath);
				} else {
					OC_Log::write('core',
						'OC_Image->loadFromFile, GIF images not supported: '.$imagepath,
						OC_Log::DEBUG);
				}
				break;
			case IMAGETYPE_JPEG:
				if (imagetypes() & IMG_JPG) {
					$this->resource = imagecreatefromjpeg($imagepath);
				} else {
					OC_Log::write('core',
						'OC_Image->loadFromFile, JPG images not supported: '.$imagepath,
						OC_Log::DEBUG);
				}
				break;
			case IMAGETYPE_PNG:
				if (imagetypes() & IMG_PNG) {
					$this->resource = imagecreatefrompng($imagepath);
				} else {
					OC_Log::write('core',
						'OC_Image->loadFromFile, PNG images not supported: '.$imagepath,
						OC_Log::DEBUG);
				}
				break;
			case IMAGETYPE_XBM:
				if (imagetypes() & IMG_XPM) {
					$this->resource = imagecreatefromxbm($imagepath);
				} else {
					OC_Log::write('core',
						'OC_Image->loadFromFile, XBM/XPM images not supported: '.$imagepath,
						OC_Log::DEBUG);
				}
				break;
			case IMAGETYPE_WBMP:
				if (imagetypes() & IMG_WBMP) {
					$this->resource = imagecreatefromwbmp($imagepath);
				} else {
					OC_Log::write('core',
						'OC_Image->loadFromFile, WBMP images not supported: '.$imagepath,
						OC_Log::DEBUG);
				}
				break;
			case IMAGETYPE_BMP:
					$this->resource = $this->imagecreatefrombmp($imagepath);
				break;
			/*
			case IMAGETYPE_TIFF_II: // (intel byte order)
				break;
			case IMAGETYPE_TIFF_MM: // (motorola byte order)
				break;
			case IMAGETYPE_JPC:
				break;
			case IMAGETYPE_JP2:
				break;
			case IMAGETYPE_JPX:
				break;
			case IMAGETYPE_JB2:
				break;
			case IMAGETYPE_SWC:
				break;
			case IMAGETYPE_IFF:
				break;
			case IMAGETYPE_ICO:
				break;
			case IMAGETYPE_SWF:
				break;
			case IMAGETYPE_PSD:
				break;
			*/
			default:

				// this is mostly file created from encrypted file
				$this->resource = imagecreatefromstring(\OC\Files\Filesystem::file_get_contents(\OC\Files\Filesystem::getLocalPath($imagepath)));
				$itype = IMAGETYPE_PNG;
				OC_Log::write('core', 'OC_Image->loadFromFile, Default', OC_Log::DEBUG);
				break;
		}
		if($this->valid()) {
			$this->imagetype = $itype;
			$this->filepath = $imagepath;
		}
		return $this->resource;
	}

	/**
	* @brief Loads an image from a string of data.
	* @param $str A string of image data as read from a file.
	* @returns An image resource or false on error
	*/
	public function loadFromData($str) {
		if(is_resource($str)) {
			return false;
		}
		$this->resource = @imagecreatefromstring($str);
		if(!$this->resource) {
			OC_Log::write('core', 'OC_Image->loadFromData, couldn\'t load', OC_Log::DEBUG);
			return false;
		}
		return $this->resource;
	}

	/**
	* @brief Loads an image from a base64 encoded string.
	* @param $str A string base64 encoded string of image data.
	* @returns An image resource or false on error
	*/
	public function loadFromBase64($str) {
		if(!is_string($str)) {
			return false;
		}
		$data = base64_decode($str);
		if($data) { // try to load from string data
			$this->resource = @imagecreatefromstring($data);
			if(!$this->resource) {
				OC_Log::write('core', 'OC_Image->loadFromBase64, couldn\'t load', OC_Log::DEBUG);
				return false;
			}
			return $this->resource;
		} else {
			return false;
		}
	}

	/**
	 * Create a new image from file or URL
	 * @link http://www.programmierer-forum.de/function-imagecreatefrombmp-laeuft-mit-allen-bitraten-t143137.htm
	 * @version 1.00
	 * @param string $filename <p>
	 * Path to the BMP image.
	 * </p>
	 * @return resource an image resource identifier on success, <b>FALSE</b> on errors.
	 */
	private function imagecreatefrombmp($filename) {
		if (!($fh = fopen($filename, 'rb'))) {
			trigger_error('imagecreatefrombmp: Can not open ' . $filename, E_USER_WARNING);
			return false;
		}
		// read file header
		$meta = unpack('vtype/Vfilesize/Vreserved/Voffset', fread($fh, 14));
		// check for bitmap
		if ($meta['type'] != 19778) {
			trigger_error('imagecreatefrombmp: ' . $filename . ' is not a bitmap!', E_USER_WARNING);
			return false;
		}
		// read image header
		$meta += unpack('Vheadersize/Vwidth/Vheight/vplanes/vbits/Vcompression/Vimagesize/Vxres/Vyres/Vcolors/Vimportant', fread($fh, 40));
		// read additional 16bit header
		if ($meta['bits'] == 16) {
			$meta += unpack('VrMask/VgMask/VbMask', fread($fh, 12));
		}
		// set bytes and padding
		$meta['bytes'] = $meta['bits'] / 8;
		$this->bit_depth = $meta['bits']; //remember the bit depth for the imagebmp call
		$meta['decal'] = 4 - (4 * (($meta['width'] * $meta['bytes'] / 4)- floor($meta['width'] * $meta['bytes'] / 4)));
		if ($meta['decal'] == 4) {
			$meta['decal'] = 0;
		}
		// obtain imagesize
		if ($meta['imagesize'] < 1) {
			$meta['imagesize'] = $meta['filesize'] - $meta['offset'];
			// in rare cases filesize is equal to offset so we need to read physical size
			if ($meta['imagesize'] < 1) {
				$meta['imagesize'] = @filesize($filename) - $meta['offset'];
				if ($meta['imagesize'] < 1) {
					trigger_error('imagecreatefrombmp: Can not obtain filesize of ' . $filename . '!', E_USER_WARNING);
					return false;
				}
			}
		}
		// calculate colors
		$meta['colors'] = !$meta['colors'] ? pow(2, $meta['bits']) : $meta['colors'];
		// read color palette
		$palette = array();
		if ($meta['bits'] < 16) {
			$palette = unpack('l' . $meta['colors'], fread($fh, $meta['colors'] * 4));
			// in rare cases the color value is signed
			if ($palette[1] < 0) {
				foreach ($palette as $i => $color) {
					$palette[$i] = $color + 16777216;
				}
			}
		}
		// create gd image
		$im = imagecreatetruecolor($meta['width'], $meta['height']);
		$data = fread($fh, $meta['imagesize']);
		$p = 0;
		$vide = chr(0);
		$y = $meta['height'] - 1;
		$error = 'imagecreatefrombmp: ' . $filename . ' has not enough data!';
		// loop through the image data beginning with the lower left corner
		while ($y >= 0) {
			$x = 0;
			while ($x < $meta['width']) {
				switch ($meta['bits']) {
					case 32:
					case 24:
						if (!($part = substr($data, $p, 3))) {
							trigger_error($error, E_USER_WARNING);
							return $im;
						}
						$color = unpack('V', $part . $vide);
						break;
					case 16:
						if (!($part = substr($data, $p, 2))) {
							trigger_error($error, E_USER_WARNING);
							return $im;
						}
						$color = unpack('v', $part);
						$color[1] = (($color[1] & 0xf800) >> 8) * 65536 + (($color[1] & 0x07e0) >> 3) * 256 + (($color[1] & 0x001f) << 3);
						break;
					case 8:
						$color = unpack('n', $vide . substr($data, $p, 1));
						$color[1] = $palette[ $color[1] + 1 ];
						break;
					case 4:
						$color = unpack('n', $vide . substr($data, floor($p), 1));
						$color[1] = ($p * 2) % 2 == 0 ? $color[1] >> 4 : $color[1] & 0x0F;
						$color[1] = $palette[ $color[1] + 1 ];
						break;
					case 1:
						$color = unpack('n', $vide . substr($data, floor($p), 1));
						switch (($p * 8) % 8) {
							case 0:
								$color[1] = $color[1] >> 7;
								break;
							case 1:
								$color[1] = ($color[1] & 0x40) >> 6;
								break;
							case 2:
								$color[1] = ($color[1] & 0x20) >> 5;
								break;
							case 3:
								$color[1] = ($color[1] & 0x10) >> 4;
								break;
							case 4:
								$color[1] = ($color[1] & 0x8) >> 3;
								break;
							case 5:
								$color[1] = ($color[1] & 0x4) >> 2;
								break;
							case 6:
								$color[1] = ($color[1] & 0x2) >> 1;
								break;
							case 7:
								$color[1] = ($color[1] & 0x1);
								break;
						}
						$color[1] = $palette[ $color[1] + 1 ];
						break;
					default:
						trigger_error('imagecreatefrombmp: '
							. $filename . ' has ' . $meta['bits'] . ' bits and this is not supported!',
							E_USER_WARNING);
						return false;
				}
				imagesetpixel($im, $x, $y, $color[1]);
				$x++;
				$p += $meta['bytes'];
			}
			$y--;
			$p += $meta['decal'];
		}
		fclose($fh);
		return $im;
	}

	/**
	* @brief Resizes the image preserving ratio.
	* @param $maxsize The maximum size of either the width or height.
	* @returns bool
	*/
	public function resize($maxsize) {
		if(!$this->valid()) {
			OC_Log::write('core', __METHOD__.'(): No image loaded', OC_Log::ERROR);
			return false;
		}
		$width_orig=imageSX($this->resource);
		$height_orig=imageSY($this->resource);
		$ratio_orig = $width_orig/$height_orig;

		if ($ratio_orig > 1) {
			$new_height = round($maxsize/$ratio_orig);
			$new_width = $maxsize;
		} else {
			$new_width = round($maxsize*$ratio_orig);
			$new_height = $maxsize;
		}

		$this->preciseResize(round($new_width), round($new_height));
		return true;
	}

	public function preciseResize($width, $height) {
		if (!$this->valid()) {
			OC_Log::write('core', __METHOD__.'(): No image loaded', OC_Log::ERROR);
			return false;
		}
		$width_orig=imageSX($this->resource);
		$height_orig=imageSY($this->resource);
		$process = imagecreatetruecolor($width, $height);

		if ($process == false) {
			OC_Log::write('core', __METHOD__.'(): Error creating true color image', OC_Log::ERROR);
			imagedestroy($process);
			return false;
		}

		// preserve transparency
		if($this->imagetype == IMAGETYPE_GIF or $this->imagetype == IMAGETYPE_PNG) {
			imagecolortransparent($process, imagecolorallocatealpha($process, 0, 0, 0, 127));
			imagealphablending($process, false);
			imagesavealpha($process, true);
		}

		imagecopyresampled($process, $this->resource, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
		if ($process == false) {
			OC_Log::write('core', __METHOD__.'(): Error resampling process image '.$width.'x'.$height, OC_Log::ERROR);
			imagedestroy($process);
			return false;
		}
		imagedestroy($this->resource);
		$this->resource = $process;
		return true;
	}

	/**
	* @brief Crops the image to the middle square. If the image is already square it just returns.
	* @param int maximum size for the result (optional)
	* @returns bool for success or failure
	*/
	public function centerCrop($size=0) {
		if(!$this->valid()) {
			OC_Log::write('core', 'OC_Image->centerCrop, No image loaded', OC_Log::ERROR);
			return false;
		}
		$width_orig=imageSX($this->resource);
		$height_orig=imageSY($this->resource);
		if($width_orig === $height_orig and $size==0) {
			return true;
		}
		$ratio_orig = $width_orig/$height_orig;
		$width = $height = min($width_orig, $height_orig);

		if ($ratio_orig > 1) {
			$x = ($width_orig/2) - ($width/2);
			$y = 0;
		} else {
			$y = ($height_orig/2) - ($height/2);
			$x = 0;
		}
		if($size>0) {
			$targetWidth=$size;
			$targetHeight=$size;
		}else{
			$targetWidth=$width;
			$targetHeight=$height;
		}
		$process = imagecreatetruecolor($targetWidth, $targetHeight);
		if ($process == false) {
			OC_Log::write('core', 'OC_Image->centerCrop. Error creating true color image', OC_Log::ERROR);
			imagedestroy($process);
			return false;
		}

		// preserve transparency
		if($this->imagetype == IMAGETYPE_GIF or $this->imagetype == IMAGETYPE_PNG) {
			imagecolortransparent($process, imagecolorallocatealpha($process, 0, 0, 0, 127));
			imagealphablending($process, false);
			imagesavealpha($process, true);
		}

		imagecopyresampled($process, $this->resource, 0, 0, $x, $y, $targetWidth, $targetHeight, $width, $height);
		if ($process == false) {
			OC_Log::write('core',
				'OC_Image->centerCrop. Error resampling process image '.$width.'x'.$height,
				OC_Log::ERROR);
			imagedestroy($process);
			return false;
		}
		imagedestroy($this->resource);
		$this->resource = $process;
		return true;
	}

	/**
	* @brief Crops the image from point $x$y with dimension $wx$h.
	* @param $x Horizontal position
	* @param $y Vertical position
	* @param $w Width
	* @param $h Height
	* @returns bool for success or failure
	*/
	public function crop($x, $y, $w, $h) {
		if(!$this->valid()) {
			OC_Log::write('core', __METHOD__.'(): No image loaded', OC_Log::ERROR);
			return false;
		}
		$process = imagecreatetruecolor($w, $h);
		if ($process == false) {
			OC_Log::write('core', __METHOD__.'(): Error creating true color image', OC_Log::ERROR);
			imagedestroy($process);
			return false;
		}
		imagecopyresampled($process, $this->resource, 0, 0, $x, $y, $w, $h, $w, $h);
		if ($process == false) {
			OC_Log::write('core', __METHOD__.'(): Error resampling process image '.$w.'x'.$h, OC_Log::ERROR);
			imagedestroy($process);
			return false;
		}
		imagedestroy($this->resource);
		$this->resource = $process;
		return true;
	}

	/**
	 * @brief Resizes the image to fit within a boundry while preserving ratio.
	 * @param $maxWidth
	 * @param $maxHeight
	 * @returns bool
	 */
	public function fitIn($maxWidth, $maxHeight) {
		if(!$this->valid()) {
			OC_Log::write('core', __METHOD__.'(): No image loaded', OC_Log::ERROR);
			return false;
		}
		$width_orig=imageSX($this->resource);
		$height_orig=imageSY($this->resource);
		$ratio = $width_orig/$height_orig;

		$newWidth = min($maxWidth, $ratio*$maxHeight);
		$newHeight = min($maxHeight, $maxWidth/$ratio);

		$this->preciseResize(round($newWidth), round($newHeight));
		return true;
	}

	public function destroy() {
		if($this->valid()) {
			imagedestroy($this->resource);
		}
		$this->resource=null;
	}

	public function __destruct() {
		$this->destroy();
	}
}
if ( ! function_exists( 'imagebmp') ) {
	/**
	 * Output a BMP image to either the browser or a file
	 * @link http://www.ugia.cn/wp-data/imagebmp.php
	 * @author legend <legendsky@hotmail.com>
	 * @link http://www.programmierer-forum.de/imagebmp-gute-funktion-gefunden-t143716.htm
	 * @author mgutt <marc@gutt.it>
	 * @version 1.00
	 * @param resource $image
	 * @param string $filename [optional] <p>The path to save the file to.</p>
	 * @param int $bit [optional] <p>Bit depth, (default is 24).</p>
	 * @param int $compression [optional]
	 * @return bool <b>TRUE</b> on success or <b>FALSE</b> on failure.
	 */
	function imagebmp($im, $filename='', $bit=24, $compression=0) {
		if (!in_array($bit, array(1, 4, 8, 16, 24, 32))) {
			$bit = 24;
		}
		else if ($bit == 32) {
			$bit = 24;
		}
		$bits = pow(2, $bit);
		imagetruecolortopalette($im, true, $bits);
		$width = imagesx($im);
		$height = imagesy($im);
		$colors_num = imagecolorstotal($im);
		$rgb_quad = '';
		if ($bit <= 8) {
			for ($i = 0; $i < $colors_num; $i++) {
				$colors = imagecolorsforindex($im, $i);
				$rgb_quad .= chr($colors['blue']) . chr($colors['green']) . chr($colors['red']) . "\0";
			}
			$bmp_data = '';
			if ($compression == 0 || $bit < 8) {
				$compression = 0;
				$extra = '';
				$padding = 4 - ceil($width / (8 / $bit)) % 4;
				if ($padding % 4 != 0) {
					$extra = str_repeat("\0", $padding);
				}
				for ($j = $height - 1; $j >= 0; $j --) {
					$i = 0;
					while ($i < $width) {
						$bin = 0;
						$limit = $width - $i < 8 / $bit ? (8 / $bit - $width + $i) * $bit : 0;
						for ($k = 8 - $bit; $k >= $limit; $k -= $bit) {
							$index = imagecolorat($im, $i, $j);
							$bin |= $index << $k;
							$i++;
						}
						$bmp_data .= chr($bin);
					}
					$bmp_data .= $extra;
				}
			}
			// RLE8
			else if ($compression == 1 && $bit == 8) {
				for ($j = $height - 1; $j >= 0; $j--) {
					$last_index = "\0";
					$same_num = 0;
					for ($i = 0; $i <= $width; $i++) {
						$index = imagecolorat($im, $i, $j);
						if ($index !== $last_index || $same_num > 255) {
							if ($same_num != 0) {
								$bmp_data .= chr($same_num) . chr($last_index);
							}
							$last_index = $index;
							$same_num = 1;
						}
						else {
							$same_num++;
						}
					}
					$bmp_data .= "\0\0";
				}
				$bmp_data .= "\0\1";
			}
			$size_quad = strlen($rgb_quad);
			$size_data = strlen($bmp_data);
		}
		else {
			$extra = '';
			$padding = 4 - ($width * ($bit / 8)) % 4;
			if ($padding % 4 != 0) {
				$extra = str_repeat("\0", $padding);
			}
			$bmp_data = '';
			for ($j = $height - 1; $j >= 0; $j--) {
				for ($i = 0; $i < $width; $i++) {
					$index  = imagecolorat($im, $i, $j);
					$colors = imagecolorsforindex($im, $index);
					if ($bit == 16) {
						$bin = 0 << $bit;
						$bin |= ($colors['red'] >> 3) << 10;
						$bin |= ($colors['green'] >> 3) << 5;
						$bin |= $colors['blue'] >> 3;
						$bmp_data .= pack("v", $bin);
					}
					else {
						$bmp_data .= pack("c*", $colors['blue'], $colors['green'], $colors['red']);
					}
				}
				$bmp_data .= $extra;
			}
			$size_quad = 0;
			$size_data = strlen($bmp_data);
			$colors_num = 0;
		}
		$file_header = 'BM' . pack('V3', 54 + $size_quad + $size_data, 0, 54 + $size_quad);
		$info_header = pack('V3v2V*', 0x28, $width, $height, 1, $bit, $compression, $size_data, 0, 0, $colors_num, 0);
		if ($filename != '') {
			$fp = fopen($filename, 'wb');
			fwrite($fp, $file_header . $info_header . $rgb_quad . $bmp_data);
			fclose($fp);
			return true;
		}
		echo $file_header . $info_header. $rgb_quad . $bmp_data;
		return true;
	}
}

if ( ! function_exists( 'exif_imagetype' ) ) {
	/**
	 * Workaround if exif_imagetype does not exist
	 * @link http://www.php.net/manual/en/function.exif-imagetype.php#80383
	 * @param string $filename
	 * @return string|boolean
	 */
	function exif_imagetype ( $filename ) {
		if ( ( $info = getimagesize( $filename ) ) !== false ) {
			return $info[2];
		}
		return false;
	}
}
