<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class TestTimedJobNew extends TimedJob {
	public bool $ran = false;

	public function __construct(ITimeFactory $timeFactory) {
		parent::__construct($timeFactory);
		$this->setInterval(10);
	}

	public function run($argument) {
		$this->ran = true;
	}
}
