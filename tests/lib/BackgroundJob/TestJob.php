<?php
/**
 * SPDX-FileCopyrightText: 2020-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;

class TestJob extends \OCP\BackgroundJob\Job {
	private $testCase;

	/**
	 * @var callable $callback
	 */
	private $callback;

	/**
	 * @param JobTest $testCase
	 * @param callable $callback
	 */
	public function __construct(?ITimeFactory $time = null, $testCase = null, $callback = null) {
		parent::__construct($time ?? \OCP\Server::get(ITimeFactory::class));
		$this->testCase = $testCase;
		$this->callback = $callback;
	}

	public function run($argument) {
		$this->testCase->markRun();
		$callback = $this->callback;
		$callback($argument);
	}
}
