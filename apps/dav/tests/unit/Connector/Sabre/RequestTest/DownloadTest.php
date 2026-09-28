<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre\RequestTest;

use OCP\AppFramework\Http;
use OCP\Lock\ILockingProvider;

/**
 * Class DownloadTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\unit\Connector\Sabre\RequestTest
 */
class DownloadTest extends RequestTestCase {
	public function testDownload(): void {
		$user = self::getUniqueID();
		$view = $this->setupUser($user, 'pass');

		$view->file_put_contents('foo.txt', 'bar');

		$response = $this->request($view, $user, 'pass', 'GET', '/foo.txt');
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals(stream_get_contents($response->getBody()), 'bar');
	}

	public function testDownloadWriteLocked(): void {
		$user = self::getUniqueID();
		$view = $this->setupUser($user, 'pass');

		$view->file_put_contents('foo.txt', 'bar');

		$view->lockFile('/foo.txt', ILockingProvider::LOCK_EXCLUSIVE);

		$result = $this->request($view, $user, 'pass', 'GET', '/foo.txt', 'asd');
		$this->assertEquals(Http::STATUS_LOCKED, $result->getStatus());
	}

	public function testDownloadReadLocked(): void {
		$user = self::getUniqueID();
		$view = $this->setupUser($user, 'pass');

		$view->file_put_contents('foo.txt', 'bar');

		$view->lockFile('/foo.txt', ILockingProvider::LOCK_SHARED);

		$response = $this->request($view, $user, 'pass', 'GET', '/foo.txt', 'asd');
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals(stream_get_contents($response->getBody()), 'bar');
	}
}
