<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Command;

use OC\BackgroundJob\QueuedJob;

class CallableJob extends QueuedJob {
	protected function run($serializedCallable) {
		$callable = unserialize($serializedCallable);
		if (is_callable($callable)) {
			$callable();
		} else {
			throw new \InvalidArgumentException('Invalid serialized callable');
		}
	}
}
