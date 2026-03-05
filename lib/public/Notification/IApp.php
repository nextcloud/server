<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCP\Notification;

use OCP\AppFramework\Attribute\Implementable;

#[Implementable(since: '9.0.0')]
interface IApp {
	/**
	 * @param INotification $notification
	 * @throws IncompleteNotificationException When the notification does not have all required fields set
	 * @since 9.0.0
	 * @since 30.0.0 throws {@see IncompleteNotificationException} instead of \InvalidArgumentException
	 */
	public function notify(INotification $notification): void;

	/**
	 * @param INotification $notification
	 * @since 9.0.0
	 */
	public function markProcessed(INotification $notification): void;

	/**
	 * @param INotification $notification
	 * @return int
	 * @since 9.0.0
	 */
	public function getCount(INotification $notification): int;
}
