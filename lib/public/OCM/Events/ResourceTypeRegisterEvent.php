<?php

declare(strict_types=1);
/*
 * @copyright Copyright (c) 2023 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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

namespace OCP\OCM\Events;

use OCP\EventDispatcher\Event;
use OCP\OCM\IOCMProvider;

/**
 * Use this event to register additional OCM resources before the API returns
 * them in the OCM provider list and capability
 *
 * @since 28.0.0
 */
class ResourceTypeRegisterEvent extends Event {
	/**
	 * @param IOCMProvider $provider
	 * @since 28.0.0
	 */
	public function __construct(
		protected IOCMProvider $provider,
	) {
		parent::__construct();
	}

	/**
	 * @param string $name
	 * @param list<string> $shareTypes List of supported share recipients, e.g. 'user', 'group', …
	 * @param array<string, string> $protocols List of supported protocols and their location,
	 * 	e.g. ['webdav' => '/remote.php/webdav/']
	 * @since 28.0.0
	 */
	public function registerResourceType(string $name, array $shareTypes, array $protocols): void {
		$resourceType = $this->provider->createNewResourceType();
		$resourceType->setName($name)
			->setShareTypes($shareTypes)
			->setProtocols($protocols);
		$this->provider->addResourceType($resourceType);
	}
}
