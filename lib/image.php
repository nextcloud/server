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
 * Class for image manipulation
 * Ideas: imagerotate, chunk_split(base64_encode())
 *
 */
class OC_Image {
	static private $resource = false; // tmp resource.
	static private $destroy = false; // if the resource is created withing the object.
	/**
	* @brief Constructor.
	* @param $imageref The path to a local file, a base64 encoded string or a resource created by an imagecreate* function.
	*					If a resource is passed it is the job of the caller to destroy it using imagedestroy($var)
	* @returns bool False on error
	*/
	function __construct($imageref = null) {
		OC_Log::write('core','OC_Image::__construct, start', OC_Log::DEBUG);
		if(!function_exists('imagecreatefromjpeg')) { // FIXME: Find a better way to check for GD
			OC_Log::write('core','OC_Image::__construct, GD module not installed', OC_Log::ERROR);
			return false;
		}
		if(!is_null($imageref)) {
			self::load($imageref);
		}
	}

	/**
	* @brief Destructor.
	*/
	function __destruct() {
		if(self::$resource && self::$destroy) {
			imagedestroy(self::$resource); // Why does this issue a warning.
		}
	}

	/**
	* @brief Prints the image.
	*/
	public function show() {
		header('Content-Type: image/png');
		imagepng(self::$resource);
	}

	/**
	* @brief Prints the image when called as $image().
	*/
	public function __invoke() {
		self::show();
	}

	/**
	* @returns Returns the image resource in any.
	*/
	public function imageResource() {
		return self::$resource;
	}

	/**
	* @returns Returns a base64 encoded string suitable for embedding in a VCard.
	*/
	function __toString() {
		ob_start();
		$res = imagepng(self::$resource);
		if (!$res) {
			OC_Log::write('core','OC_Image::_string. Error writing image',OC_Log::ERROR);
		}
		return chunk_split(base64_encode(ob_get_clean()));
	}

	/**
	* @brief Loads an image from a local file, a base64 encoded string or a resource created by an imagecreate* function.
	* @param $imageref The path to a local file, a base64 encoded string or a resource created by an imagecreate* function.
	*					If a resource is passed it is the job of the caller to destroy it using imagedestroy($var)
	* @returns An image resource or false on error
	*/
	static public function load($imageref) {
		if(self::loadFromFile($imageref) !== false) {
			return self::$resource;
		} elseif(self::loadFromBase64($imageref) !== false) {
			return self::$resource;
		} elseif(self::loadFromData($imageref) !== false) {
			return self::$resource;
		} elseif(self::loadFromResource($imageref) !== false) {
			return self::$resource;
		} else {
			OC_Log::write('core','OC_Image::load, couldn\'t load anything. Giving up!', OC_Log::DEBUG);
			return false;
		}
	}

	/**
	* @brief Loads an image from a local file.
	* @param $imageref The path to a local file.
	* @returns An image resource or false on error
	*/
	static public function loadFromFile($imagepath=false) {
		if(!is_string($imagepath)) {
			return false;
		}
		if(!is_file($imagepath) || !file_exists($imagepath) || !is_readable($imagepath)) {
			OC_Log::write('core','OC_Image::loadFromFile, couldn\'t load'.$imagepath, OC_Log::DEBUG);
			return false;
		}
		self::$resource = imagecreatefromstring(file_get_contents($imagepath));
		self::$destroy = true;
		return self::$resource;
	}

	/**
	* @brief Loads an image from a string of data.
	* @param $str A string of image data as read from a file.
	* @returns An image resource or false on error
	*/
	static public function loadFromData($str) {
		if(is_resource($str)) {
			return false;
		}
		self::$resource = imagecreatefromstring($str);
		if(!self::$resource) {
			OC_Log::write('core','OC_Image::loadFromData, couldn\'t load', OC_Log::DEBUG);
			return false;
		}
		self::$destroy = true;
		return self::$resource;
	}

	/**
	* @brief Loads an image from a base64 encoded string.
	* @param $str A string base64 encoded string of image data.
	* @returns An image resource or false on error
	*/
	static public function loadFromBase64($str) {
		if(!is_string($str)) {
			return false;
		}
		$data = base64_decode($str);
		if($data) { // try to load from string data
			self::$resource = imagecreatefromstring($data);
			if(!self::$resource) {
				OC_Log::write('core','OC_Image::loadFromBase64, couldn\'t load', OC_Log::DEBUG);
				return false;
			}
			self::$destroy = true;
			return self::$resource;
		} else {
			return false;
		}
	}

	/**
	* @brief Checks if image resource is valid and assigns it to self::$resource.
	* @param $res An image resource.
	* @returns An image resource or false on error
	*/
	static public function loadFromResource($res) {
		if(!is_resource($res)) {
			return false;
		}
		self::$resource = $res;
	}

	/**
	* @brief Resizes the image preserving ratio.
	* @param $maxsize The maximum size of either the width or height.
	* @returns bool
	*/
	public function resize($maxsize) {
		if(!self::$resource) {
			OC_Log::write('core','OC_Image::resize, No image loaded', OC_Log::ERROR);
			throw new Exception('OC_Image::resize, No image loaded!', self::ERR_NO_IMAGE);
		}
		$width_orig=imageSX(self::$resource);
		$height_orig=imageSY(self::$resource);
		$ratio_orig = $width_orig/$height_orig;
		
		if ($ratio_orig > 1) {
			$new_height = round($maxsize/$ratio_orig);
			$new_width = $maxsize;
		} else {
			$new_width = round($maxsize*$ratio_orig);
			$new_height = $maxsize;
		}

		$process = imagecreatetruecolor(round($new_width), round($new_height));
		if ($process == false) {
			OC_Log::write('core','OC_Image::resize. Error creating true color image',OC_Log::ERROR);
			imagedestroy($process);
			return false;
		}

		imagecopyresampled($process, self::$resource, 0, 0, 0, 0, $new_width, $new_height, $width_orig, $height_orig);
		if ($process == false) {
			OC_Log::write('core','OC_Image::resize. Error resampling process image '.$new_width.'x'.$new_height,OC_Log::ERROR);
			imagedestroy($process);
			return false;
		}
		self::$resource = $process;
		return true;
	}

	/**
	* @brief Crops the image to the middle square. If the image is already square it just returns.
	* @returns bool for success or failure
	*/
	public function centerCrop() {
		if(!self::$resource) {
			OC_Log::write('core','OC_Image::centerCrop, No image loaded', OC_Log::ERROR);
			return false;
		}
		$width_orig=imageSX(self::$resource);
		$height_orig=imageSY(self::$resource);
		OC_Log::write('core','OC_Image::centerCrop. Original size: '.$width_orig.'x'.$height_orig, OC_Log::DEBUG);
		if($width_orig === $height_orig) {
			return true;
		}
		$ratio_orig = $width_orig/$height_orig;
		OC_Log::write('core','OC_Image::centerCrop. Ratio: '.$ratio_orig, OC_Log::DEBUG);
		$width = $height = min($width_orig, $height_orig);
		OC_Log::write('core','OC_Image::centerCrop. New size: '.$width.'x'.$height, OC_Log::DEBUG);

		if ($ratio_orig > 1) {
			$x = ($width_orig/2) - ($width/2);
			$y = 0;
		} else {
			$y = ($height_orig/2) - ($height/2);
			$x = 0;
		}
		$process = imagecreatetruecolor($width, $height);
		if ($process == false) {
			OC_Log::write('core','OC_Image::centerCrop. Error creating true color image',OC_Log::ERROR);
			imagedestroy($process);
			return false;
		}
		imagecopyresampled($process, self::$resource, 0, 0, $x, $y, $width, $height, $width, $height);
		if ($process == false) {
			OC_Log::write('core','OC_Image::centerCrop. Error resampling process image '.$width.'x'.$height,OC_Log::ERROR);
			imagedestroy($process);
			return false;
		}
		self::$resource = $process;
		return true;
	}

	/**
	* @brief Crops the image from point $x$y with dimension $wx$h.
	* @param $x Horizontal position
	* @param $y Vertical position
	* @param $w Width
	* @param $h Hight
	* @returns bool for success or failure
	*/
	public function crop($x, $y, $w, $h) {
		if(!self::$resource) {
			OC_Log::write('core','OC_Image::crop, No image loaded', OC_Log::ERROR);
			return false;
		}
		$width_orig=imageSX(self::$resource);
		$height_orig=imageSY(self::$resource);
		//OC_Log::write('core','OC_Image::crop. Original size: '.$width_orig.'x'.$height_orig, OC_Log::DEBUG);
		$process = imagecreatetruecolor($w, $h);
		if ($process == false) {
			OC_Log::write('core','OC_Image::crop. Error creating true color image',OC_Log::ERROR);
			imagedestroy($process);
			return false;
		}
		imagecopyresampled($process, self::$resource, 0, 0, $x, $y, $w, $h, $w, $h);
		if ($process == false) {
			OC_Log::write('core','OC_Image::crop. Error resampling process image '.$w.'x'.$h,OC_Log::ERROR);
			imagedestroy($process);
			return false;
		}
		self::$resource = $process;
		return true;
	}
}
