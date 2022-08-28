<?php

declare(strict_types=1);

/**
 * @copyright 2018, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
namespace OC\Calendar\Resource;

use OC\AppFramework\Bootstrap\Coordinator;
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
	 * @throws \OCP\AppFramework\QueryException
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
	 * @throws \OCP\AppFramework\QueryException
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
}
