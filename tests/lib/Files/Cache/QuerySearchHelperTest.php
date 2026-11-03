<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Josh Duffy <joshduff.jd@gmail.com>
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Cache;

use OC\DB\Connection;
use OC\DB\ConnectionAdapter;
use OC\DB\ConnectionFactory;
use OC\DB\FilesSearchSnapshot;
use OC\DB\QueryBuilder\Sharded\ShardDefinition;
use OC\Files\Cache\Cache;
use OC\Files\Cache\CacheDependencies;
use OC\Files\Cache\QuerySearchHelper;
use OC\Files\Cache\SearchBuilder;
use OC\Files\Cache\Wrapper\CacheJail;
use OC\Files\Search\FilesSearchQuery;
use OC\Files\Search\QueryOptimizer\QueryOptimizer;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchOrder;
use OC\Files\Search\SearchQuery;
use OC\Files\Storage\Temporary;
use OC\SystemConfig;
use OC\User\DisplayNameCache;
use OCP\DB\QueryBuilder\Sharded\IShardMapper;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Files\Cache\ICacheEntry;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOrder;
use OCP\FilesMetadata\IFilesMetadataManager;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Psr\Log\LoggerInterface;
use Test\TestCase;

#[Group('DB')]
class QuerySearchHelperTest extends TestCase {
	private SystemConfig $baseConfig;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->baseConfig = Server::get(SystemConfig::class);
	}

	/** @param list<string>|null $readKeys */
	private function helper(bool $enabled = true, ?array &$readKeys = null, ?IDBConnection $connection = null): QuerySearchHelper {
		$config = $this->createMock(SystemConfig::class);
		$config->method('getValue')
			->willReturnCallback(function (string $key, mixed $default = '') use ($enabled, &$readKeys): mixed {
				if ($readKeys !== null) {
					$readKeys[] = $key;
				}
				return match ($key) {
					'files_search_bounded_name' => $enabled,
					'files_search_bounded_name_limits' => [
						'statement_seconds' => 2,
						'lock_seconds' => 1,
						'total_seconds' => 3,
					],
					default => $this->baseConfig->getValue($key, $default),
				};
			});

		return new QuerySearchHelper(
			Server::get(IMimeTypeLoader::class),
			$connection ?? Server::get(IDBConnection::class),
			Server::get(SearchBuilder::class),
			Server::get(QueryOptimizer::class),
			Server::get(IGroupManager::class),
			Server::get(IFilesMetadataManager::class),
			new FilesSearchSnapshot($config, Server::get(ConnectionFactory::class)),
		);
	}

	private function cache(QuerySearchHelper $helper): Cache {
		$storage = new Temporary([]);
		return new Cache($storage, new CacheDependencies(
			Server::get(IMimeTypeLoader::class),
			Server::get(IDBConnection::class),
			Server::get(IEventDispatcher::class),
			$helper,
			$this->baseConfig,
			Server::get(LoggerInterface::class),
			Server::get(IFilesMetadataManager::class),
			Server::get(DisplayNameCache::class),
		));
	}

	private function boundedQuery(int $limit = 2, int $offset = 0): FilesSearchQuery {
		return new FilesSearchQuery(
			new SearchComparison(ISearchComparison::COMPARE_LIKE, 'name', '%needle%'),
			$limit,
			$offset,
			[
				new SearchOrder(ISearchOrder::DIRECTION_DESCENDING, 'mtime'),
				new SearchOrder(ISearchOrder::DIRECTION_DESCENDING, 'fileid'),
			],
		);
	}

	private function nativeQuery(int $limit = 2, int $offset = 0): SearchQuery {
		return new SearchQuery(
			new SearchComparison(ISearchComparison::COMPARE_LIKE, 'name', '%needle%'),
			$limit,
			$offset,
			[
				new SearchOrder(ISearchOrder::DIRECTION_DESCENDING, 'mtime'),
				new SearchOrder(ISearchOrder::DIRECTION_DESCENDING, 'fileid'),
			],
		);
	}

	/** @param list<ICacheEntry> $entries
	 * @return list<int>
	 */
	private function ids(array $entries): array {
		return array_map(static fn (ICacheEntry $entry): int => $entry->getId(), $entries);
	}

	/** @param list<ICacheEntry> $entries
	 * @return list<array<string, mixed>>
	 */
	private function payloads(array $entries): array {
		return array_map(static function (ICacheEntry $entry): array {
			self::assertTrue(method_exists($entry, 'getData'));
			/** @var array<string, mixed> $data */
			$data = $entry->getData();
			return $data;
		}, $entries);
	}

	public function testBoundedRootJailRetainsNativeResultOrderAndPaging(): void {
		$readKeys = [];
		$helper = $this->helper(true, $readKeys);
		$cache = $this->cache($helper);
		$cache->put('', ['size' => 0, 'mtime' => 200, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->put('files', ['size' => 0, 'mtime' => 200, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$ids = [
			$cache->put('files/needle-a.txt', ['size' => 1, 'mtime' => 100, 'mimetype' => 'text/plain']),
			$cache->put('files/needle-b.txt', ['size' => 1, 'mtime' => 100, 'mimetype' => 'text/plain']),
			$cache->put('files/needle-c.txt', ['size' => 1, 'mtime' => 100, 'mimetype' => 'text/plain']),
		];
		$jail = new CacheJail($cache, 'files');

		$bounded = $helper->searchInCaches($this->boundedQuery(), ['files' => $jail]);
		$native = $helper->searchInCaches($this->nativeQuery(), ['files' => $jail]);
		rsort($ids, SORT_NUMERIC);

		self::assertSame(array_slice($ids, 0, 2), $this->ids($bounded['files']));
		self::assertSame($this->ids($native['files']), $this->ids($bounded['files']));
		self::assertSame($this->payloads($native['files']), $this->payloads($bounded['files']));
		// These values are read only by FilesSearchSnapshot::run().  Their presence
		// proves the public helper admitted the bounded snapshot preflight instead
		// of merely comparing two native fallback calls.
		self::assertContains('files_search_bounded_name_limits', $readKeys);
		self::assertContains('dbtype', $readKeys);

		$boundedPage = $helper->searchInCaches($this->boundedQuery(2, 1), ['files' => $jail]);
		$nativePage = $helper->searchInCaches($this->nativeQuery(2, 1), ['files' => $jail]);
		self::assertSame(array_slice($ids, 1, 2), $this->ids($boundedPage['files']));
		self::assertSame($this->ids($nativePage['files']), $this->ids($boundedPage['files']));
		self::assertSame($this->payloads($nativePage['files']), $this->payloads($boundedPage['files']));
	}

	public function testWidenedCandidatesPreserveRootJailPayloads(): void {
		$readKeys = [];
		$helper = $this->helper(true, $readKeys);
		$cache = $this->cache($helper);
		$cache->put('', ['size' => 0, 'mtime' => 400, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->put('files', ['size' => 0, 'mtime' => 400, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->put('outside-1-needle.txt', ['size' => 1, 'mtime' => 300, 'mimetype' => 'text/plain']);
		$cache->put('outside-2-needle.txt', ['size' => 1, 'mtime' => 200, 'mimetype' => 'text/plain']);
		$inside = $cache->put('files/older-needle.txt', ['size' => 7, 'mtime' => 100, 'mimetype' => 'text/plain']);
		$jail = new CacheJail($cache, 'files');

		$bounded = $helper->searchInCaches($this->boundedQuery(1), ['files' => $jail]);
		$native = $helper->searchInCaches($this->nativeQuery(1), ['files' => $jail]);

		self::assertSame([$inside], $this->ids($bounded['files']));
		self::assertSame($this->payloads($native['files']), $this->payloads($bounded['files']));
		self::assertContains('files_search_bounded_name_limits', $readKeys);
	}

	public function testNoMatchRetainsNativeEmptyResult(): void {
		$helper = $this->helper();
		$cache = $this->cache($helper);
		$cache->put('', ['size' => 0, 'mtime' => 200, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->put('files', ['size' => 0, 'mtime' => 200, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->put('files/other.txt', ['size' => 1, 'mtime' => 100, 'mimetype' => 'text/plain']);
		$jail = new CacheJail($cache, 'files');

		$bounded = $helper->searchInCaches($this->boundedQuery(), ['files' => $jail]);
		$native = $helper->searchInCaches($this->nativeQuery(), ['files' => $jail]);

		self::assertSame([], $bounded['files']);
		self::assertSame($this->payloads($native['files']), $this->payloads($bounded['files']));
	}

	public function testNestedJailKeepsNativeScopeAndFallsBackToNativeSemantics(): void {
		$helper = $this->helper();
		$cache = $this->cache($helper);
		$cache->put('', ['size' => 0, 'mtime' => 200, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->put('files', ['size' => 0, 'mtime' => 200, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->put('files/private', ['size' => 0, 'mtime' => 200, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$inside = $cache->put('files/private/needle-inside.txt', ['size' => 1, 'mtime' => 100, 'mimetype' => 'text/plain']);
		$cache->put('files/needle-outside.txt', ['size' => 1, 'mtime' => 100, 'mimetype' => 'text/plain']);
		$jail = new CacheJail($cache, 'files/private');

		$bounded = $helper->searchInCaches($this->boundedQuery(), ['private' => $jail]);
		$native = $helper->searchInCaches($this->nativeQuery(), ['private' => $jail]);

		self::assertSame([$inside], $this->ids($bounded['private']));
		self::assertSame($this->ids($native['private']), $this->ids($bounded['private']));
		self::assertSame($this->payloads($native['private']), $this->payloads($bounded['private']));
	}

	public function testSelectedFieldsSkipSnapshotAndKeepNativePayload(): void {
		$readKeys = [];
		$helper = $this->helper(true, $readKeys);
		$cache = $this->cache($helper);
		$cache->put('', ['size' => 0, 'mtime' => 200, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->put('files', ['size' => 0, 'mtime' => 200, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$id = $cache->put('files/needle.txt', ['size' => 1, 'mtime' => 100, 'mimetype' => 'text/plain']);
		$jail = new CacheJail($cache, 'files');
		$bounded = new FilesSearchQuery(
			new SearchComparison(ISearchComparison::COMPARE_LIKE, 'name', '%needle%'),
			2,
			0,
			[
				new SearchOrder(ISearchOrder::DIRECTION_DESCENDING, 'mtime'),
				new SearchOrder(ISearchOrder::DIRECTION_DESCENDING, 'fileid'),
			],
			null,
			false,
			['metadata_etag'],
		);
		$native = new SearchQuery(
			$bounded->getSearchOperation(),
			$bounded->getLimit(),
			$bounded->getOffset(),
			$bounded->getOrder(),
			null,
			false,
			['metadata_etag'],
		);

		$boundedResults = $helper->searchInCaches($bounded, ['files' => $jail]);
		$nativeResults = $helper->searchInCaches($native, ['files' => $jail]);

		self::assertSame([$id], $this->ids($boundedResults['files']));
		self::assertSame($this->payloads($nativeResults['files']), $this->payloads($boundedResults['files']));
		self::assertSame([], $readKeys);
	}

	public function testDisabledSnapshotUsesNativeSearchSemantics(): void {
		$helper = $this->helper(false);
		$cache = $this->cache($helper);
		$cache->put('', ['size' => 0, 'mtime' => 200, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->put('files', ['size' => 0, 'mtime' => 200, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$id = $cache->put('files/needle.txt', ['size' => 1, 'mtime' => 100, 'mimetype' => 'text/plain']);
		$jail = new CacheJail($cache, 'files');

		$bounded = $helper->searchInCaches($this->boundedQuery(), ['files' => $jail]);
		$native = $helper->searchInCaches($this->nativeQuery(), ['files' => $jail]);

		self::assertSame([$id], $this->ids($bounded['files']));
		self::assertSame($this->ids($native['files']), $this->ids($bounded['files']));
		self::assertSame($this->payloads($native['files']), $this->payloads($bounded['files']));
	}

	public function testShardedConnectionSkipsSnapshotBeforeConfigAdmission(): void {
		$actual = Server::get(IDBConnection::class);
		self::assertInstanceOf(ConnectionAdapter::class, $actual);
		$mapper = $this->createMock(IShardMapper::class);
		$shard = new ShardDefinition('filecache', 'fileid', [], 'storage', $mapper, [], [], 0, 0);
		$inner = $this->createMock(Connection::class);
		$inner->method('getShardDefinition')->with('filecache')->willReturn($shard);
		$inner->method('getQueryBuilder')->willReturn($actual->getQueryBuilder());
		$connection = new ConnectionAdapter($inner);
		$readKeys = [];
		$helper = $this->helper(true, $readKeys, $connection);
		$cache = $this->cache($helper);
		$cache->put('', ['size' => 0, 'mtime' => 200, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$cache->put('files', ['size' => 0, 'mtime' => 200, 'mimetype' => ICacheEntry::DIRECTORY_MIMETYPE]);
		$id = $cache->put('files/needle.txt', ['size' => 1, 'mtime' => 100, 'mimetype' => 'text/plain']);
		$jail = new CacheJail($cache, 'files');

		$results = $helper->searchInCaches($this->boundedQuery(), ['files' => $jail]);

		self::assertSame([$id], $this->ids($results['files']));
		self::assertSame([], $readKeys);
	}
}
