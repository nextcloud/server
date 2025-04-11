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

	public function testLegacyPath(): void {
		$this->assertEquals([
			\OC::$SERVERROOT . '/lib/private/legacy/json.php',
		], $this->loader->findClass('OC_JSON'));
	}

	public function testLoadTestTestCase(): void {
		$this->assertEquals([
			\OC::$SERVERROOT . '/tests/lib/TestCase.php'
		], $this->loader->findClass('Test\TestCase'));
	}

	public function testLoadCore(): void {
		$this->assertEquals([
			\OC::$SERVERROOT . '/lib/private/legacy/foo/bar.php',
		], $this->loader->findClass('OC_Foo_Bar'));
	}

	public function testLoadPublicNamespace(): void {
		$this->assertEquals([], $this->loader->findClass('OCP\Foo\Bar'));
	}

	public function testLoadAppNamespace(): void {
		$result = $this->loader->findClass('OCA\Files\Foobar');
		$this->assertEquals(2, count($result));
		$this->assertStringEndsWith('apps/files/foobar.php', $result[0]);
		$this->assertStringEndsWith('apps/files/lib/foobar.php', $result[1]);
	}

	public function testLoadCoreNamespaceCore(): void {
		$this->assertEquals([], $this->loader->findClass('OC\Core\Foo\Bar'));
	}

	public function testLoadCoreNamespaceSettings(): void {
		$this->assertEquals([], $this->loader->findClass('OC\Settings\Foo\Bar'));
	}
}
