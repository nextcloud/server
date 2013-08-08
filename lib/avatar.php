<?php
/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * This class gets and sets users avatars.
 * Avalaible backends are local (saved in users root at avatar.[png|jpg]) and gravatar.
 * However the get function is easy to extend with further backends.
*/

class OC_Avatar {
	/**
	 * @brief gets the users avatar
	 * @param $user string username, if not provided, the default avatar will be returned
	 * @param $size integer size in px of the avatar, defaults to 64
	 * @return mixed \OC_Image containing the avatar, a link to the avatar, false if avatars are disabled
	*/
	public static function get ($user = false, $size = 64) {
		$mode = self::getMode();
		if ($mode === "none") {
			// avatars are disabled
			return false;
		} else {
			if ($user === false) {
				return self::getDefaultAvatar($size);
			} elseif ($mode === "gravatar") {
				return self::getGravatar($user, $size);
			} elseif ($mode === "local") {
				return self::getLocalAvatar($user, $size);
			} elseif ($mode === "custom") {
				return self::getCustomAvatar($user, $size);
			}
		}
	}

	/**
	 * @brief returns the active avatar mode
	 * @return string active avatar mode
	*/
	public static function getMode () {
		return \OC_Config::getValue("avatar", "local");
	}

	/**
	 * @brief sets the users local avatar
	 * @param $user string user to set the avatar for
	 * @param $data mixed imagedata or path to set a new avatar, or false to delete the current avatar
	 * @throws Exception if the provided file is not a jpg or png image
	 * @throws Exception if the provided image is not valid, or not a square
	 * @return true on success
	*/
	public static function setLocalAvatar ($user, $data) {
		$view = new \OC\Files\View('/'.$user);

		if ($data === false) {
			$view->unlink('avatar.jpg');
			$view->unlink('avatar.png');
			return true;
		} else {
			$img = new OC_Image($data);
			$type = substr($img->mimeType(), -3);
			if ($type === 'peg') { $type = 'jpg'; }
			if ($type !== 'jpg' && $type !== 'png') {
				throw new Exception("Unknown filetype for avatar");
			}

			if (!( $img->valid() && ($img->height() === $img->width()) )) {
				throw new Exception("Invalid image, or the provided image is not square");
			}

			$view->unlink('avatar.jpg');
			$view->unlink('avatar.png');
			$view->file_put_contents('avatar.'.$type, $data);
			return true;
		}
	}

	/**
	 * @brief get the users gravatar
	 * @param $user string which user to get the gravatar for
	 * @param size integer size in px of the avatar, defaults to 64
	 * @return string link to the gravatar, or \OC_Image with the default avatar
	*/
	public static function getGravatar ($user, $size = 64) {
		$email = \OC_Preferences::getValue($user, 'settings', 'email');
		if ($email !== null) {
			$emailhash = md5(strtolower(trim($email)));
			$url = "http://www.gravatar.com/avatar/".$emailhash."?s=".$size;
			return $url;
		} else {
			return self::getDefaultAvatar($size);
		}
	}

	/**
	 * @brief get the local avatar
	 * @param $user string which user to get the avatar for
	 * @param $size integer size in px of the avatar, defaults to 64
	 * @return string \OC_Image containing the avatar
	*/
	public static function getLocalAvatar ($user, $size = 64) {
		$view = new \OC\Files\View('/'.$user);

		if ($view->file_exists('avatar.jpg')) {
			$ext = 'jpg';
		} elseif ($view->file_exists('avatar.png')) {
			$ext = 'png';
		} else {
			return self::getDefaultAvatar($size);
		}

		$avatar = new OC_Image($view->file_get_contents('avatar.'.$ext));
		$avatar->resize($size);
		return $avatar;
	}

	/**
	 *
	*/
	public static function getCustomAvatar($user, $size) {
		// TODO
	}

	/**
	 * @brief gets the default avatar
	 * @param $size integer size of the avatar in px, defaults to 64
	 * @return \OC_Image containing the default avatar
	*/
	public static function getDefaultAvatar ($size = 64) {
		$default = new OC_Image(OC::$SERVERROOT."/core/img/defaultavatar.png");
		$default->resize($size);
		return $default;
	}
}
