<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @license AGPL-3.0-or-later
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

namespace OCA\UpdateNotification\Listener;

use OCA\UpdateNotification\AppInfo\Application;
use OCA\UpdateNotification\BackgroundJob\AppUpdatedNotifications;
use OCP\App\Events\AppUpdateEvent;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use Psr\Log\LoggerInterface;

/** @template-implements IEventListener<AppUpdateEvent> */
class AppUpdateEventListener implements IEventListener {

	public function __construct(
		private IJobList $jobList,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @param AppUpdateEvent $event
	 */
	public function handle(Event $event): void {
		if (!($event instanceof AppUpdateEvent)) {
			return;
		}

		foreach ($this->jobList->getJobsIterator(AppUpdatedNotifications::class, null, 0) as $job) {
			// Remove waiting notification jobs for this app
			if ($job->getArgument()['appId'] === $event->getAppId()) {
				$this->jobList->remove($job);
			}
		}

		$this->jobList->add(AppUpdatedNotifications::class, [
			'appId' => $event->getAppId(),
			'timestamp' => time(),
		]);

		$this->logger->debug(
			'Scheduled app update notification for "' . $event->getAppId() . '"',
			[
				'app' => Application::APP_NAME,
			],
		);
	}
}
