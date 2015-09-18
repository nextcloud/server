<?php
/**
 * @author Joas Schilling <nickvergessen@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

namespace OC\Notification;


class Manager implements IManager {
	/** @var IApp */
	protected $apps;

	/** @var INotifier */
	protected $notifiers;

	/** @var \Closure */
	protected $appsClosures;

	/** @var \Closure */
	protected $notifiersClosures;

	public function __construct() {
		$this->apps = [];
		$this->notifiers = [];
		$this->appsClosures = [];
		$this->notifiersClosures = [];
	}

	/**
	 * @param \Closure $service The service must implement IApp, otherwise a
	 *                          \InvalidArgumentException is thrown later
	 * @return null
	 * @since 8.2.0
	 */
	public function registerApp(\Closure $service) {
		$this->appsClosures[] = $service;
		$this->apps = [];
	}

	/**
	 * @param \Closure $service The service must implement INotifier, otherwise a
	 *                          \InvalidArgumentException is thrown later
	 * @return null
	 * @since 8.2.0
	 */
	public function registerNotifier(\Closure $service) {
		$this->notifiersClosures[] = $service;
		$this->notifiers = [];
	}

	/**
	 * @return IApp[]
	 */
	protected function getApps() {
		if (!empty($this->apps)) {
			return $this->apps;
		}

		$this->apps = [];
		foreach ($this->appsClosures as $closure) {
			$app = $closure();
			if (!($app instanceof IApp)) {
				throw new \InvalidArgumentException('The given notification app does not implement the IApp interface');
			}
			$this->apps[] = $app;
		}

		return $this->apps;
	}

	/**
	 * @return INotifier[]
	 */
	protected function getNotifiers() {
		if (!empty($this->notifiers)) {
			return $this->notifiers;
		}

		$this->notifiers = [];
		foreach ($this->notifiersClosures as $closure) {
			$notifier = $closure();
			if (!($notifier instanceof INotifier)) {
				throw new \InvalidArgumentException('The given notification app does not implement the INotifier interface');
			}
			$this->notifiers[] = $notifier;
		}

		return $this->notifiers;
	}

	/**
	 * @return INotification
	 * @since 8.2.0
	 */
	public function createNotification() {
		return new Notification();
	}

	/**
	 * @return bool
	 * @since 8.2.0
	 */
	public function hasNotifiers() {
		return !empty($this->notifiersClosures);
	}

	/**
	 * @param INotification $notification
	 * @return null
	 * @throws \InvalidArgumentException When the notification is not valid
	 * @since 8.2.0
	 */
	public function notify(INotification $notification) {
		if (!$notification->isValid()) {
			throw new \InvalidArgumentException('The given notification is invalid');
		}

		$apps = $this->getApps();

		foreach ($apps as $app) {
			try {
				$app->notify($notification);
			} catch (\InvalidArgumentException $e) {
			}
		}
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 * @throws \InvalidArgumentException When the notification was not prepared by a notifier
	 * @since 8.2.0
	 */
	public function prepare(INotification $notification, $languageCode) {
		$notifiers = $this->getNotifiers();

		foreach ($notifiers as $notifier) {
			try {
				$notification = $notifier->prepare($notification, $languageCode);
			} catch (\InvalidArgumentException $e) {
				continue;
			}

			if (!($notification instanceof INotification) || !$notification->isValidParsed()) {
				throw new \InvalidArgumentException('The given notification has not been handled');
			}
		}

		if (!($notification instanceof INotification) || !$notification->isValidParsed()) {
			throw new \InvalidArgumentException('The given notification has not been handled');
		}

		return $notification;
	}

	/**
	 * @param INotification $notification
	 * @return null
	 */
	public function markProcessed(INotification $notification) {
		$apps = $this->getApps();

		foreach ($apps as $app) {
			$app->markProcessed($notification);
		}
	}

	/**
	 * @param INotification $notification
	 * @return int
	 */
	public function getCount(INotification $notification) {
		$apps = $this->getApps();

		$count = 0;
		foreach ($apps as $app) {
			$count += $app->getCount($notification);
		}

		return $count;
	}
}
