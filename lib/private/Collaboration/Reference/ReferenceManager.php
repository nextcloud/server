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
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\IReferenceManager;
use OCP\Collaboration\Reference\IReferenceProvider;
use OCP\Collaboration\Reference\Reference;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IURLGenerator;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class ReferenceManager implements IReferenceManager {
	public const CACHE_TTL = 3600;

	/** @var IReferenceProvider[]|null */
	private ?array $providers = null;
	private ICache $cache;
	private Coordinator $coordinator;
	private ContainerInterface $container;
	private LinkReferenceProvider $linkReferenceProvider;
	private LoggerInterface $logger;

	public function __construct(LinkReferenceProvider $linkReferenceProvider, ICacheFactory $cacheFactory, Coordinator $coordinator, ContainerInterface $container, LoggerInterface $logger) {
		$this->linkReferenceProvider = $linkReferenceProvider;
		$this->cache = $cacheFactory->createDistributed('reference');
		$this->coordinator = $coordinator;
		$this->container = $container;
		$this->logger = $logger;
	}

	public function extractReferences(string $text): array {
		preg_match_all(IURLGenerator::URL_REGEX, $text, $matches);
		$references = $matches[0] ?? [];
		return array_map(function ($reference) {
			return trim($reference);
		}, $references);
	}

	public function getReferenceFromCache(string $referenceId): ?IReference {
		$matchedProvider = $this->getMatchedProvider($referenceId);

		if ($matchedProvider === null) {
			return null;
		}

		$cacheKey = $this->getFullCacheKey($matchedProvider, $referenceId);
		return $this->getReferenceByCacheKey($cacheKey);
	}

	public function getReferenceByCacheKey(string $cacheKey): ?IReference {
		$cached = $this->cache->get($cacheKey);
		if ($cached) {
			return Reference::fromCache($cached);
		}

		return null;
	}

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
			$this->cache->set($cacheKey, Reference::toCache($reference), self::CACHE_TTL);
			return $reference;
		}

		return null;
	}

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

	private function getFullCacheKey(IReferenceProvider $provider, string $referenceId): string {
		$cacheKey = $provider->getCacheKey($referenceId);
		return md5($provider->getCachePrefix($referenceId)) . (
			$cacheKey !== null ? ('-' . md5($cacheKey)) : ''
		);
	}

	public function invalidateCache(string $cachePrefix, ?string $cacheKey = null): void {
		if ($cacheKey === null) {
			$this->cache->clear(md5($cachePrefix));
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
}
