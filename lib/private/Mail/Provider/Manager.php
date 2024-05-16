<?php
declare(strict_types=1);

/**
* @copyright Copyright (c) 2023 Sebastian Krupinski <krupinski01@gmail.com>
*
* @author Sebastian Krupinski <krupinski01@gmail.com>
*
* @license AGPL-3.0-or-later
*
* This program is free software: you can redistribute it and/or modify
* it under the terms of the GNU Affero General Public License as
* published by the Free Software Foundation, either version 3 of the
* License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU Affero General Public License for more details.
*
* You should have received a copy of the GNU Affero General Public License
* along with this program.  If not, see <http://www.gnu.org/licenses/>.
*
*/
namespace OC\Mail\Provider;

use OC\AppFramework\Bootstrap\Coordinator;
use OCP\IConfig;
use OCP\IServerContainer;
use OCP\Mail\Provider\IManager;
use OCP\Mail\Provider\IProvider;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class Manager implements IManager {
	
	public function __construct(
		private Coordinator $coordinator,
		private ContainerInterface $container,
		private LoggerInterface $logger,
	) {
	}

	public function register($provider): void {

		// add provider to internal collection
		$this->_providers[$provider->id()] = $provider;
		
	}

	public function has(): bool {

		// return true if collection has any providers
		return count($this->_providers) > 0;

	}

	public function count(): int {

		// return count of providers in collection
		return count($this->_providers);

	}

	public function types(): array {
		
		// construct types collection
		$types = [];
		// extract id and name from providers collection
		foreach ($this->_providers as $entry) {
			$types[$entry->id()] = $entry->name(); 
		}
		// return types collection
		return $types;
		
	}

	public function providers(): array {

		$context = $this->coordinator->getRegistrationContext();

		if ($context === null) {
			return [];
		}

		$providers = [];

		foreach ($context->getMailProviders() as $entry) {
			try {
				/** @var IMailProvider $provider */
				$providers[] = $this->container->get($entry->getService());
			} catch (Throwable $e) {
				$this->logger->error('Could not load mail provider ' . $entry->getService() . ': ' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		}

		return $providers;
	}

	public function services(string $uid): array {
		
		// initilize collection
		$services = [];
		// retrieve and iterate through mail providers
		foreach ($this->providers() as $entry) {
			// extract id and services from providers collection
			$services[$entry->id()] = $entry->listServices($uid);
		}
		// return collection
		return $services;
		
	}

	public function findService(string $uid, string $address) {
		
		// retrieve and iterate through mail providers
		foreach ($this->providers() as $provider) {
			// retrieve and iterate through mail services
			foreach ($provider->listServices($uid) as $service) {
				// evaluate if primary mail address matches
				if ($service->getPrimaryAddress() == $address) {
					return $service;
				}
			}
		}
		// return null if no match was found
		return null;

	}

	
}
