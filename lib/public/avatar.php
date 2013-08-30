<?php
/**
 * Copyright (c) 2013 Christopher Schäpers <christopher@schaepers.it>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCP;

class Avatar {
	private $avatar;

	public function __construct () {
		$this->avatar = new \OC_Avatar();

	public function get ($user, $size = 64) {
		return $avatar->get($user, $size);
	}

	public function set ($user, $data) {
		return $avatar->set($user, $data);
	}

	public function remove ($user) {
		return $avatar->remove($user);
	}
}
