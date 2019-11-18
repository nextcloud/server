<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2019, Thomas Citharel
 * @copyright Copyright (c) 2019, Georg Ehrke
 *
 * @author Thomas Citharel <tcit@tcit.fr>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\CalDAV\Reminder;

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
			throw new NotificationProvider\ProviderNotAvailableException($type);
		}
		throw new NotificationTypeDoesNotExistException($type);
	}

	/**
	 * Registers a new provider
	 *
	 * @param string $providerClassName
	 * @throws \OCP\AppFramework\QueryException
	 */
	public function registerProvider(string $providerClassName):void {
		$provider = \OC::$server->query($providerClassName);

		if (!$provider instanceof INotificationProvider) {
			throw new \InvalidArgumentException('Invalid notification provider registered');
		}

		$this->providers[$provider::NOTIFICATION_TYPE] = $provider;
	}
}
