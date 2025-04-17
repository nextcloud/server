<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Migration;

use OC\Repair;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use Psr\Log\LoggerInterface;

/**
 * Class BackgroundRepair
 *
 * @package OC\Migration
 */
class BackgroundRepair extends TimedJob {
	public function __construct(
		private Repair $repair,
		ITimeFactory $time,
		private LoggerInterface $logger,
		private IJobList $jobList,
		private IAppManager $appManager,
	) {
		parent::__construct($time);
		$this->setInterval(15 * 60);
	}

	/**
	 * @param array $argument
	 * @throws \Exception
	 */
	protected function run($argument): void {
		if (!isset($argument['app']) || !isset($argument['step'])) {
			// remove the job - we can never execute it
			$this->jobList->remove($this, $this->argument);
			return;
		}
		$app = $argument['app'];

		$this->appManager->loadApp($app);

		$step = $argument['step'];
		$this->repair->setRepairSteps([]);
		try {
			$this->repair->addStep($step);
		} catch (\Exception $ex) {
			$this->logger->error($ex->getMessage(), [
				'app' => 'migration',
				'exception' => $ex,
			]);

			// remove the job - we can never execute it
			$this->jobList->remove($this, $this->argument);
			return;
		}

		// execute the repair step
		$this->repair->run();

		// remove the job once executed successfully
		$this->jobList->remove($this, $this->argument);
	}
}
