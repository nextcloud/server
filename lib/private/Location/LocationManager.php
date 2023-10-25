<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023, Thomas Citharel
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
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
 */


namespace OC\Location;

use OC\AppFramework\Bootstrap\Coordinator;
use OCP\Location\CouldNotGeocodeException;
use OCP\Location\CouldNotSearchLocationException;
use OCP\Location\ILocationAutocompleteProvider;
use OCP\Location\ILocationManager;
use OCP\Location\ILocationProvider;
use OCP\PreConditionNotMetException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Throwable;

class LocationManager implements ILocationManager {
	/** @var ?ILocationProvider[] */
	private ?array $providers = null;

	public function __construct(
		private ContainerInterface $serverContainer,
		private Coordinator $coordinator,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @throws PreConditionNotMetException
	 */
	public function geocode(float $longitude, float $latitude, array $options = []): array {
		if (!$this->hasProviders()) {
			throw new PreConditionNotMetException('No location providers available');
		}

		foreach ($this->getProviders() as $provider) {
			try {
				return $provider->geocode($longitude, $latitude, $options);
			} catch (RuntimeException $e) {
				$this->logger->warning("Failed to geocode coords {$longitude}:${latitude} using provider {$provider->getName()}", ['exception' => $e]);
			}
		}

		throw new CouldNotGeocodeException($longitude, $latitude);
	}

	/**
	 * @throws PreConditionNotMetException
	 */
	public function search(string $address, array $options = []): array {
		if (!$this->hasProviders()) {
			throw new PreConditionNotMetException('No location providers available');
		}

		foreach ($this->getProviders() as $provider) {
			try {
				return $provider->search($address, $options);
			} catch (RuntimeException $e) {
				$this->logger->warning("Failed to search for location {$address} using provider {$provider->getName()}", ['exception' => $e]);
			}
		}

		throw new CouldNotSearchLocationException($address);
	}

	/**
	 * @throws PreConditionNotMetException
	 */
	public function autocomplete(string $address, array $options = []): array {
		if (!$this->canAutocomplete()) {
			throw new PreConditionNotMetException('No location autocomplete providers available');
		}

		foreach ($this->getProviders() as $provider) {
			try {
				if ($provider instanceof ILocationAutocompleteProvider) {
					return $provider->autocomplete($address, $options);
				}
			} catch (RuntimeException $e) {
				$this->logger->warning("Failed to autocomplete location {$address} using provider {$provider->getName()}", ['exception' => $e]);
			}
		}

		throw new CouldNotSearchLocationException($address);
	}

	public function getProviders(): array {
		$context = $this->coordinator->getRegistrationContext();

		if ($this->providers !== null) {
			return $this->providers;
		}

		$this->providers = [];
		foreach ($context->getLocationProviders() as $providerRegistration) {
			$class = $providerRegistration->getService();
			try {
				$this->providers[$class] = $this->serverContainer->get($class);
			} catch (NotFoundExceptionInterface|ContainerExceptionInterface|Throwable $e) {
				$this->logger->error('Failed to load location provider ' . $class, [
					'exception' => $e
				]);
			}
		}

		return $this->providers;
	}

	public function hasProviders(): bool {
		$context = $this->coordinator->getRegistrationContext();
		return !empty($context->getLocationProviders());
	}

	public function canAutocomplete(): bool {
		foreach ($this->getProviders() as $provider) {
			if ($provider instanceof ILocationAutocompleteProvider) {
				return true;
			}
		}
		return false;
	}
}
