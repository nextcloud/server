<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP;

/**
 * This class provides avatar functionality
 */

interface IAvatar {

	/**
	 * get the users avatar
	 * @param int $size size in px of the avatar, avatars are square, defaults to 64
	 * @return boolean|\OC_Image containing the avatar or false if there's no image
	 */
	function get($size = 64);

	/**
	 * sets the users avatar
	 * @param Image $data mixed imagedata or path to set a new avatar
	 * @throws \Exception if the provided file is not a jpg or png image
	 * @throws \Exception if the provided image is not valid
	 * @throws \OC\NotSquareException if the image is not square
	 * @return void
	 */
	function set($data);

	/**
	 * remove the users avatar
	 * @return void
	 */
	function remove();
}
