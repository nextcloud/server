<?php

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Storage;

use OC\Files\Storage\Wrapper\Jail;
use OC\Files\Storage\Wrapper\Wrapper;
use OCP\Files;
use OCP\Files\IFilenameValidator;
use OCP\Files\InvalidCharacterInPathException;
use OCP\Files\InvalidPathException;
use OCP\ITempManager;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class CommonTest
 *
 *
 * @package Test\Files\Storage
 */
#[\PHPUnit\Framework\Attributes\Group('DB')]
class CommonTest extends Storage {

	private string $tmpDir;
	private IFilenameValidator&MockObject $filenameValidator;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->filenameValidator = $this->createMock(IFilenameValidator::class);
		$this->overwriteService(IFilenameValidator::class, $this->filenameValidator);
		$this->tmpDir = Server::get(ITempManager::class)->getTemporaryFolder();
		$this->instance = new \OC\Files\Storage\CommonTest(['datadir' => $this->tmpDir]);
	}

	#[\Override]
	protected function tearDown(): void {
		Files::rmdirr($this->tmpDir);
		$this->restoreService(IFilenameValidator::class);
		parent::tearDown();
	}

	public function testVerifyPath(): void {
		$this->filenameValidator
			->expects($this->once())
			->method('validateFilename')
			->with('invalid:char.txt')
			->willThrowException(new InvalidCharacterInPathException());
		$this->expectException(InvalidPathException::class);

		$this->instance->verifyPath('/', 'invalid:char.txt');
	}

	public function testVerifyPathSucceed(): void {
		$this->filenameValidator
			->expects($this->once())
			->method('validateFilename')
			->with('valid-char.txt');

		$this->instance->verifyPath('/', 'valid-char.txt');
	}

	public function testMoveFromStorageWrapped(): void {
		/** @var \OC\Files\Storage\CommonTest|MockObject $instance */
		$instance = $this->getMockBuilder(\OC\Files\Storage\CommonTest::class)
			->onlyMethods(['copyFromStorage', 'rmdir', 'unlink'])
			->setConstructorArgs([['datadir' => $this->tmpDir]])
			->getMock();
		$instance->method('copyFromStorage')
			->willThrowException(new \Exception('copy'));

		$source = new Wrapper([
			'storage' => $instance,
		]);

		$instance->file_put_contents('foo.txt', 'bar');
		$instance->moveFromStorage($source, 'foo.txt', 'bar.txt');
		$this->assertTrue($instance->file_exists('bar.txt'));
	}

	public function testMoveFromStorageJailed(): void {
		/** @var \OC\Files\Storage\CommonTest|MockObject $instance */
		$instance = $this->getMockBuilder(\OC\Files\Storage\CommonTest::class)
			->onlyMethods(['copyFromStorage', 'rmdir', 'unlink'])
			->setConstructorArgs([['datadir' => $this->tmpDir]])
			->getMock();
		$instance->method('copyFromStorage')
			->willThrowException(new \Exception('copy'));

		$source = new Jail([
			'storage' => $instance,
			'root' => 'foo'
		]);
		$source = new Wrapper([
			'storage' => $source
		]);

		$instance->mkdir('foo');
		$instance->file_put_contents('foo/foo.txt', 'bar');
		$instance->moveFromStorage($source, 'foo.txt', 'bar.txt');
		$this->assertTrue($instance->file_exists('bar.txt'));
	}

	public function testMoveFromStorageNestedJail(): void {
		/** @var \OC\Files\Storage\CommonTest|MockObject $instance */
		$instance = $this->getMockBuilder(\OC\Files\Storage\CommonTest::class)
			->onlyMethods(['copyFromStorage', 'rmdir', 'unlink'])
			->setConstructorArgs([['datadir' => $this->tmpDir]])
			->getMock();
		$instance->method('copyFromStorage')
			->willThrowException(new \Exception('copy'));

		$source = new Jail([
			'storage' => $instance,
			'root' => 'foo'
		]);
		$source = new Wrapper([
			'storage' => $source
		]);
		$source = new Jail([
			'storage' => $source,
			'root' => 'bar'
		]);
		$source = new Wrapper([
			'storage' => $source
		]);

		$instance->mkdir('foo');
		$instance->mkdir('foo/bar');
		$instance->file_put_contents('foo/bar/foo.txt', 'bar');
		$instance->moveFromStorage($source, 'foo.txt', 'bar.txt');
		$this->assertTrue($instance->file_exists('bar.txt'));
	}

	public function testCopyFileOntoItselfIsSuccessfulNoOp(): void {
		$this->instance->file_put_contents('same.txt', 'contents');

		$this->assertTrue($this->instance->copy('same.txt', 'same.txt'));
		$this->assertSame('contents', $this->instance->file_get_contents('same.txt'));
	}

	public function testCopyDirectoryOntoItselfIsSuccessfulNoOp(): void {
		$this->instance->mkdir('same');
		$this->instance->file_put_contents('same/file.txt', 'contents');

		$this->assertTrue($this->instance->copy('same', 'same'));
		$this->assertSame('contents', $this->instance->file_get_contents('same/file.txt'));
	}

	public function testCopyMissingPathOntoItselfFails(): void {
		$this->assertFalse($this->instance->copy('missing', 'missing'));
	}

	public function testCopyReturnsFalseWhenDirectoryCannotBeOpened(): void {
		$this->instance->mkdir('source');

		$instance = $this->getMockBuilder(\OC\Files\Storage\CommonTest::class)
			->onlyMethods(['opendir'])
			->setConstructorArgs([['datadir' => $this->tmpDir]])
			->getMock();
		$instance->expects($this->once())
			->method('opendir')
			->with('source')
			->willReturn(false);

		$this->assertFalse($instance->copy('source', 'target'));
	}

	public function testCopyClosesSourceStreamWhenTargetCannotBeOpened(): void {
		$sourceStream = fopen('php://temp', 'r+');
		fwrite($sourceStream, 'contents');
		rewind($sourceStream);

		$instance = $this->getMockBuilder(\OC\Files\Storage\CommonTest::class)
			->onlyMethods(['fopen'])
			->setConstructorArgs([['datadir' => $this->tmpDir]])
			->getMock();
		$instance->expects($this->exactly(2))
			->method('fopen')
			->willReturnOnConsecutiveCalls($sourceStream, false);

			$this->assertFalse($instance->copy('source.txt', 'target.txt'));
			$this->assertFalse(is_resource($sourceStream));
	}
}
