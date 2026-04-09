<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\UpdateNotification\BackgroundJob;

use OCA\UpdateNotification\AppInfo\Application;
use OCA\UpdateNotification\Manager;
use OCP\App\IAppManager;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\QueuedJob;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use Psr\Log\LoggerInterface;

class AppUpdatedNotifications extends QueuedJob {
	public function __construct(
		ITimeFactory $time,
		private IConfig $config,
		private IAppConfig $appConfig,
		private IManager $notificationManager,
		private IUserManager $userManager,
		private IAppManager $appManager,
		private LoggerInterface $logger,
		private Manager $manager,
	) {
		parent::__construct($time);
	}

	/**
	 * @param array{appId: string, timestamp: int} $argument
	 */
	protected function run(mixed $argument): void {
		$appId = $argument['appId'];
		$timestamp = $argument['timestamp'];
		$dateTime = $this->time->getDateTime();
		$dateTime->setTimestamp($timestamp);

		$this->logger->debug(
			'Running background job to create app update notifications for "' . $appId . '"',
			[
				'app' => Application::APP_NAME,
			],
		);

		if ($this->manager->getChangelogFile($appId, 'en') === null) {
			$this->logger->debug('Skipping app updated notification - no changelog provided');
			return;
		}

		$this->stopPreviousNotifications($appId);

		// Create new notifications
		$notification = $this->notificationManager->createNotification();
		$notification->setApp(Application::APP_NAME)
			->setDateTime($dateTime)
			->setSubject('app_updated', [$appId])
			->setObject('app_updated', $appId);

		$this->notifyUsers($appId, $notification);
	}

	/**
	 * Stop all previous notifications users might not have dismissed until now
	 * @param string $appId The app to stop update notifications for
	 */
	private function stopPreviousNotifications(string $appId): void {
		$notification = $this->notificationManager->createNotification();
		$notification->setApp(Application::APP_NAME)
			->setObject('app_updated', $appId);
		$this->notificationManager->markProcessed($notification);
	}

	/**
	 * Notify all users for which the updated app is enabled
	 */
	private function notifyUsers(string $appId, INotification $notification): void {
		$guestsEnabled = $this->appConfig->getAppValueBool('app_updated.notify_guests', false) && class_exists('\OCA\Guests\UserBackend');

		$isDefer = $this->notificationManager->defer();

		// Notify all seen users about the app update
		$this->userManager->callForSeenUsers(function (IUser $user) use ($guestsEnabled, $appId, $notification): void {
			if (!$guestsEnabled && ($user->getBackendClassName() === '\OCA\Guests\UserBackend')) {
				return;
			}

			if (!$this->appManager->isEnabledForUser($appId, $user)) {
				return;
			}

			$notification->setUser($user->getUID());
			$this->notificationManager->notify($notification);
		});

		// If we enabled the defer we call the flush
		if ($isDefer) {
			$this->notificationManager->flush();
		}
	}
}
