<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Files\Storage;

class Loader {
	private function $wrappers

	public function load($class, $arguments) {
		return new $class($arguments);
	}
}
