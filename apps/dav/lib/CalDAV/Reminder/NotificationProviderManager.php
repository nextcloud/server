<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Reminder;

use OCA\DAV\CalDAV\Reminder\NotificationProvider\ProviderNotAvailableException;
use OCP\AppFramework\QueryException;

/**
 * Class NotificationProviderManager
 *
 * @package OCA\DAV\CalDAV\Reminder
 */
class NotificationProviderManager {

	/** @var INotificationProvider[] */
	private $providers = [];

	/**
	 * Checks whether a provider for a given ACTION exists
	 *
	 * @param string $type
	 * @return bool
	 */
	public function hasProvider(string $type):bool {
		return (\in_array($type, ReminderService::REMINDER_TYPES, true)
			&& isset($this->providers[$type]));
	}

	/**
	 * Get provider for a given ACTION
	 *
	 * @param string $type
	 * @return INotificationProvider
	 * @throws NotificationProvider\ProviderNotAvailableException
	 * @throws NotificationTypeDoesNotExistException
	 */
	public function getProvider(string $type):INotificationProvider {
		if (in_array($type, ReminderService::REMINDER_TYPES, true)) {
			if (isset($this->providers[$type])) {
				return $this->providers[$type];
			}
			throw new ProviderNotAvailableException($type);
		}
		throw new NotificationTypeDoesNotExistException($type);
	}

	/**
	 * Registers a new provider
	 *
	 * @param string $providerClassName
	 * @throws QueryException
	 */
	public function registerProvider(string $providerClassName):void {
		$provider = \OC::$server->query($providerClassName);

		if (!$provider instanceof INotificationProvider) {
			throw new \InvalidArgumentException('Invalid notification provider registered');
		}

		$this->providers[$provider::NOTIFICATION_TYPE] = $provider;
	}
}
