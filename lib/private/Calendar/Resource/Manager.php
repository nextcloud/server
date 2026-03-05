<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Calendar\Resource;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\Calendar\ResourcesRoomsUpdater;
use OCP\AppFramework\QueryException;
use OCP\Calendar\Resource\IBackend;
use OCP\Calendar\Resource\IManager;
use OCP\IServerContainer;

class Manager implements IManager {
	private bool $bootstrapBackendsLoaded = false;

	/**
	 * @var string[] holds all registered resource backends
	 * @psalm-var class-string<IBackend>[]
	 */
	private array $backends = [];

	/** @var IBackend[] holds all backends that have been initialized already */
	private array $initializedBackends = [];

	public function __construct(
		private Coordinator $bootstrapCoordinator,
		private IServerContainer $server,
		private ResourcesRoomsUpdater $updater,
	) {
	}

	/**
	 * Registers a resource backend
	 *
	 * @since 14.0.0
	 */
	public function registerBackend(string $backendClass): void {
		$this->backends[$backendClass] = $backendClass;
	}

	/**
	 * Unregisters a resource backend
	 *
	 * @since 14.0.0
	 */
	public function unregisterBackend(string $backendClass): void {
		unset($this->backends[$backendClass], $this->initializedBackends[$backendClass]);
	}

	private function fetchBootstrapBackends(): void {
		if ($this->bootstrapBackendsLoaded) {
			return;
		}

		$context = $this->bootstrapCoordinator->getRegistrationContext();
		if ($context === null) {
			// Too soon
			return;
		}

		foreach ($context->getCalendarResourceBackendRegistrations() as $registration) {
			$this->backends[] = $registration->getService();
		}
	}

	/**
	 * @return IBackend[]
	 * @throws QueryException
	 * @since 14.0.0
	 */
	public function getBackends():array {
		$this->fetchBootstrapBackends();

		foreach ($this->backends as $backend) {
			if (isset($this->initializedBackends[$backend])) {
				continue;
			}

			$this->initializedBackends[$backend] = $this->server->query($backend);
		}

		return array_values($this->initializedBackends);
	}

	/**
	 * @param string $backendId
	 * @throws QueryException
	 */
	public function getBackend($backendId): ?IBackend {
		$backends = $this->getBackends();
		foreach ($backends as $backend) {
			if ($backend->getBackendIdentifier() === $backendId) {
				return $backend;
			}
		}

		return null;
	}

	/**
	 * removes all registered backend instances
	 *
	 * @since 14.0.0
	 */
	public function clear(): void {
		$this->backends = [];
		$this->initializedBackends = [];
	}

	public function update(): void {
		$this->updater->updateResources();
	}
}
