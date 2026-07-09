<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OneTimePassword\Events;

use OCP\EventDispatcher\Event;
use OCP\OneTimePassword\IOneTimePasswordProvider;

/**
 * @since 35.0.0
 */
class GetOneTimePasswordProvidersEvent extends Event {
	private array $providers = [];

	/**
	 * Create a new GetOneTimePasswordProvidersEvent
	 *
	 * @param string|null $filterProviderId if set, only providers with the given ID are returned
	 * @since 35.0.0
	 */
	public function __construct(
		private readonly ?string $filterProviderId = null,
	) {
		parent::__construct();
	}

	/**
	 * Get all providers collected during event propagation
	 *
	 * @return IOneTimePasswordProvider[] the OTP providers
	 * @since 35.0.0
	 */
	public function getProviders(): array {
		return $this->providers;
	}

	/**
	 * Add a provider to the list (filtering logic is handled inside the method)
	 *
	 * @param IOneTimePasswordProvider $provider the OTP provider
	 * @return void
	 * @since 35.0.0
	 */
	public function addProvider(IOneTimePasswordProvider $provider): void {
		if ($this->filterProviderId === null || $this->filterProviderId === $provider->getProviderId()) {
			$this->providers[] = $provider;
		}
	}

}
