<?php

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OC\Governance;

use NCU\Governance\GovernanceUnavailableException;
use NCU\Governance\IGovernanceBackend;
use NCU\Governance\IGovernanceLabelManager;
use NCU\Governance\IGovernanceManager;
use NCU\Governance\IGovernanceRegistry;
use NCU\Governance\Resource\IGovernanceResourceManager;
use OC\AppFramework\Bootstrap\Coordinator;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class GovernanceManager implements IGovernanceManager {
	private ?bool $isAvailable = null;

	private ?IGovernanceBackend $backend = null;

	public function __construct(
		private Coordinator $coordinator,
		private GovernanceResourceProviderRegistry $serviceRegistry,
		private ContainerInterface $container,
		private LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function isAvailable(): bool {
		if ($this->isAvailable !== null) {
			return $this->isAvailable;
		}

		if ($this->coordinator->getRegistrationContext() === null) {
			// Backend requested too soon, e.g. from the bootstrap `register` method of an app
			throw new RuntimeException('Not all apps have been registered yet');
		}

		$service = $this->serviceRegistry->getBackendClass();
		if ($service === null) {
			return $this->isAvailable = false;
		}

		try {
			$this->backend = $this->container->get($service);

			// Remember and return
			return $this->isAvailable = true;
		} catch (Throwable $e) {
			$this->logger->error('Governance backend {class} could not be loaded: ' . $e->getMessage(), [
				'class' => $service,
				'exception' => $e,
			]);

			// Temporary result. Maybe the next time the backend is requested it can be loaded.
			return false;
		}
	}

	/**
	 * @throws GovernanceUnavailableException when no backend is registered, or it failed to load
	 */
	private function backend(): IGovernanceBackend {
		if (!$this->isAvailable() || $this->backend === null) {
			throw new GovernanceUnavailableException('No governance backend is registered');
		}

		return $this->backend;
	}

	#[\Override]
	public function getRegistry(): IGovernanceRegistry {
		return $this->backend()->getRegistry();
	}

	#[\Override]
	public function getLabelManager(): IGovernanceLabelManager {
		return $this->backend()->getLabelManager();
	}

	#[\Override]
	public function getResourceManager(): IGovernanceResourceManager {
		return $this->backend()->getResourceManager();
	}
}
