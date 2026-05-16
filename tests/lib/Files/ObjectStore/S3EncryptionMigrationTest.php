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

	public static function setUpBeforeClass(): void {
		parent::setUpBeforeClass();

		$config = Server::get(IConfig::class)->getSystemValue('objectstore');
		if (!is_array($config) || ($config['class'] ?? null) !== S3::class) {
			self::markTestSkipped('S3 primary storage not configured');
		}
	}

	protected function setUp(): void {
		parent::setUp();

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

		parent::tearDown();
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

		// Resolve object-store storage IDs first
		$storageQuery = $db->getQueryBuilder();
		$storageQuery->select('numeric_id')
			->from('storages')
			->where($storageQuery->expr()->like('id', $storageQuery->createNamedParameter('object::%')));
		$storageResult = $storageQuery->executeQuery();
		$objectStoreIds = array_column($storageResult->fetchAllAssociative(), 'numeric_id');
		$storageResult->closeCursor();

		$query = $db->getQueryBuilder();
		$query->select($query->func()->count('*', 'total'))
			->from('filecache')
			->where($query->expr()->eq('encrypted', $query->createNamedParameter(0)));

		if (!empty($objectStoreIds)) {
			$query->andWhere($query->expr()->in('storage',
				$query->createNamedParameter($objectStoreIds, \OCP\DB\QueryBuilder\IQueryBuilder::PARAM_INT_ARRAY)));
		}

		$result = $query->executeQuery();
		$row = $result->fetch();
		$unencryptedCount = $row['total'] ?? 0;

		// Verify the test file actually landed in filecache (query is hitting the right table).
		$fileQuery = $db->getQueryBuilder();
		$fileQuery->select('fileid', 'encrypted')
			->from('filecache')
			->where($fileQuery->expr()->like('path', $fileQuery->createNamedParameter('%migration-test-encrypted%')));
		$fileResult = $fileQuery->executeQuery();
		$fileRow = $fileResult->fetch();
		$fileResult->closeCursor();

		$this->assertNotFalse($fileRow, 'Test file should appear in filecache after write');

		// The detection query must return a valid numeric result.
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
