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

namespace OCA\UpdateNotification\BackgroundJob;

use OCA\UpdateNotification\AppInfo\Application;
use OCA\UpdateNotification\Manager;
use OCP\App\IAppManager;
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
		$guestsEnabled = class_exists('\OCA\Guests\UserBackend');

		$isDefer = $this->notificationManager->defer();

		// Notify all seen users about the app update
		$this->userManager->callForSeenUsers(function (IUser $user) use ($guestsEnabled, $appId, $notification) {
			if ($guestsEnabled && ($user->getBackend() instanceof ('\OCA\Guests\UserBackend'))) {
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
