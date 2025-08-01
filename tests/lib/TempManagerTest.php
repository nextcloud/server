<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use bantu\IniGetWrapper\IniGetWrapper;
use OC\TempManager;
use OCP\Files;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class TempManagerTest extends \Test\TestCase {
	protected $baseDir = null;

	protected function setUp(): void {
		parent::setUp();

		$this->baseDir = $this->getManager()->getTempBaseDir() . $this->getUniqueID('/oc_tmp_test');
		if (!is_dir($this->baseDir)) {
			mkdir($this->baseDir);
		}
	}

	protected function tearDown(): void {
		if ($this->baseDir !== null) {
			Files::rmdirr($this->baseDir);
		}
		$this->baseDir = null;
		parent::tearDown();
	}

	/**
	 * @param ?LoggerInterface $logger
	 * @param ?IConfig $config
	 * @return \OC\TempManager
	 */
	protected function getManager($logger = null, $config = null) {
		if (!$logger) {
			$logger = $this->createMock(LoggerInterface::class);
		}
		if (!$config) {
			$config = $this->createMock(IConfig::class);
			$config->method('getSystemValue')
				->with('tempdirectory', null)
				->willReturn('/tmp');
		}
		$iniGetWrapper = $this->createMock(IniGetWrapper::class);
		$manager = new TempManager($logger, $config, $iniGetWrapper);
		if ($this->baseDir) {
			$manager->overrideTempBaseDir($this->baseDir);
		}
		return $manager;
	}

	public function testGetFile(): void {
		$manager = $this->getManager();
		$file = $manager->getTemporaryFile('txt');
		$this->assertStringEndsWith('.txt', $file);
		$this->assertTrue(is_file($file));
		$this->assertTrue(is_writable($file));

		file_put_contents($file, 'bar');
		$this->assertEquals('bar', file_get_contents($file));
	}

	public function testGetFolder(): void {
		$manager = $this->getManager();
		$folder = $manager->getTemporaryFolder();
		$this->assertStringEndsWith('/', $folder);
		$this->assertTrue(is_dir($folder));
		$this->assertTrue(is_writable($folder));

		file_put_contents($folder . 'foo.txt', 'bar');
		$this->assertEquals('bar', file_get_contents($folder . 'foo.txt'));
	}

	public function testCleanFiles(): void {
		$manager = $this->getManager();
		$file1 = $manager->getTemporaryFile('txt');
		$file2 = $manager->getTemporaryFile('txt');
		$this->assertTrue(file_exists($file1));
		$this->assertTrue(file_exists($file2));

		$manager->clean();

		$this->assertFalse(file_exists($file1));
		$this->assertFalse(file_exists($file2));
	}

	public function testCleanFolder(): void {
		$manager = $this->getManager();
		$folder1 = $manager->getTemporaryFolder();
		$folder2 = $manager->getTemporaryFolder();
		touch($folder1 . 'foo.txt');
		touch($folder1 . 'bar.txt');
		$this->assertTrue(file_exists($folder1));
		$this->assertTrue(file_exists($folder2));
		$this->assertTrue(file_exists($folder1 . 'foo.txt'));
		$this->assertTrue(file_exists($folder1 . 'bar.txt'));

		$manager->clean();

		$this->assertFalse(file_exists($folder1));
		$this->assertFalse(file_exists($folder2));
		$this->assertFalse(file_exists($folder1 . 'foo.txt'));
		$this->assertFalse(file_exists($folder1 . 'bar.txt'));
	}

	public function testCleanOld(): void {
		$manager = $this->getManager();
		$oldFile = $manager->getTemporaryFile('txt');
		$newFile = $manager->getTemporaryFile('txt');
		$folder = $manager->getTemporaryFolder();
		$nonOcFile = $this->baseDir . '/foo.txt';
		file_put_contents($nonOcFile, 'bar');

		$past = time() - 2 * 3600;
		touch($oldFile, $past);
		touch($folder, $past);
		touch($nonOcFile, $past);

		$manager2 = $this->getManager();
		$manager2->cleanOld();
		$this->assertFalse(file_exists($oldFile));
		$this->assertFalse(file_exists($folder));
		$this->assertTrue(file_exists($nonOcFile));
		$this->assertTrue(file_exists($newFile));
	}

	public function testLogCantCreateFile(): void {
		$this->markTestSkipped('TODO: Disable because fails on drone');

		$logger = $this->createMock(LoggerInterface::class);
		$manager = $this->getManager($logger);
		chmod($this->baseDir, 0500);
		$logger->expects($this->once())
			->method('warning')
			->with($this->stringContains('Can not create a temporary file in directory'));
		$this->assertFalse($manager->getTemporaryFile('txt'));
	}

	public function testLogCantCreateFolder(): void {
		$this->markTestSkipped('TODO: Disable because fails on drone');

		$logger = $this->createMock(LoggerInterface::class);
		$manager = $this->getManager($logger);
		chmod($this->baseDir, 0500);
		$logger->expects($this->once())
			->method('warning')
			->with($this->stringContains('Can not create a temporary folder in directory'));
		$this->assertFalse($manager->getTemporaryFolder());
	}

	public function testGenerateTemporaryPathWithPostfix(): void {
		$logger = $this->createMock(LoggerInterface::class);
		$tmpManager = self::invokePrivate(
			$this->getManager($logger),
			'generateTemporaryPath',
			['postfix']
		);

		$this->assertStringEndsWith('.postfix', $tmpManager);
	}

	public function testGenerateTemporaryPathTraversal(): void {
		$logger = $this->createMock(LoggerInterface::class);
		$tmpManager = self::invokePrivate(
			$this->getManager($logger),
			'generateTemporaryPath',
			['../Traversal\\../FileName']
		);

		$this->assertStringEndsNotWith('./Traversal\\../FileName', $tmpManager);
		$this->assertStringEndsWith('.Traversal..FileName', $tmpManager);
	}

	public function testGetTempBaseDirFromConfig(): void {
		$dir = $this->getManager()->getTemporaryFolder();

		$config = $this->createMock(IConfig::class);
		$config->expects($this->once())
			->method('getSystemValue')
			->with('tempdirectory', null)
			->willReturn($dir);

		$this->baseDir = null; // prevent override
		$tmpManager = $this->getManager(null, $config);

		$this->assertEquals($dir, $tmpManager->getTempBaseDir());
	}
}
