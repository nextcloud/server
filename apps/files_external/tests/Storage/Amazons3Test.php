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
	 * Regression test for uncached S3 folders with a real directory marker object.
	 *
	 * mkdir() creates a `<path>/` object in S3. Before the fix, getDirectoryMetaData()
	 * ignored that marker on a cache miss and returned time(), so simply reading the
	 * folder could stamp it as "few seconds ago". stat() must use the marker metadata
	 * instead, which stays stable across repeated reads.
	 */
	public function testStatDirectoryMarkerPreservesStorageMtimeWithoutCache(): void {
		$this->instance->mkdir('markerdir');

		$firstStat = $this->instance->stat('markerdir');
		$this->assertNotFalse($firstStat);

		sleep(2);

		$secondStat = $this->instance->stat('markerdir');
		$this->assertNotFalse($secondStat);
		$this->assertEquals(
			$firstStat['storage_mtime'],
			$secondStat['storage_mtime'],
			'stat() for an uncached S3 directory marker must not synthesize a fresh timestamp on every read'
		);
	}

	/**
	 * Regression test for Common::getMetaData discarding storage_mtime for S3 directories.
	 *
	 * Common::getMetaData sets storage_mtime = mtime unconditionally. For S3 virtual
	 * directories, mtime can be updated by mtime propagation while storage_mtime reflects
	 * the actual last storage change. Without the AmazonS3::getMetaData override the
	 * scanner would see a spurious storage_mtime change on every read, triggering
	 * propagateChange and stamping every ancestor folder with the current timestamp.
	 */
	public function testGetMetaDataDirectoryPreservesStorageMtimeSeparateFromMtime(): void {
		$this->instance->mkdir('testmtimedir');
		$this->instance->getScanner()->scan('testmtimedir', Scanner::SCAN_SHALLOW);

		$cachedEntry = $this->instance->getCache()->get('testmtimedir');
		$this->assertNotFalse($cachedEntry);
		$originalStorageMtime = $cachedEntry['storage_mtime'];

		// Simulate what mtime propagation does: bump mtime without touching storage_mtime.
		// This mirrors the state after a child file is written and propagateChange fires.
		$bumpedMtime = $originalStorageMtime + 100;
		$this->instance->getCache()->put('testmtimedir', ['mtime' => $bumpedMtime]);

		$data = $this->instance->getMetaData('testmtimedir');
		$this->assertNotNull($data);
		$this->assertEquals(
			$originalStorageMtime,
			$data['storage_mtime'],
			'getMetaData for an S3 directory must return the cached storage_mtime, not the (possibly propagation-updated) mtime'
		);
	}
}
