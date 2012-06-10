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

//From user comments at http://dk2.php.net/manual/en/function.exif-imagetype.php
if ( ! function_exists( 'exif_imagetype' ) ) {
    function exif_imagetype ( $filename ) {
        if ( ( list($width, $height, $type, $attr) = getimagesize( $filename ) ) !== false ) {
            return $type;
        }
    return false;
    }
}

function ellipsis($str, $maxlen) {
	if (strlen($str) > $maxlen) {
		$characters = floor($maxlen / 2);
		return substr($str, 0, $characters) . '...' . substr($str, -1 * $characters);
	}
	return $str;
}

/**
 * Class for basic image manipulation
 *
 */
class OC_Image {
	protected $resource = false; // tmp resource.
	protected $imagetype = IMAGETYPE_PNG; // Default to png if file type isn't evident.
	protected $filepath = null;

	/**
	* @brief Get mime type for an image file.
	* @param $filepath The path to a local image file.
	* @returns string The mime type if the it could be determined, otherwise an empty string.
	*/
	static public function getMimeTypeForFile($filepath) {
		$imagetype = exif_imagetype($filepath);
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
		//if(!function_exists('imagecreatefromjpeg')) {
			OC_Log::write('core',__METHOD__.'(): GD module not installed', OC_Log::ERROR);
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
			OC_Log::write('core',__METHOD__.'(): called with no path.', OC_Log::ERROR);
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
				OC_Log::write('core',__METHOD__.'(): Directory \''.dirname($filepath).'\' is not writable.', OC_Log::ERROR);
				return false;
			} elseif(is_writable(dirname($filepath)) && file_exists($filepath) && !is_writable($filepath)) {
				OC_Log::write('core',__METHOD__.'(): File \''.$filepath.'\' is not writable.', OC_Log::ERROR);
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
			case IMAGETYPE_BMP:
				$retval = imagewbmp($this->resource, $filepath);
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
	* @returns Returns a base64 encoded string suitable for embedding in a VCard.
	*/
	function __toString() {
		ob_start();
		$res = imagepng($this->resource);
		if (!$res) {
			OC_Log::write('core','OC_Image->__toString. Error writing image',OC_Log::ERROR);
		}
		return base64_encode(ob_get_clean());
	}

	/**
	* (I'm open for suggestions on better method name ;)
	* @brief Fixes orientation based on EXIF data.
	* @returns bool.
	*/
	public function fixOrientation() {
		if(!is_callable('exif_read_data')){
			OC_Log::write('core','OC_Image->fixOrientation() Exif module not enabled.', OC_Log::DEBUG);
			return false;
		}
		if(!$this->valid()) {
			OC_Log::write('core','OC_Image->fixOrientation() No image loaded.', OC_Log::DEBUG);
			return false;
		}
		if(is_null($this->filepath) || !is_readable($this->filepath)) {
			OC_Log::write('core','OC_Image->fixOrientation() No readable file path set.', OC_Log::DEBUG);
			return false;
		}
		$exif = @exif_read_data($this->filepath, 'IFD0');
		if(!$exif) {
			return false;
		}
		if(!isset($exif['Orientation'])) {
			return true; // Nothing to fix
		}
		$o = $exif['Orientation'];
		OC_Log::write('core','OC_Image->fixOrientation() Orientation: '.$o, OC_Log::DEBUG);
		$rotate = 0;
		$flip = false;
		switch($o) {
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
						OC_Log::write('core','OC_Image->fixOrientation() Error during alphasaving.', OC_Log::DEBUG);
						return false;
					}
				} else {
					OC_Log::write('core','OC_Image->fixOrientation() Error during alphablending.', OC_Log::DEBUG);
					return false;
				}
			} else {
				OC_Log::write('core','OC_Image->fixOrientation() Error during oriention fixing.', OC_Log::DEBUG);
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
				$this->resource = $res;
				return $this->resource;
			} elseif(in_array(get_resource_type($imageref), array('file','stream'))) {
				return $this->loadFromFileHandle($imageref);
			}
		} elseif($this->loadFromFile($imageref) !== false) {
			return $this->resource;
		} elseif($this->loadFromBase64($imageref) !== false) {
			return $this->resource;
		} elseif($this->loadFromData($imageref) !== false) {
			return $this->resource;
		} else {
			OC_Log::write('core',__METHOD__.'(): couldn\'t load anything. Giving up!', OC_Log::DEBUG);
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
		OC_Log::write('core',__METHOD__.'(): Trying', OC_Log::DEBUG);
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
		if(!is_file($imagepath) || !file_exists($imagepath) || !is_readable($imagepath)) {
			// Debug output disabled because this method is tried before loadFromBase64?
			OC_Log::write('core','OC_Image->loadFromFile, couldn\'t load: '.ellipsis($imagepath, 50), OC_Log::DEBUG);
			return false;
		}
		$itype = exif_imagetype($imagepath);
		switch($itype) {
			case IMAGETYPE_GIF:
				if (imagetypes() & IMG_GIF) {
					$this->resource = imagecreatefromgif($imagepath);
				} else {
					OC_Log::write('core','OC_Image->loadFromFile, GIF images not supported: '.$imagepath, OC_Log::DEBUG);
				}
				break;
			case IMAGETYPE_JPEG:
				if (imagetypes() & IMG_JPG) {
					$this->resource = imagecreatefromjpeg($imagepath);
				} else {
					OC_Log::write('core','OC_Image->loadFromFile, JPG images not supported: '.$imagepath, OC_Log::DEBUG);
				}
				break;
			case IMAGETYPE_PNG:
				if (imagetypes() & IMG_PNG) {
					$this->resource = imagecreatefrompng($imagepath);
				} else {
					OC_Log::write('core','OC_Image->loadFromFile, PNG images not supported: '.$imagepath, OC_Log::DEBUG);
				}
				break;
			case IMAGETYPE_XBM:
				if (imagetypes() & IMG_XPM) {
					$this->resource = imagecreatefromxbm($imagepath);
				} else {
					OC_Log::write('core','OC_Image->loadFromFile, XBM/XPM images not supported: '.$imagepath, OC_Log::DEBUG);
				}
				break;
			case IMAGETYPE_WBMP:
			case IMAGETYPE_BMP:
				if (imagetypes() & IMG_WBMP) {
					$this->resource = imagecreatefromwbmp($imagepath);
				} else {
					OC_Log::write('core','OC_Image->loadFromFile, (W)BMP images not supported: '.$imagepath, OC_Log::DEBUG);
				}
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
				$this->resource = imagecreatefromstring(\OC_Filesystem::file_get_contents(\OC_Filesystem::getLocalPath($imagepath)));
				$itype = IMAGETYPE_PNG;
				OC_Log::write('core','OC_Image->loadFromFile, Default', OC_Log::DEBUG);
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
			OC_Log::write('core','OC_Image->loadFromData, couldn\'t load', OC_Log::DEBUG);
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
				OC_Log::write('core','OC_Image->loadFromBase64, couldn\'t load', OC_Log::DEBUG);
				return false;
			}
			return $this->resource;
		} else {
			return false;
		}
	}

	/**
	* @brief Resizes the image preserving ratio.
	* @param $maxsize The maximum size of either the width or height.
	* @returns bool
	*/
	public function resize($maxsize) {
		if(!$this->valid()) {
			OC_Log::write('core',__METHOD__.'(): No image loaded', OC_Log::ERROR);
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

		$process = imagecreatetruecolor(round($new_width), round($new_height));
		if ($process == false) {
			OC_Log::write('core',__METHOD__.'(): Error creating true color image',OC_Log::ERROR);
			imagedestroy($process);
			return false;
		}

		imagecopyresampled($process, $this->resource, 0, 0, 0, 0, $new_width, $new_height, $width_orig, $height_orig);
		if ($process == false) {
			OC_Log::write('core',__METHOD__.'(): Error resampling process image '.$new_width.'x'.$new_height,OC_Log::ERROR);
			imagedestroy($process);
			return false;
		}
		imagedestroy($this->resource);
		$this->resource = $process;
		return true;
	}

	public function preciseResize($width, $height) {
		if (!$this->valid()) {
			OC_Log::write('core',__METHOD__.'(): No image loaded', OC_Log::ERROR);
			return false;			
		}
		$width_orig=imageSX($this->resource);
		$height_orig=imageSY($this->resource);
		$process = imagecreatetruecolor($width, $height);

		if ($process == false) {
			OC_Log::write('core',__METHOD__.'(): Error creating true color image',OC_Log::ERROR);
			imagedestroy($process);
			return false;
		}

		imagecopyresampled($process, $this->resource, 0, 0, 0, 0, $width, $height, $width_orig, $height_orig);
		if ($process == false) {
			OC_Log::write('core',__METHOD__.'(): Error resampling process image '.$width.'x'.$height,OC_Log::ERROR);
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
			OC_Log::write('core','OC_Image->centerCrop, No image loaded', OC_Log::ERROR);
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
		if($size>0){
			$targetWidth=$size;
			$targetHeight=$size;
		}else{
			$targetWidth=$width;
			$targetHeight=$height;
		}
		$process = imagecreatetruecolor($targetWidth, $targetHeight);
		if ($process == false) {
			OC_Log::write('core','OC_Image->centerCrop. Error creating true color image',OC_Log::ERROR);
			imagedestroy($process);
			return false;
		}
		imagecopyresampled($process, $this->resource, 0, 0, $x, $y, $targetWidth, $targetHeight, $width, $height);
		if ($process == false) {
			OC_Log::write('core','OC_Image->centerCrop. Error resampling process image '.$width.'x'.$height,OC_Log::ERROR);
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
	* @param $h Hight
	* @returns bool for success or failure
	*/
	public function crop($x, $y, $w, $h) {
		if(!$this->valid()) {
			OC_Log::write('core',__METHOD__.'(): No image loaded', OC_Log::ERROR);
			return false;
		}
		$width_orig=imageSX($this->resource);
		$height_orig=imageSY($this->resource);
		//OC_Log::write('core',__METHOD__.'(): Original size: '.$width_orig.'x'.$height_orig, OC_Log::DEBUG);
		$process = imagecreatetruecolor($w, $h);
		if ($process == false) {
			OC_Log::write('core',__METHOD__.'(): Error creating true color image',OC_Log::ERROR);
			imagedestroy($process);
			return false;
		}
		imagecopyresampled($process, $this->resource, 0, 0, $x, $y, $w, $h, $w, $h);
		if ($process == false) {
			OC_Log::write('core',__METHOD__.'(): Error resampling process image '.$w.'x'.$h,OC_Log::ERROR);
			imagedestroy($process);
			return false;
		}
		imagedestroy($this->resource);
		$this->resource = $process;
		return true;
	}

	public function destroy(){
		if($this->valid()){
			imagedestroy($this->resource);
		}
		$this->resource=null;
	}

	public function __destruct(){
		$this->destroy();
	}
}
