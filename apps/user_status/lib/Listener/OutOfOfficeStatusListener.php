<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
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
namespace OCA\UserStatus\Listener;

use OCA\DAV\BackgroundJob\UserStatusAutomation;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\OutOfOfficeChangedEvent;
use OCP\User\Events\OutOfOfficeClearedEvent;
use OCP\User\Events\OutOfOfficeEndedEvent;
use OCP\User\Events\OutOfOfficeScheduledEvent;
use OCP\User\Events\OutOfOfficeStartedEvent;
use OCP\UserStatus\IManager;
use OCP\UserStatus\IUserStatus;

/**
 * Class UserDeletedListener
 *
 * @template-implements IEventListener<OutOfOfficeScheduledEvent|OutOfOfficeChangedEvent|OutOfOfficeClearedEvent|OutOfOfficeStartedEvent|OutOfOfficeEndedEvent>
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
			|| $event instanceof OutOfOfficeChangedEvent
			|| $event instanceof OutOfOfficeStartedEvent
			|| $event instanceof OutOfOfficeEndedEvent
		) {
			// This might be overwritten by the office hours automation, but that is ok. This is just in case no office hours are set
			$this->jobsList->scheduleAfter(UserStatusAutomation::class, $this->time->getTime(), ['userId' => $event->getData()->getUser()->getUID()]);
		}
	}
}
