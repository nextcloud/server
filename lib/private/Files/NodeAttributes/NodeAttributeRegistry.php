<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Files\NodeAttributes;

use NCU\Files\NodeAttributes\INodeAttributeProvider;
use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\NodeAttributeProviderRegistration;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Maps the registered attributes to their providers, loading providers only when needed
 */
class NodeAttributeRegistry {
	/** @var array<string, NodeAttributeProviderRegistration>|null registration by attribute in Clark notation */
	private ?array $registrations = null;

	/** @var array<class-string, INodeAttributeProvider|null> */
	private array $providers = [];

	public function __construct(
		private readonly Coordinator $coordinator,
		private readonly ContainerInterface $container,
		private readonly LoggerInterface $logger,
	) {
	}

	/**
	 * @return list<string> the registered attributes in Clark notation
	 */
	public function getAttributeNames(): array {
		return array_keys($this->getRegistrations());
	}

	/**
	 * Group the requested attributes by the provider serving them, unknown attributes are skipped
	 *
	 * @param list<string> $attributes in Clark notation
	 * @return list<array{provider: INodeAttributeProvider, attributes: list<string>}>
	 */
	public function getProvidersFor(array $attributes): array {
		$registrations = $this->getRegistrations();
		$groups = [];
		foreach (array_unique($attributes) as $attribute) {
			$registration = $registrations[$attribute] ?? null;
			if ($registration === null) {
				continue;
			}

			$service = $registration->getService();
			$provider = $this->getProvider($service);
			if ($provider === null) {
				continue;
			}

			$groups[$service] ??= ['provider' => $provider, 'attributes' => []];
			$groups[$service]['attributes'][] = $attribute;
		}
		return array_values($groups);
	}

	/**
	 * Get the local name of an attribute in Clark notation, e.g. `has-preview` for `{http://nextcloud.org/ns}has-preview`
	 */
	public static function getLocalName(string $attribute): string {
		$position = strrpos($attribute, '}');
		return $position === false ? $attribute : substr($attribute, $position + 1);
	}

	/**
	 * @return array<string, NodeAttributeProviderRegistration>
	 */
	private function getRegistrations(): array {
		if ($this->registrations !== null) {
			return $this->registrations;
		}

		$context = $this->coordinator->getRegistrationContext();
		if ($context === null) {
			// Too early, apps are not registered yet: do not cache the empty result
			return [];
		}

		$this->registrations = [];
		$localNames = [];
		foreach ($context->getNodeAttributeProviders() as $registration) {
			foreach ($registration->getAttributes() as $attribute) {
				$localName = self::getLocalName($attribute);
				// Attributes are keyed by local name in the serialized nodes, so they must be unique
				if (isset($localNames[$localName])) {
					$this->logger->error('Node attribute ' . $attribute . ' of ' . $registration->getService() . ' is ignored, ' . $localNames[$localName] . ' already uses the name ' . $localName, [
						'app' => $registration->getAppId(),
					]);
					continue;
				}

				$localNames[$localName] = $attribute;
				$this->registrations[$attribute] = $registration;
			}
		}
		return $this->registrations;
	}

	/**
	 * @param class-string $service
	 */
	private function getProvider(string $service): ?INodeAttributeProvider {
		if (!array_key_exists($service, $this->providers)) {
			try {
				$provider = $this->container->get($service);
				$this->providers[$service] = $provider instanceof INodeAttributeProvider ? $provider : null;
				if ($this->providers[$service] === null) {
					$this->logger->error($service . ' is registered as node attribute provider but does not implement ' . INodeAttributeProvider::class);
				}
			} catch (Throwable $e) {
				$this->logger->error('Could not load node attribute provider ' . $service . ': ' . $e->getMessage(), [
					'exception' => $e,
				]);
				$this->providers[$service] = null;
			}
		}
		return $this->providers[$service];
	}
}
