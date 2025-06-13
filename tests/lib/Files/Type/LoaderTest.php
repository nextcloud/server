<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Files\Type;

use OC\Files\Type\Loader;
use OCP\IDBConnection;
use OCP\Server;
use Test\TestCase;

class LoaderTest extends TestCase {
	protected IDBConnection $db;
	protected Loader $loader;

	protected function setUp(): void {
		$this->db = Server::get(IDBConnection::class);
		$this->loader = new Loader($this->db);
	}

	protected function tearDown(): void {
		$deleteMimetypes = $this->db->getQueryBuilder();
		$deleteMimetypes->delete('mimetypes')
			->where($deleteMimetypes->expr()->like(
				'mimetype', $deleteMimetypes->createPositionalParameter('testing/%')
			));
		$deleteMimetypes->execute();
	}


	public function testGetMimetype(): void {
		$qb = $this->db->getQueryBuilder();
		$qb->insert('mimetypes')
			->values([
				'mimetype' => $qb->createPositionalParameter('testing/mymimetype')
			]);
		$qb->execute();

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

		$result = $qb->execute();
		$mimetype = $result->fetch();
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
}
