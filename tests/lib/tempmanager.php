<?php

/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

use OC\Log;

class NullLogger extends Log {
	public function __construct($logger = null) {
		//disable original constructor
	}

	public function log($level, $message, array $context = array()) {
		//noop
	}
}

class TempManager extends \PHPUnit_Framework_TestCase {
	protected $baseDir;

	public function setUp() {
		$this->baseDir = get_temp_dir() . '/oc_tmp_test';
		if (!is_dir($this->baseDir)) {
			mkdir($this->baseDir);
		}
	}

	public function tearDown() {
		\OC_Helper::rmdirr($this->baseDir);
	}

	/**
	 * @param \Psr\Log\LoggerInterface $logger
	 * @return \OC\TempManager
	 */
	protected function getManager($logger = null) {
		if (!$logger) {
			$logger = new NullLogger();
		}
		return new \OC\TempManager($this->baseDir, $logger);
	}

	public function testGetFile() {
		$manager = $this->getManager();
		$file = $manager->getTemporaryFile('.txt');
		$this->assertStringEndsWith('.txt', $file);
		$this->assertTrue(is_file($file));
		$this->assertTrue(is_writable($file));

		file_put_contents($file, 'bar');
		$this->assertEquals('bar', file_get_contents($file));
	}

	public function testGetFolder() {
		$manager = $this->getManager();
		$folder = $manager->getTemporaryFolder();
		$this->assertStringEndsWith('/', $folder);
		$this->assertTrue(is_dir($folder));
		$this->assertTrue(is_writable($folder));

		file_put_contents($folder . 'foo.txt', 'bar');
		$this->assertEquals('bar', file_get_contents($folder . 'foo.txt'));
	}

	public function testCleanFiles() {
		$manager = $this->getManager();
		$file1 = $manager->getTemporaryFile('.txt');
		$file2 = $manager->getTemporaryFile('.txt');
		$this->assertTrue(file_exists($file1));
		$this->assertTrue(file_exists($file2));

		$manager->clean();

		$this->assertFalse(file_exists($file1));
		$this->assertFalse(file_exists($file2));
	}

	public function testCleanFolder() {
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

	public function testCleanOld() {
		$manager = $this->getManager();
		$oldFile = $manager->getTemporaryFile('.txt');
		$newFile = $manager->getTemporaryFile('.txt');
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

	public function testLogCantCreateFile() {
		$logger = $this->getMock('\Test\NullLogger');
		$manager = $this->getManager($logger);
		chmod($this->baseDir, 0500);
		$logger->expects($this->once())
			->method('warning')
			->with($this->stringContains('Can not create a temporary file in directory'));
		$this->assertFalse($manager->getTemporaryFile('.txt'));
	}

	public function testLogCantCreateFolder() {
		$logger = $this->getMock('\Test\NullLogger');
		$manager = $this->getManager($logger);
		chmod($this->baseDir, 0500);
		$logger->expects($this->once())
			->method('warning')
			->with($this->stringContains('Can not create a temporary folder in directory'));
		$this->assertFalse($manager->getTemporaryFolder());
	}
}
