<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_External\Tests\Storage;

use OC\Files\Storage\DAV;
use OC\Files\Type\Detection;
use OCP\Files\IMimeTypeDetector;
use OCP\Server;

/**
 * Class WebdavTest
 *
 *
 * @package OCA\Files_External\Tests\Storage
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class WebdavTest extends \Test\Files\Storage\Storage {
	use ConfigurableStorageTrait;

	protected function setUp(): void {
		parent::setUp();

		$id = $this->getUniqueID();
		$this->loadConfig('files_external/tests/config.webdav.php');
		if (isset($this->config['wait'])) {
			$this->waitDelay = $this->config['wait'];
		}
		$this->config['root'] .= '/' . $id; //make sure we have an new empty folder to work in
		$this->instance = new DAV($this->config);
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
		$mimeDetector = Server::get(IMimeTypeDetector::class);
		$mimeDetector->registerType('bar', 'application/x-bar');

		$this->assertEquals('application/x-bar', $this->instance->getMimeType('foo.bar'));
	}
}
