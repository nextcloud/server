<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
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
namespace OCP\Notification;

/**
 * Interface INotifier
 *
 * @since 9.0.0
 */
interface INotifier {
	/**
	 * Identifier of the notifier, only use [a-z0-9_]
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getID(): string;

	/**
	 * Human-readable name describing the notifier
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getName(): string;

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 * @throws UnknownNotificationException When the notification was not prepared by a notifier
	 * @throws AlreadyProcessedException When the notification is not needed anymore and should be deleted
	 * @throws IncompleteParsedNotificationException Only to be thrown by the {@see IManager}
	 * @since 9.0.0
	 * @since 30.0.0 Notifiers should throw {@see UnknownNotificationException} instead of \InvalidArgumentException
	 *  when they did not handle the notification. Throwing \InvalidArgumentException directly is deprecated and will
	 *  be logged as an error in Nextcloud 39.
	 * @since 30.0.0 Throws {@see IncompleteParsedNotificationException} when not all required fields
	 *  are set at the end of the manager or after a INotifier that claimed to have parsed the notification.
	 */
	public function prepare(INotification $notification, string $languageCode): INotification;
}
