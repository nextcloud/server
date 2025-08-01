<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Migration;

use OCA\DAV\BackgroundJob\UpdateCalendarResourcesRoomsBackgroundJob;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class RegisterUpdateCalendarResourcesRoomBackgroundJob implements IRepairStep {
	public function __construct(
		private readonly IJobList $jobList,
	) {
	}

	public function getName() {
		return 'Register a background job to update rooms and resources';
	}

	public function run(IOutput $output) {
		$this->jobList->add(UpdateCalendarResourcesRoomsBackgroundJob::class);
	}
}
