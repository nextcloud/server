<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Reminder;

class NotificationTypeDoesNotExistException extends \Exception {

	/**
	 * NotificationTypeDoesNotExistException constructor.
	 *
	 * @since 16.0.0
	 *
	 * @param string $type ReminderType
	 */
	public function __construct(string $type) {
		parent::__construct("Type $type is not an accepted type of notification");
	}
}
