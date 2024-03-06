<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2024 Ferdinand Thiessen <opensource@fthiessen.de>
 *
 * @author Ferdinand Thiessen <opensource@fthiessen.de>
 * @author Joas Schilling <coding@schilljs.com>
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

namespace OCA\UpdateNotification\Notification;

use OCA\UpdateNotification\AppInfo\Application;
use OCP\App\IAppManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Notification\IAction;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
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
	 * @throws \InvalidArgumentException When the notification was not prepared by a notifier
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== Application::APP_NAME) {
			throw new \InvalidArgumentException('Unknown app');
		}

		if ($notification->getSubject() !== 'app_updated') {
			throw new \InvalidArgumentException('Unknown subject');
		}

		$appId = $notification->getSubjectParameters()[0];
		$appInfo = $this->appManager->getAppInfo($appId, lang:$languageCode);
		if ($appInfo === null) {
			throw new \InvalidArgumentException('App info not found');
		}

		// Prepare translation factory for requested language
		$l = $this->l10nFactory->get(Application::APP_NAME, $languageCode);
		
		$icon = $this->appManager->getAppIcon($appId);
		if ($icon === null) {
			$icon = $this->urlGenerator->imagePath('core', 'default-app-icon');
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
