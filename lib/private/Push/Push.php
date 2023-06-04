<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2023, Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Push;

use OCP\IUser;
use OCP\Push\IProvider;
use OCP\Push\IPush;
use OCP\Push\IPushNotification;

/**
 * @since 28.0.0
 */
class Push implements IPush {

	/** @var IProvider[] */
	private array $pushNotificationServices;

	public function __construct(private Manager $manager) {
		$this->pushNotificationServices = $this->manager->getPushNotifierServices();
	}

	public function createNotification(IUser $user, string $payload): IPushNotification {

	}


	public function queueNotification(IPushNotification $pushNotification): array {
		return array_map(function ($service) use ($pushNotification) {
			return $service->queueNotification($pushNotification);
		}, $this->pushNotificationServices);
	}

	public function queueNotifications(array $pushNotifications, \Closure $report): void {
		foreach ($this->pushNotificationServices as $service) {
			$service->queueNotifications($pushNotifications, $report);
		}
	}
}
