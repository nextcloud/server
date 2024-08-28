<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Migration;

use OCA\DAV\BackgroundJob\DeleteOutdatedSchedulingObjects;
use OCA\DAV\CalDAV\CalDavBackend;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class DeleteSchedulingObjects implements IRepairStep {
	public function __construct(private IJobList $jobList,
		private ITimeFactory $time,
		private CalDavBackend $calDavBackend
	) {
	}

	public function getName(): string {
		return 'Handle outdated scheduling events';
	}

	public function run(IOutput $output): void {
		$output->info('Cleaning up old scheduling events');
		$time = $this->time->getTime() - (60 * 60);
		$this->calDavBackend->deleteOutdatedSchedulingObjects($time, 50000);
		if (!$this->jobList->has(DeleteOutdatedSchedulingObjects::class, null)) {
			$output->info('Adding background job to delete old scheduling objects');
			$this->jobList->add(DeleteOutdatedSchedulingObjects::class, null);
		}
	}
}
