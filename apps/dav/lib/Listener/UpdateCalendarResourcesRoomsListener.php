<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Listener;

use OCA\DAV\BackgroundJob\UpdateCalendarResourcesRoomsBackgroundJob;
use OCA\DAV\Events\ScheduleResourcesRoomsUpdateEvent;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** @template-implements IEventListener<ScheduleResourcesRoomsUpdateEvent> */
class UpdateCalendarResourcesRoomsListener implements IEventListener {

	public function __construct(
		private IJobList $jobList,
	) {
	}

	public function handle(Event $event): void {
		if (!($event instanceof ScheduleResourcesRoomsUpdateEvent)) {
			return;
		}

		$jobs = $this->jobList->getJobsIterator(
			UpdateCalendarResourcesRoomsBackgroundJob::class,
			null,
			0,
		);
		foreach ($jobs as $job) {
			$this->jobList->resetBackgroundJob($job);
		}
	}
}
