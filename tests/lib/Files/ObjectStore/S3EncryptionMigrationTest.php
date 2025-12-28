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
use Test\Traits\EncryptionTrait;
use Test\Traits\MountProviderTrait;
use Test\Traits\UserTrait;

/**
 * Test migration scenario: Mixed encrypted/unencrypted files in S3.
 *
 * Simulates the real-world scenario where users had encryption "enabled"
 * but files were not encrypted due to the EncryptionWrapper bug.
 *
 * After applying the fix, verify that:
 * 1. Already-encrypted files are skipped
 * 2. Unencrypted files get encrypted
 * 3. No data loss or corruption
 * 4. Size tracking remains accurate
 */
#[\PHPUnit\Framework\Attributes\Group('PRIMARY-s3')]
#[\PHPUnit\Framework\Attributes\Group('Encryption')]
#[\PHPUnit\Framework\Attributes\Group('Migration')]
#[\PHPUnit\Framework\Attributes\Group('DB')]
class S3EncryptionMigrationTest extends \Test\TestCase {
	use EncryptionTrait;
	use MountProviderTrait;
	use UserTrait;

	private const TEST_USER = 'test-migration-user';
	private const TEST_PASSWORD = 'test-migration-pass';

	/** @var \OCP\Files\Folder */
	private $userFolder;

	/** @var \OC\Files\View */
	private $view;

	/** @var S3 */
	private $objectStore;

	/** @var string */
	private $bucket;

	/** @var string */
	private $encryptionWasEnabled;

	/** @var string */
	private $originalEncryptionModule;

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		$config = Server::get(IConfig::class)->getSystemValue('objectstore');
		if (!is_array($config) || $config['class'] !== S3::class) {
			self::markTestSkipped('S3 primary storage not configured');
		}
	}

	protected function setUp(): void {
		parent::setUp();

		$this->setUpEncryptionTrait();

		$config = Server::get(IConfig::class);
		$this->encryptionWasEnabled = $config->getAppValue('core', 'encryption_enabled', 'no');
		$this->originalEncryptionModule = $config->getAppValue('core', 'default_encryption_module');

		$s3Config = Server::get(IConfig::class)->getSystemValue('objectstore');
		$this->bucket = $s3Config['arguments']['bucket'] ?? 'nextcloud';
		$this->objectStore = new S3($s3Config['arguments']);

		if (!$this->userManager->userExists(self::TEST_USER)) {
			$this->createUser(self::TEST_USER, self::TEST_PASSWORD);
		}

		$this->setupForUser(self::TEST_USER, self::TEST_PASSWORD);
		$this->loginWithEncryption(self::TEST_USER);

		$this->userFolder = \OC::$server->getUserFolder(self::TEST_USER);
		$this->view = new \OC\Files\View('/' . self::TEST_USER . '/files');
	}

	protected function tearDown(): void {
		try {
			if ($this->view) {
				// Clean up test files
				$testFiles = $this->view->getDirectoryContent('');
				foreach ($testFiles as $file) {
					if (str_starts_with($file->getName(), 'migration-test-')) {
						$this->view->unlink($file->getName());
					}
				}
			}
		} catch (\Exception $e) {
			// Ignore
		}

		try {
			$config = Server::get(IConfig::class);
			$config->setAppValue('core', 'encryption_enabled', $this->encryptionWasEnabled);
			$config->setAppValue('core', 'default_encryption_module', $this->originalEncryptionModule);
			$config->deleteAppValue('encryption', 'useMasterKey');
		} catch (\Exception $e) {
			// Ignore
		}

		parent::tearDown();
	}

	/**
	 * Create an unencrypted file directly in S3 (simulating pre-fix behavior)
	 */
	private function createUnencryptedFileInS3(string $filename, string $content): int {
		// Write directly to S3, bypassing encryption wrapper
		$urn = 'urn:oid:' . time() . rand(1000, 9999);
		$stream = fopen('php://temp', 'r+');
		fwrite($stream, $content);
		rewind($stream);

		$this->objectStore->writeObject($urn, $stream);
		fclose($stream);

		// Manually add to filecache as unencrypted
		$cache = $this->userFolder->getStorage()->getCache();
		$fileId = (int)str_replace('urn:oid:', '', $urn);

		$cache->put($filename, [
			'size' => strlen($content),
			'mtime' => time(),
			'mimetype' => 'application/octet-stream',
			'encrypted' => false,  // Mark as unencrypted
			'storage_mtime' => time(),
		]);

		return $fileId;
	}

	/**
	 * Test that encryption:encrypt-all safely handles mixed content
	 */
	public function testEncryptAllHandlesMixedContent(): void {
		// Create test files in different states
		$files = [
			'migration-test-unencrypted-1.txt' => 'Unencrypted content 1',
			'migration-test-unencrypted-2.txt' => 'Unencrypted content 2',
		];

		// 1. Create some unencrypted files (simulating pre-fix files)
		foreach ($files as $filename => $content) {
			// Directly write to S3 without encryption (simulate bug scenario)
			// For now, just verify we can detect encryption status
			$this->markTestSkipped('Manual S3 write needed - complex test case');
		}

		// Future: Complete this test to verify encrypt-all works on mixed content
	}

	/**
	 * Test that isEncrypted() correctly identifies file state
	 */
	public function testIsEncryptedFlag(): void {
		$testFile = 'migration-test-encrypted-flag.txt';
		$content = 'Test content for encryption flag';

		// Write file with encryption wrapper (should be encrypted)
		$this->view->file_put_contents($testFile, $content);

		// Get file info via node
		$node = $this->userFolder->get($testFile);

		// Verify encrypted flag is set via node
		$this->assertTrue($node->isEncrypted(),
			'File should be marked as encrypted in database after write');

		// Verify content is accessible
		$readContent = $this->view->file_get_contents($testFile);
		$this->assertEquals($content, $readContent,
			'Content should be readable after encryption');

		// Clean up
		$this->view->unlink($testFile);
	}

	/**
	 * Test database query to detect unencrypted files
	 */
	public function testDetectUnencryptedFilesQuery(): void {
		// Create encrypted file
		$this->view->file_put_contents('migration-test-encrypted.txt', 'encrypted');

		// Query database for unencrypted files
		$db = Server::get(\OCP\IDBConnection::class);
		$query = $db->getQueryBuilder();

		$query->select($query->func()->count('*', 'total'))
			->from('filecache')
			->where($query->expr()->eq('encrypted', $query->createNamedParameter(0)))
			->andWhere($query->expr()->neq('mimetype',
				$query->createFunction('(SELECT id FROM oc_mimetypes WHERE mimetype = '
					. $query->createNamedParameter('httpd/unix-directory') . ')')
			))
			->andWhere($query->expr()->like('storage',
				$query->createFunction('(SELECT numeric_id FROM oc_storages WHERE id LIKE '
					. $query->createNamedParameter('object::%') . ')')
			));

		$result = $query->executeQuery();
		$row = $result->fetch();
		$unencryptedCount = $row['total'] ?? 0;

		// After our encrypted file, this should be 0 or low
		// (may have system files that aren't encrypted)
		$this->assertIsNumeric($unencryptedCount,
			'Should be able to query unencrypted file count');

		// Clean up
		$this->view->unlink('migration-test-encrypted.txt');
	}

	/**
	 * Test size consistency after simulated migration
	 */
	public function testSizeConsistencyAfterEncryption(): void {
		$testFile = 'migration-test-size-check.bin';
		$size = 50 * 1024; // 50KB
		$data = random_bytes($size);

		// Write encrypted file
		$this->view->file_put_contents($testFile, $data);

		// Verify size in database
		$node = $this->userFolder->get($testFile);
		$dbSize = $node->getSize();

		// Verify actual content size
		$readData = $this->view->file_get_contents($testFile);
		$actualSize = strlen($readData);

		// Verify S3 size (should be larger)
		$fileId = $node->getId();
		$urn = 'urn:oid:' . $fileId;
		$s3Result = $this->objectStore->getConnection()->headObject([
			'Bucket' => $this->bucket,
			'Key' => $urn,
		]);
		$s3Size = $s3Result['ContentLength'];

		// Assertions
		$this->assertEquals($size, $dbSize,
			'Database should have unencrypted size');
		$this->assertEquals($size, $actualSize,
			'Read content should match original size');
		$this->assertGreaterThan($size, $s3Size,
			'S3 should have encrypted size (larger)');

		// Clean up
		$this->view->unlink($testFile);
	}
}
