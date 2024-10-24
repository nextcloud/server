<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	 *                                         e.g. ['webdav' => '/remote.php/webdav/']
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
