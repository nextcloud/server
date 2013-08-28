<?php
/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP;

class Avatar {
	public static function get ($user, $size = 64) {
		$avatar = new \OC_Avatar();
		return $avatar->get($user, $size);
	}
}
