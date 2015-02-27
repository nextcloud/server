<?php
/**
 * Copyright (c) 2013 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\BackgroundJob\Legacy;

class RegularJob extends \OC\BackgroundJob\Job {
	public function run($argument) {
		if (is_callable($argument)) {
			call_user_func($argument);
		}
	}
}
