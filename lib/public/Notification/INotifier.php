<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Notification;

use OCP\AppFramework\Attribute\Implementable;

/**
 * Please consider implementing {@see IPreloadableNotifier} to improve performance. It allows to
 * preload and cache data for many notifications at once instead of loading the data for each
 * prepared notification separately.
 */
#[Implementable(since: '9.0.0')]
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
