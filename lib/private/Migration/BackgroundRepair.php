<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Robin Appelman <robin@icewind.nl>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Migration;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\EventDispatcher\IEventDispatcher;
use OC\NeedsUpdateException;
use OC\Repair;
use OC_App;
use Psr\Log\LoggerInterface;

/**
 * Class BackgroundRepair
 *
 * @package OC\Migration
 */
class BackgroundRepair extends TimedJob {
	private IJobList $jobList;
	private LoggerInterface $logger;
	private IEventDispatcher $dispatcher;

	public function __construct(IEventDispatcher $dispatcher, ITimeFactory $time, LoggerInterface $logger, IJobList $jobList) {
		parent::__construct($time);
		$this->dispatcher = $dispatcher;
		$this->logger = $logger;
		$this->jobList = $jobList;
		$this->setInterval(15 * 60);
	}

	/**
	 * @param array $argument
	 * @throws \Exception
	 * @throws \OC\NeedsUpdateException
	 */
	protected function run($argument) {
		if (!isset($argument['app']) || !isset($argument['step'])) {
			// remove the job - we can never execute it
			$this->jobList->remove($this, $this->argument);
			return;
		}
		$app = $argument['app'];

		try {
			$this->loadApp($app);
		} catch (NeedsUpdateException $ex) {
			// as long as the app is not yet done with it's offline migration
			// we better not start with the live migration
			return;
		}

		$step = $argument['step'];
		$repair = new Repair([], $this->dispatcher, \OC::$server->get(LoggerInterface::class));
		try {
			$repair->addStep($step);
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
		$repair->run();

		// remove the job once executed successfully
		$this->jobList->remove($this, $this->argument);
	}

	/**
	 * @codeCoverageIgnore
	 * @param $app
	 * @throws NeedsUpdateException
	 */
	protected function loadApp($app) {
		OC_App::loadApp($app);
	}
}
