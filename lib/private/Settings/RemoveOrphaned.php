<?php
/**
 * @copyright Copyright (c) 2016 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Settings;

use OC\BackgroundJob\JobList;
use OC\BackgroundJob\TimedJob;
use OC\NeedsUpdateException;
use OCP\BackgroundJob\IJobList;
use OCP\ILogger;

/**
 * Class RemoveOrphaned
 *
 * @package OC\Settings
 */
class RemoveOrphaned extends TimedJob {

	/** @var IJobList */
	private $jobList;

	/** @var ILogger */
	private $logger;

	/** @var Manager */
	private $manager;

	public function __construct(Manager $manager = null) {
		if($manager !== null) {
			$this->manager = $manager;
		} else {
			// fix DI for Jobs
			$this->manager = \OC::$server->getSettingsManager();
		}
	}

	/**
	 * run the job, then remove it from the job list
	 *
	 * @param JobList $jobList
	 * @param ILogger $logger
	 */
	public function execute($jobList, ILogger $logger = null) {
		// add an interval of 15 mins
		$this->setInterval(15*60);

		$this->jobList = $jobList;
		$this->logger = $logger;
		parent::execute($jobList, $logger);
	}

	/**
	 * @param array $argument
	 * @throws \Exception
	 * @throws \OC\NeedsUpdateException
	 */
	protected function run($argument) {
		try {
			\OC_App::loadApps();
		} catch (NeedsUpdateException $ex) {
			// only run when apps are up to date
			return;
		}

		$this->manager->checkForOrphanedClassNames();

		// remove the job once executed successfully
		$this->jobList->remove($this);
	}

}
