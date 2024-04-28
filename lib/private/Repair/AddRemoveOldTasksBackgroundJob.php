<?php

declare(strict_types=1);

/**
 * @copyright 2023 Marcel Klehr <mklehr@gmx.net>
 *
 * @author Marcel Klehr <mklehr@gmx.net>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Repair;

use OC\TextProcessing\RemoveOldTasksBackgroundJob as RemoveOldTextProcessingTasksBackgroundJob;
use OC\TextToImage\RemoveOldTasksBackgroundJob as RemoveOldTextToImageTasksBackgroundJob;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class AddRemoveOldTasksBackgroundJob implements IRepairStep {
	private IJobList $jobList;

	public function __construct(IJobList $jobList) {
		$this->jobList = $jobList;
	}

	public function getName(): string {
		return 'Add AI tasks cleanup job';
	}

	public function run(IOutput $output) {
		$this->jobList->add(RemoveOldTextProcessingTasksBackgroundJob::class);
		$this->jobList->add(RemoveOldTextToImageTasksBackgroundJob::class);
	}
}
