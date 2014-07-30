<?php

/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @author Georg Ehrke
 * @copyright 2013 Frank Karlitschek frank@owncloud.org
 * @copyright 2013 Georg Ehrke georg@owncloud.com
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
 * Public interface of ownCloud for apps to use.
 * Preview interface
 *
 */

// use OCP namespace for all classes that are considered public.
// This means that they should be used by apps instead of the internal ownCloud classes
namespace OCP;

/**
 * This class provides functions to render and show thumbnails and previews of files
 */
interface IPreview
{

	/**
	 * Return a preview of a file
	 * @param string $file The path to the file where you want a thumbnail from
	 * @param int $maxX The maximum X size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param int $maxY The maximum Y size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param boolean $scaleUp Scale smaller images up to the thumbnail size or not. Might look ugly
	 * @return \OCP\Image
	 */
	function createPreview($file, $maxX = 100, $maxY = 75, $scaleUp = false);


	/**
	 * Returns true if the passed mime type is supported
	 * @param string $mimeType
	 * @return boolean
	 */
	function isMimeSupported($mimeType = '*');

	/**
	 * Check if a preview can be generated for a file
	 *
	 * @param \OCP\Files\FileInfo $file
	 * @return bool
	 */
	function isAvailable($file);
}
