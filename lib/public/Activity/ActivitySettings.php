<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Robin Appelman <robin@icewind.nl>
 *
 * @author Robin Appelman <robin@icewind.nl>
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
namespace OCP\Activity;

/**
 * @since 20.0.0
 */
abstract class ActivitySettings implements ISetting {
	/**
	 * @return string Lowercase a-z and underscore only identifier
	 * @since 20.0.0
	 */
	abstract public function getIdentifier(): string;

	/**
	 * @return string A translated string
	 * @since 20.0.0
	 */
	abstract public function getName(): string;

	/**
	 * @return string Lowercase a-z and underscore only group identifier
	 * @since 20.0.0
	 */
	abstract public function getGroupIdentifier(): string;

	/**
	 * @return string A translated string for the settings group
	 * @since 20.0.0
	 */
	abstract public function getGroupName(): string;

	/**
	 * @return int whether the filter should be rather on the top or bottom of
	 * the admin section. The filters are arranged in ascending order of the
	 * priority values. It is required to return a value between 0 and 100.
	 * @since 20.0.0
	 */
	abstract public function getPriority(): int;

	/**
	 * @return bool True when the option can be changed for the mail
	 * @since 20.0.0
	 */
	public function canChangeMail(): bool {
		return true;
	}

	/**
	 * @return bool True when the option can be changed for the notification
	 * @since 20.0.0
	 */
	public function canChangeNotification(): bool {
		return true;
	}

	/**
	 * @return bool Whether an activity email should be sent by default
	 * @since 20.0.0
	 */
	public function isDefaultEnabledMail(): bool {
		return false;
	}

	/**
	 * @return bool Whether an activity notification should be sent by default
	 * @since 20.0.0
	 */
	public function isDefaultEnabledNotification(): bool {
		return $this->isDefaultEnabledMail() && !$this->canChangeMail();
	}

	/**
	 * Left in for backwards compatibility
	 *
	 * @return bool
	 * @since 20.0.0
	 */
	public function canChangeStream(): bool {
		return false;
	}

	/**
	 * Left in for backwards compatibility
	 *
	 * @return bool
	 * @since 20.0.0
	 */
	public function isDefaultEnabledStream(): bool {
		return true;
	}
}
