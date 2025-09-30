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
use OCP\Files\IMimeTypeLoader;
use OCP\IDBConnection;
use OCP\IPreview;
use OCP\Server;
use Test\TestCase;

/**
 * @group DB
 */
class PreviewMapperTest extends TestCase {
	private PreviewMapper $previewMapper;
	private IDBConnection $connection;
	private IMimeTypeLoader $mimeTypeLoader;

	public function setUp(): void {
		$this->previewMapper = Server::get(PreviewMapper::class);
		$this->connection = Server::get(IDBConnection::class);
		$this->mimeTypeLoader = Server::get(IMimeTypeLoader::class);
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
			$qb->insert('preview_locations')
				->values([
					'bucket_name' => $qb->createNamedParameter('preview-' . $bucket),
					'object_store_name' => $qb->createNamedParameter('default'),
				]);
			$qb->executeStatement();
			$locationId = $qb->getLastInsertId();
		}
		$preview = new Preview();
		$preview->setFileId($fileId);
		$preview->setStorageId(1);
		$preview->setCropped(true);
		$preview->setMax(true);
		$preview->setWidth(100);
		$preview->setSourceMimetype(1);
		$preview->setHeight(100);
		$preview->setSize(100);
		$preview->setMtime(time());
		$preview->setMimetype($this->mimeTypeLoader->getId('image/jpeg'));
		$preview->setEtag('abcdefg');

		if ($locationId !== null) {
			$preview->setLocationId($locationId);
		}
		$this->previewMapper->insert($preview);
	}
}
