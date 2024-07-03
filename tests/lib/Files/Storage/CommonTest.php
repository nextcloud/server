<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Storage;

use OC\Files\Storage\Wrapper\Jail;
use OC\Files\Storage\Wrapper\Wrapper;
use OCP\Files\EmptyFileNameException;
use OCP\Files\FileNameTooLongException;
use OCP\Files\InvalidCharacterInPathException;
use OCP\Files\InvalidPathException;
use OCP\Files\ReservedWordException;
use OCP\ITempManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class CommonTest
 *
 * @group DB
 *
 * @package Test\Files\Storage
 * @backupStaticAttributes enabled
 */
class CommonTest extends Storage {
	/**
	 * @var string tmpDir
	 */
	private $tmpDir;

	protected function setUp(): void {
		parent::setUp();

		$this->tmpDir = \OCP\Server::get(ITempManager::class)->getTemporaryFolder();
		$this->instance = new \OC\Files\Storage\CommonTest(['datadir' => $this->tmpDir]);
	}

	protected function tearDown(): void {
		\OC_Helper::rmdirr($this->tmpDir);
		parent::tearDown();
	}

	/**
	 * @dataProvider dataVerifyPath
	 */
	public function testVerifyPath(string $filename, ?string $exception, bool $throws) {
		/** @var \OC\Files\Storage\CommonTest|MockObject $instance */
		$instance = $this->getMockBuilder(\OC\Files\Storage\CommonTest::class)
			->onlyMethods(['copyFromStorage', 'rmdir', 'unlink'])
			->setConstructorArgs([['datadir' => $this->tmpDir]])
			->getMock();
		$instance->method('copyFromStorage')
			->willThrowException(new \Exception('copy'));

		if ($exception !== null) {
			$this->filenameValidator->expects($this->any())
				->method('validateFilename')
				->with($filename)
				->willThrowException(new $exception());
		}

		if ($throws) {
			$this->expectException(InvalidPathException::class);
		} else {
			$this->expectNotToPerformAssertions();
		}
		$instance->verifyPath('/', $filename);
	}

	public function dataVerifyPath(): array {
		return [
			['a/b.txt', InvalidCharacterInPathException::class, true],
			['', EmptyFileNameException::class, true],
			['verylooooooong.txt', FileNameTooLongException::class, true],
			['COM1', ReservedWordException::class, true],
			['a/b.txt', InvalidCharacterInPathException::class, true],

			['a: b.txt', null, false],
			['🌫️.txt', null, false],
		];
	}

	public function testMoveFromStorageWrapped() {
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

	public function testMoveFromStorageJailed() {
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

	public function testMoveFromStorageNestedJail() {
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
}
