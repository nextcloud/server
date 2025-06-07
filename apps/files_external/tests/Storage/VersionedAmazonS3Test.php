<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Tests\Storage;

/**
 * @group DB
 * @group S3
 */
class VersionedAmazonS3Test extends Amazons3Test {
	protected function setUp(): void {
		parent::setUp();
		try {
			$this->instance->getConnection()->putBucketVersioning([
				'Bucket' => $this->instance->getBucket(),
				'VersioningConfiguration' => [
					'Status' => 'Enabled',
				],
			]);
		} catch (\Exception $e) {
			$this->markTestSkipped("s3 backend doesn't seem to support versioning");
		}
	}

	public function testCopyOverWriteDirectory(): void {
		if (isset($this->config['minio'])) {
			$this->markTestSkipped('MinIO has a bug with batch deletion on versioned storages, see https://github.com/minio/minio/issues/21366');
		}

		parent::testCopyOverWriteDirectory();
	}
}
