<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Paginate;

use Generator;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IDBConnection;
use OCP\Security\ISecureRandom;

/**
 * @psalm-type PaginateContext = array<string, string|int|bool|null>
 */
class PaginateCache {
	public const TTL = 60 * 60;
	private const string CACHE_COUNT_SUFFIX = 'count';
	private const string CACHE_CONTEXT_SUFFIX = 'context';

	private ICache $cache;

	public function __construct(
		private IDBConnection $database,
		private ISecureRandom $random,
		ICacheFactory $cacheFactory,
	) {
		$this->cache = $cacheFactory->createDistributed('pagination_');
	}

	/**
	 * @param string $uri
	 * @param \Iterator $items
	 * @param PaginateContext $context
	 * @return array{'token': string, 'count': int}
	 */
	public function store(string $uri, \Iterator $items, array $context): array {
		$token = $this->random->generate(32);
		$cacheKey = $this->buildCacheKey($uri, $token);
		$this->cache->set($cacheKey . self::CACHE_CONTEXT_SUFFIX, $context, self::TTL + 60);

		$count = 0;
		foreach ($items as $item) {
			// Add small margin to avoid fetching valid count and then expired entries
			$this->cache->set($cacheKey . $count, $item, self::TTL + 60);
			++$count;
		}
		$this->cache->set($cacheKey . self::CACHE_COUNT_SUFFIX, $count, self::TTL);

		return ['token' => $token, 'count' => $count];
	}

	/**
	 * @param PaginateContext $context
	 * @return Generator<mixed>
	 */
	public function get(string $uri, string $token, int $offset, int $count, array $context): Generator {
		$cacheKey = $this->buildCacheKey($uri, $token);
		if (!$this->matchesContext($cacheKey, $context)) {
			return [];
		}

		$nbItems = $this->cache->get($cacheKey . self::CACHE_COUNT_SUFFIX);
		if (!$nbItems || $offset > $nbItems) {
			return [];
		}

		$lastItem = min($nbItems, $offset + $count);
		for ($i = $offset; $i < $lastItem; ++$i) {
			yield $this->cache->get($cacheKey . $i);
		}
	}

	/**
	 * @param PaginateContext $context
	 */
	public function exists(string $uri, string $token, array $context): bool {
		$cacheKey = $this->buildCacheKey($uri, $token);
		return $this->matchesContext($cacheKey, $context)
			&& $this->cache->get($cacheKey . self::CACHE_COUNT_SUFFIX) > 0;
	}

	/**
	 * @param PaginateContext $context
	 */
	private function matchesContext(string $cacheKey, array $context): bool {
		return $this->cache->get($cacheKey . self::CACHE_CONTEXT_SUFFIX) === $context;
	}

	private function buildCacheKey(string $uri, string $token): string {
		return $token . '_' . crc32($uri) . '_';
	}
}
