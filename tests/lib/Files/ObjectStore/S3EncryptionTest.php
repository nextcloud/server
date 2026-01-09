<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use OC\Files\ObjectStore\ObjectStoreStorage;
use OC\Files\ObjectStore\S3;
use OCP\IConfig;
use OCP\Server;
use Test\Traits\EncryptionTrait;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

/**
 * Comprehensive test suite for Nextcloud encryption with S3 primary storage.
 *
 * Tests critical size validation across:
 * - Database (filecache) - should store unencrypted size
 * - S3 Object (headObject) - will be larger (encrypted with overhead)
 * - Actual Content - should match original unencrypted size
 */
#[\PHPUnit\Framework\Attributes\Group('PRIMARY-s3')]
#[\PHPUnit\Framework\Attributes\Group('Encryption')]
#[\PHPUnit\Framework\Attributes\Group('DB')]
class S3EncryptionTest extends \Test\TestCase {
	use EncryptionTrait;
	use MountProviderTrait;
	use UserTrait;

	private const TEST_USER = 'test-s3-enc-user';
	private const TEST_PASSWORD = 'test-s3-enc-password';

	/** @var \OCP\Files\Folder */
	private $userFolder;

	/** @var \OC\Files\View */
	private $view;

	/** @var S3 */
	private $objectStore;

	/** @var string */
	private $bucket;

	/** @var array */
	private $s3Config;

	/** @var ObjectStoreStorage */
	private $rootStorage;

	/** @var string */
	private $encryptionWasEnabled;

	/** @var string */
	private $originalEncryptionModule;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		// Check if S3 primary storage is configured
		$config = Server::get(IConfig::class)->getSystemValue('objectstore');
		if (!is_array($config) || $config['class'] !== S3::class) {
			self::markTestSkipped('S3 primary storage not configured. Configure objectstore in config.php to run these tests.');
		}
	}

	protected function setUp(): void {
		parent::setUp();

		// Set up encryption
		$this->setUpEncryptionTrait();

		// Save encryption state for teardown
		$config = Server::get(IConfig::class);
		$this->encryptionWasEnabled = $config->getAppValue('core', 'encryption_enabled', 'no');
		$this->originalEncryptionModule = $config->getAppValue('core', 'default_encryption_module');

		// Get S3 config from system config
		$this->s3Config = Server::get(IConfig::class)->getSystemValue('objectstore');
		$this->bucket = $this->s3Config['arguments']['bucket'] ?? 'nextcloud';

		// Create S3 object store
		$this->objectStore = new S3($this->s3Config['arguments']);

		// Create test user
		if (!$this->userManager->userExists(self::TEST_USER)) {
			$this->createUser(self::TEST_USER, self::TEST_PASSWORD);
		}

		// Set up encryption for user
		$this->setupForUser(self::TEST_USER, self::TEST_PASSWORD);
		$this->loginWithEncryption(self::TEST_USER);

		// Get user folder (this will have encryption wrapper applied)
		$this->userFolder = \OC::$server->getUserFolder(self::TEST_USER);

		// Get the view for the user
		$this->view = new \OC\Files\View('/' . self::TEST_USER . '/files');

		// Get the root ObjectStoreStorage (without wrapper) to check S3 sizes
		$mount = \OC\Files\Filesystem::getMountManager()->find('/' . self::TEST_USER . '/files');
		$this->rootStorage = $mount->getStorage();

		// Unwrap to get the actual ObjectStoreStorage if it's wrapped
		while ($this->rootStorage instanceof \OC\Files\Storage\Wrapper\Wrapper) {
			$this->rootStorage = $this->rootStorage->getWrapperStorage();
		}
	}

	protected function tearDown(): void {
		// Clean up test files
		try {
			if ($this->view) {
				$this->cleanupTestFiles();
			}
		} catch (\Exception $e) {
			// Ignore cleanup errors
		}

		// Tear down encryption
		try {
			$config = Server::get(IConfig::class);
			$config->setAppValue('core', 'encryption_enabled', $this->encryptionWasEnabled ?? 'no');
			$config->setAppValue('core', 'default_encryption_module', $this->originalEncryptionModule ?? '');
			$config->deleteAppValue('encryption', 'useMasterKey');
		} catch (\Exception $e) {
			// Ignore
		}

		parent::tearDown();
	}

	private function cleanupTestFiles(): void {
		// Clean up any test files that match our patterns
		$patterns = ['test-size-*', 'test-roundtrip-*', 'test-integrity-*',
			'test-partial-read*', 'test-seek*', 'test-multipart*', 'test.txt'];

		foreach ($patterns as $pattern) {
			try {
				$files = $this->view->getDirectoryContent('');
				foreach ($files as $file) {
					$name = $file->getName();
					if (fnmatch($pattern, $name)) {
						$this->view->unlink($name);
					}
				}
			} catch (\Exception $e) {
				// Ignore
			}
		}
	}

	/**
	 * Get the S3 URN for a path in the user's files
	 */
	private function getObjectUrn(string $path): string {
		// Get file info from user folder
		try {
			$node = $this->userFolder->get($path);
			$fileId = $node->getId();
			// URN format: urn:oid:{fileId}
			return 'urn:oid:' . $fileId;
		} catch (\Exception $e) {
			throw new \Exception("File not found: $path - " . $e->getMessage());
		}
	}

	/**
	 * Data provider for file sizes
	 */
	public static function dataFileSizes(): array {
		return [
			'0 bytes (empty file)' => [0],
			'1KB' => [1024],
			'1MB' => [1024 * 1024],
			'5MB (multipart threshold)' => [5 * 1024 * 1024],
			'16MB (historical issue)' => [16 * 1024 * 1024],
			'64MB (historical issue)' => [64 * 1024 * 1024],
			'100MB (stress test)' => [100 * 1024 * 1024],
		];
	}

	/**
	 * CRITICAL SIZE VALIDATION TEST
	 *
	 * This test validates size consistency across three sources:
	 * 1. Database (filecache) - should store unencrypted size
	 * 2. S3 Object (headObject) - will be larger (encrypted)
	 * 3. Actual content - should match original unencrypted size
	 *
	 * Known issues exist with size mismatches between these sources.
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataFileSizes')]
	public function testSizeConsistencyAcrossSources(int $originalSize): void {
		$testFile = 'test-size-' . ($originalSize / 1024) . 'kb.bin';

		// 1. Write file of known size using View (encryption wrapper applied)
		$data = $originalSize > 0 ? random_bytes($originalSize) : '';
		$bytesWritten = $this->view->file_put_contents($testFile, $data);

		$this->assertEquals($originalSize, $bytesWritten,
			'file_put_contents should return original size written');

		// 2. Get database size (from filecache via userFolder)
		$node = $this->userFolder->get($testFile);
		$dbSize = $node->getSize();

		// 3. Get S3 object size (encrypted size) directly from S3
		$urn = $this->getObjectUrn($testFile);
		$s3Result = $this->objectStore->getConnection()->headObject([
			'Bucket' => $this->bucket,
			'Key' => $urn,
		]);
		$s3Size = $s3Result['ContentLength'];

		// 4. Get actual content size (after decryption via View)
		$content = $this->view->file_get_contents($testFile);
		$actualSize = strlen($content);

		// ASSERTIONS - Critical size relationships
		// After fixing CacheEntry.php getUnencryptedSize() bug, all sizes should be correct
		$this->assertEquals($originalSize, $dbSize,
			"Database should store unencrypted size (original: $originalSize, db: $dbSize)");

		$this->assertEquals($originalSize, $actualSize,
			"Actual content should match original size after decryption (original: $originalSize, actual: $actualSize)");

		if ($originalSize === 0) {
			// Zero-byte files still get encryption header in S3
			$this->assertGreaterThan(0, $s3Size,
				'S3 should have encryption header even for empty files');
		} else {
			$this->assertGreaterThan($originalSize, $s3Size,
				"S3 size should be larger than original due to encryption overhead (original: $originalSize, s3: $s3Size)");
		}

		// Verify content integrity - critical!
		$this->assertEquals($data, $content,
			'Content should be identical after encrypt/decrypt cycle - corruption detected!');

		// Validate encryption overhead is reasonable
		// Binary signed format: Header (8192 bytes) + data blocks
		// Each encrypted block is 8192 bytes, holds 8096 bytes unencrypted
		// Overhead: ~2% for large files, more for small files due to header

		// Special case for zero-byte files
		if ($originalSize === 0) {
			// Zero-byte files still get encryption header
			$this->assertGreaterThan(0, $s3Size,
				'Even empty files should have encryption header in S3');
			$this->assertLessThanOrEqual(8192, $s3Size,
				'Empty file should only have header block');
		} else {
			$overheadPercent = (($s3Size - $originalSize) / $originalSize) * 100;

			// Sanity checks for overhead
			if ($originalSize < 10240) { // < 10KB
				// Small files have large relative overhead due to 8KB header
				$this->assertLessThan(1000, $overheadPercent,
					"Encryption overhead should be reasonable even for small files (got: {$overheadPercent}%)");
			} else {
				// Larger files should have ~1-3% overhead
				$this->assertGreaterThan(0.5, $overheadPercent,
					"Should have some encryption overhead (got: {$overheadPercent}%)");
				$this->assertLessThan(5, $overheadPercent,
					"Encryption overhead should be under 5% for files > 10KB (got: {$overheadPercent}%)");
			}
		}

		// Clean up
		$this->view->unlink($testFile);
	}

	/**
	 * Test encrypted file round trip - write and read back
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataFileSizes')]
	public function testEncryptedFileRoundTrip(int $size): void {
		$testFile = 'test-roundtrip-' . ($size / 1024) . 'kb.bin';
		$data = $size > 0 ? random_bytes($size) : '';

		// Write
		$written = $this->view->file_put_contents($testFile, $data);
		$this->assertEquals($size, $written);

		// Verify exists
		$this->assertTrue($this->view->file_exists($testFile));

		// Read back
		$readData = $this->view->file_get_contents($testFile);

		// Verify size
		$this->assertEquals($size, strlen($readData));

		// Verify content
		$this->assertEquals($data, $readData, 'Content mismatch after round trip');

		// Clean up
		$this->view->unlink($testFile);
	}

	/**
	 * Test encrypted file integrity with streaming reads
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataFileSizes')]
	public function testEncryptedFileIntegrity(int $size): void {
		$testFile = 'test-integrity-' . ($size / 1024) . 'kb.bin';
		$data = $size > 0 ? random_bytes($size) : '';

		// Write
		$this->view->file_put_contents($testFile, $data);

		// Stream read
		$handle = $this->view->fopen($testFile, 'r');
		$this->assertIsResource($handle);

		// Read in chunks
		$chunkSize = 8192;
		$readData = '';
		while (!feof($handle)) {
			$chunk = fread($handle, $chunkSize);
			$readData .= $chunk;
		}
		fclose($handle);

		// Verify
		$this->assertEquals($size, strlen($readData), 'Size mismatch in streaming read');
		$this->assertEquals($data, $readData, 'Content mismatch in streaming read');

		// Clean up
		$this->view->unlink($testFile);
	}

	/**
	 * Test partial reads (seeking) on encrypted files
	 */
	public function testEncryptedFilePartialRead(): void {
		$testFile = 'test-partial-read.bin';
		$size = 1024 * 100; // 100KB
		$data = random_bytes($size);

		// Write
		$this->view->file_put_contents($testFile, $data);

		// Test partial reads at various offsets
		$testCases = [
			['offset' => 0, 'length' => 100],
			['offset' => 1000, 'length' => 500],
			['offset' => 50000, 'length' => 1000],
			['offset' => $size - 100, 'length' => 100], // End of file
		];

		foreach ($testCases as $test) {
			$offset = $test['offset'];
			$length = $test['length'];

			$handle = $this->view->fopen($testFile, 'r');
			fseek($handle, $offset);
			$chunk = fread($handle, $length);
			fclose($handle);

			$expected = substr($data, $offset, $length);
			$this->assertEquals($expected, $chunk,
				"Partial read mismatch at offset $offset, length $length");
		}

		// Clean up
		$this->view->unlink($testFile);
	}

	/**
	 * Test seeking within encrypted files
	 */
	public function testEncryptedFileSeek(): void {
		$testFile = 'test-seek.bin';
		$size = 1024 * 50; // 50KB
		$data = random_bytes($size);

		// Write
		$this->view->file_put_contents($testFile, $data);

		$handle = $this->view->fopen($testFile, 'r');

		// Test SEEK_SET
		fseek($handle, 1000, SEEK_SET);
		$this->assertEquals(1000, ftell($handle));
		$chunk = fread($handle, 100);
		$this->assertEquals(substr($data, 1000, 100), $chunk);

		// Test SEEK_CUR
		fseek($handle, 500, SEEK_CUR);
		$this->assertEquals(1600, ftell($handle));

		// Test SEEK_END
		fseek($handle, -100, SEEK_END);
		$this->assertEquals($size - 100, ftell($handle));
		$chunk = fread($handle, 100);
		$this->assertEquals(substr($data, -100), $chunk);

		fclose($handle);

		// Clean up
		$this->view->unlink($testFile);
	}

	/**
	 * Test that multipart upload works correctly with encryption
	 */
	public function testEncryptedMultipartUpload(): void {
		$testFile = 'test-multipart.bin';
		// 6MB file to trigger multipart upload (threshold is 100MB, use 110MB to be safe)
		$size = 110 * 1024 * 1024;
		$data = random_bytes($size);

		// Write (should use multipart upload)
		$written = $this->view->file_put_contents($testFile, $data);
		$this->assertEquals($size, $written);

		// Verify file was created
		$this->assertTrue($this->view->file_exists($testFile));

		// Verify size in database
		$node = $this->userFolder->get($testFile);
		$dbSize = $node->getSize();
		$this->assertEquals($size, $dbSize,
			'Database should have unencrypted size even for multipart upload');

		// Verify content
		$readData = $this->view->file_get_contents($testFile);
		$this->assertEquals($data, $readData,
			'Content mismatch for multipart encrypted upload');

		// Clean up
		$this->view->unlink($testFile);
	}

	/**
	 * Test that file size tracking works correctly during writes
	 */
	public function testEncryptedFileSizeTracking(): void {
		$testFile = 'test-size-tracking.bin';
		$sizes = [1024, 10240, 102400]; // 1KB, 10KB, 100KB

		foreach ($sizes as $size) {
			$data = random_bytes($size);

			// Write
			$this->view->file_put_contents($testFile, $data);

			// Check filesize() returns unencrypted size
			$reportedSize = $this->view->filesize($testFile);
			$this->assertEquals($size, $reportedSize,
				"filesize() should return unencrypted size (expected: $size, got: $reportedSize)");

			// Check stat() returns unencrypted size via userFolder
			$node = $this->userFolder->get($testFile);
			$nodeSize = $node->getSize();
			$this->assertEquals($size, $nodeSize,
				"Node size should return unencrypted size (expected: $size, got: $nodeSize)");
		}

		// Clean up
		$this->view->unlink($testFile);
	}

	/**
	 * Test mime type handling with encryption
	 */
	public function testEncryptedFileMimeType(): void {
		$testFile = 'test.txt';
		$data = 'This is a text file';

		// Write
		$this->view->file_put_contents($testFile, $data);

		// Get mime type via userFolder node
		$node = $this->userFolder->get($testFile);
		$mimeType = $node->getMimetype();

		// Should detect as text/plain
		$this->assertEquals('text/plain', $mimeType,
			'MIME type detection should work on encrypted files');

		// Clean up
		$this->view->unlink($testFile);
	}
}
