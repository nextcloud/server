<?php
/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

/**
 * This class gets and sets users avatars.
 */

class OC_Avatar {
	/**
         * @brief get the users avatar
         * @param $user string which user to get the avatar for
         * @param $size integer size in px of the avatar, defaults to 64
         * @return \OC_Image containing the avatar
        */
        public static function get ($user, $size = 64) {
		if ($user === false) {
			return self::getDefaultAvatar($user, $size);
		}

                $view = new \OC\Files\View('/'.$user);

                if ($view->file_exists('avatar.jpg')) {
                        $ext = 'jpg';
                } elseif ($view->file_exists('avatar.png')) {
                        $ext = 'png';
                } else {
                        return self::getDefaultAvatar($user, $size);
                }

                $avatar = new OC_Image($view->file_get_contents('avatar.'.$ext));
                $avatar->resize($size);
                return $avatar;
        }

	/**
	 * @brief sets the users avatar
	 * @param $user string user to set the avatar for
	 * @param $data mixed imagedata or path to set a new avatar, or false to delete the current avatar
	 * @throws Exception if the provided file is not a jpg or png image
	 * @throws Exception if the provided image is not valid, or not a square
	 * @return true on success
	*/
	public static function set ($user, $data) {
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
				$l = \OC_L10N::get('lib');
				throw new \Exception($l->t("Unknown filetype"));
			}

			if (!( $img->valid() && ($img->height() === $img->width()) )) {
				$l = \OC_L10N::get('lib');
				throw new \Exception($l->t("Invalid image, or the provided image is not square"));
			}

			$view->unlink('avatar.jpg');
			$view->unlink('avatar.png');
			$view->file_put_contents('avatar.'.$type, $data);
			return true;
		}
	}

	/**
	 * @brief gets the default avatar
	 * @brief $user string which user to get the avatar for
	 * @param $size integer size of the avatar in px, defaults to 64
	 * @return \OC_Image containing the default avatar
	 * @todo use custom default images, when they arive
	*/
	public static function getDefaultAvatar ($user, $size = 64) {
		// TODO
		/*$default = new OC_Image(OC::$SERVERROOT."/core/img/defaultavatar.png");
		$default->resize($size);
		return $default;*/
		return;
	}
}
