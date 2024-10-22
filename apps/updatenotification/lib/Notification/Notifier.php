<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\UpdateNotification\Notification;

use OCP\App\IAppManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Notification\AlreadyProcessedException;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;
use OCP\Server;
use OCP\Util;

class Notifier implements INotifier {
	/** @var string[] */
	protected $appVersions;

	/**
	 * Notifier constructor.
	 *
	 * @param IURLGenerator $url
	 * @param IConfig $config
	 * @param IManager $notificationManager
	 * @param IFactory $l10NFactory
	 * @param IUserSession $userSession
	 * @param IGroupManager $groupManager
	 */
	public function __construct(
		protected IURLGenerator $url,
		protected IConfig $config,
		protected IManager $notificationManager,
		protected IFactory $l10NFactory,
		protected IUserSession $userSession,
		protected IGroupManager $groupManager,
	) {
		$this->appVersions = $this->getAppVersions();
	}

	/**
	 * Identifier of the notifier, only use [a-z0-9_]
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getID(): string {
		return 'updatenotification';
	}

	/**
	 * Human readable name describing the notifier
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getName(): string {
		return $this->l10NFactory->get('updatenotification')->t('Update notifications');
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 * @throws UnknownNotificationException When the notification was not prepared by a notifier
	 * @throws AlreadyProcessedException When the notification is not needed anymore and should be deleted
	 * @since 9.0.0
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== 'updatenotification') {
			throw new UnknownNotificationException('Unknown app id');
		}

		if ($notification->getSubject() !== 'update_available' && $notification->getSubject() !== 'connection_error') {
			throw new UnknownNotificationException('Unknown subject');
		}

		$l = $this->l10NFactory->get('updatenotification', $languageCode);
		if ($notification->getSubject() === 'connection_error') {
			$errors = (int)$this->config->getAppValue('updatenotification', 'update_check_errors', '0');
			if ($errors === 0) {
				throw new AlreadyProcessedException();
			}

			$notification->setParsedSubject($l->t('The update server could not be reached since %d days to check for new updates.', [$errors]))
				->setParsedMessage($l->t('Please check the Nextcloud and server log files for errors.'));
		} else {
			if ($notification->getObjectType() === 'core') {
				$this->updateAlreadyInstalledCheck($notification, $this->getCoreVersions());

				$parameters = $notification->getSubjectParameters();
				$notification->setRichSubject($l->t('Update to {serverAndVersion} is available.'), [
					'serverAndVersion' => [
						'type' => 'highlight',
						'id' => $notification->getObjectType(),
						'name' => $parameters['version'],
					]
				]);

				if ($this->isAdmin()) {
					$notification->setLink($this->url->linkToRouteAbsolute('settings.AdminSettings.index', ['section' => 'overview']) . '#version');
				}
			} else {
				$appInfo = $this->getAppInfo($notification->getObjectType(), $languageCode);
				$appName = ($appInfo === null) ? $notification->getObjectType() : $appInfo['name'];

				if (isset($this->appVersions[$notification->getObjectType()])) {
					$this->updateAlreadyInstalledCheck($notification, $this->appVersions[$notification->getObjectType()]);
				}

				$notification->setRichSubject($l->t('Update for {app} to version %s is available.', [$notification->getObjectId()]), [
					'app' => [
						'type' => 'app',
						'id' => $notification->getObjectType(),
						'name' => $appName,
					]
				]);

				if ($this->isAdmin()) {
					$notification->setLink($this->url->linkToRouteAbsolute('settings.AppSettings.viewApps', ['category' => 'updates']) . '#app-' . $notification->getObjectType());
				}
			}
		}

		$notification->setIcon($this->url->getAbsoluteURL($this->url->imagePath('updatenotification', 'notification.svg')));

		return $notification;
	}

	/**
	 * Remove the notification and prevent rendering, when the update is installed
	 *
	 * @param INotification $notification
	 * @param string $installedVersion
	 * @throws AlreadyProcessedException When the update is already installed
	 */
	protected function updateAlreadyInstalledCheck(INotification $notification, $installedVersion) {
		if (version_compare($notification->getObjectId(), $installedVersion, '<=')) {
			throw new AlreadyProcessedException();
		}
	}

	/**
	 * @return bool
	 */
	protected function isAdmin(): bool {
		$user = $this->userSession->getUser();

		if ($user instanceof IUser) {
			return $this->groupManager->isAdmin($user->getUID());
		}

		return false;
	}

	protected function getCoreVersions(): string {
		return implode('.', Util::getVersion());
	}

	protected function getAppVersions(): array {
		return \OC_App::getAppVersions();
	}

	protected function getAppInfo($appId, $languageCode) {
		return Server::get(IAppManager::class)->getAppInfo($appId, false, $languageCode);
	}
}
