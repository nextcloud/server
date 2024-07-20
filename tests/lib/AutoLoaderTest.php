<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

class AutoLoaderTest extends TestCase {
	/**
	 * @var \OC\Autoloader $loader
	 */
	private $loader;

	protected function setUp(): void {
		parent::setUp();
		$this->loader = new \OC\AutoLoader([]);
	}

	public function testLegacyPath() {
		$this->assertEquals([
			\OC::$SERVERROOT . '/lib/private/legacy/files.php',
		], $this->loader->findClass('OC_Files'));
	}

	public function testLoadTestTestCase() {
		$this->assertEquals([
			\OC::$SERVERROOT . '/tests/lib/TestCase.php'
		], $this->loader->findClass('Test\TestCase'));
	}

	public function testLoadCore() {
		$this->assertEquals([
			\OC::$SERVERROOT . '/lib/private/legacy/foo/bar.php',
		], $this->loader->findClass('OC_Foo_Bar'));
	}

	public function testLoadPublicNamespace() {
		$this->assertEquals([], $this->loader->findClass('OCP\Foo\Bar'));
	}

	public function testLoadAppNamespace() {
		$result = $this->loader->findClass('OCA\Files\Foobar');
		$this->assertEquals(2, count($result));
		$this->assertStringEndsWith('apps/files/foobar.php', $result[0]);
		$this->assertStringEndsWith('apps/files/lib/foobar.php', $result[1]);
	}

	public function testLoadCoreNamespaceCore() {
		$this->assertEquals([], $this->loader->findClass('OC\Core\Foo\Bar'));
	}

	public function testLoadCoreNamespaceSettings() {
		$this->assertEquals([], $this->loader->findClass('OC\Settings\Foo\Bar'));
	}
}
