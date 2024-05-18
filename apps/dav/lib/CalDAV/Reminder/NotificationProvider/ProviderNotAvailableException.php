<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018 Thomas Citharel <tcit@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
