<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_External\Tests\Storage;

use OC\Files\Cache\Scanner;
use OCA\Files_External\Lib\Storage\AmazonS3;

/**
 * Class Amazons3Test
 *
 *
 * @package OCA\Files_External\Tests\Storage
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
#[\PHPUnit\Framework\Attributes\Group('S3')]
class Amazons3Test extends \Test\Files\Storage\Storage {
	use ConfigurableStorageTrait;
	/** @var AmazonS3 */
	protected $instance;

	protected function setUp(): void {
		parent::setUp();

		$this->loadConfig(__DIR__ . '/../config.amazons3.php');
		$this->instance = new AmazonS3($this->config);
	}

	protected function tearDown(): void {
		if ($this->instance) {
			$this->instance->rmdir('');
		}

		parent::tearDown();
	}

	/**
	 * Regression test for the '.' vs '' root path mismatch in getDirectoryMetaData.
	 *
	 * normalizePath('') returns '.' for S3 object keys, but the filecache stores the
	 * storage root under the key ''. Before the fix, getCache()->get('.') returned false,
	 * causing getDirectoryMetaData to return a fabricated time() on every call, which
	 * made getCacheEntry always see a changed storage_mtime and fire propagateChange.
	 */
	public function testStatRootPreservesStorageMtimeFromCache(): void {
		$this->instance->getScanner()->scan('', Scanner::SCAN_SHALLOW);

		$cachedRoot = $this->instance->getCache()->get('');
		$this->assertNotFalse($cachedRoot, 'Root entry must exist in cache after scan');

		$cachedStorageMtime = $cachedRoot['storage_mtime'];

		$stat = $this->instance->stat('');
		$this->assertNotFalse($stat, 'stat(\'\') must return data');
		$this->assertEquals(
			$cachedStorageMtime,
			$stat['storage_mtime'],
			'stat(\'\') must return storage_mtime from the cache entry, not a fabricated time()'
		);
	}

	/**
	 * Regression test: Common::getMetaData sets storage_mtime = mtime, but for S3 virtual
	 * directories mtime may have been bumped by propagation while storage_mtime should stay
	 * stable. The override restores storage_mtime from the cache entry so the scanner does
	 * not see a spurious mismatch and re-write the cache on every scan.
	 */
	public function testGetMetaDataDirectoryPreservesStorageMtimeSeparateFromMtime(): void {
		$this->instance->getScanner()->scan('', Scanner::SCAN_SHALLOW);

		$cachedRoot = $this->instance->getCache()->get('');
		$this->assertNotFalse($cachedRoot, 'Root entry must exist in cache after scan');

		// Simulate propagation bumping mtime without touching storage_mtime
		$originalStorageMtime = $cachedRoot['storage_mtime'];
		$this->instance->getCache()->update($cachedRoot->getId(), [
			'mtime' => $originalStorageMtime + 9999,
		]);

		$meta = $this->instance->getMetaData('');
		$this->assertNotNull($meta, 'getMetaData(\'\') must return data');
		$this->assertEquals(
			$originalStorageMtime,
			$meta['storage_mtime'],
			'getMetaData must return storage_mtime from cache, not the propagated mtime'
		);
	}

}
