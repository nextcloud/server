<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Memcache;

class APCu extends APC {
	public function clear($prefix = '') {
		$ns = $this->getNamespace() . $prefix;
		$ns = preg_quote($ns, '/');
		$iter = new \APCIterator('/^'.$ns.'/');
		return apc_delete($iter);
	}

	static public function isAvailable() {
		if (!extension_loaded('apcu')) {
			return false;
		} elseif (!ini_get('apc.enable_cli') && \OC::$CLI) {
			return false;
		} else {
			return true;
		}
	}
}
