<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Files\Type;

use OC\Files\Type\Loader;
use OC\Memcache\ArrayCache;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IDBConnection;
use OCP\Server;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group('DB')]
class LoaderTest extends TestCase {
	protected IDBConnection $db;
	protected ICache $cache;
	protected Loader $loader;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->db = Server::get(IDBConnection::class);
		$this->cache = new ArrayCache();
		$this->loader = $this->createLoader($this->db);
	}

	#[\Override]
	protected function tearDown(): void {
		$deleteMimetypes = $this->db->getQueryBuilder();
		$deleteMimetypes->delete('mimetypes')
			->where($deleteMimetypes->expr()->like(
				'mimetype', $deleteMimetypes->createPositionalParameter('testing/%')
			));
		$deleteMimetypes->executeStatement();
		parent::tearDown();
	}

	private function createLoader(IDBConnection $db): Loader {
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->method('createLocal')->willReturn($this->cache);
		return new Loader($db, $cacheFactory);
	}

	private function insertMimetype(string $mimetype): int {
		$qb = $this->db->getQueryBuilder();
		$qb->insert('mimetypes')
			->values([
				'mimetype' => $qb->createPositionalParameter($mimetype)
			]);
		$qb->executeStatement();
		return $qb->getLastInsertId();
	}

	public function testGetMimetype(): void {
		$this->insertMimetype('testing/mymimetype');

		$this->assertTrue($this->loader->exists('testing/mymimetype'));
		$mimetypeId = $this->loader->getId('testing/mymimetype');
		$this->assertNotNull($mimetypeId);

		$mimetype = $this->loader->getMimetypeById($mimetypeId);
		$this->assertEquals('testing/mymimetype', $mimetype);
	}

	public function testGetNonexistentMimetype(): void {
		$this->assertFalse($this->loader->exists('testing/nonexistent'));
		// hopefully this ID doesn't exist
		$this->assertNull($this->loader->getMimetypeById(12345));
	}

	public function testStore(): void {
		$this->assertFalse($this->loader->exists('testing/mymimetype'));
		$mimetypeId = $this->loader->getId('testing/mymimetype');

		$qb = $this->db->getQueryBuilder();
		$qb->select('mimetype')
			->from('mimetypes')
			->where($qb->expr()->eq('id', $qb->createPositionalParameter($mimetypeId)));

		$result = $qb->executeQuery();
		$mimetype = $result->fetchAssociative();
		$result->closeCursor();
		$this->assertEquals('testing/mymimetype', $mimetype['mimetype']);

		$this->assertEquals('testing/mymimetype', $this->loader->getMimetypeById($mimetypeId));
		$this->assertEquals($mimetypeId, $this->loader->getId('testing/mymimetype'));
	}

	public function testStoreExists(): void {
		$mimetypeId = $this->loader->getId('testing/mymimetype');
		$mimetypeId2 = $this->loader->getId('testing/mymimetype');

		$this->assertEquals($mimetypeId, $mimetypeId2);
	}

	public function testMimetypesAreServedFromTheLocalCache(): void {
		$mimetypeId = $this->insertMimetype('testing/cached');
		$this->assertEquals('testing/cached', $this->loader->getMimetypeById($mimetypeId));

		$db = $this->createMock(IDBConnection::class);
		$db->expects($this->never())->method('getQueryBuilder');
		$loader = $this->createLoader($db);

		$this->assertEquals('testing/cached', $loader->getMimetypeById($mimetypeId));
		$this->assertEquals($mimetypeId, $loader->getId('testing/cached'));
		$this->assertTrue($loader->exists('testing/cached'));
	}

	public function testUnknownIdIsReloadedFromTheDatabase(): void {
		$this->assertTrue($this->loader->exists('httpd/unix-directory'));

		// added by another process after this instance loaded the table
		$mimetypeId = $this->insertMimetype('testing/late');

		$this->assertEquals('testing/late', $this->loader->getMimetypeById($mimetypeId));
		$this->assertEquals($mimetypeId, $this->createLoader($this->db)->getId('testing/late'));
		// only one reload per instance
		$this->assertNull($this->loader->getMimetypeById(12345));
	}

	public function testStoreAndResetInvalidateTheCache(): void {
		$this->assertTrue($this->loader->exists('httpd/unix-directory'));
		$this->assertTrue($this->cache->hasKey('mimetypes'));

		$this->loader->getId('testing/new');
		$this->assertFalse($this->cache->hasKey('mimetypes'));

		$this->assertTrue($this->createLoader($this->db)->exists('testing/new'));
		$this->assertTrue($this->cache->hasKey('mimetypes'));

		$this->loader->reset();
		$this->assertFalse($this->cache->hasKey('mimetypes'));
	}
}
