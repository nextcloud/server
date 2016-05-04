<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\UpdateNotification\Notification;


use OC\BackgroundJob\TimedJob;
use OC\Installer;
use OC\Updater\VersionCheck;
use OCP\App\IAppManager;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\Notification\IManager;

class BackgroundJob extends TimedJob {

	/** @var IConfig */
	protected $config;

	/** @var IManager */
	protected $notificationManager;

	/** @var IGroupManager */
	protected $groupManager;

	/** @var IAppManager */
	protected $appManager;

	/** @var IClientService */
	protected $client;

	/** @var IUser[] */
	protected $users;

	/**
	 * NotificationBackgroundJob constructor.
	 *
	 * @param IConfig $config
	 * @param IManager $notificationManager
	 * @param IGroupManager $groupManager
	 * @param IAppManager $appManager
	 * @param IClientService $client
	 */
	public function __construct(IConfig $config, IManager $notificationManager, IGroupManager $groupManager, IAppManager $appManager, IClientService $client) {
		// Run once a day
		$this->setInterval(60 * 60 * 24);

		$this->config = $config;
		$this->notificationManager = $notificationManager;
		$this->groupManager = $groupManager;
		$this->appManager = $appManager;
		$this->client = $client;
	}

	protected function run($argument) {
		$this->checkCoreUpdate();
		$this->checkAppUpdates();
	}

	/**
	 * Check for ownCloud update
	 */
	protected function checkCoreUpdate() {
		if (in_array(\OC_Util::getChannel(), ['daily', 'git'])) {
			// "These aren't the update channels you're looking for." - Ben Obi-Wan Kenobi
			return;
		}

		$updater = new VersionCheck(
			$this->client,
			$this->config
		);

		$status = $updater->check();
		if (isset($status['version'])) {
			$this->createNotifications('core', $status['version']);
		}
	}

	/**
	 * Check all installed apps for updates
	 */
	protected function checkAppUpdates() {
		$apps = $this->appManager->getInstalledApps();
		foreach ($apps as $app) {
			$update = Installer::isUpdateAvailable($app);
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
	 */
	protected function createNotifications($app, $version) {
		$lastNotification = $this->config->getAppValue('updatenotification', $app, false);
		if ($lastNotification === $version) {
			// We already notified about this update
			return;
		} else if ($lastNotification !== false) {
			// Delete old updates
			$this->deleteOutdatedNotifications($app, $lastNotification);
		}


		$notification = $this->notificationManager->createNotification();
		$notification->setApp('updatenotification')
			->setDateTime(new \DateTime())
			->setObject($app, $version)
			->setSubject('update_available');

		foreach ($this->getUsersToNotify() as $user) {
			$notification->setUser($user->getUID());
			$this->notificationManager->notify($notification);
		}

		$this->config->setAppValue('updatenotification', $app, $version);
	}

	/**
	 * @return \OCP\IUser[]
	 */
	protected function getUsersToNotify() {
		if ($this->users !== null) {
			return $this->users;
		}

		$groupToNotify = $this->groupManager->get('admin');
		$this->users = $groupToNotify->getUsers();
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
		$notification->setApp('updatenotification')
			->setObject($app, $version);
		$this->notificationManager->markProcessed($notification);
	}
}
