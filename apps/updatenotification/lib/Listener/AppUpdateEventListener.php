<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\UpdateNotification\Listener;

use OCA\UpdateNotification\AppInfo\Application;
use OCA\UpdateNotification\BackgroundJob\AppUpdatedNotifications;
use OCP\App\Events\AppUpdateEvent;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IAppConfig;
use Psr\Log\LoggerInterface;

/** @template-implements IEventListener<AppUpdateEvent> */
class AppUpdateEventListener implements IEventListener {

	public function __construct(
		private IJobList $jobList,
		private LoggerInterface $logger,
		private IAppConfig $appConfig,
	) {
	}

	/**
	 * @param AppUpdateEvent $event
	 */
	public function handle(Event $event): void {
		if (!($event instanceof AppUpdateEvent)) {
			return;
		}

		if (!$this->appConfig->getValueBool(Application::APP_NAME, 'app_updated.enabled', true)) {
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
