<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\CalDAV\Reminder;

use OCA\DAV\CalDAV\Reminder\NotificationProvider\ProviderNotAvailableException;
use OCP\Server;
use Psr\Container\ContainerExceptionInterface;

/**
 * Class NotificationProviderManager
 *
 * @package OCA\DAV\CalDAV\Reminder
 */
class NotificationProviderManager {

	/** @var array<string, class-string<INotificationProvider>> */
	private array $providerClasses = [];

	/** @var array<string, INotificationProvider> */
	private array $providers = [];

	/**
	 * Checks whether a provider for a given ACTION exists
	 *
	 * @param string $type
	 * @return bool
	 */
	public function hasProvider(string $type):bool {
		return (\in_array($type, ReminderService::REMINDER_TYPES, true)
			&& isset($this->providerClasses[$type]));
	}

	/**
	 * Get provider for a given ACTION
	 *
	 * @param string $type
	 * @return INotificationProvider
	 * @throws NotificationProvider\ProviderNotAvailableException
	 * @throws NotificationTypeDoesNotExistException
	 * @throws ContainerExceptionInterface
	 */
	public function getProvider(string $type):INotificationProvider {
		if (in_array($type, ReminderService::REMINDER_TYPES, true)) {
			if (!isset($this->providerClasses[$type])) {
				throw new ProviderNotAvailableException($type);
			}
			if (!isset($this->providers[$type])) {
				$provider = Server::get($this->providerClasses[$type]);
				if (!$provider instanceof INotificationProvider) {
					throw new \InvalidArgumentException('Invalid notification provider registered');
				}
				$this->providers[$type] = $provider;
			}
			return $this->providers[$type];
		}
		throw new NotificationTypeDoesNotExistException($type);
	}

	/**
	 * Registers a new provider, it is only instantiated when it is used
	 *
	 * @param class-string<INotificationProvider> $providerClassName
	 */
	public function registerProvider(string $providerClassName):void {
		if (!is_subclass_of($providerClassName, INotificationProvider::class)) {
			throw new \InvalidArgumentException('Invalid notification provider registered');
		}

		$this->providerClasses[$providerClassName::NOTIFICATION_TYPE] = $providerClassName;
	}
}
