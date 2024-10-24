<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests\Storage;

use OC\Files\Storage\DAV;
use OC\Files\Type\Detection;

/**
 * Class WebdavTest
 *
 * @group DB
 *
 * @package OCA\Files_External\Tests\Storage
 */
class WebdavTest extends \Test\Files\Storage\Storage {
	protected function setUp(): void {
		parent::setUp();

		$id = $this->getUniqueID();
		$config = include('files_external/tests/config.webdav.php');
		if (!is_array($config) or !$config['run']) {
			$this->markTestSkipped('WebDAV backend not configured');
		}
		if (isset($config['wait'])) {
			$this->waitDelay = $config['wait'];
		}
		$config['root'] .= '/' . $id; //make sure we have an new empty folder to work in
		$this->instance = new DAV($config);
		$this->instance->mkdir('/');
	}

	protected function tearDown(): void {
		if ($this->instance) {
			$this->instance->rmdir('/');
		}

		parent::tearDown();
	}

	public function testMimetypeFallback(): void {
		$this->instance->file_put_contents('foo.bar', 'asd');

		/** @var Detection $mimeDetector */
		$mimeDetector = \OC::$server->getMimeTypeDetector();
		$mimeDetector->registerType('bar', 'application/x-bar');

		$this->assertEquals('application/x-bar', $this->instance->getMimeType('foo.bar'));
	}
}
