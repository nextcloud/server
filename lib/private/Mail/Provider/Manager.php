<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Mail\Provider;

use OC\AppFramework\Bootstrap\Coordinator;
use OCP\Mail\Provider\IManager;
use OCP\Mail\Provider\IProvider;
use OCP\Mail\Provider\IService;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class Manager implements IManager {
	
	protected ?array $providersCollection = null;

	public function __construct(
		private Coordinator $coordinator,
		private ContainerInterface $container,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * Register a mail provider
	 *
	 * @since 30.0.0
	 *
	 */
	public function register(IProvider $value): void {

		// add provider to internal collection
		$this->providersCollection[$value->id()] = $value;
		
	}

	/**
	 * Determine if any mail providers are registered
	 *
	 * @since 30.0.0
	 *
	 * @return bool
	 */
	public function has(): bool {

		// return true if collection has any providers
		return !empty($this->providers());

	}

	/**
	 * Retrieve a count of how many mail providers are registered
	 *
	 * @since 30.0.0
	 *
	 * @return int
	 */
	public function count(): int {

		// return count of providers in collection
		return count($this->providers());

	}

	/**
	 * Retrieve which mail providers are registered
	 *
	 * @since 30.0.0
	 *
	 * @return array<string,String>
	 */
	public function types(): array {
		
		// construct types collection
		$types = [];
		// extract id and name from providers collection
		foreach ($this->providers() as $entry) {
			$types[$entry->id()] = $entry->label();
		}
		// return types collection
		return $types;
		
	}

	/**
	 * Retrieve all registered mail providers
	 *
	 * @since 30.0.0
	 *
	 * @return array<string,IProvider>
	 */
	public function providers(): array {

		// evaluate if we already have a cached collection of providers and return the collection if we do
		if (is_array($this->providersCollection)) {
			return $this->providersCollection;
		}
		// retrieve server registration context
		$context = $this->coordinator->getRegistrationContext();
		// evaluate if registration context was returned
		if ($context === null) {
			return [];
		}
		// initilize cached collection
		$this->providersCollection = [];
		// iterate through all registered mail providers
		foreach ($context->getMailProviders() as $entry) {
			try {
				/** @var IProvider $provider */
				// instance provider
				$provider = $this->container->get($entry->getService());
				// add provider to cache collection
				$this->providersCollection[$provider->id()] = $provider;
			} catch (Throwable $e) {
				$this->logger->error(
					'Could not load mail provider ' . $entry->getService() . ': ' . $e->getMessage(),
					['exception' => $e]
				);
			}
		}
		// return mail provider collection
		return $this->providersCollection;

	}

	/**
	 * Retrieve a provider with a specific id
	 *
	 * @since 30.0.0
	 *
	 * @return IProvider|null
	 */
	public function findProviderById(string $id): IProvider | null {

		// evaluate if we already have a cached collection of providers
		if (!is_array($this->providersCollection)) {
			$this->providers();
		}
		
		if (isset($this->providersCollection[$id])) {
			return $this->providersCollection[$id];
		}
		// return null if provider was not found
		return null;

	}

	/**
	 * Retrieve all services for all registered mail providers
	 *
	 * @since 30.0.0
	 *
	 * @param string $uid					user id
	 *
	 * @return array<string,array<string,IService>>		returns collection of service objects or null if non found
	 */
	public function services(string $uid): array {
		
		// initilize collection
		$services = [];
		// retrieve and iterate through mail providers
		foreach ($this->providers() as $entry) {
			// retrieve collection of services
			$mailServices = $entry->listServices($uid);
			// evaluate if mail services collection is not empty and add results to services collection
			if (!empty($mailServices)) {
				$services[$entry->id()] = $mailServices;
			}
		}
		// return collection
		return $services;
		
	}

	/**
	 * Retrieve a service with a specific id
	 *
	 * @since 30.0.0
	 *
	 * @param string $uid				user id
	 * @param string $sid				service id
	 * @param string $pid				provider id
	 *
	 * @return IService|null			returns service object or null if non found
	 */
	public function findServiceById(string $uid, string $sid, ?string $pid = null): IService | null {
		
		// evaluate if provider id was specified
		if ($pid !== null) {
			// find provider
			$provider = $this->findProviderById($pid);
			// evaluate if provider was found
			if ($provider instanceof IProvider) {
				// find service with specific id
				$service = $provider->findServiceById($uid, $sid);
				// evaluate if mail service was found
				if ($service instanceof IService) {
					return $service;
				}
			}
		} else {
			// retrieve and iterate through mail providers
			foreach ($this->providers() as $provider) {
				// find service with specific id
				$service = $provider->findServiceById($uid, $sid);
				// evaluate if mail service was found
				if ($service instanceof IService) {
					return $service;
				}
			}
		}
		
		// return null if no match was found
		return null;

	}

	/**
	 * Retrieve a service for a specific mail address
	 * returns first service with specific primary address
	 *
	 * @since 30.0.0
	 *
	 * @param string $uid				user id
	 * @param string $address			mail address (e.g. test@example.com)
	 * @param string $pid				provider id
	 *
	 * @return IService|null			returns service object or null if non found
	 */
	public function findServiceByAddress(string $uid, string $address, ?string $pid = null): IService | null {
		
		// evaluate if provider id was specified
		if ($pid !== null) {
			// find provider
			$provider = $this->findProviderById($pid);
			// evaluate if provider was found
			if ($provider instanceof IProvider) {
				// find service with specific mail address
				$service = $provider->findServiceByAddress($uid, $address);
				// evaluate if mail service was found
				if ($service instanceof IService) {
					return $service;
				}
			}
		} else {
			// retrieve and iterate through mail providers
			foreach ($this->providers() as $provider) {
				// find service with specific mail address
				$service = $provider->findServiceByAddress($uid, $address);
				// evaluate if mail service was found
				if ($service instanceof IService) {
					return $service;
				}
			}
		}
		// return null if no match was found
		return null;

	}
}
