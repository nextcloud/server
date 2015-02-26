<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OC\Command;

use OC\BackgroundJob\QueuedJob;
use SuperClosure\Serializer;

class ClosureJob extends QueuedJob {
	protected function run($serializedCallable) {
		$serializer = new Serializer();
		$callable = $serializer->unserialize($serializedCallable);
		if (is_callable($callable)) {
			$callable();
		} else {
			throw new \InvalidArgumentException('Invalid serialized callable');
		}
	}
}
