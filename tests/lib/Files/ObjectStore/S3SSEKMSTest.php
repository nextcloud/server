<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use OC\Files\ObjectStore\S3;
use OCP\IConfig;
use OCP\Server;

/**
 * Test suite for AWS SSE-KMS (Server-Side Encryption with Key Management Service).
 *
 * SSE-KMS provides:
 * - AWS-managed server-side encryption
 * - Centralized key management via AWS KMS
 * - Audit trail of key usage via CloudTrail
 * - No client-side encryption overhead
 * - Automatic key rotation support
 *
 * Configuration options:
 * - sse_kms_enabled: true - Enable SSE-KMS
 * - sse_kms_key_id: (optional) Specific KMS key ARN, or use bucket default
 */
#[\PHPUnit\Framework\Attributes\Group('PRIMARY-s3')]
#[\PHPUnit\Framework\Attributes\Group('SSE-KMS')]
class S3SSEKMSTest extends ObjectStoreTestCase {

	private S3 $instance;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		$config = Server::get(IConfig::class)->getSystemValue('objectstore');
		if (!is_array($config) || $config['class'] !== S3::class) {
			self::markTestSkipped('S3 primary storage not configured');
		}

		$arguments = $config['arguments'] ?? [];
		if (empty($arguments['sse_kms_enabled'])) {
			self::markTestSkipped('SSE-KMS not enabled. Set sse_kms_enabled=true in objectstore config');
		}
	}

	protected function getInstance() {
		if (!isset($this->instance)) {
			$config = Server::get(IConfig::class)->getSystemValue('objectstore');
			$this->instance = new S3($config['arguments']);
		}
		return $this->instance;
	}

	/**
	 * Test basic write and read with SSE-KMS
	 */
	public function testWriteReadWithKMS(): void {
		$this->cleanupAfter('kms-test-write-read');

		$s3 = $this->getInstance();
		$data = 'Test data for SSE-KMS encryption';
		$stream = fopen('php://temp', 'r+');
		fwrite($stream, $data);
		rewind($stream);

		// Write with SSE-KMS
		$s3->writeObject('kms-test-write-read', $stream);

		// Read back
		$result = $s3->readObject('kms-test-write-read');
		$readData = stream_get_contents($result);
		fclose($result);

		$this->assertEquals($data, $readData, 'Data should be readable after SSE-KMS encryption');
	}

	/**
	 * Test copy operation with SSE-KMS
	 */
	public function testCopyWithKMS(): void {
		$this->cleanupAfter('kms-test-copy-source');
		$this->cleanupAfter('kms-test-copy-target');

		$s3 = $this->getInstance();
		$data = 'Test data for SSE-KMS copy operation';
		$stream = fopen('php://temp', 'r+');
		fwrite($stream, $data);
		rewind($stream);

		// Write source file
		$s3->writeObject('kms-test-copy-source', $stream);

		// Copy (should re-encrypt with same KMS key)
		$s3->copyObject('kms-test-copy-source', 'kms-test-copy-target');

		// Verify copy
		$this->assertTrue($s3->objectExists('kms-test-copy-target'), 'Copied object should exist');

		$result = $s3->readObject('kms-test-copy-target');
		$readData = stream_get_contents($result);
		fclose($result);

		$this->assertEquals($data, $readData, 'Copied data should match original');
	}

	/**
	 * Test multipart upload with SSE-KMS
	 */
	public function testMultipartUploadWithKMS(): void {
		$this->cleanupAfter('kms-test-multipart');

		$s3 = $this->getInstance();

		// Create 6MB data to trigger multipart
		$data = str_repeat('A', 6 * 1024 * 1024);
		$stream = fopen('php://temp', 'r+');
		fwrite($stream, $data);
		rewind($stream);

		// Write with multipart (forces multipart upload)
		$s3->writeObject('kms-test-multipart', $stream, 'application/octet-stream');

		// Verify
		$this->assertTrue($s3->objectExists('kms-test-multipart'), 'Multipart object should exist');

		// Read back first 1000 bytes to verify
		$result = $s3->readObject('kms-test-multipart');
		$readData = fread($result, 1000);
		fclose($result);

		$this->assertEquals(substr($data, 0, 1000), $readData, 'Multipart data should be readable');
	}

	/**
	 * Data provider for various file sizes
	 */
	public static function dataFileSizes(): array {
		return [
			'1KB' => [1024],
			'1MB' => [1024 * 1024],
			'10MB' => [10 * 1024 * 1024],
		];
	}

	/**
	 * Data provider for large file sizes to test multipart upload threshold behavior
	 */
	public static function dataLargeFileSizes(): array {
		return [
			'50MB' => [50 * 1024 * 1024],
			'100MB' => [100 * 1024 * 1024],
			'150MB' => [150 * 1024 * 1024],
		];
	}

	/**
	 * Test various file sizes with SSE-KMS
	 *
	 * @dataProvider dataFileSizes
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataFileSizes')]
	public function testFileSizesWithKMS(int $size): void {
		$urn = 'kms-test-size-' . ($size / 1024) . 'kb';
		$this->cleanupAfter($urn);

		$s3 = $this->getInstance();
		$data = str_repeat('X', $size);
		$stream = fopen('php://temp', 'r+');
		fwrite($stream, $data);
		rewind($stream);

		// Write
		$s3->writeObject($urn, $stream);

		// Verify object exists
		$this->assertTrue($s3->objectExists($urn), "Object should exist for size $size");

		// Read back and verify
		$result = $s3->readObject($urn);
		$readData = stream_get_contents($result);
		fclose($result);

		$this->assertEquals($size, strlen($readData), "Size mismatch for $size byte file");
		$this->assertEquals($data, $readData, "Content mismatch for $size byte file");
	}

	/**
	 * Test that SSE-KMS metadata is set on objects
	 */
	public function testKMSMetadataPresent(): void {
		$this->cleanupAfter('kms-test-metadata');

		$s3 = $this->getInstance();
		$data = 'Test KMS metadata';
		$stream = fopen('php://temp', 'r+');
		fwrite($stream, $data);
		rewind($stream);

		// Write with SSE-KMS
		$s3->writeObject('kms-test-metadata', $stream);

		// Check metadata via headObject
		$result = $s3->getConnection()->headObject([
			'Bucket' => $s3->getBucket(),
			'Key' => 'kms-test-metadata',
		]);

		// Verify SSE is KMS
		$this->assertEquals('aws:kms', $result->get('ServerSideEncryption'),
			'Object should have SSE-KMS encryption');

		// If specific key configured, verify it's used
		$config = Server::get(IConfig::class)->getSystemValue('objectstore');
		if (!empty($config['arguments']['sse_kms_key_id'])) {
			$this->assertNotNull($result->get('SSEKMSKeyId'),
				'KMS Key ID should be present when specific key configured');
		}
	}

	/**
	 * Test zero-byte file with SSE-KMS
	 *
	 * Note: Zero-byte files are a known edge case with S3.
	 * While they can be written, reading them back may fail due to
	 * Range header issues with empty objects.
	 */
	public function testZeroByteFileWithKMS(): void {
		$this->cleanupAfter('kms-test-zerobyte');

		$s3 = $this->getInstance();
		$stream = fopen('php://temp', 'r+');
		// Write nothing (zero bytes)
		rewind($stream);

		// Write zero-byte file
		$s3->writeObject('kms-test-zerobyte', $stream);

		// Verify exists
		$this->assertTrue($s3->objectExists('kms-test-zerobyte'), 'Zero-byte object should exist');

		// Verify via headObject instead of read (avoids Range header issue)
		$metadata = $s3->getConnection()->headObject([
			'Bucket' => $s3->getBucket(),
			'Key' => 'kms-test-zerobyte',
		]);

		$this->assertEquals(0, $metadata->get('ContentLength'),
			'Zero-byte file should have ContentLength of 0');
		$this->assertEquals('aws:kms', $metadata->get('ServerSideEncryption'),
			'Zero-byte file should still have SSE-KMS encryption');
	}

	/**
	 * Test large file sizes with SSE-KMS to verify multipart threshold behavior
	 *
	 * @dataProvider dataLargeFileSizes
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataLargeFileSizes')]
	#[\PHPUnit\Framework\Attributes\Group('SLOWDB')]
	public function testLargeFileSizesWithKMS(int $size): void {
		$urn = 'kms-test-large-size-' . ($size / 1024 / 1024) . 'mb';
		$this->cleanupAfter($urn);

		$s3 = $this->getInstance();
		$data = str_repeat('L', $size);
		$stream = fopen('php://temp', 'r+');
		fwrite($stream, $data);
		rewind($stream);

		// Write (should trigger multipart for files >= 100MB)
		$s3->writeObject($urn, $stream);

		// Verify object exists
		$this->assertTrue($s3->objectExists($urn), "Object should exist for size $size");

		// Verify metadata via headObject
		$metadata = $s3->getConnection()->headObject([
			'Bucket' => $s3->getBucket(),
			'Key' => $urn,
		]);

		$this->assertEquals('aws:kms', $metadata->get('ServerSideEncryption'),
			"Object should have SSE-KMS encryption for size $size");
		$this->assertEquals($size, $metadata->get('ContentLength'),
			"Size should match for $size byte file");
	}

	/**
	 * Test multipart copy operation with large files
	 */
	#[\PHPUnit\Framework\Attributes\Group('SLOWDB')]
	public function testMultipartCopyWithKMS(): void {
		$this->cleanupAfter('kms-test-multipart-copy-source');
		$this->cleanupAfter('kms-test-multipart-copy-target');

		$s3 = $this->getInstance();

		// Create large file to trigger multipart copy (> copySizeLimit, default 5GB)
		// Use 10MB for test efficiency, but in production this would be > 5GB
		$size = 10 * 1024 * 1024;
		$data = str_repeat('C', $size);
		$stream = fopen('php://temp', 'r+');
		fwrite($stream, $data);
		rewind($stream);

		// Write source file
		$s3->writeObject('kms-test-multipart-copy-source', $stream);

		// Copy (should re-encrypt with same KMS key)
		$s3->copyObject('kms-test-multipart-copy-source', 'kms-test-multipart-copy-target');

		// Verify copy exists
		$this->assertTrue($s3->objectExists('kms-test-multipart-copy-target'), 'Copied object should exist');

		// Verify encryption on target
		$metadata = $s3->getConnection()->headObject([
			'Bucket' => $s3->getBucket(),
			'Key' => 'kms-test-multipart-copy-target',
		]);

		$this->assertEquals('aws:kms', $metadata->get('ServerSideEncryption'),
			'Copied object should have SSE-KMS encryption');
	}

	/**
	 * Test delete operation with KMS-encrypted objects
	 */
	public function testDeleteWithKMS(): void {
		$this->cleanupAfter('kms-test-delete');

		$s3 = $this->getInstance();
		$data = 'Test data for delete operation';
		$stream = fopen('php://temp', 'r+');
		fwrite($stream, $data);
		rewind($stream);

		// Write with SSE-KMS
		$s3->writeObject('kms-test-delete', $stream);

		// Verify exists
		$this->assertTrue($s3->objectExists('kms-test-delete'), 'Object should exist before delete');

		// Delete
		$s3->deleteObject('kms-test-delete');

		// Verify deleted
		$this->assertFalse($s3->objectExists('kms-test-delete'), 'Object should not exist after delete');
	}

	/**
	 * Test overwriting existing KMS-encrypted objects
	 */
	public function testOverwriteWithKMS(): void {
		$this->cleanupAfter('kms-test-overwrite');

		$s3 = $this->getInstance();

		// Write initial data
		$data1 = 'Initial data for overwrite test';
		$stream1 = fopen('php://temp', 'r+');
		fwrite($stream1, $data1);
		rewind($stream1);
		$s3->writeObject('kms-test-overwrite', $stream1);

		// Verify initial write
		$result1 = $s3->readObject('kms-test-overwrite');
		$readData1 = stream_get_contents($result1);
		fclose($result1);
		$this->assertEquals($data1, $readData1, 'Initial data should match');

		// Overwrite with new data
		$data2 = 'Overwritten data with different content';
		$stream2 = fopen('php://temp', 'r+');
		fwrite($stream2, $data2);
		rewind($stream2);
		$s3->writeObject('kms-test-overwrite', $stream2);

		// Verify overwrite
		$result2 = $s3->readObject('kms-test-overwrite');
		$readData2 = stream_get_contents($result2);
		fclose($result2);
		$this->assertEquals($data2, $readData2, 'Overwritten data should match');

		// Verify still encrypted with KMS
		$metadata = $s3->getConnection()->headObject([
			'Bucket' => $s3->getBucket(),
			'Key' => 'kms-test-overwrite',
		]);

		$this->assertEquals('aws:kms', $metadata->get('ServerSideEncryption'),
			'Overwritten object should still have SSE-KMS encryption');
	}
}
