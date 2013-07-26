<?php
/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_Avatar {
	/**
	 * @brief gets the users avatar
	 * @param $user string username
	 * @param $size integer size in px of the avatar, defaults to 64
	 * @return mixed link to the avatar, false if avatars are disabled
	*/
	public static function get ($user, $size = 64) {
		$mode = OC_Config::getValue("avatar", "local");
		if ($mode === "none") {
			// avatars are disabled
			return false;
		} elseif ($mode === "gravatar") {
			$email = OC_Preferences::getValue($user, 'settings', 'email');
			if ($email !== null) {
				$emailhash = md5(strtolower(trim($email)));
				$url = "http://www.gravatar.com/avatar/".$emailhash."?s=".$size;
				return $url;
			} else {
				return \OC_Avatar::getDefaultAvatar($size);
			}
		} elseif ($mode === "local") {
			if (false) {
				//
			} else {
				return \OC_Avatar::getDefaultAvatar($size);
			}
		}
	}


	/**
	 * @brief sets the users local avatar
	 * @param $user string user to set the avatar for
	 * @param $path string path where the avatar is
	 * @return true on success
	*/
	public static function setLocalAvatar ($user, $path) {
		if (OC_Config::getValue("avatar", "local") === "local") {
			//
		}
	}

	/**
	 * @brief gets the default avatar
	 * @return link to the default avatar
	*/
	public static function getDefaultAvatar ($size) {
		return OC_Helper::imagePath("core", "defaultavatar.png");
	}
}
