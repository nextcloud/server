<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Repair;

use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class DropOldJobs implements IRepairStep {

	/** @var IJobList */
	protected $jobList;

	/**
	 * @param IJobList $jobList
	 */
	public function __construct(IJobList $jobList) {
		$this->jobList = $jobList;
	}

	/**
	 * Returns the step's name
	 *
	 * @return string
	 */
	public function getName() {
		return 'Drop old background jobs';
	}

	/**
	 * Run repair step.
	 * Must throw exception on error.
	 *
	 * @throws \Exception in case of failure
	 */
	public function run(IOutput $output) {
		$oldJobs = $this->oldJobs();
		foreach($oldJobs as $job) {
			if($this->jobList->has($job['class'], $job['arguments'])) {
				$this->jobList->remove($job['class'], $job['arguments']);
			}
		}
	}

	/**
	 * returns a list of old jobs as an associative array with keys 'class' and
	 * 'arguments'.
	 *
	 * @return array
	 */
	public function oldJobs() {
		return [
			['class' => 'OC_Cache_FileGlobalGC', 'arguments' => null],
			['class' => 'OC\Cache\FileGlobalGC', 'arguments' => null],
			['class' => 'OCA\Files\BackgroundJob\DeleteOrphanedTagsJob', 'arguments' => null],

			['class' => 'OCA\Files_sharing\Lib\DeleteOrphanedSharesJob', 'arguments' => null],
			['class' => 'OCA\Files_sharing\ExpireSharesJob', 'arguments' => null],

			['class' => 'OCA\user_ldap\lib\Jobs', 'arguments' => null],
			['class' => '\OCA\User_LDAP\Jobs\CleanUp', 'arguments' => null],
		];
	}


}
