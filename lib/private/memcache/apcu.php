<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Memcache;

class APCu extends APC {
	static public function isAvailable() {
		if (!extension_loaded('apcu')) {
			return false;
		} elseif (!ini_get('apc.enable_cli') && \OC::$CLI) {
			return false;
		} elseif (version_compare(phpversion('apc'), '4.0.6') === -1) {
			return false;
		} else {
			return true;
		}
	}
}
