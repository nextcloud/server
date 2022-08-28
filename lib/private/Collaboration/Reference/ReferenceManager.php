<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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

namespace OC\Collaboration\Reference;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\Collaboration\Reference\File\FileReferenceProvider;
use OCP\Collaboration\Reference\IDiscoverableReferenceProvider;
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\IReferenceManager;
use OCP\Collaboration\Reference\IReferenceProvider;
use OCP\Collaboration\Reference\Reference;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUserSession;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class ReferenceManager implements IReferenceManager {
	public const CACHE_TTL = 3600;

	/** @var IReferenceProvider[]|null */
	private ?array $providers = null;
	private ICache $cache;

	public function __construct(
		private LinkReferenceProvider $linkReferenceProvider,
		ICacheFactory $cacheFactory,
		private Coordinator $coordinator,
		private ContainerInterface $container,
		private LoggerInterface $logger,
		private IConfig $config,
		private IUserSession $userSession,
	) {
		$this->cache = $cacheFactory->createDistributed('reference');
	}

	/**
	 * Extract a list of URLs from a text
	 *
	 * @return string[]
	 */
	public function extractReferences(string $text): array {
		preg_match_all(IURLGenerator::URL_REGEX, $text, $matches);
		$references = $matches[0] ?? [];
		return array_map(function ($reference) {
			return trim($reference);
		}, $references);
	}

	/**
	 * Try to get a cached reference object from a reference string
	 */
	public function getReferenceFromCache(string $referenceId): ?IReference {
		$matchedProvider = $this->getMatchedProvider($referenceId);

		if ($matchedProvider === null) {
			return null;
		}

		$cacheKey = $this->getFullCacheKey($matchedProvider, $referenceId);
		return $this->getReferenceByCacheKey($cacheKey);
	}

	/**
	 * Try to get a cached reference object from a full cache key
	 */
	public function getReferenceByCacheKey(string $cacheKey): ?IReference {
		$cached = $this->cache->get($cacheKey);
		if ($cached) {
			return Reference::fromCache($cached);
		}

		return null;
	}

	/**
	 * Get a reference object from a reference string with a matching provider
	 * Use a cached reference if possible
	 */
	public function resolveReference(string $referenceId): ?IReference {
		$matchedProvider = $this->getMatchedProvider($referenceId);

		if ($matchedProvider === null) {
			return null;
		}

		$cacheKey = $this->getFullCacheKey($matchedProvider, $referenceId);
		$cached = $this->cache->get($cacheKey);
		if ($cached) {
			return Reference::fromCache($cached);
		}

		$reference = $matchedProvider->resolveReference($referenceId);
		if ($reference) {
			$cachePrefix = $matchedProvider->getCachePrefix($referenceId);
			if ($cachePrefix !== '') {
				// If a prefix is used we set an additional key to know when we need to delete by prefix during invalidateCache()
				$this->cache->set('hasPrefix-' . md5($cachePrefix), true, self::CACHE_TTL);
			}
			$this->cache->set($cacheKey, Reference::toCache($reference), self::CACHE_TTL);
			return $reference;
		}

		return null;
	}

	/**
	 * Try to match a reference string with all the registered providers
	 * Fallback to the link reference provider (using OpenGraph)
	 *
	 * @return IReferenceProvider|null the first matching provider
	 */
	private function getMatchedProvider(string $referenceId): ?IReferenceProvider {
		$matchedProvider = null;
		foreach ($this->getProviders() as $provider) {
			$matchedProvider = $provider->matchReference($referenceId) ? $provider : null;
			if ($matchedProvider !== null) {
				break;
			}
		}

		if ($matchedProvider === null && $this->linkReferenceProvider->matchReference($referenceId)) {
			$matchedProvider = $this->linkReferenceProvider;
		}

		return $matchedProvider;
	}

	/**
	 * Get a hashed full cache key from a key and prefix given by a provider
	 */
	private function getFullCacheKey(IReferenceProvider $provider, string $referenceId): string {
		$cacheKey = $provider->getCacheKey($referenceId);
		return md5($provider->getCachePrefix($referenceId)) . (
			$cacheKey !== null ? ('-' . md5($cacheKey)) : ''
		);
	}

	/**
	 * Remove a specific cache entry from its key+prefix
	 */
	public function invalidateCache(string $cachePrefix, ?string $cacheKey = null): void {
		if ($cacheKey === null) {
			// clear might be a heavy operation, so we only do it if there have actually been keys set
			if ($this->cache->remove('hasPrefix-' . md5($cachePrefix))) {
				$this->cache->clear(md5($cachePrefix));
			}

			return;
		}

		$this->cache->remove(md5($cachePrefix) . '-' . md5($cacheKey));
	}

	/**
	 * @return IReferenceProvider[]
	 */
	public function getProviders(): array {
		if ($this->providers === null) {
			$context = $this->coordinator->getRegistrationContext();
			if ($context === null) {
				return [];
			}

			$this->providers = array_filter(array_map(function ($registration): ?IReferenceProvider {
				try {
					/** @var IReferenceProvider $provider */
					$provider = $this->container->get($registration->getService());
				} catch (Throwable $e) {
					$this->logger->error('Could not load reference provider ' . $registration->getService() . ': ' . $e->getMessage(), [
						'exception' => $e,
					]);
					return null;
				}

				return $provider;
			}, $context->getReferenceProviders()));

			$this->providers[] = $this->container->get(FileReferenceProvider::class);
		}

		return $this->providers;
	}

	/**
	 * @inheritDoc
	 */
	public function getDiscoverableProviders(): array {
		// preserve 0 based index to avoid returning an object in data responses
		return array_values(
			array_filter($this->getProviders(), static function (IReferenceProvider $provider) {
				return $provider instanceof IDiscoverableReferenceProvider;
			})
		);
	}

	/**
	 * @inheritDoc
	 */
	public function touchProvider(string $userId, string $providerId, ?int $timestamp = null): bool {
		$providers = $this->getDiscoverableProviders();
		$matchingProviders = array_filter($providers, static function (IDiscoverableReferenceProvider $provider) use ($providerId) {
			return $provider->getId() === $providerId;
		});
		if (!empty($matchingProviders)) {
			if ($timestamp === null) {
				$timestamp = time();
			}

			$configKey = 'provider-last-use_' . $providerId;
			$this->config->setUserValue($userId, 'references', $configKey, (string) $timestamp);
			return true;
		}
		return false;
	}

	/**
	 * @inheritDoc
	 */
	public function getUserProviderTimestamps(): array {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return [];
		}
		$userId = $user->getUID();
		$keys = $this->config->getUserKeys($userId, 'references');
		$prefix = 'provider-last-use_';
		$keys = array_filter($keys, static function (string $key) use ($prefix) {
			return str_starts_with($key, $prefix);
		});
		$timestamps = [];
		foreach ($keys as $key) {
			$providerId = substr($key, strlen($prefix));
			$timestamp = (int) $this->config->getUserValue($userId, 'references', $key);
			$timestamps[$providerId] = $timestamp;
		}
		return $timestamps;
	}
}
