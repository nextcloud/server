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
		$this->assertEquals(array('files/storage/local.php'), $this->loader->findClass('\OC\Files\Storage\Local'));
	}

	public function testNoLeadingSlashOnClassName() {
		$this->assertEquals(array('files/storage/local.php'), $this->loader->findClass('OC\Files\Storage\Local'));
	}

	public function testLegacyPath() {
		$this->assertEquals(array('legacy/files.php', 'files.php'), $this->loader->findClass('OC_Files'));
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

	public function loadTestNamespace() {
		$this->assertEquals(array('test/foo/bar.php'), $this->loader->findClass('Test\Foo\Bar'));
	}

	public function loadTest() {
		$this->assertEquals(array('test/foo/bar.php'), $this->loader->findClass('Test_Foo_Bar'));
	}

	public function loadCoreNamespace() {
		$this->assertEquals(array('lib/foo/bar.php'), $this->loader->findClass('OC\Foo\Bar'));
	}

	public function loadCore() {
		$this->assertEquals(array('lib/legacy/foo/bar.php', 'lib/foo/bar.php'), $this->loader->findClass('OC_Foo_Bar'));
	}

	public function loadPublicNamespace() {
		$this->assertEquals(array('lib/public/foo/bar.php'), $this->loader->findClass('OCP\Foo\Bar'));
	}
}
