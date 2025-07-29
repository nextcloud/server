<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\UpdateNotification\Notification;

use OCA\UpdateNotification\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\AppFramework\Services\IAppConfig;
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
use OCP\ServerVersion;

class Notifier implements INotifier {
	/** @var string[] */
	protected $appVersions;

	/**
	 * Notifier constructor.
	 */
	public function __construct(
		protected IURLGenerator $url,
		protected IAppConfig $appConfig,
		protected IManager $notificationManager,
		protected IFactory $l10NFactory,
		protected IUserSession $userSession,
		protected IGroupManager $groupManager,
		protected IAppManager $appManager,
		protected ServerVersion $serverVersion,
	) {
		$this->appVersions = $this->appManager->getAppInstalledVersions();
	}

	/**
	 * Identifier of the notifier, only use [a-z0-9_]
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getID(): string {
		return Application::APP_NAME;
	}

	/**
	 * Human readable name describing the notifier
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getName(): string {
		return $this->l10NFactory->get(Application::APP_NAME)->t('Update notifications');
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
		if ($notification->getApp() !== Application::APP_NAME) {
			throw new UnknownNotificationException('Unknown app id');
		}

		if ($notification->getSubject() !== 'update_available' && $notification->getSubject() !== 'connection_error') {
			throw new UnknownNotificationException('Unknown subject');
		}

		$l = $this->l10NFactory->get(Application::APP_NAME, $languageCode);
		if ($notification->getSubject() === 'connection_error') {
			$errors = $this->appConfig->getAppValueInt('update_check_errors', 0);
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

		$notification->setIcon($this->url->getAbsoluteURL($this->url->imagePath(Application::APP_NAME, 'notification.svg')));

		return $notification;
	}

	/**
	 * Remove the notification and prevent rendering, when the update is installed
	 *
	 * @param INotification $notification
	 * @param string $installedVersion
	 * @throws AlreadyProcessedException When the update is already installed
	 */
	protected function updateAlreadyInstalledCheck(INotification $notification, $installedVersion): void {
		if (version_compare($notification->getObjectId(), $installedVersion, '<=')) {
			throw new AlreadyProcessedException();
		}
	}

	protected function isAdmin(): bool {
		$user = $this->userSession->getUser();

		if ($user instanceof IUser) {
			return $this->groupManager->isAdmin($user->getUID());
		}

		return false;
	}

	protected function getCoreVersions(): string {
		return implode('.', $this->serverVersion->getVersion());
	}

	protected function getAppInfo(string $appId, ?string $languageCode): ?array {
		return $this->appManager->getAppInfo($appId, false, $languageCode);
	}
}
