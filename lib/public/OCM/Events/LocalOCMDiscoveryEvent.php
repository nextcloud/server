<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OCM\Events;

use OCP\AppFramework\Attribute\Listenable;
use OCP\EventDispatcher\Event;
use OCP\OCM\IOCMProvider;

/**
 * Use this event to register additional resources before the API returns
 * them in the OCM provider list and capability
 *
 * @since 33.0.0
 *
 */
#[Listenable(since: '33.0.0')]
class LocalOCMDiscoveryEvent extends Event {
	/**
	 * @param IOCMProvider $provider
	 * @since 33.0.0
	 */
	public function __construct(
		private readonly IOCMProvider $provider,
	) {
		parent::__construct();
	}

	/**
	 * Add a new OCM capability to the discovery data of local instance
	 *
	 * @since 33.0.0
	 */
	public function addCapability(string $capability): void {
		$this->provider->setCapabilities([$capability]);
	}

	/**
	 * @param string $name
	 * @param list<string> $shareTypes List of supported share recipients, e.g. 'user', 'group', …
	 * @param array<string, string> $protocols List of supported protocols and their location,
	 *                                         e.g. ['webdav' => '/remote.php/webdav/']
	 * @since 33.0.0
	 */
	public function registerResourceType(string $name, array $shareTypes, array $protocols): void {
		$resourceType = $this->provider->createNewResourceType();
		$resourceType->setName($name)
			->setShareTypes($shareTypes)
			->setProtocols($protocols);
		$this->provider->addResourceType($resourceType);
	}
}
