<?php
/**
 * Copyright (c) 2014 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Licensed under the MIT license:
 * http://opensource.org/licenses/MIT
 */

namespace Icewind\Streams\Tests;

use PHPUnit\Framework\TestCase;

class UrlCallbackTest extends TestCase {
	protected $tempDirs = [];

	protected function getTempDir() {
		$dir = sys_get_temp_dir() . '/streams_' . uniqid();
		mkdir($dir);
		$this->tempDirs[] = $dir;
		return $dir;
	}

	public function tearDown(): void {
		foreach ($this->tempDirs as $dir) {
			$this->rmdir($dir);
		}
	}

	protected function rmdir($path) {
		$directory = new \RecursiveDirectoryIterator($path);
		$iterator = new \RecursiveIteratorIterator($directory, \RecursiveIteratorIterator::CHILD_FIRST);
		/**
		 * @var \SplFileInfo $file
		 */
		foreach ($iterator as $file) {
			if (in_array($file->getBasename(), ['.', '..'])) {
				continue;
			} elseif ($file->isDir()) {
				rmdir($file->getPathname());
			} elseif ($file->isFile() || $file->isLink()) {
				unlink($file->getPathname());
			}
		}
	}

	public function testFOpenCallBack() {
		$called = false;
		$callback = function () use (&$called) {
			$called = true;
		};
		$path = \Icewind\Streams\UrlCallback::wrap('php://temp', $callback);
		$fh = fopen($path, 'r');
		fclose($fh);
		$this->assertTrue($called);
	}

	public function testOpenDirCallBack() {
		$called = false;
		$callback = function () use (&$called) {
			$called = true;
		};
		$path = \Icewind\Streams\UrlCallback::wrap($this->getTempDir(), null, $callback);
		$fh = opendir($path);
		closedir($fh);
		$this->assertTrue($called);
	}

	public function testMKDirCallBack() {
		$called = false;
		$callback = function () use (&$called) {
			$called = true;
		};
		$dir = $this->getTempDir() . '/test';
		$path = \Icewind\Streams\UrlCallback::wrap($dir, null, null, $callback);
		mkdir($path);
		$this->assertFileExists($dir);
		$this->assertTrue($called);
	}

	public function testRMDirCallBack() {
		$called = false;
		$callback = function () use (&$called) {
			$called = true;
		};
		$dir = $this->getTempDir() . '/test';
		mkdir($dir);
		$path = \Icewind\Streams\UrlCallback::wrap($dir, null, null, null, null, $callback);
		rmdir($path);
		$this->assertFileNotExists($dir);
		$this->assertTrue($called);
	}

	public function testRenameCallBack() {
		$called = false;
		$callback = function () use (&$called) {
			$called = true;
		};
		$source = $this->getTempDir() . '/test';
		touch($source);
		$path = \Icewind\Streams\UrlCallback::wrap($source, null, null, null, $callback);
		$target = $path->wrapPath($source . '_rename');
		rename($path, $target);
		$this->assertFileExists($source . '_rename');
		$this->assertTrue($called);
	}

	public function testUnlinkCallBack() {
		$called = false;
		$callback = function () use (&$called) {
			$called = true;
		};
		$file = $this->getTempDir() . '/test';
		touch($file);
		$path = \Icewind\Streams\UrlCallback::wrap($file, null, null, null, null, null, $callback);
		unlink($path);
		$this->assertFileNotExists($file);
		$this->assertTrue($called);
	}

	public function testStatCallBack() {
		$called = false;
		$callback = function () use (&$called) {
			$called = true;
		};
		$file = $this->getTempDir() . '/test';
		touch($file);
		$path = \Icewind\Streams\UrlCallback::wrap($file, null, null, null, null, null, null, $callback);
		try {
			stat($path);
		} catch (\Exception $e) {
			$this->markTestSkipped('url_stat doesn\'t receive the context parameter, see php bug 50526');
		}
		$this->assertTrue($called);
	}

	public function testMKDirRecursive() {
		$dir = $this->getTempDir() . '/test/sad';
		$path = \Icewind\Streams\UrlCallback::wrap($dir);
		mkdir($path, 0700, true);
		$this->assertFileExists($dir);
	}
}
