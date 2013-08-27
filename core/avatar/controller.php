<?php
/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_Core_Avatar_Controller {
	public static function getAvatar($args) {
		if (!\OC_User::isLoggedIn()) {
			$l = new \OC_L10n('core');
			header("HTTP/1.0 403 Forbidden");
			\OC_Template::printErrorPage($l->t("Permission denied"));
			return;
		}

		$user = stripslashes($args['user']);
		$size = (int)$args['size'];
		if ($size > 2048) {
			$size = 2048;
		}
		// Undefined size
		elseif ($size === 0) {
			$size = 64;
		}

		$image = \OC_Avatar::get($user, $size);

		if ($image instanceof \OC_Image) {
			$image->show();
		} elseif ($image === false) {
			\OC_JSON::success(array('user' => $user, 'size' => $size));
		}
	}

	public static function postAvatar($args) {
		$user = \OC_User::getUser();

		if (isset($_POST['path'])) {
			$path = stripslashes($_POST['path']);
			$avatar = OC::$SERVERROOT.'/data/'.$user.'/files'.$path;
		}

		if (!empty($_FILES)) {
			$files = $_FILES['files'];
			if ($files['error'][0] === 0) {
				$avatar = file_get_contents($files['tmp_name'][0]);
				unlink($files['tmp_name'][0]);
			}
		}

		try {
			\OC_Avatar::set($user, $avatar);
			\OC_JSON::success();
		} catch (\OC\NotSquareException $e) {
			// TODO move unfitting avatar to /datadir/$user/tmpavatar{png.jpg} here
			\OC_JSON::error(array("data" => array("message" => "notsquare") ));
		} catch (\Exception $e) {
			\OC_JSON::error(array("data" => array("message" => $e->getMessage()) ));
		}
	}

	public static function deleteAvatar($args) {
		$user = OC_User::getUser();

		try {
			\OC_Avatar::remove($user);
			\OC_JSON::success();
		} catch (\Exception $e) {
			\OC_JSON::error(array("data" => array ("message" => $e->getMessage()) ));
		}
	}

	public static function getTmpAvatar($args) {
		// TODO deliver /datadir/$user/tmpavatar.{png|jpg} here, filename may include a timestamp
		// TODO make a cronjob that cleans up the tmpavatar after it's older than 2 hours, should be run every hour
		$user = OC_User::getUser();
	}

	public static function postCroppedAvatar($args) {
		$user = OC_User::getUser();
		$crop = json_decode($_POST['crop'], true);
		$image = new \OC_Image($avatar);
		$image->crop($x, $y, $w, $h);
		$avatar = $image->data();
		$cropped = true;
	}
}
