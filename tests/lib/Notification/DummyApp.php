<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Notification;

use OCP\Notification\IApp;
use OCP\Notification\INotification;

class DummyApp implements IApp {
	/**
	 * @param INotification $notification
	 * @throws \InvalidArgumentException When the notification is not valid
	 * @since 9.0.0
	 */
	public function notify(INotification $notification): void {
		// TODO: Implement notify() method.
	}

	/**
	 * @param INotification $notification
	 * @since 9.0.0
	 */
	public function markProcessed(INotification $notification): void {
		// TODO: Implement markProcessed() method.
	}

	/**
	 * @param INotification $notification
	 * @return int
	 * @since 9.0.0
	 */
	public function getCount(INotification $notification): int {
		// TODO: Implement getCount() method.
	}
}
