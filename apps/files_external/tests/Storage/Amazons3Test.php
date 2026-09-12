<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_External\Tests\Storage;

use OC\Files\Cache\Scanner;
use OCA\Files_External\ConfigLexicon;
use OCA\Files_External\Lib\Storage\AmazonS3;
use OCP\Config\Lexicon\Preset;
use OCP\IAppConfig;
use OCP\Server;
use ReflectionClassConstant;
use ReflectionMethod;
use ReflectionProperty;

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
	use CrossMountS3ConfigTrait;
	/** @var AmazonS3 */
	protected $instance;

	protected function setUp(): void {
		parent::setUp();

		$this->loadConfig(__DIR__ . '/../config.amazons3.php');
		if ($this->config['run_cross_mount'] ?? false) {
			$this->enableServerSideCopyFlagBeforeInstances();
		}
		$this->instance = new AmazonS3($this->config);
	}

	protected function tearDown(): void {
		if ($this->instance) {
			$this->instance->rmdir('');
		}
		AmazonS3::resetServerSideCopyFailureCounter();
		$this->restoreServerSideCopyFlag();

		parent::tearDown();
	}

	private function arrangeSourceFile(string $path, string $payload, ?AmazonS3 $storage = null): void {
		$storage = $storage ?? $this->instance;
		$storage->file_put_contents($path, $payload);
		$storage->getScanner()->scanFile($path);
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

	public function testLexiconDefaultIsFalse(): void {
		$lexicon = new ConfigLexicon();
		foreach ($lexicon->getAppConfigs() as $entry) {
			if ($entry->getKey() === ConfigLexicon::AMAZONS3_SERVER_SIDE_COPY) {
				// Entry::convertFromBool serialises the bool default via '1'/'0' strings.
				$this->assertSame('0', $entry->getDefault(Preset::PRIVATE));
				return;
			}
		}
		$this->fail('AMAZONS3_SERVER_SIDE_COPY entry missing from ConfigLexicon');
	}

	public function testAppConfigReturnsFalseWhenFlagUnset(): void {
		$this->restoreServerSideCopyFlag();
		$appConfig = Server::get(IAppConfig::class);
		$appConfig->deleteKey('files_external', ConfigLexicon::AMAZONS3_SERVER_SIDE_COPY);
		// Passing true as the PHP fallback proves the lexicon default supplied the answer.
		$this->assertFalse($appConfig->getValueBool('files_external', ConfigLexicon::AMAZONS3_SERVER_SIDE_COPY, true));
	}

	public function testCrossMountFileMoveEndToEnd(): void {
		$peer = $this->newPeerStorage();
		try {
			$peer->getScanner()->scan('', Scanner::SCAN_SHALLOW);
			$this->arrangeSourceFile('cross-move-src.txt', 'payload-move');

			$result = $peer->moveFromStorage($this->instance, 'cross-move-src.txt', 'cross-move-dst.txt');
			$this->assertTrue($result);
			$this->assertFalse($this->instance->file_exists('cross-move-src.txt'), 'source must be deleted after move');
			$this->assertTrue($peer->file_exists('cross-move-dst.txt'));
			$this->assertSame('payload-move', $peer->file_get_contents('cross-move-dst.txt'));
		} finally {
			$peer->unlink('cross-move-dst.txt');
			$this->instance->unlink('cross-move-src.txt');
		}
	}

	public function testCrossMountFileCopyEndToEnd(): void {
		$peer = $this->newPeerStorage();
		try {
			$peer->getScanner()->scan('', Scanner::SCAN_SHALLOW);
			$this->arrangeSourceFile('cross-copy-src.txt', 'payload-copy');

			$result = $peer->copyFromStorage($this->instance, 'cross-copy-src.txt', 'cross-copy-dst.txt');
			$this->assertTrue($result);
			$this->assertTrue($this->instance->file_exists('cross-copy-src.txt'), 'source must remain after copy');
			$this->assertTrue($peer->file_exists('cross-copy-dst.txt'));
			$this->assertSame('payload-copy', $peer->file_get_contents('cross-copy-dst.txt'));
		} finally {
			$peer->unlink('cross-copy-dst.txt');
			$this->instance->unlink('cross-copy-src.txt');
		}
	}

	public function testCrossMountDirectoryMoveRecurses(): void {
		$peer = $this->newPeerStorage();
		try {
			$peer->getScanner()->scan('', Scanner::SCAN_SHALLOW);

			$this->instance->mkdir('cross-tree');
			$this->instance->mkdir('cross-tree/sub');
			$this->instance->file_put_contents('cross-tree/root.txt', 'root-payload');
			$this->instance->file_put_contents('cross-tree/sub/leaf.txt', 'leaf-payload');
			$this->instance->getScanner()->scan('cross-tree');

			$result = $peer->moveFromStorage($this->instance, 'cross-tree', 'cross-tree-dst');
			$this->assertTrue($result);
			$this->assertFalse($this->instance->file_exists('cross-tree'));
			$this->assertTrue($peer->is_dir('cross-tree-dst'));
			$this->assertSame('root-payload', $peer->file_get_contents('cross-tree-dst/root.txt'));
			$this->assertSame('leaf-payload', $peer->file_get_contents('cross-tree-dst/sub/leaf.txt'));
		} finally {
			$peer->rmdir('cross-tree-dst');
			$this->instance->rmdir('cross-tree');
		}
	}

	public function testSameObjectCopyReturnsTrue(): void {
		$this->requireCrossMountConfig();
		$peer = new AmazonS3($this->config);
		try {
			$this->arrangeSourceFile('same-object.txt', 'unchanged');

			$result = $peer->copyFromStorage($this->instance, 'same-object.txt', 'same-object.txt');
			$this->assertTrue($result);
			$this->assertSame('unchanged', $this->instance->file_get_contents('same-object.txt'));
		} finally {
			$this->instance->unlink('same-object.txt');
		}
	}

	public function testSameObjectMoveReturnsFalseAndLogsWarning(): void {
		$this->requireCrossMountConfig();
		$peer = new AmazonS3($this->config);
		try {
			$this->arrangeSourceFile('same-object-move.txt', 'preserved');

			$result = $peer->moveFromStorage($this->instance, 'same-object-move.txt', 'same-object-move.txt');
			$this->assertFalse($result);
			$this->assertSame('preserved', $this->instance->file_get_contents('same-object-move.txt'), 'source object must not be destroyed');
		} finally {
			$this->instance->unlink('same-object-move.txt');
		}
	}

	public function testFeatureFlagOffFallsBackToStreamCopy(): void {
		$this->requireCrossMountConfig();
		// AmazonS3 snapshots the flag in its constructor. Rebuild both storages after flipping.
		Server::get(IAppConfig::class)->setValueBool('files_external', ConfigLexicon::AMAZONS3_SERVER_SIDE_COPY, false);
		$sourceOff = new AmazonS3($this->config);
		$peer = $this->newPeerStorage();

		try {
			$peer->getScanner()->scan('', Scanner::SCAN_SHALLOW);
			$this->arrangeSourceFile('flag-off.txt', 'streamed', $sourceOff);

			$result = $peer->copyFromStorage($sourceOff, 'flag-off.txt', 'flag-off.txt');
			$this->assertTrue($result, 'streamed fallback must still copy the object');
			$this->assertSame('streamed', $peer->file_get_contents('flag-off.txt'));
		} finally {
			$peer->unlink('flag-off.txt');
			$sourceOff->unlink('flag-off.txt');
		}
	}

	public function testPreserveMtimeTrueFallsBackToStreamCopy(): void {
		$peer = $this->newPeerStorage();
		try {
			$peer->getScanner()->scan('', Scanner::SCAN_SHALLOW);
			$this->arrangeSourceFile('preserve-mtime.txt', 'mtime-preserved');

			$result = $peer->copyFromStorage($this->instance, 'preserve-mtime.txt', 'preserve-mtime.txt', true);
			$this->assertTrue($result);
			$this->assertSame('mtime-preserved', $peer->file_get_contents('preserve-mtime.txt'));
		} finally {
			$peer->unlink('preserve-mtime.txt');
			$this->instance->unlink('preserve-mtime.txt');
		}
	}

	public function testFailureLimitStopsFastPathAfterFourExceptions(): void {
		$this->requireCrossMountConfig();
		$peer = $this->newPeerStorage();
		try {
			$peer->getScanner()->scan('', Scanner::SCAN_SHALLOW);
			$this->arrangeSourceFile('breaker.txt', 'via-fallback');

			$counter = new ReflectionProperty(AmazonS3::class, 'serverSideCopyFailureCounter');
			$limit = (new ReflectionClassConstant(AmazonS3::class, 'SERVER_SIDE_COPY_FAILURE_LIMIT'))->getValue();
			$identityKey = (new ReflectionMethod(AmazonS3::class, 'endpointIdentityKey'))->invoke($peer);
			$counter->setValue(null, [$identityKey => $limit]);

			$result = $peer->copyFromStorage($this->instance, 'breaker.txt', 'breaker.txt');
			$this->assertTrue($result, 'fallback stream copy must still succeed while breaker is tripped');
			$this->assertSame('via-fallback', $peer->file_get_contents('breaker.txt'));
			$this->assertSame($limit, $counter->getValue()[$identityKey] ?? 0, 'counter must not reset while tripped');
		} finally {
			$peer->unlink('breaker.txt');
			$this->instance->unlink('breaker.txt');
		}
	}
}
