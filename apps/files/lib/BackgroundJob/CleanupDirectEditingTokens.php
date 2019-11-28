<?php

namespace OCA\Files\BackgroundJob;

use OC\BackgroundJob\TimedJob;
use OCP\DirectEditing\IManager;

class CleanupDirectEditingTokens extends TimedJob {

	private const INTERVAL_MINUTES = 15 * 60;

	/**
	 * @var IManager
	 */
	private $manager;

	public function __construct(IManager $manager) {
		$this->interval = self::INTERVAL_MINUTES;
		$this->manager = $manager;
	}

	/**
	 * Makes the background job do its work
	 *
	 * @param array $argument unused argument
	 * @throws \Exception
	 */
	public function run($argument) {
		$this->manager->cleanup();
	}
}
