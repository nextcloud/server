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

namespace OCA\DAV\Push;


use OCP\Push\IManager;

/**
 * @since 28.0.0
 */
class PushTransportManager {
	private array $pushTransportProviders = [];

	public function __construct(IManager $pushManager) {
		$this->pushTransportProviders = array_filter($pushManager->getPushNotifierServices(), function ($pushNotifierService) {
			return $pushNotifierService instanceof IPushTransportProvider;
		});
	}

	/**
	 * @return IPushTransportProvider[]
	 */
	public function getPushTransportProviders(): array
	{
		return $this->pushTransportProviders;
	}

}
