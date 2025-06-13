<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Storage\Wrapper;

use OC\Files\Storage\Temporary;
use OC\Files\Storage\Wrapper\Encoding;

class EncodingTest extends \Test\Files\Storage\Storage {
	public const NFD_NAME = 'ümlaut';
	public const NFC_NAME = 'ümlaut';

	/**
	 * @var \OC\Files\Storage\Temporary
	 */
	private $sourceStorage;

	protected function setUp(): void {
		parent::setUp();
		$this->sourceStorage = new Temporary([]);
		$this->instance = new Encoding([
			'storage' => $this->sourceStorage
		]);
	}

	protected function tearDown(): void {
		$this->sourceStorage->cleanUp();
		parent::tearDown();
	}

	public static function directoryProvider(): array {
		$a = parent::directoryProvider();
		$a[] = [self::NFC_NAME];
		return $a;
	}

	public static function fileNameProvider(): array {
		$a = parent::fileNameProvider();
		$a[] = [self::NFD_NAME . '.txt'];
		return $a;
	}

	public static function copyAndMoveProvider(): array {
		$a = parent::copyAndMoveProvider();
		$a[] = [self::NFD_NAME . '.txt', self::NFC_NAME . '-renamed.txt'];
		return $a;
	}

	public static function accessNameProvider(): array {
		return [
			[self::NFD_NAME],
			[self::NFC_NAME],
		];
	}

	/**
	 * @dataProvider accessNameProvider
	 */
	public function testFputEncoding($accessName): void {
		$this->sourceStorage->file_put_contents(self::NFD_NAME, 'bar');
		$this->assertEquals('bar', $this->instance->file_get_contents($accessName));
	}

	/**
	 * @dataProvider accessNameProvider
	 */
	public function testFopenReadEncoding($accessName): void {
		$this->sourceStorage->file_put_contents(self::NFD_NAME, 'bar');
		$fh = $this->instance->fopen($accessName, 'r');
		$data = fgets($fh);
		fclose($fh);
		$this->assertEquals('bar', $data);
	}

	/**
	 * @dataProvider accessNameProvider
	 */
	public function testFopenOverwriteEncoding($accessName): void {
		$this->sourceStorage->file_put_contents(self::NFD_NAME, 'bar');
		$fh = $this->instance->fopen($accessName, 'w');
		$data = fputs($fh, 'test');
		fclose($fh);
		$data = $this->sourceStorage->file_get_contents(self::NFD_NAME);
		$this->assertEquals('test', $data);
		$this->assertFalse($this->sourceStorage->file_exists(self::NFC_NAME));
	}

	/**
	 * @dataProvider accessNameProvider
	 */
	public function testFileExistsEncoding($accessName): void {
		$this->sourceStorage->file_put_contents(self::NFD_NAME, 'bar');
		$this->assertTrue($this->instance->file_exists($accessName));
	}

	/**
	 * @dataProvider accessNameProvider
	 */
	public function testUnlinkEncoding($accessName): void {
		$this->sourceStorage->file_put_contents(self::NFD_NAME, 'bar');
		$this->assertTrue($this->instance->unlink($accessName));
		$this->assertFalse($this->sourceStorage->file_exists(self::NFC_NAME));
		$this->assertFalse($this->sourceStorage->file_exists(self::NFD_NAME));
	}

	public function testNfcHigherPriority(): void {
		$this->sourceStorage->file_put_contents(self::NFC_NAME, 'nfc');
		$this->sourceStorage->file_put_contents(self::NFD_NAME, 'nfd');
		$this->assertEquals('nfc', $this->instance->file_get_contents(self::NFC_NAME));
	}

	public static function encodedDirectoriesProvider(): array {
		return [
			[self::NFD_NAME, self::NFC_NAME],
			[self::NFD_NAME . '/' . self::NFD_NAME, self::NFC_NAME . '/' . self::NFC_NAME],
			[self::NFD_NAME . '/' . self::NFC_NAME . '/' . self::NFD_NAME, self::NFC_NAME . '/' . self::NFC_NAME . '/' . self::NFC_NAME],
		];
	}

	/**
	 * @dataProvider encodedDirectoriesProvider
	 */
	public function testOperationInsideDirectory($sourceDir, $accessDir): void {
		$this->sourceStorage->mkdir($sourceDir);
		$this->instance->file_put_contents($accessDir . '/test.txt', 'bar');
		$this->assertTrue($this->instance->file_exists($accessDir . '/test.txt'));
		$this->assertEquals('bar', $this->instance->file_get_contents($accessDir . '/test.txt'));

		$this->sourceStorage->file_put_contents($sourceDir . '/' . self::NFD_NAME, 'foo');
		$this->assertTrue($this->instance->file_exists($accessDir . '/' . self::NFC_NAME));
		$this->assertEquals('foo', $this->instance->file_get_contents($accessDir . '/' . self::NFC_NAME));

		// try again to make it use cached path
		$this->assertEquals('bar', $this->instance->file_get_contents($accessDir . '/test.txt'));
		$this->assertTrue($this->instance->file_exists($accessDir . '/test.txt'));
		$this->assertEquals('foo', $this->instance->file_get_contents($accessDir . '/' . self::NFC_NAME));
		$this->assertTrue($this->instance->file_exists($accessDir . '/' . self::NFC_NAME));
	}

	public function testCacheExtraSlash(): void {
		$this->sourceStorage->file_put_contents(self::NFD_NAME, 'foo');
		$this->assertEquals(3, $this->instance->file_put_contents(self::NFC_NAME, 'bar'));
		$this->assertEquals('bar', $this->instance->file_get_contents(self::NFC_NAME));
		clearstatcache();
		$this->assertEquals(5, $this->instance->file_put_contents('/' . self::NFC_NAME, 'baric'));
		$this->assertEquals('baric', $this->instance->file_get_contents(self::NFC_NAME));
		clearstatcache();
		$this->assertEquals(8, $this->instance->file_put_contents('/' . self::NFC_NAME, 'barbaric'));
		$this->assertEquals('barbaric', $this->instance->file_get_contents('//' . self::NFC_NAME));
	}

	public static function sourceAndTargetDirectoryProvider(): array {
		return [
			[self::NFC_NAME . '1', self::NFC_NAME . '2'],
			[self::NFD_NAME . '1', self::NFC_NAME . '2'],
			[self::NFC_NAME . '1', self::NFD_NAME . '2'],
			[self::NFD_NAME . '1', self::NFD_NAME . '2'],
		];
	}

	/**
	 * @dataProvider sourceAndTargetDirectoryProvider
	 */
	public function testCopyAndMoveEncodedFolder($sourceDir, $targetDir): void {
		$this->sourceStorage->mkdir($sourceDir);
		$this->sourceStorage->mkdir($targetDir);
		$this->sourceStorage->file_put_contents($sourceDir . '/test.txt', 'bar');
		$this->assertTrue($this->instance->copy(self::NFC_NAME . '1/test.txt', self::NFC_NAME . '2/test.txt'));

		$this->assertTrue($this->instance->file_exists(self::NFC_NAME . '1/test.txt'));
		$this->assertTrue($this->instance->file_exists(self::NFC_NAME . '2/test.txt'));
		$this->assertEquals('bar', $this->instance->file_get_contents(self::NFC_NAME . '2/test.txt'));

		$this->assertTrue($this->instance->rename(self::NFC_NAME . '1/test.txt', self::NFC_NAME . '2/test2.txt'));
		$this->assertFalse($this->instance->file_exists(self::NFC_NAME . '1/test.txt'));
		$this->assertTrue($this->instance->file_exists(self::NFC_NAME . '2/test2.txt'));

		$this->assertEquals('bar', $this->instance->file_get_contents(self::NFC_NAME . '2/test2.txt'));
	}

	/**
	 * @dataProvider sourceAndTargetDirectoryProvider
	 */
	public function testCopyAndMoveFromStorageEncodedFolder($sourceDir, $targetDir): void {
		$this->sourceStorage->mkdir($sourceDir);
		$this->sourceStorage->mkdir($targetDir);
		$this->sourceStorage->file_put_contents($sourceDir . '/test.txt', 'bar');
		$this->assertTrue($this->instance->copyFromStorage($this->instance, self::NFC_NAME . '1/test.txt', self::NFC_NAME . '2/test.txt'));

		$this->assertTrue($this->instance->file_exists(self::NFC_NAME . '1/test.txt'));
		$this->assertTrue($this->instance->file_exists(self::NFC_NAME . '2/test.txt'));
		$this->assertEquals('bar', $this->instance->file_get_contents(self::NFC_NAME . '2/test.txt'));

		$this->assertTrue($this->instance->moveFromStorage($this->instance, self::NFC_NAME . '1/test.txt', self::NFC_NAME . '2/test2.txt'));
		$this->assertFalse($this->instance->file_exists(self::NFC_NAME . '1/test.txt'));
		$this->assertTrue($this->instance->file_exists(self::NFC_NAME . '2/test2.txt'));

		$this->assertEquals('bar', $this->instance->file_get_contents(self::NFC_NAME . '2/test2.txt'));
	}

	public function testNormalizedDirectoryEntriesOpenDir(): void {
		$this->sourceStorage->mkdir('/test');
		$this->sourceStorage->mkdir('/test/' . self::NFD_NAME);

		$this->assertTrue($this->instance->file_exists('/test/' . self::NFC_NAME));
		$this->assertTrue($this->instance->file_exists('/test/' . self::NFD_NAME));

		$dh = $this->instance->opendir('/test');
		$content = [];
		while (($file = readdir($dh)) !== false) {
			if ($file != '.' and $file != '..') {
				$content[] = $file;
			}
		}

		$this->assertCount(1, $content);
		$this->assertEquals(self::NFC_NAME, $content[0]);
	}

	public function testNormalizedDirectoryEntriesGetDirectoryContent(): void {
		$this->sourceStorage->mkdir('/test');
		$this->sourceStorage->mkdir('/test/' . self::NFD_NAME);

		$this->assertTrue($this->instance->file_exists('/test/' . self::NFC_NAME));
		$this->assertTrue($this->instance->file_exists('/test/' . self::NFD_NAME));

		$content = iterator_to_array($this->instance->getDirectoryContent('/test'));
		$this->assertCount(1, $content);
		$this->assertEquals(self::NFC_NAME, $content[0]['name']);
	}

	public function testNormalizedGetMetaData(): void {
		$this->sourceStorage->mkdir('/test');
		$this->sourceStorage->mkdir('/test/' . self::NFD_NAME);

		$entry = $this->instance->getMetaData('/test/' . self::NFC_NAME);
		$this->assertEquals(self::NFC_NAME, $entry['name']);

		$entry = $this->instance->getMetaData('/test/' . self::NFD_NAME);
		$this->assertEquals(self::NFC_NAME, $entry['name']);
	}

	/**
	 * Regression test of https://github.com/nextcloud/server/issues/50431
	 */
	public function testNoMetadata() {
		$this->assertNull($this->instance->getMetaData('/test/null'));
	}

}
