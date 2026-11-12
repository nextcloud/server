<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Paginate;

use OC\Memcache\ArrayCache;
use OCA\DAV\Paginate\PaginateCache;
use OCP\ICacheFactory;
use OCP\IDBConnection;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class PaginateCacheTest extends TestCase {
	private const CONTEXT = ['principal' => 'principals/users/alice', 'minimal' => false];

	private PaginateCache $paginateCache;

	protected function setUp(): void {
		parent::setUp();

		$random = $this->createMock(ISecureRandom::class);
		$random->method('generate')
			->willReturn('token');

		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createDistributed')
			->willReturn(new ArrayCache('pagination_'));

		$this->paginateCache = new PaginateCache(
			$this->createMock(IDBConnection::class),
			$random,
			$cacheFactory,
		);
	}

	public function testStoreAndGetWithSameContext(): void {
		$result = $this->paginateCache->store('url', new \ArrayIterator(['a', 'b', 'c']), self::CONTEXT);

		self::assertSame(['token' => 'token', 'count' => 3], $result);
		self::assertTrue($this->paginateCache->exists('url', 'token', self::CONTEXT));
		self::assertSame(['b', 'c'], iterator_to_array($this->paginateCache->get('url', 'token', 1, 5, self::CONTEXT), false));
	}

	public static function dataDifferentContextDoesNotUseCache(): array {
		return [
			'other principal' => [['principal' => 'principals/users/bob', 'minimal' => false]],
			'no principal' => [['principal' => null, 'minimal' => false]],
			'other preference' => [['principal' => 'principals/users/alice', 'minimal' => true]],
			'empty context' => [[]],
		];
	}

	#[DataProvider(methodName: 'dataDifferentContextDoesNotUseCache')]
	public function testDifferentContextDoesNotUseCache(array $context): void {
		$this->paginateCache->store('url', new \ArrayIterator(['a', 'b']), self::CONTEXT);

		self::assertFalse($this->paginateCache->exists('url', 'token', $context));
		self::assertSame([], iterator_to_array($this->paginateCache->get('url', 'token', 0, 5, $context), false));
	}

	public function testDifferentUrlCannotAccess(): void {
		$this->paginateCache->store('url', new \ArrayIterator(['a', 'b']), self::CONTEXT);

		self::assertFalse($this->paginateCache->exists('other-url', 'token', self::CONTEXT));
	}
}
