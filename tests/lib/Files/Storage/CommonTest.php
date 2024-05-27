<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\Storage;

use OC\Files\Storage\Wrapper\Jail;
use OC\Files\Storage\Wrapper\Wrapper;
use OCP\Files\InvalidPathException;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class CommonTest
 *
 * @group DB
 *
 * @package Test\Files\Storage
 */
class CommonTest extends Storage {
	/**
	 * @var string tmpDir
	 */
	private $tmpDir;

	private array $invalidCharsBackup;

	protected function setUp(): void {
		parent::setUp();

		$this->tmpDir = \OC::$server->getTempManager()->getTemporaryFolder();
		$this->instance = new \OC\Files\Storage\CommonTest(['datadir' => $this->tmpDir]);
		$this->invalidCharsBackup = \OC::$server->getConfig()->getSystemValue('forbidden_chars', []);
	}

	protected function tearDown(): void {
		\OC_Helper::rmdirr($this->tmpDir);
		\OC::$server->getConfig()->setSystemValue('forbidden_chars', $this->invalidCharsBackup);
		parent::tearDown();
	}

	/**
	 * @dataProvider dataVerifyPath
	 */
	public function testVerifyPath(string $filename, array $additionalChars, bool $throws) {
		/** @var \OC\Files\Storage\CommonTest|MockObject $instance */
		$instance = $this->getMockBuilder(\OC\Files\Storage\CommonTest::class)
			->onlyMethods(['copyFromStorage', 'rmdir', 'unlink'])
			->setConstructorArgs([['datadir' => $this->tmpDir]])
			->getMock();
		$instance->method('copyFromStorage')
			->willThrowException(new \Exception('copy'));

		\OC::$server->getConfig()->setSystemValue('forbidden_chars', $additionalChars);

		if ($throws) {
			$this->expectException(InvalidPathException::class);
		} else {
			$this->expectNotToPerformAssertions();
		}
		$instance->verifyPath('/', $filename);
	}

	public function dataVerifyPath(): array {
		return [
			// slash is always forbidden
			'invalid slash' => ['a/b.txt', [], true],
			// backslash is also forbidden
			'invalid backslash' => ['a\\b.txt', [], true],
			// by default colon is not forbidden
			'valid name' => ['a: b.txt', [], false],
			// colon can be added to the list of forbidden character
			'invalid custom character' => ['a: b.txt', [':'], true],
			// make sure to not split the list entries as they migh contain Unicode sequences
			// in this example the "face in clouds" emoji contains the clouds emoji so only having clouds is ok
			'valid unicode sequence' => ['🌫️.txt', ['😶‍🌫️'], false],
			// This is the reverse: clouds are forbidden -> so is also the face in the clouds emoji
			'valid unicode sequence' => ['😶‍🌫️.txt', ['🌫️'], true],
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
