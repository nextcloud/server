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
use OCP\IServerContainer;
use Psr\Log\LoggerInterface;

class ProviderManager implements IProviderManager {
	/** @var string[] */
	protected array $providers = [];

	/** @var IProvider[] */
	protected array $providerInstances = [];

	public function __construct(
		protected IServerContainer $serverContainer,
		protected LoggerInterface $logger,
	) {
	}

	public function getResourceProviders(): array {
		if ($this->providers !== []) {
			foreach ($this->providers as $provider) {
				try {
					$this->providerInstances[] = $this->serverContainer->query($provider);
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

	public function registerResourceProvider(string $provider): void {
		$this->providers[] = $provider;
	}
}
