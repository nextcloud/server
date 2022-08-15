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
use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\IReferenceManager;
use OCP\Collaboration\Reference\IReferenceProvider;
use OCP\ICache;
use OCP\ICacheFactory;
use Psr\Container\ContainerInterface;
use Throwable;
use function OCP\Log\logger;

class ReferenceManager implements IReferenceManager {
	public const URL_PATTERN = '/(\s|\n|^)(https?:\/\/)?((?:[-A-Z0-9+_]+\.)+[-A-Z]+(?:\/[-A-Z0-9+&@#%?=~_|!:,.;()]*)*)(\s|\n|$)/mi';
	public const CACHE_TTL = 60;

	/** @var IReferenceProvider[]|null */
	private ?array $providers = null;
	private ICache $cache;
	private Coordinator $coordinator;
	private ContainerInterface $container;

	public function __construct(LinkReferenceProvider $linkReferenceProvider, ICacheFactory $cacheFactory, Coordinator $coordinator, ContainerInterface $container) {
		$this->linkReferenceProvider = $linkReferenceProvider;
		$this->cache = $cacheFactory->createDistributed('reference');
		$this->coordinator = $coordinator;
		$this->container = $container;
	}

	public function extractReferences(string $text): array {
		preg_match_all(self::URL_PATTERN, $text, $matches);
		$references = $matches[0] ?? [];
		return array_map(function ($reference) {
			return trim($reference);
		}, $references);
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

		$cacheKey = $this->getCacheKey($matchedProvider, $referenceId);
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
		}

		if ($matchedProvider === null && $this->linkReferenceProvider->matchReference($referenceId)) {
			$matchedProvider = $this->linkReferenceProvider;
		}

		return $matchedProvider;
	}

	private function getCacheKey(IReferenceProvider $provider, string $referenceId): string {
		return md5($referenceId) . (
			$provider->isGloballyCacheable()
				? ''
				: '-' . md5($provider->getCacheKey($referenceId))
		);
	}

	public function invalidateCache(string $referenceId, ?string $providerCacheKey = null): void {
		$matchedProvider = $this->getMatchedProvider($referenceId);

		if ($matchedProvider === null) {
			return;
		}

		if ($providerCacheKey === null) {
			$this->cache->clear(md5($referenceId));
			return;
		}

		$this->cache->remove($this->getCacheKey($matchedProvider, $referenceId));
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
					logger()->error('Could not load reference provider ' . $registration->getService() . ': ' . $e->getMessage(), [
						'exception' => $e,
					]);
					return null;
				}

				return $provider;
			}, $context->getReferenceProviders()));

			// TODO: Move to files app
			$this->providers[] = $this->container->get(FileReferenceProvider::class);
		}

		return $this->providers;
	}
}
