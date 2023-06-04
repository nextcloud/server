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

use OC\AppFramework\Bootstrap\Coordinator;
use OCP\Push\IManager;
use OCP\Push\IProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * @since 28.0.0
 */
class Manager implements IManager {

	public function __construct(private LoggerInterface $logger, private Coordinator $coordinator) { }

	/**
	 * @return IProvider[]
	 */
	public function getPushNotifierServices(): array {
		$notifierServices = [];

		$notifierServiceRegistrations = $this->coordinator->getRegistrationContext()->getPushNotifierServices();

		if (empty($notifierServiceRegistrations)) {
			return $notifierServices;
		}

		foreach ($notifierServiceRegistrations as $notifierServiceRegistration) {
			$serviceClass = $notifierServiceRegistration->getService();
			try {
				$notifierService = \OC::$server->get($serviceClass);
			} catch (ContainerExceptionInterface $e) {
				$this->logger->error('Failed to load push notification service class: ' . $serviceClass, [
					'exception' => $e,
					'app' => 'notifications',
				]);
				continue;
			}

			if (!($notifierService instanceof IProvider)) {
				$this->logger->error('Push notification notifier class ' . $serviceClass . ' is not implementing ' . IProvider::class, [
					'app' => 'notifications',
				]);
				continue;
			}

			$notifierServices[] = $notifierService;
		}

		return $notifierServices;
	}
}
