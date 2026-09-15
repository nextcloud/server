<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Search;

use OC\Files\Search\SearchBinaryOperator;
use OC\Files\Search\SearchComparison;
use OC\Files\Search\SearchQuery;
use OC\Files\Storage\Temporary;
use OCP\Files\Cache\ICache;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\Search\ISearchBinaryOperator;
use OCP\Files\Search\ISearchComparison;
use OCP\Files\Search\ISearchOperator;
use OCP\Files\Storage\IStorage;
use OCP\IUser;
use OCP\Server;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group('DB')]
class SearchIntegrationTest extends TestCase {
	private ICache $cache;
	private IStorage $storage;
	private string $mountPoint;
	private IUserMountCache $mountCache;
	private IUser $user;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')
			->willReturn('user');
		$this->storage = new Temporary([]);
		$this->cache = $this->storage->getCache();
		$this->storage->getScanner()->scan('');
		$this->mountCache = Server::get(IUserMountCache::class);
		$this->mountPoint = '/user/files/search_test/';
		$this->mountCache->addMount($this->user, $this->mountPoint, $this->cache->get(''), 'dummy');
	}

	protected function tearDown(): void {
		$this->mountCache->removeMount($this->mountPoint);

		parent::tearDown();
	}

	public function testThousandAndOneFilters(): void {
		$id = $this->cache->put('file10', ['size' => 1, 'mtime' => 50, 'mimetype' => 'foo/folder']);

		$comparisons = [];
		for ($i = 1; $i <= 1001; $i++) {
			$comparisons[] = new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'name', "file$i");
		}
		$operator = new SearchBinaryOperator(ISearchBinaryOperator::OPERATOR_OR, $comparisons);
		$query = new SearchQuery($operator, 10, 0, []);

		$results = $this->cache->searchQuery($query);

		$this->assertCount(1, $results);
		$this->assertEquals($id, $results[0]->getId());
	}

	public static function searchMountNameProvider(): array {
		return [
			[new SearchComparison(ISearchComparison::COMPARE_LIKE, 'mount_point_name', '%search%'), ''],
			[new SearchComparison(ISearchComparison::COMPARE_EQUAL, 'mount_point_name', 'search_test'), ''],
			[new SearchComparison(ISearchComparison::COMPARE_LIKE, 'mount_point_name', '%search_test%'), ''],
			[new SearchComparison(ISearchComparison::COMPARE_LIKE, 'mount_point_name', '%files%'), null],
		];
	}

	#[DataProvider('searchMountNameProvider')]
	public function testSearchMountName(ISearchOperator $operator, ?string $resultPath): void {
		$query = new SearchQuery($operator, 10, 0, [], $this->user);

		$results = $this->cache->searchQuery($query);

		if (is_null($resultPath)) {
			$this->assertCount(0, $results);
		} else {
			$this->assertCount(1, $results);
			$this->assertEquals($this->cache->getId($resultPath), $results[0]->getId());
		}
	}
}
