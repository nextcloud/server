<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Listener;

use OCA\DAV\BackgroundJob\UserStatusAutomation;
use OCP\BackgroundJob\IJobList;
use OCP\Config\BeforePreferenceDeletedEvent;
use OCP\Config\BeforePreferenceSetEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/** @template-implements IEventListener<BeforePreferenceSetEvent|BeforePreferenceDeletedEvent> */
class UserPreferenceListener implements IEventListener {

	public function __construct(
		protected IJobList $jobList,
	) {
	}

	public function handle(Event $event): void {
		if ($event instanceof BeforePreferenceSetEvent) {
			if ($event->getAppId() === 'dav' && $event->getConfigKey() === 'user_status_automation' && $event->getConfigValue() === 'yes') {
				$event->setValid(true);

				// Not the cleanest way, but we just add the job in the before event.
				// If something ever turns wrong the first execution will remove the job again.
				// We also first delete the current job, so the next run time is reset.
				$this->jobList->remove(UserStatusAutomation::class, ['userId' => $event->getUserId()]);
				$this->jobList->add(UserStatusAutomation::class, ['userId' => $event->getUserId()]);
			}
		} elseif ($event instanceof BeforePreferenceDeletedEvent) {
			if ($event->getAppId() === 'dav' && $event->getConfigKey() === 'user_status_automation') {
				$event->setValid(true);
			}
		}
	}
}
