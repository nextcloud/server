<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\UpdateNotification\Notification;

use OCA\UpdateNotification\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\AlreadyProcessedException;
use OCP\Notification\IAction;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;
use Psr\Log\LoggerInterface;

class AppUpdateNotifier implements INotifier {

	public function __construct(
		private IFactory $l10nFactory,
		private INotificationManager $notificationManager,
		private IUserManager $userManager,
		private IURLGenerator $urlGenerator,
		private IAppManager $appManager,
		private LoggerInterface $logger,
	) {
	}

	public function getID(): string {
		return 'updatenotification_app_updated';
	}

	/**
	 * Human readable name describing the notifier
	 */
	public function getName(): string {
		return $this->l10nFactory->get(Application::APP_NAME)->t('App updated');
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 * @throws UnknownNotificationException When the notification was not prepared by a notifier
	 * @throws AlreadyProcessedException When the app is no longer known
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== Application::APP_NAME) {
			throw new UnknownNotificationException('Unknown app');
		}

		if ($notification->getSubject() !== 'app_updated') {
			throw new UnknownNotificationException('Unknown subject');
		}

		$appId = $notification->getSubjectParameters()[0];
		$appInfo = $this->appManager->getAppInfo($appId, lang:$languageCode);
		if ($appInfo === null) {
			throw new AlreadyProcessedException();
		}

		// Prepare translation factory for requested language
		$l = $this->l10nFactory->get(Application::APP_NAME, $languageCode);

		$icon = $this->appManager->getAppIcon($appId, true);
		if ($icon === null) {
			$icon = $this->urlGenerator->imagePath('core', 'actions/change.svg');
		}

		$action = $notification->createAction();
		$action
			->setLabel($l->t('See what\'s new'))
			->setParsedLabel($l->t('See what\'s new'))
			->setLink($this->urlGenerator->linkToRouteAbsolute('updatenotification.Changelog.showChangelog', ['app' => $appId, 'version' => $this->appManager->getAppVersion($appId)]), IAction::TYPE_WEB);

		$notification
			->setIcon($this->urlGenerator->getAbsoluteURL($icon))
			->addParsedAction($action)
			->setRichSubject(
				$l->t('{app} updated to version {version}'),
				[
					'app' => [
						'type' => 'app',
						'id' => $appId,
						'name' => $appInfo['name'],
					],
					'version' => [
						'type' => 'highlight',
						'id' => $appId,
						'name' => $appInfo['version'],
					],
				],
			);

		return $notification;
	}
}
