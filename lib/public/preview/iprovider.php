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
namespace OCP\Preview;

interface IProvider {
	/**
	 * @return string Regex with the mimetypes that are supported by this provider
	 */
	public function getMimeType();

	/**
	 * Check if a preview can be generated for $path
	 *
	 * @param \OCP\Files\FileInfo $file
	 * @return bool
	 */
	public function isAvailable(\OCP\Files\FileInfo $file);

	/**
	 * get thumbnail for file at path $path
	 *
	 * @param string $path Path of file
	 * @param int $maxX The maximum X size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param int $maxY The maximum Y size of the thumbnail. It can be smaller depending on the shape of the image
	 * @param bool $scalingup Disable/Enable upscaling of previews
	 * @param \OC\Files\View $fileview fileview object of user folder
	 * @return mixed
	 *        false if no preview was generated
	 *        OC_Image object of the preview
	 */
	public function getThumbnail($path, $maxX, $maxY, $scalingup, $fileview);
}
