<?php
/**
 * Copyright (c) 2013 Thomas Müller <thomas.mueller@tmit.eu>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

class AutoLoader extends \PHPUnit_Framework_TestCase {
	/**
	 * @var \OC\Autoloader $loader
	 */
	private $loader;

	public function setUp() {
		$this->loader = new \OC\AutoLoader();
	}

	public function testLeadingSlashOnClassName() {
		$this->assertEquals(array('private/files/storage/local.php', 'files/storage/local.php'), $this->loader->findClass('\OC\Files\Storage\Local'));
	}

	public function testNoLeadingSlashOnClassName() {
		$this->assertEquals(array('private/files/storage/local.php', 'files/storage/local.php'), $this->loader->findClass('OC\Files\Storage\Local'));
	}

	public function testLegacyPath() {
		$this->assertEquals(array('private/legacy/files.php', 'private/files.php'), $this->loader->findClass('OC_Files'));
	}

	public function testClassPath() {
		$this->loader->registerClass('Foo\Bar', 'foobar.php');
		$this->assertEquals(array('foobar.php'), $this->loader->findClass('Foo\Bar'));
	}

	public function testPrefixNamespace() {
		$this->loader->registerPrefix('Foo', 'foo');
		$this->assertEquals(array('foo/Foo/Bar.php'), $this->loader->findClass('Foo\Bar'));
	}

	public function testPrefix() {
		$this->loader->registerPrefix('Foo_', 'foo');
		$this->assertEquals(array('foo/Foo/Bar.php'), $this->loader->findClass('Foo_Bar'));
	}

	public function testLoadTestNamespace() {
		$this->assertEquals(array('tests/lib/foo/bar.php'), $this->loader->findClass('Test\Foo\Bar'));
	}

	public function testLoadTest() {
		$this->assertEquals(array('tests/lib/foo/bar.php'), $this->loader->findClass('Test_Foo_Bar'));
	}

	public function testLoadCoreNamespace() {
		$this->assertEquals(array('private/foo/bar.php', 'foo/bar.php'), $this->loader->findClass('OC\Foo\Bar'));
	}

	public function testLoadCore() {
		$this->assertEquals(array('private/legacy/foo/bar.php', 'private/foo/bar.php'), $this->loader->findClass('OC_Foo_Bar'));
	}

	public function testLoadPublicNamespace() {
		$this->assertEquals(array('public/foo/bar.php'), $this->loader->findClass('OCP\Foo\Bar'));
	}

	public function testLoadAppNamespace() {
		$result = $this->loader->findClass('OCA\Files\Foobar');
		$this->assertEquals(2, count($result));
		$this->assertStringEndsWith('apps/files/foobar.php', $result[0]);
		$this->assertStringEndsWith('apps/files/lib/foobar.php', $result[1]);
	}
}
