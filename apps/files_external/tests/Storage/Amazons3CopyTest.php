<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_External\Tests\Storage;

use Aws\Command;
use Aws\S3\Exception\S3Exception;
use OCA\Files_External\Lib\Storage\AmazonS3;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

class Amazons3CopyTest extends \Test\TestCase {
	/**
	 * @param string[] $methods
	 * @return AmazonS3|MockObject
	 */
	private function getStorageMock(array $methods) {
		$storage = $this->getMockBuilder(AmazonS3::class)
			->disableOriginalConstructor()
			->onlyMethods($methods)
			->getMock();

		// The constructor is disabled, so the properties copy() relies on are uninitialised
		$this->invokePrivate($storage, 'initCaches');
		$this->invokePrivate($storage, 'storageClass', ['STANDARD']);
		// invokePrivate() cannot reach these two: $logger is private, so it is invisible on
		// the mock subclass, and 'test' resolves to the test() method before the property
		(new \ReflectionProperty(AmazonS3::class, 'logger'))->setValue($storage, new NullLogger());
		(new \ReflectionProperty(AmazonS3::class, 'test'))->setValue($storage, false);

		return $storage;
	}

	private function failingCopyException(): S3Exception {
		return new S3Exception('NotImplemented', new Command('CopyObject'));
	}

	/**
	 * A directory copy must report failure when copying one of its children fails.
	 */
	public function testCopyDirectoryReportsFailureWhenChildCopyFails(): void {
		$storage = $this->getStorageMock(['is_file', 'remove', 'mkdir', 'getDirectoryContent', 'copyObject']);

		$storage->method('is_file')->willReturn(false);
		$storage->method('remove')->willReturn(true);
		$storage->method('mkdir')->willReturn(true);
		$storage->method('getDirectoryContent')->willReturn(new \ArrayIterator([
			['name' => 'child.txt', 'mimetype' => 'text/plain'],
		]));
		$storage->method('copyObject')->willThrowException($this->failingCopyException());

		$this->assertFalse(
			$storage->copy('source', 'target'),
			'copy() must report failure when copying a child object fails'
		);
	}

	/**
	 * Renaming a directory must not remove the source when the copy failed.
	 */
	public function testRenameDirectoryKeepsSourceWhenCopyFails(): void {
		$storage = $this->getStorageMock(['is_file', 'remove', 'mkdir', 'getDirectoryContent', 'copyObject', 'rmdir', 'unlink']);

		$storage->method('is_file')->willReturn(false);
		$storage->method('remove')->willReturn(true);
		$storage->method('mkdir')->willReturn(true);
		$storage->method('getDirectoryContent')->willReturn(new \ArrayIterator([
			['name' => 'child.txt', 'mimetype' => 'text/plain'],
		]));
		$storage->method('copyObject')->willThrowException($this->failingCopyException());

		$storage->expects($this->never())->method('rmdir');
		$storage->expects($this->never())->method('unlink');

		$this->assertFalse(
			$storage->rename('source', 'target'),
			'rename() must report failure when the underlying copy failed'
		);
	}

	/**
	 * A failing grandchild must propagate through nested directories as well.
	 */
	public function testCopyDirectoryReportsFailureFromNestedDirectory(): void {
		$storage = $this->getStorageMock(['is_file', 'remove', 'mkdir', 'getDirectoryContent', 'copyObject']);

		$storage->method('is_file')->willReturn(false);
		$storage->method('remove')->willReturn(true);
		$storage->method('mkdir')->willReturn(true);
		$storage->method('getDirectoryContent')->willReturnCallback(
			function (string $directory): \Traversable {
				if ($directory === 'source') {
					return new \ArrayIterator([
						['name' => 'nested', 'mimetype' => \OC\Files\FileInfo::MIMETYPE_FOLDER],
					]);
				}
				return new \ArrayIterator([
					['name' => 'child.txt', 'mimetype' => 'text/plain'],
				]);
			}
		);
		$storage->method('copyObject')->willThrowException($this->failingCopyException());

		$this->assertFalse(
			$storage->copy('source', 'target'),
			'copy() must propagate a failure from a nested directory'
		);
	}

	/**
	 * The success path must keep working: a directory whose children all copy fine
	 * still reports true.
	 */
	public function testCopyDirectoryReportsSuccessWhenAllChildrenSucceed(): void {
		$storage = $this->getStorageMock(['is_file', 'remove', 'mkdir', 'getDirectoryContent', 'copyObject']);

		$storage->method('is_file')->willReturn(false);
		$storage->method('remove')->willReturn(true);
		$storage->method('mkdir')->willReturn(true);
		$storage->method('getDirectoryContent')->willReturn(new \ArrayIterator([
			['name' => 'child.txt', 'mimetype' => 'text/plain'],
		]));
		$storage->method('copyObject')->willReturn(null);

		$this->assertTrue(
			$storage->copy('source', 'target'),
			'copy() must still report success when every child copies fine'
		);
	}
}
