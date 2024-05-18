<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2019, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OC\Core\Notification;

use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\IAction;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class CoreNotifier implements INotifier {
	public function __construct(
		private IConfig $config,
		private IFactory $factory,
		private IURLGenerator $url,
	) {
	}

	/**
	 * Identifier of the notifier, only use [a-z0-9_]
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getID(): string {
		return 'core';
	}

	/**
	 * Human readable name describing the notifier
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getName(): string {
		return $this->factory->get('core')->t('Nextcloud Server');
	}

	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== 'core') {
			throw new \InvalidArgumentException();
		}
		$l = $this->factory->get('core', $languageCode);

		if ($notification->getSubject() === 'repair_exposing_links') {
			$notification->setParsedSubject($l->t('Some of your link shares have been removed'));
			$notification->setParsedMessage($l->t('Due to a security bug we had to remove some of your link shares. Please see the link for more information.'));
			$notification->setLink('https://nextcloud.com/security/advisory/?id=NC-SA-2019-003');
			return $notification;
		}

		if ($notification->getSubject() === 'user_limit_reached') {
			$notification->setParsedSubject($l->t('The account limit of this instance is reached.'));
			$notification->setParsedMessage($l->t('Enter your subscription key in the support app in order to increase the account limit. This does also grant you all additional benefits that Nextcloud Enterprise offers and is highly recommended for the operation in companies.'));
			$notification->setIcon($this->url->getAbsoluteURL($this->url->imagePath('core', 'places/contacts.svg')));
			$action = $notification->createAction();
			$label = $l->t('Learn more ↗');
			$link = $this->config->getSystemValueString('one-click-instance.link', 'https://nextcloud.com/enterprise/');
			$action->setLabel($label)
				->setParsedLabel($label)
				->setLink($link, IAction::TYPE_WEB)
				->setPrimary(true);
			$notification->addParsedAction($action);
			return $notification;
		}

		throw new \InvalidArgumentException('Invalid subject');
	}
}
