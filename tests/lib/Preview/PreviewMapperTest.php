<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Preview;

use OC\Preview\Db\Preview;
use OC\Preview\Db\PreviewMapper;
use OCP\Files\IMimeTypeLoader;
use OCP\IDBConnection;
use OCP\Server;
use OCP\Snowflake\ISnowflakeGenerator;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group('DB')]
class PreviewMapperTest extends TestCase {
	private PreviewMapper $previewMapper;
	private IDBConnection $connection;
	private ISnowflakeGenerator $snowflake;
	private IMimeTypeLoader $mimeTypeLoader;

	#[\Override]
	public function setUp(): void {
		parent::setUp();
		$this->previewMapper = Server::get(PreviewMapper::class);
		$this->connection = Server::get(IDBConnection::class);
		$this->snowflake = Server::get(ISnowflakeGenerator::class);
		$this->mimeTypeLoader = Server::get(IMimeTypeLoader::class);

		$qb = $this->connection->getQueryBuilder();
		$qb->delete('preview_locations')->executeStatement();

		$qb = $this->connection->getQueryBuilder();
		$qb->delete('preview_versions')->executeStatement();

		$qb = $this->connection->getQueryBuilder();
		$qb->delete('previews')->executeStatement();
	}

	#[\Override]
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

	private function createPreviewForFileId(int $fileId, ?int $bucket = null, int $size = 100, ?string $version = null, bool $cropped = true): string {
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
		$preview->generateId();
		$preview->setFileId($fileId);
		$preview->setStorageId(1);
		$preview->setCropped($cropped);
		$preview->setMax(true);
		$preview->setWidth($size);
		$preview->setSourceMimeType('image/jpeg');
		$preview->setHeight($size);
		$preview->setSize(100);
		$preview->setMtime(time());
		$preview->setMimetype('image/jpeg');
		$preview->setEtag('abcdefg');
		$preview->setVersion($version);

		if ($locationId !== null) {
			$preview->setLocationId($locationId);
		}
		$this->previewMapper->insert($preview);

		return $preview->id;
	}

	/**
	 * The previews table is joined with preview_versions, which also has a
	 * file_id column, so the condition has to be qualified with the alias.
	 */
	public function testGetByFileId(): void {
		$fileId = 4242;
		$this->createPreviewForFileId($fileId);
		$this->createPreviewForFileId($fileId, size: 256);
		$this->createPreviewForFileId(4243);

		$previews = iterator_to_array($this->previewMapper->getByFileId($fileId));

		$this->assertCount(2, $previews);
		foreach ($previews as $preview) {
			$this->assertSame($fileId, $preview->getFileId());
		}
	}

	/**
	 * Same ambiguity, reached through the specification lookup that
	 * Generator::savePreview() uses to recover from a unique constraint
	 * violation. It passes the cropped flag as a PHP bool, and false is the
	 * common case, so both values have to be covered.
	 */
	#[\PHPUnit\Framework\Attributes\TestWith([false])]
	#[\PHPUnit\Framework\Attributes\TestWith([true])]
	public function testGetPreviewForSpecification(bool $cropped): void {
		$fileId = 4244;
		$previewId = $this->createPreviewForFileId($fileId, cropped: $cropped);

		$preview = $this->previewMapper->getPreviewForSpecification([
			'file_id' => $fileId,
			'width' => 100,
			'height' => 100,
			'mimetype_id' => $this->mimeTypeLoader->getId('image/jpeg'),
			'cropped' => $cropped,
			'version_id' => '-1',
		]);

		$this->assertNotNull($preview);
		$this->assertEquals($previewId, $preview->getId());
	}

	/**
	 * version lives in the joined preview_versions table, so it has to keep
	 * resolving to that alias rather than to the previews table.
	 */
	public function testGetPreviewForSpecificationOnJoinedColumn(): void {
		$fileId = 4245;
		$previewId = $this->createPreviewForFileId($fileId, version: '1000');

		$preview = $this->previewMapper->getPreviewForSpecification([
			'file_id' => $fileId,
			'version' => '1000',
		]);

		$this->assertNotNull($preview);
		$this->assertEquals($previewId, $preview->getId());
	}

	public function testLargeIdInsertRetrieve(): void {
		$fileId = PHP_INT_MAX;
		$originalPreviewId = $this->createPreviewForFileId($fileId);

		$dbPreview = $this->previewMapper->getAvailablePreviews([$fileId])[$fileId][0];
		$this->assertEquals($originalPreviewId, $dbPreview->id);
		$this->assertEquals($fileId, $dbPreview->getFileId());
	}
}
