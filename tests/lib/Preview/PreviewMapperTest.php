<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH
 * SPDX-FileContributor: Carl Schwan
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Preview\Db\Preview;
use OC\Preview\Db\PreviewMapper;
use OCP\IDBConnection;
use OCP\Server;
use OCP\Snowflake\IGenerator;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group('DB')]
class PreviewMapperTest extends TestCase {
	private PreviewMapper $previewMapper;
	private IDBConnection $connection;
	private IGenerator $snowflake;

	public function setUp(): void {
		parent::setUp();
		$this->previewMapper = Server::get(PreviewMapper::class);
		$this->connection = Server::get(IDBConnection::class);
		$this->snowflake = Server::get(IGenerator::class);

		$qb = $this->connection->getQueryBuilder();
		$qb->delete('preview_locations')->executeStatement();

		$qb = $this->connection->getQueryBuilder();
		$qb->delete('preview_versions')->executeStatement();

		$qb = $this->connection->getQueryBuilder();
		$qb->delete('previews')->executeStatement();
	}

	public function tearDown(): void {
		$this->previewMapper->deleteAll();
		parent::tearDown();
	}

	public function testGetAvailablePreviews(): void {
		// Empty
		$this->assertEquals([], $this->previewMapper->getAvailablePreviews([]));

		// No preview available
		$this->assertEquals([42 => []], $this->previewMapper->getAvailablePreviews([42]));

		$this->createPreviewForFileId(42);
		$previews = $this->previewMapper->getAvailablePreviews([42]);
		$this->assertNotEmpty($previews[42]);
		$this->assertNull($previews[42][0]->getLocationId());
		$this->assertNull($previews[42][0]->getBucketName());
		$this->assertNull($previews[42][0]->getObjectStoreName());

		$this->createPreviewForFileId(43, 2);
		$previews = $this->previewMapper->getAvailablePreviews([43]);
		$this->assertNotEmpty($previews[43]);
		$this->assertEquals('preview-2', $previews[43][0]->getBucketName());
		$this->assertEquals('default', $previews[43][0]->getObjectStoreName());
	}

	private function createPreviewForFileId(int $fileId, ?int $bucket = null): void {
		$locationId = null;
		if ($bucket) {
			$qb = $this->connection->getQueryBuilder();
			$locationId = $this->snowflake->nextId();
			$qb->insert('preview_locations')
				->values([
					'id' => $locationId,
					'bucket_name' => $qb->createNamedParameter('preview-' . $bucket),
					'object_store_name' => $qb->createNamedParameter('default'),
				]);
			$qb->executeStatement();
		}
		$preview = new Preview();
		$preview->setId($this->snowflake->nextId());
		$preview->setFileId($fileId);
		$preview->setStorageId(1);
		$preview->setCropped(true);
		$preview->setMax(true);
		$preview->setWidth(100);
		$preview->setSourceMimeType('image/jpeg');
		$preview->setHeight(100);
		$preview->setSize(100);
		$preview->setMtime(time());
		$preview->setMimetype('image/jpeg');
		$preview->setEtag('abcdefg');

		if ($locationId !== null) {
			$preview->setLocationId($locationId);
		}
		$this->previewMapper->insert($preview);
	}
}
