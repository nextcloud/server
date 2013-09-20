<?php
/**
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC;

use OCP\IAvatar;

/*
 * This class implements methods to access Avatar functionality
 */
class AvatarManager implements IAvatarManager {

	private $avatar;

	/**
	 * @brief constructor
	 * @param $user string user to do avatar-management with
	 */
	function __construct($user) {
		$this->avatar = new \OC\Avatar($user);
	}

	/**
	 * @brief get the users avatar
	 * @param $size integer size in px of the avatar, defaults to 64
	 * @return boolean|\OC_Image containing the avatar or false if there's no image
	*/
	function get($size = 64) {
		$this->avatar->get($size);
	}

	/**
	 * @brief sets the users avatar
	 * @param $data mixed imagedata or path to set a new avatar
	 * @throws Exception if the provided file is not a jpg or png image
	 * @throws Exception if the provided image is not valid
	 * @throws \OC\NotSquareException if the image is not square
	 * @return void
	*/
	function set($data) {
		$this->avatar->set($data);
	}

	/**
	 * @brief remove the users avatar
	 * @return void
	*/
	function remove() {
		$this->avatar->remove();
	}
}
