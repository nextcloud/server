<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\UpdateNotification\Notification;

use OC\Installer;
use OC\Updater\VersionCheck;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\Notification\IManager;

class BackgroundJob extends TimedJob {
	protected $connectionNotifications = [3, 7, 14, 30];

	/** @var string[] */
	protected $users;

	public function __construct(
		ITimeFactory $timeFactory,
		protected IConfig $config,
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
	 * Check for ownCloud update
	 */
	protected function checkCoreUpdate() {
		if (\in_array($this->getChannel(), ['daily', 'git'], true)) {
			// "These aren't the update channels you're looking for." - Ben Obi-Wan Kenobi
			return;
		}

		$status = $this->versionCheck->check();
		if ($status === false) {
			$errors = 1 + (int) $this->config->getAppValue('updatenotification', 'update_check_errors', '0');
			$this->config->setAppValue('updatenotification', 'update_check_errors', (string) $errors);

			if (\in_array($errors, $this->connectionNotifications, true)) {
				$this->sendErrorNotifications($errors);
			}
		} elseif (\is_array($status)) {
			$this->config->setAppValue('updatenotification', 'update_check_errors', '0');
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
	protected function sendErrorNotifications($numDays) {
		$this->clearErrorNotifications();

		$notification = $this->notificationManager->createNotification();
		try {
			$notification->setApp('updatenotification')
				->setDateTime(new \DateTime())
				->setObject('updatenotification', 'error')
				->setSubject('connection_error', ['days' => $numDays]);

			foreach ($this->getUsersToNotify() as $uid) {
				$notification->setUser((string) $uid);
				$this->notificationManager->notify($notification);
			}
		} catch (\InvalidArgumentException $e) {
			return;
		}
	}

	/**
	 * Remove error notifications again
	 */
	protected function clearErrorNotifications() {
		$notification = $this->notificationManager->createNotification();
		try {
			$notification->setApp('updatenotification')
				->setSubject('connection_error')
				->setObject('updatenotification', 'error');
		} catch (\InvalidArgumentException $e) {
			return;
		}
		$this->notificationManager->markProcessed($notification);
	}

	/**
	 * Check all installed apps for updates
	 */
	protected function checkAppUpdates() {
		$apps = $this->appManager->getInstalledApps();
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
	protected function createNotifications($app, $version, $visibleVersion = '') {
		$lastNotification = $this->config->getAppValue('updatenotification', $app, false);
		if ($lastNotification === $version) {
			// We already notified about this update
			return;
		}

		if ($lastNotification !== false) {
			// Delete old updates
			$this->deleteOutdatedNotifications($app, $lastNotification);
		}

		$notification = $this->notificationManager->createNotification();
		try {
			$notification->setApp('updatenotification')
				->setDateTime(new \DateTime())
				->setObject($app, $version);

			if ($visibleVersion !== '') {
				$notification->setSubject('update_available', ['version' => $visibleVersion]);
			} else {
				$notification->setSubject('update_available');
			}

			foreach ($this->getUsersToNotify() as $uid) {
				$notification->setUser((string) $uid);
				$this->notificationManager->notify($notification);
			}
		} catch (\InvalidArgumentException $e) {
			return;
		}

		$this->config->setAppValue('updatenotification', $app, $version);
	}

	/**
	 * @return string[]
	 */
	protected function getUsersToNotify(): array {
		if ($this->users !== null) {
			return $this->users;
		}

		$notifyGroups = (array) json_decode($this->config->getAppValue('updatenotification', 'notify_groups', '["admin"]'), true);
		$this->users = [];
		foreach ($notifyGroups as $group) {
			$groupToNotify = $this->groupManager->get($group);
			if ($groupToNotify instanceof IGroup) {
				foreach ($groupToNotify->getUsers() as $user) {
					$this->users[$user->getUID()] = true;
				}
			}
		}

		$this->users = array_keys($this->users);

		return $this->users;
	}

	/**
	 * Delete notifications for old updates
	 *
	 * @param string $app
	 * @param string $version
	 */
	protected function deleteOutdatedNotifications($app, $version) {
		$notification = $this->notificationManager->createNotification();
		try {
			$notification->setApp('updatenotification')
				->setObject($app, $version);
		} catch (\InvalidArgumentException $e) {
			return;
		}
		$this->notificationManager->markProcessed($notification);
	}

	/**
	 * @return string
	 */
	protected function getChannel(): string {
		return \OC_Util::getChannel();
	}

	/**
	 * @param string $app
	 * @return string|false
	 */
	protected function isUpdateAvailable($app) {
		return $this->installer->isUpdateAvailable($app);
	}
}
