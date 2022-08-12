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

use OCP\Collaboration\Reference\IReference;
use OCP\Collaboration\Reference\IReferenceManager;
use OCP\Collaboration\Reference\IReferenceProvider;
use OCP\ICache;
use OCP\ICacheFactory;

class ReferenceManager implements IReferenceManager {
	public const URL_PATTERN = '/(\s|\n|^)(https?:\/\/)?((?:[-A-Z0-9+_]+\.)+[-A-Z]+(?:\/[-A-Z0-9+&@#%?=~_|!:,.;()]*)*)(\s|\n|$)/mi';

	/** @var IReferenceProvider[] */
	private array $providers = [];
	private ICache $cache;

	private LinkReferenceProvider $linkReferenceProvider;

	public function __construct(LinkReferenceProvider $linkReferenceProvider, FileReferenceProvider $fileReferenceProvider, ICacheFactory $cacheFactory) {
		$this->registerReferenceProvider($fileReferenceProvider);
		$this->linkReferenceProvider = $linkReferenceProvider;
		$this->cache = $cacheFactory->createDistributed('reference');
	}

	public function extractReferences(string $text): array {
		preg_match_all(self::URL_PATTERN, $text, $matches);
		$references = $matches[0] ?? [];
		return array_map(function ($reference) {
			return trim($reference);
		}, $references);
	}

	public function resolveReference(string $referenceId): ?IReference {
		$matchedProvider = null;
		foreach ($this->providers as $provider) {
			$matchedProvider = $provider->matchReference($referenceId) ? $provider : null;
		}

		if ($matchedProvider === null) {
			$matchedProvider = $this->linkReferenceProvider;
		}

		$cacheKey = md5(serialize([
			$matchedProvider->isGloballyCachable() ? 0 : $matchedProvider->getCacheKey($referenceId),
			$referenceId
		]));
		$cached = $this->cache->get($cacheKey);
		if ($cached) {
			return Reference::fromCache($cached);
		}

		$reference = $matchedProvider->resolveReference($referenceId);
		if ($reference) {
			$this->cache->set($cacheKey, Reference::toCache($reference), 60);
			return $reference;
		}

		return null;
	}

	public function registerReferenceProvider(IReferenceProvider $provider): void {
		$this->providers[] = $provider;
	}
}
