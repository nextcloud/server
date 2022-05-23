<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
namespace OCA\DAV\Listener;

use OCA\DAV\BackgroundJob\UserStatusAutomation;
use OCP\BackgroundJob\IJobList;
use OCP\Config\BeforePreferenceDeletedEvent;
use OCP\Config\BeforePreferenceSetEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

class UserPreferenceListener implements IEventListener {

	protected IJobList $jobList;

	public function __construct(IJobList $jobList) {
		$this->jobList = $jobList;
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
