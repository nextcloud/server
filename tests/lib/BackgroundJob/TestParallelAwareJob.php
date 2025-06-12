<?php
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\Job;
use OCP\Server;

class TestParallelAwareJob extends Job {
	/**
	 * @var callable $callback
	 */
	private $callback;

	/**
	 * @param JobTest $testCase
	 * @param callable $callback
	 */
	public function __construct(
		?ITimeFactory $time = null,
		private $testCase = null,
		$callback = null,
	) {
		parent::__construct($time ?? Server::get(ITimeFactory::class));
		$this->setAllowParallelRuns(false);
		$this->callback = $callback;
	}

	public function run($argument) {
		$this->testCase->markRun();
		$callback = $this->callback;
		$callback($argument);
	}
}
