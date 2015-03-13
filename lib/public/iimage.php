<?php
/**
 * ownCloud
 *
 * @author Joas Schilling
 * @copyright 2015 Joas Schilling nickvergessen@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */
namespace OCP;


/**
 * Class for basic image manipulation
 */
interface IImage {

	/**
	 * Determine whether the object contains an image resource.
	 *
	 * @return bool
	 */
	public function valid();

	/**
	 * Returns the MIME type of the image or an empty string if no image is loaded.
	 *
	 * @return string
	 */
	public function mimeType();

	/**
	 * Returns the width of the image or -1 if no image is loaded.
	 *
	 * @return int
	 */
	public function width();

	/**
	 * Returns the height of the image or -1 if no image is loaded.
	 *
	 * @return int
	 */
	public function height();

	/**
	 * Returns the width when the image orientation is top-left.
	 *
	 * @return int
	 */
	public function widthTopLeft();

	/**
	 * Returns the height when the image orientation is top-left.
	 *
	 * @return int
	 */
	public function heightTopLeft();

	/**
	 * Outputs the image.
	 *
	 * @param string $mimeType
	 * @return bool
	 */
	public function show($mimeType = null);

	/**
	 * Saves the image.
	 *
	 * @param string $filePath
	 * @param string $mimeType
	 * @return bool
	 */
	public function save($filePath = null, $mimeType = null);

	/**
	 * @return resource Returns the image resource in any.
	 */
	public function resource();

	/**
	 * @return string Returns the raw image data.
	 */
	public function data();

	/**
	 * (I'm open for suggestions on better method name ;)
	 * Get the orientation based on EXIF data.
	 *
	 * @return int The orientation or -1 if no EXIF data is available.
	 */
	public function getOrientation();

	/**
	 * (I'm open for suggestions on better method name ;)
	 * Fixes orientation based on EXIF data.
	 *
	 * @return bool.
	 */
	public function fixOrientation();

	/**
	 * Loads an image from a local file, a base64 encoded string or a resource created by an imagecreate* function.
	 *
	 * @param resource|string $imageRef The path to a local file, a base64 encoded string or a resource created by an imagecreate* function or a file resource (file handle    ).
	 * @return resource|false An image resource or false on error
	 */
	public function load($imageRef);

	/**
	 * Loads an image from an open file handle.
	 * It is the responsibility of the caller to position the pointer at the correct place and to close the handle again.
	 *
	 * @param resource $handle
	 * @return resource|false An image resource or false on error
	 */
	public function loadFromFileHandle($handle);

	/**
	 * Loads an image from a local file.
	 *
	 * @param bool|string $imagePath The path to a local file.
	 * @return bool|resource An image resource or false on error
	 */
	public function loadFromFile($imagePath = false);

	/**
	 * Loads an image from a string of data.
	 *
	 * @param string $str A string of image data as read from a file.
	 * @return bool|resource An image resource or false on error
	 */
	public function loadFromData($str);

	/**
	 * Loads an image from a base64 encoded string.
	 *
	 * @param string $str A string base64 encoded string of image data.
	 * @return bool|resource An image resource or false on error
	 */
	public function loadFromBase64($str);

	/**
	 * Resizes the image preserving ratio.
	 *
	 * @param integer $maxSize The maximum size of either the width or height.
	 * @return bool
	 */
	public function resize($maxSize);

	/**
	 * @param int $width
	 * @param int $height
	 * @return bool
	 */
	public function preciseResize($width, $height);

	/**
	 * Crops the image to the middle square. If the image is already square it just returns.
	 *
	 * @param int $size maximum size for the result (optional)
	 * @return bool for success or failure
	 */
	public function centerCrop($size = 0);

	/**
	 * Crops the image from point $x$y with dimension $wx$h.
	 *
	 * @param int $x Horizontal position
	 * @param int $y Vertical position
	 * @param int $w Width
	 * @param int $h Height
	 * @return bool for success or failure
	 */
	public function crop($x, $y, $w, $h);

	/**
	 * Resizes the image to fit within a boundary while preserving ratio.
	 *
	 * @param integer $maxWidth
	 * @param integer $maxHeight
	 * @return bool
	 */
	public function fitIn($maxWidth, $maxHeight);

	/**
	 * Destroys the current image and resets the object
	 */
	public function destroy();
}
