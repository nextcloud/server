<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Reminder\NotificationProvider;

class ProviderNotAvailableException extends \Exception {

	/**
	 * ProviderNotAvailableException constructor.
	 *
	 * @since 16.0.0
	 *
	 * @param string $type ReminderType
	 */
	public function __construct(string $type) {
		parent::__construct("No notification provider for type $type available");
	}
}
