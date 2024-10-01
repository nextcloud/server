<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Command;

use Laravel\SerializableClosure\SerializableClosure as LaravelClosure;
use OCP\BackgroundJob\QueuedJob;

class ClosureJob extends QueuedJob {
	protected function run($argument) {
		$callable = unserialize($argument, [LaravelClosure::class]);
		$callable = $callable->getClosure();
		if (is_callable($callable)) {
			$callable();
		} else {
			throw new \InvalidArgumentException('Invalid serialized callable');
		}
	}
}
