<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_External\Tests\Storage;

use OCA\Files_External\Lib\Storage\AmazonS3;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class AmazonS3PrefixUnitTest extends TestCase {
	private function makeStorage(array $params = []): AmazonS3 {
		return new AmazonS3(array_merge([
			'bucket' => 'test-bucket',
			'key' => 'test-key',
			'secret' => 'test-secret',
			'region' => 'us-east-1',
			'hostname' => 's3.us-east-1.amazonaws.com',
		], $params));
	}

	private function getPrefix(AmazonS3 $storage): string {
		$ref = new \ReflectionProperty(AmazonS3::class, 'prefix');
		$ref->setAccessible(true);
		return $ref->getValue($storage);
	}

	private function callAddPrefix(AmazonS3 $storage, string $path): string {
		$ref = new \ReflectionMethod(AmazonS3::class, 'addPrefix');
		$ref->setAccessible(true);
		return $ref->invoke($storage, $path);
	}

	private function callStripPrefix(AmazonS3 $storage, string $key): string {
		$ref = new \ReflectionMethod(AmazonS3::class, 'stripPrefix');
		$ref->setAccessible(true);
		return $ref->invoke($storage, $key);
	}

	public static function prefixNormalizationProvider(): array {
		return [
			'empty string stays empty' => ['', ''],
			'bare name gets trailing slash' => ['nextcloud', 'nextcloud/'],
			'already has trailing slash' => ['nextcloud/', 'nextcloud/'],
			'leading slash is stripped' => ['/nextcloud/', 'nextcloud/'],
			'nested path' => ['a/b/c', 'a/b/c/'],
			'nested path with slashes' => ['/a/b/c/', 'a/b/c/'],
			'whitespace is trimmed' => ['  nextcloud  ', 'nextcloud/'],
		];
	}

	#[DataProvider('prefixNormalizationProvider')]
	public function testPrefixNormalization(string $input, string $expected): void {
		$storage = $this->makeStorage(['prefix' => $input]);
		$this->assertSame($expected, $this->getPrefix($storage));
	}

	public function testNoPrefixParameterGivesEmptyPrefix(): void {
		$storage = $this->makeStorage();
		$this->assertSame('', $this->getPrefix($storage));
	}

	public static function addPrefixProvider(): array {
		return [
			['nextcloud/', 'path/to/file', 'nextcloud/path/to/file'],
			['nextcloud/', '',             'nextcloud/'],
			['nextcloud/', 'dir/',         'nextcloud/dir/'],
			['a/b/',       'file.txt',     'a/b/file.txt'],
			['',           'path/to/file', 'path/to/file'],
		];
	}

	#[DataProvider('addPrefixProvider')]
	public function testAddPrefix(string $prefix, string $path, string $expected): void {
		$storage = $this->makeStorage(['prefix' => $prefix]);
		$this->assertSame($expected, $this->callAddPrefix($storage, $path));
	}

	public static function stripPrefixProvider(): array {
		return [
			'strips matching prefix' => ['nextcloud/', 'nextcloud/path/to/file', 'path/to/file'],
			'strips to empty string' => ['nextcloud/', 'nextcloud/',              ''],
			'strips with trailing slash on key' => ['nextcloud/', 'nextcloud/dir/',          'dir/'],
			'strips nested prefix' => ['a/b/',       'a/b/file.txt',            'file.txt'],
			'empty prefix is passthrough' => ['',           'path/to/file',            'path/to/file'],
			'non-matching key returned unchanged' => ['nextcloud/', 'other/path',              'other/path'],
		];
	}

	#[DataProvider('stripPrefixProvider')]
	public function testStripPrefix(string $prefix, string $key, string $expected): void {
		$storage = $this->makeStorage(['prefix' => $prefix]);
		$this->assertSame($expected, $this->callStripPrefix($storage, $key));
	}

	public function testAddThenStripIsIdentity(): void {
		$storage = $this->makeStorage(['prefix' => 'myns/']);
		foreach (['file.txt', 'dir/file.txt', 'a/b/c/d.png', ''] as $path) {
			$this->assertSame(
				$path,
				$this->callStripPrefix($storage, $this->callAddPrefix($storage, $path)),
				"roundtrip failed for path: $path"
			);
		}
	}

	public function testStorageIdDiffersWithDifferentPrefix(): void {
		$base = ['bucket' => 'b', 'key' => 'k', 'secret' => 's', 'region' => 'us-east-1', 'hostname' => 'h'];
		$withoutPrefix = new AmazonS3($base);
		$withPrefix = new AmazonS3($base + ['prefix' => 'myns/']);
		$withOtherPrefix = new AmazonS3($base + ['prefix' => 'other/']);

		$this->assertNotSame($withoutPrefix->getId(), $withPrefix->getId());
		$this->assertNotSame($withPrefix->getId(), $withOtherPrefix->getId());
		$this->assertNotSame($withoutPrefix->getId(), $withOtherPrefix->getId());
	}

	public function testStorageIdSameForEquivalentPrefixForms(): void {
		$base = ['bucket' => 'b', 'key' => 'k', 'secret' => 's', 'region' => 'us-east-1', 'hostname' => 'h'];
		$bare = new AmazonS3($base + ['prefix' => 'myns']);
		$trailing = new AmazonS3($base + ['prefix' => 'myns/']);
		$leading = new AmazonS3($base + ['prefix' => '/myns/']);

		$this->assertSame($bare->getId(), $trailing->getId());
		$this->assertSame($trailing->getId(), $leading->getId());
	}
}
