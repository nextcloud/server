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
	public const REF_PATTERN = '/#[A-z0-9_]+\/[A-z0-9_]+/i';

	/** @var IReferenceProvider[] */
	private array $providers = [];
	private ICache $cache;

	public function __construct(LinkReferenceProvider $linkReferenceProvider, FileReferenceProvider $fileReferenceProvider, ICacheFactory $cacheFactory) {
		$this->registerReferenceProvider($fileReferenceProvider);
		$this->registerReferenceProvider($linkReferenceProvider);
		$this->cache = $cacheFactory->createDistributed('reference');
	}

	public function extractReferences(string $text): array {
		$matches = [];
		preg_match_all(self::REF_PATTERN, $text, $matches);
		$references = $matches[0] ?? [];

		preg_match_all(self::URL_PATTERN, $text, $matches);
		$references = array_merge($references, $matches[0] ?? []);
		return array_map(function ($reference) {
			return trim($reference);
		}, $references);
	}

	public function resolveReference(string $referenceId): ?IReference {
		$cached = $this->cache->get($referenceId);
		if ($cached) {
			// TODO: Figure out caching for references that depend on the viewer user
			return Reference::fromCache($cached);
		}
		foreach ($this->providers as $provider) {
			$reference = $provider->resolveReference($referenceId);
			if ($reference) {
				$this->cache->set($referenceId, Reference::toCache($reference), 60);
				return $reference;
			}
		}

		return null;
	}

	public function registerReferenceProvider(IReferenceProvider $provider): void {
		$this->providers[] = $provider;
	}
}
