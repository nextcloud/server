<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\BackgroundJob;

use OCP\AppFramework\Utility\ITimeFactory;

class TestParallelAwareJob extends \OCP\BackgroundJob\Job {
	private $testCase;

	/**
	 * @var callable $callback
	 */
	private $callback;

	/**
	 * @param JobTest $testCase
	 * @param callable $callback
	 */
	public function __construct(ITimeFactory $time = null, $testCase = null, $callback = null) {
		parent::__construct($time ?? \OC::$server->get(ITimeFactory::class));
		$this->setAllowParallelRuns(false);
		$this->testCase = $testCase;
		$this->callback = $callback;
	}

	public function run($argument) {
		$this->testCase->markRun();
		$callback = $this->callback;
		$callback($argument);
	}
}
