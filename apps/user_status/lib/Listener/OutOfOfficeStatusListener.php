<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\Listener;

use OCA\DAV\BackgroundJob\UserStatusAutomation;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\OutOfOfficeChangedEvent;
use OCP\User\Events\OutOfOfficeClearedEvent;
use OCP\User\Events\OutOfOfficeScheduledEvent;
use OCP\UserStatus\IManager;
use OCP\UserStatus\IUserStatus;

/**
 * Class UserDeletedListener
 *
 * @template-implements IEventListener<OutOfOfficeScheduledEvent|OutOfOfficeChangedEvent|OutOfOfficeClearedEvent>
 *
 */
class OutOfOfficeStatusListener implements IEventListener {
	public function __construct(private IJobList $jobsList,
		private ITimeFactory $time,
		private IManager $manager) {
	}

	/**
	 * @inheritDoc
	 */
	public function handle(Event $event): void {
		if($event instanceof OutOfOfficeClearedEvent) {
			$this->manager->revertUserStatus($event->getData()->getUser()->getUID(), IUserStatus::MESSAGE_OUT_OF_OFFICE, IUserStatus::DND);
			$this->jobsList->scheduleAfter(UserStatusAutomation::class, $this->time->getTime(), ['userId' => $event->getData()->getUser()->getUID()]);
			return;
		}

		if ($event instanceof OutOfOfficeScheduledEvent
			|| $event instanceof OutOfOfficeChangedEvent) {
			// This might be overwritten by the office hours automation, but that is ok. This is just in case no office hours are set
			$this->jobsList->scheduleAfter(UserStatusAutomation::class, $this->time->getTime(), ['userId' => $event->getData()->getUser()->getUID()]);
		}
	}
}
