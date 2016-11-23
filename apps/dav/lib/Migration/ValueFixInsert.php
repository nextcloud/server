<?php
/**
 * @copyright 2016, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class ValueFixInsert implements IRepairStep {

	/** @var IUserManager */
	private $userManager;

	/** @var IJobList */
	private $jobList;

	/** @var IConfig */
	private $config;

	public function __construct(IUserManager $userManager,
								IJobList $jobList,
								IConfig $config) {
		$this->userManager = $userManager;
		$this->jobList = $jobList;
		$this->config = $config;
	}

	public function getName() {
		return 'Insert ValueFix background job for each user';
	}

	public function run(IOutput $output) {
		if ($this->config->getAppValue('dav', self::class . '_ran', 'false') !== 'true') {
			$this->userManager->callForSeenUsers(function (IUser $user) {
				$this->jobList->add(ValueFix::class, ['user' => $user->getUID()]);
			});
			$this->config->setAppValue('dav', self::class . '_ran', 'true');
		}
	}
}
