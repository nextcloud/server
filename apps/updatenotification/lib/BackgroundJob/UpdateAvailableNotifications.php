<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\UpdateNotification\BackgroundJob;

use OC\Installer;
use OC\Updater\VersionCheck;
use OCA\UpdateNotification\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\AppFramework\Services\IAppConfig;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\Notification\IManager;
use OCP\ServerVersion;

class UpdateAvailableNotifications extends TimedJob {

	/**
	 * Numbers of failed updater connection to report error as notification.
	 * @var list<int>
	 */
	protected const CONNECTION_NOTIFICATIONS = [3, 7, 14, 30];

	/** @var ?string[] */
	protected $users = null;

	public function __construct(
		ITimeFactory $timeFactory,
		protected ServerVersion $serverVersion,
		protected IConfig $config,
		protected IAppConfig $appConfig,
		protected IManager $notificationManager,
		protected IGroupManager $groupManager,
		protected IAppManager $appManager,
		protected Installer $installer,
		protected VersionCheck $versionCheck,
	) {
		parent::__construct($timeFactory);
		// Run once a day
		$this->setInterval(60 * 60 * 24);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	protected function run($argument) {
		// Do not check for updates if not connected to the internet
		if (!$this->config->getSystemValueBool('has_internet_connection', true)) {
			return;
		}

		if (\OC::$CLI && !$this->config->getSystemValueBool('debug', false)) {
			try {
				// Jitter the pinging of the updater server and the appstore a bit.
				// Otherwise all Nextcloud installations are pinging the servers
				// in one of 288
				sleep(random_int(1, 180));
			} catch (\Exception $e) {
			}
		}

		$this->checkCoreUpdate();
		$this->checkAppUpdates();
	}

	/**
	 * Check for Nextcloud server update
	 */
	protected function checkCoreUpdate(): void {
		if (!$this->config->getSystemValueBool('updatechecker', true)) {
			// update checker is disabled so no core update check!
			return;
		}

		if (\in_array($this->serverVersion->getChannel(), ['daily', 'git'], true)) {
			// "These aren't the update channels you're looking for." - Ben Obi-Wan Kenobi
			return;
		}

		$status = $this->versionCheck->check();
		if ($status === false) {
			$errors = 1 + $this->appConfig->getAppValueInt('update_check_errors', 0);
			$this->appConfig->setAppValueInt('update_check_errors', $errors);

			if (\in_array($errors, self::CONNECTION_NOTIFICATIONS, true)) {
				$this->sendErrorNotifications($errors);
			}
		} elseif (\is_array($status)) {
			$this->appConfig->setAppValueInt('update_check_errors', 0);
			$this->clearErrorNotifications();

			if (isset($status['version'])) {
				$this->createNotifications('core', $status['version'], $status['versionstring']);
			}
		}
	}

	/**
	 * Send a message to the admin when the update server could not be reached
	 * @param int $numDays
	 */
	protected function sendErrorNotifications($numDays): void {
		$this->clearErrorNotifications();

		$notification = $this->notificationManager->createNotification();
		try {
			$notification->setApp(Application::APP_NAME)
				->setDateTime(new \DateTime())
				->setObject(Application::APP_NAME, 'error')
				->setSubject('connection_error', ['days' => $numDays]);

			foreach ($this->getUsersToNotify() as $uid) {
				$notification->setUser($uid);
				$this->notificationManager->notify($notification);
			}
		} catch (\InvalidArgumentException $e) {
			return;
		}
	}

	/**
	 * Remove error notifications again
	 */
	protected function clearErrorNotifications(): void {
		$notification = $this->notificationManager->createNotification();
		try {
			$notification->setApp(Application::APP_NAME)
				->setSubject('connection_error')
				->setObject(Application::APP_NAME, 'error');
		} catch (\InvalidArgumentException $e) {
			return;
		}
		$this->notificationManager->markProcessed($notification);
	}

	/**
	 * Check all installed apps for updates
	 */
	protected function checkAppUpdates(): void {
		$apps = $this->appManager->getEnabledApps();
		foreach ($apps as $app) {
			$update = $this->isUpdateAvailable($app);
			if ($update !== false) {
				$this->createNotifications($app, $update);
			}
		}
	}

	/**
	 * Create notifications for this app version
	 *
	 * @param string $app
	 * @param string $version
	 * @param string $visibleVersion
	 */
	protected function createNotifications($app, $version, $visibleVersion = ''): void {
		$lastNotification = $this->appConfig->getAppValueString($app, '');
		if ($lastNotification === $version) {
			// We already notified about this update
			return;
		}

		if ($lastNotification !== '') {
			// Delete old updates
			$this->deleteOutdatedNotifications($app, $lastNotification);
		}

		$notification = $this->notificationManager->createNotification();
		try {
			$notification->setApp(Application::APP_NAME)
				->setDateTime(new \DateTime())
				->setObject($app, $version);

			if ($visibleVersion !== '') {
				$notification->setSubject('update_available', ['version' => $visibleVersion]);
			} else {
				$notification->setSubject('update_available');
			}

			foreach ($this->getUsersToNotify() as $uid) {
				$notification->setUser($uid);
				$this->notificationManager->notify($notification);
			}
		} catch (\InvalidArgumentException $e) {
			return;
		}

		$this->appConfig->setAppValueString($app, $version);
	}

	/**
	 * @return string[]
	 */
	protected function getUsersToNotify(): array {
		if ($this->users !== null) {
			return $this->users;
		}

		$notifyGroups = $this->appConfig->getAppValueArray('notify_groups', ['admin']);
		$this->users = [];
		foreach ($notifyGroups as $group) {
			$groupToNotify = $this->groupManager->get($group);
			if ($groupToNotify instanceof IGroup) {
				foreach ($groupToNotify->getUsers() as $user) {
					$this->users[] = $user->getUID();
				}
			}
		}

		$this->users = array_values(array_unique($this->users));
		return $this->users;
	}

	/**
	 * Delete notifications for old updates
	 *
	 * @param string $app
	 * @param string $version
	 */
	protected function deleteOutdatedNotifications($app, $version): void {
		$notification = $this->notificationManager->createNotification();
		try {
			$notification->setApp(Application::APP_NAME)
				->setObject($app, $version);
		} catch (\InvalidArgumentException) {
			return;
		}
		$this->notificationManager->markProcessed($notification);
	}

	/**
	 * @param string $app
	 * @return string|false
	 */
	protected function isUpdateAvailable($app) {
		return $this->installer->isUpdateAvailable($app);
	}
}
