<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\Notification;

/**
 * Interface INotifier classes should implement if they want to process notifications
 * that are dismissed by the user.
 *
 * This can be useful if dismissing the notification will leave it in an incomplete
 * state. The handler can choose to for example do some default action.
 *
 * @since 18.0.0
 */
interface IDismissableNotifier extends INotifier {
	/**
	 * @param INotification $notification
	 * @throws UnknownNotificationException when the notifier is not in charge of the notification
	 *
	 * @since 18.0.0
	 * @since 30.0.0 Notifiers should throw {@see UnknownNotificationException} instead of \InvalidArgumentException
	 *  when they did not handle the notification. Throwing \InvalidArgumentException directly is deprecated and will
	 *  be logged as an error in Nextcloud 39.
	 */
	public function dismissNotification(INotification $notification): void;
}
