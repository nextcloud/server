<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Collaboration\Resources;

use OCP\AppFramework\QueryException;
use OCP\Collaboration\Resources\IProvider;
use OCP\Collaboration\Resources\IProviderManager;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class ProviderManager implements IProviderManager {
	/** @var string[] */
	protected array $providers = [];

	/** @var IProvider[] */
	protected array $providerInstances = [];

	public function __construct(
		protected ContainerInterface $serverContainer,
		protected LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function getResourceProviders(): array {
		if ($this->providers !== []) {
			foreach ($this->providers as $provider) {
				try {
					$this->providerInstances[] = $this->serverContainer->get($provider);
				} catch (QueryException $e) {
					$this->logger->error("Could not query resource provider $provider: " . $e->getMessage(), [
						'exception' => $e,
					]);
				}
			}
			$this->providers = [];
		}

		return $this->providerInstances;
	}

	#[\Override]
	public function registerResourceProvider(string $provider): void {
		$this->providers[] = $provider;
	}
}
