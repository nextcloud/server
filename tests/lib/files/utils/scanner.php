<?php
/**
 * Copyright (c) 2012 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test\Files\Utils;

use OC\Files\Filesystem;
use OC\Files\Mount\Mount;
use OC\Files\Storage\Temporary;

class TestScanner extends \OC\Files\Utils\Scanner {
	/**
	 * @var \OC\Files\Mount\Mount[] $mounts
	 */
	private $mounts = array();

	/**
	 * @param \OC\Files\Mount\Mount $mount
	 */
	public function addMount($mount) {
		$this->mounts[] = $mount;
	}

	protected function getMounts($dir) {
		return $this->mounts;
	}

	public function getPropagator() {
		return $this->propagator;
	}

	public function setPropagator($propagator) {
		$this->propagator = $propagator;
	}
}

class Scanner extends \PHPUnit_Framework_TestCase {
	public function testReuseExistingRoot() {
		$storage = new Temporary(array());
		$mount = new Mount($storage, '');
		Filesystem::getMountManager()->addMount($mount);
		$cache = $storage->getCache();

		$storage->mkdir('folder');
		$storage->file_put_contents('foo.txt', 'qwerty');
		$storage->file_put_contents('folder/bar.txt', 'qwerty');

		$scanner = new TestScanner('');
		$scanner->addMount($mount);

		$scanner->scan('');
		$this->assertTrue($cache->inCache('folder/bar.txt'));
		$oldRoot = $cache->get('');

		$scanner->scan('');
		$newRoot = $cache->get('');
		$this->assertEquals($oldRoot, $newRoot);
	}

	public function testReuseExistingFile() {
		$storage = new Temporary(array());
		$mount = new Mount($storage, '');
		Filesystem::getMountManager()->addMount($mount);
		$cache = $storage->getCache();

		$storage->mkdir('folder');
		$storage->file_put_contents('foo.txt', 'qwerty');
		$storage->file_put_contents('folder/bar.txt', 'qwerty');

		$scanner = new TestScanner('');
		$scanner->addMount($mount);

		$scanner->scan('');
		$this->assertTrue($cache->inCache('folder/bar.txt'));
		$old = $cache->get('folder/bar.txt');

		$scanner->scan('');
		$new = $cache->get('folder/bar.txt');
		$this->assertEquals($old, $new);
	}

	public function testChangePropagator() {
		/**
		 * @var \OC\Files\Cache\ChangePropagator $propagator
		 */
		$propagator = $this->getMock('\OC\Files\Cache\ChangePropagator', array('propagateChanges'), array(), '', false);

		$storage = new Temporary(array());
		$mount = new Mount($storage, '/foo');
		Filesystem::getMountManager()->addMount($mount);
		$cache = $storage->getCache();

		$storage->mkdir('folder');
		$storage->file_put_contents('foo.txt', 'qwerty');
		$storage->file_put_contents('folder/bar.txt', 'qwerty');

		$scanner = new TestScanner('');
		$originalPropagator = $scanner->getPropagator();
		$scanner->setPropagator($propagator);
		$scanner->addMount($mount);

		$scanner->scan('');

		$this->assertEquals(array('/foo', '/foo/foo.txt', '/foo/folder', '/foo/folder/bar.txt'), $propagator->getChanges());
		$this->assertEquals(array('/', '/foo', '/foo/folder'), $propagator->getAllParents());

		$cache->put('foo.txt', array('mtime' => time() - 50));

		$propagator = $this->getMock('\OC\Files\Cache\ChangePropagator', array('propagateChanges'), array(), '', false);
		$scanner->setPropagator($propagator);
		$storage->file_put_contents('foo.txt', 'asdasd');

		$scanner->scan('');

		$this->assertEquals(array('/foo/foo.txt'), $propagator->getChanges());
		$this->assertEquals(array('/', '/foo'), $propagator->getAllParents());

		$scanner->setPropagator($originalPropagator);

		$oldInfo = $cache->get('');
		$cache->put('foo.txt', array('mtime' => time() - 70));
		$storage->file_put_contents('foo.txt', 'asdasd');

		$scanner->scan('');
		$newInfo = $cache->get('');
		$this->assertNotEquals($oldInfo['etag'], $newInfo['etag']);
	}
}
