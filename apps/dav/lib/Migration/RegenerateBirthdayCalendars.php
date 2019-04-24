<?php
/**
 * @copyright 2019 Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
namespace OCA\DAV\Migration;

use OCA\DAV\BackgroundJob\GenerateBirthdayCalendarBackgroundJob;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RegenerateBirthdayCalendars implements IRepairStep {

	/** @var IUserManager */
	private $userManager;

	/** @var IJobList */
	private $jobList;

	/** @var IConfig */
	private $config;

	/**
	 * @param IUserManager $userManager,
	 * @param IJobList $jobList
	 * @param IConfig $config
	 */
	public function __construct(IUserManager $userManager,
								IJobList $jobList,
								IConfig $config) {
		$this->userManager = $userManager;
		$this->jobList = $jobList;
		$this->config = $config;
	}

	/**
	 * @return string
	 */
	public function getName() {
		return 'Regenerating birthday calendars to use new icons and fix old birthday events without year';
	}

	/**
	 * @param IOutput $output
	 */
	public function run(IOutput $output) {
		// only run once
		if ($this->config->getAppValue('dav', 'regeneratedBirthdayCalendarsForYearFix') === 'yes') {
			$output->info('Repair step already executed');
			return;
		}

		$output->info('Adding background jobs to regenerate birthday calendar');
		$this->userManager->callForSeenUsers(function(IUser $user) {
			$this->jobList->add(GenerateBirthdayCalendarBackgroundJob::class, [
				'userId' => $user->getUID(),
				'purgeBeforeGenerating' => true
			]);
		});

		// if all were done, no need to redo the repair during next upgrade
		$this->config->setAppValue('dav', 'regeneratedBirthdayCalendarsForYearFix', 'yes');
	}
}
