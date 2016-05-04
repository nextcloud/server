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


use OCP\App\IAppManager;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {

	/** @var IFactory */
	protected $l10NFactory;

	/**
	 * Notifier constructor.
	 *
	 * @param IFactory $l10NFactory
	 */
	public function __construct(IFactory $l10NFactory) {
		$this->l10NFactory = $l10NFactory;
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 * @throws \InvalidArgumentException When the notification was not prepared by a notifier
	 * @since 9.0.0
	 */
	public function prepare(INotification $notification, $languageCode) {
		if ($notification->getApp() !== 'updatenotification') {
			throw new \InvalidArgumentException();
		}

		$l = $this->l10NFactory->get('updatenotification', $languageCode);
		if ($notification->getObjectType() === 'core') {
			$appName = $l->t('ownCloud core');
		} else {
			$appInfo = \OC_App::getAppInfo($notification->getObjectType());
			$appName = ($appInfo === null) ? $notification->getObjectType() : $appInfo['name'];
		}

		$notification->setParsedSubject($l->t('Update for %1$s to version %2$s is available.', [$appName, $notification->getObjectId()]));
		return $notification;
	}
}
