<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Reminder;

use OCP\IUser;
use Sabre\VObject\Component\VEvent;

/**
 * Interface INotificationProvider
 *
 * @package OCA\DAV\CalDAV\Reminder
 */
interface INotificationProvider {

	/**
	 * Send notification
	 *
	 * @param VEvent $vevent
	 * @param string|null $calendarDisplayName
	 * @param string[] $principalEmailAddresses All email addresses associated to the principal owning the calendar object
	 * @param IUser[] $users
	 * @return void
	 */
	public function send(VEvent $vevent,
		?string $calendarDisplayName,
		array $principalEmailAddresses,
		array $users = []): void;
}
