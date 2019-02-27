<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
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

namespace OCA\TwoFactorBackupCodes\Notifications;

use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {

	/** @var IFactory */
	private $factory;

	/** @var IURLGenerator */
	private $url;

	public function __construct(IFactory $factory, IURLGenerator $url) {
		$this->factory = $factory;
		$this->url = $url;
	}

	public function prepare(INotification $notification, $languageCode) {
		if ($notification->getApp() !== 'twofactor_backupcodes') {
			// Not my app => throw
			throw new \InvalidArgumentException();
		}

		// Read the language from the notification
		$l = $this->factory->get('twofactor_backupcodes', $languageCode);

		switch ($notification->getSubject()) {
			case 'create_backupcodes':
				$notification->setParsedSubject(
					$l->t('Generate backup codes')
				)->setParsedMessage(
					$l->t('You have enabled two-factor authentication but have not yet generated backup codes. Be sure to do this in case you lose access to your second factor.')
				);

				$notification->setLink($this->url->linkToRouteAbsolute('settings.PersonalSettings.index', ['section' => 'security']));
				return $notification;

			default:
				// Unknown subject => Unknown notification => throw
				throw new \InvalidArgumentException();
		}
	}

}
