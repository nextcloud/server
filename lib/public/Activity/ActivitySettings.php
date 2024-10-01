<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	abstract public function getIdentifier();

	/**
	 * @return string A translated string
	 * @since 20.0.0
	 */
	abstract public function getName();

	/**
	 * @return string Lowercase a-z and underscore only group identifier
	 * @since 20.0.0
	 */
	abstract public function getGroupIdentifier();

	/**
	 * @return string A translated string for the settings group
	 * @since 20.0.0
	 */
	abstract public function getGroupName();

	/**
	 * @return int whether the filter should be rather on the top or bottom of
	 *             the admin section. The filters are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 * @since 20.0.0
	 */
	abstract public function getPriority();

	/**
	 * @return bool True when the option can be changed for the mail
	 * @since 20.0.0
	 */
	public function canChangeMail() {
		return true;
	}

	/**
	 * @return bool True when the option can be changed for the notification
	 * @since 20.0.0
	 */
	public function canChangeNotification() {
		return true;
	}

	/**
	 * @return bool Whether or not an activity email should be send by default
	 * @since 20.0.0
	 */
	public function isDefaultEnabledMail() {
		return false;
	}

	/**
	 * @return bool Whether or not an activity notification should be send by default
	 * @since 20.0.0
	 */
	public function isDefaultEnabledNotification() {
		return $this->isDefaultEnabledMail() && !$this->canChangeMail();
	}

	/**
	 * Left in for backwards compatibility
	 *
	 * @return bool
	 * @since 20.0.0
	 */
	public function canChangeStream() {
		return false;
	}

	/**
	 * Left in for backwards compatibility
	 *
	 * @return bool
	 * @since 20.0.0
	 */
	public function isDefaultEnabledStream() {
		return true;
	}
}
