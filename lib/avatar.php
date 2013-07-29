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
	 * @brief gets a link to the users avatar
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
			return \OC_Avatar::getGravatar($user, $size);
		} elseif ($mode === "local") {
			return \OC_Avatar::getLocalAvatar($user, $size);
		}
	}

	/**
	 * @brief returns the active avatar mode
	 * @return string active avatar mode
	*/
	public static function getMode () {
		return OC_Config::getValue("avatar", "local");
	}

	/**
	 * @brief sets the users local avatar
	 * @param $user string user to set the avatar for
	 * @param $img mixed imagedata to set a new avatar, or false to delete the current avatar
	 * @param $type string fileextension
	 * @throws Exception if the provided image is not valid, or not a square
	 * @return true on success
	*/
	public static function setLocalAvatar ($user, $img, $type) {
		$view = new \OC\Files\View('/'.$user);

		if ($img === false) {
			$view->unlink('avatar.jpg');
			$view->unlink('avatar.png');
			return true;
		} else {
			$img = new OC_Image($img);

			if (!( $img->valid() && ($img->height() === $img->width()) )) {
				throw new Exception();
			}

			$view->unlink('avatar.jpg');
			$view->unlink('avatar.png');
			$view->file_put_contents('avatar.'.$type, $img);
			return true;
		}
	}

	/**
	 * @brief get the users gravatar
	 * @param $user string which user to get the gravatar for
	 * @param size integer size in px of the avatar, defaults to 64
	 * @return string link to the gravatar, or base64encoded, html-ready image
	*/
	public static function getGravatar ($user, $size = 64) {
		$email = OC_Preferences::getValue($user, 'settings', 'email');
		if ($email !== null) {
			$emailhash = md5(strtolower(trim($email)));
			$url = "http://www.gravatar.com/avatar/".$emailhash."?s=".$size;
			return $url;
		} else {
			return \OC_Avatar::wrapIntoImg(\OC_Avatar::getDefaultAvatar($size), 'png');
		}
	}

	/**
	 * @brief get the local avatar
	 * @param $user string which user to get the avatar for
	 * @param $size integer size in px of the avatar, defaults to 64
	 * @return string base64encoded encoded, html-ready image
	*/
	public static function getLocalAvatar ($user, $size = 64) {
		$view = new \OC\Files\View('/'.$user);

		if ($view->file_exists('avatar.jpg')) {
			$type = 'jpg';
		} elseif ($view->file_exists('avatar.png')) {
			$type = 'png';
		} else {
			return \OC_Avatar::wrapIntoImg(\OC_Avatar::getDefaultAvatar($size), 'png');
		}

		$avatar = new OC_Image($view->file_get_contents('avatar.'.$type));
		$avatar->resize($size);
		return \OC_Avatar::wrapIntoImg((string)$avatar, $type);
	}

	/**
	 * @brief gets the default avatar
	 * @param $size integer size of the avatar in px, defaults to 64
	 * @return string base64 encoded default avatar
	*/
	public static function getDefaultAvatar ($size = 64) {
		$default = new OC_Image(OC::$SERVERROOT."/core/img/defaultavatar.png");
		$default->resize($size);
		return (string)$default;
	}

	/**
	 * @brief wrap a base64encoded image, so it can be used in html
	 * @param $img string base64encoded image
	 * @param $type string imagetype
	 * @return string wrapped image
	*/
	public static function wrapIntoImg($img, $type) {
		return 'data:image/'.$type.';base64,'.$img;
	}
}
