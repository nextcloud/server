<?php
/**
 * Copyright (c) 2014 Vincent Petry <PVince81@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 *
 */

namespace OC;

class NaturalSort_DefaultCollator {
	public function compare($a, $b) {
		if ($a === $b) {
			return 0;
		}
		return ($a < $b) ? -1 : 1;
	}
}
