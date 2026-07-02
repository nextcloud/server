<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\OneTimePassword\Events;

use OCP\EventDispatcher\Event;
use OCP\OneTimePassword\IOneTimePasswordProvider;

class GetOneTimePasswordProvidersEvent extends Event {
	private array $providers = [];

	public function __construct(
		private readonly ?string $filterProviderId = null,
	) {
		parent::__construct();
	}

	/**
	 * @return IOneTimePasswordProvider[]
	 */
	public function getProviders(): array {
		return $this->providers;
	}

	/**
	 * @param IOneTimePasswordProvider $provider
	 * @return void
	 */
	public function addProvider(IOneTimePasswordProvider $provider): void {
		if ($this->filterProviderId === null || $this->filterProviderId === $provider->getProviderId()) {
			$this->providers[] = $provider;
		}
	}

}
