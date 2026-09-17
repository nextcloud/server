<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Search;

use NCU\Search\IAccountScopedSearchProvider;
use NCU\Search\IAccountScopedSearchProviderRegistry;
use OC\AppFramework\Bootstrap\Coordinator;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

final class AccountScopedSearchProviderRegistry implements IAccountScopedSearchProviderRegistry {
	/** @var array<non-empty-lowercase-string, IAccountScopedSearchProvider>|null */
	private ?array $providers = null;

	public function __construct(
		private readonly Coordinator $coordinator,
		private readonly ContainerInterface $container,
		private readonly LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function clear(): void {
		$this->providers = null;
	}

	#[\Override]
	public function getProviders(): array {
		return $this->load();
	}

	#[\Override]
	public function getProvider(string $id): ?IAccountScopedSearchProvider {
		return $this->load()[$id] ?? null;
	}

	/**
	 * @return array<non-empty-lowercase-string, IAccountScopedSearchProvider>
	 */
	private function load(): array {
		if ($this->providers !== null) {
			return $this->providers;
		}

		$context = $this->coordinator->getRegistrationContext();
		if ($context === null) {
			// Too early, nothing registered yet — not cached, so a later call once boot has
			// finished still sees the real list.
			return [];
		}

		$providers = [];
		foreach ($context->getAccountScopedSearchProviders() as $registration) {
			try {
				/** @var IAccountScopedSearchProvider $provider */
				$provider = $this->container->get($registration->getService());
			} catch (Throwable $e) {
				$this->logger->error('Could not load an account-scoped search provider', [
					'exception' => $e,
					'app' => $registration->getAppId(),
				]);

				continue;
			}
			$providers[$provider->getId()] = $provider;
		}

		$this->providers = $providers;

		return $providers;
	}
}
