<?php
/**
 * Copyright (c) 2015 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\DAV\Tests\Unit\Connector\Sabre\RequestTest;

use OCP\AppFramework\Http;
use OCP\Lock\ILockingProvider;

/**
 * Class DownloadTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\Unit\Connector\Sabre\RequestTest
 */
class DownloadTest extends RequestTest {
	public function testDownload() {
		$user = $this->getUniqueID();
		$view = $this->setupUser($user, 'pass');

		$view->file_put_contents('foo.txt', 'bar');

		$response = $this->request($view, $user, 'pass', 'GET', '/foo.txt');
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals(stream_get_contents($response->getBody()), 'bar');
	}

	/**
	 * @expectedException \OCA\DAV\Connector\Sabre\Exception\FileLocked
	 */
	public function testDownloadWriteLocked() {
		$user = $this->getUniqueID();
		$view = $this->setupUser($user, 'pass');

		$view->file_put_contents('foo.txt', 'bar');

		$view->lockFile('/foo.txt', ILockingProvider::LOCK_EXCLUSIVE);

		$this->request($view, $user, 'pass', 'GET', '/foo.txt', 'asd');
	}

	public function testDownloadReadLocked() {
		$user = $this->getUniqueID();
		$view = $this->setupUser($user, 'pass');

		$view->file_put_contents('foo.txt', 'bar');

		$view->lockFile('/foo.txt', ILockingProvider::LOCK_SHARED);

		$response = $this->request($view, $user, 'pass', 'GET', '/foo.txt', 'asd');
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals(stream_get_contents($response->getBody()), 'bar');
	}
}
