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
 * Class UploadTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\unit\Connector\Sabre\RequestTest
 */
class UploadTest extends RequestTestCase {
	public function testBasicUpload(): void {
		$user = self::getUniqueID();
		$view = $this->setupUser($user, 'pass');

		$this->assertFalse($view->file_exists('foo.txt'));
		$response = $this->request($view, $user, 'pass', 'PUT', '/foo.txt', 'asd');

		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
		$this->assertTrue($view->file_exists('foo.txt'));
		$this->assertEquals('asd', $view->file_get_contents('foo.txt'));

		$info = $view->getFileInfo('foo.txt');
		$this->assertInstanceOf('\OC\Files\FileInfo', $info);
		$this->assertEquals(3, $info->getSize());
	}

	public function testUploadOverWrite(): void {
		$user = self::getUniqueID();
		$view = $this->setupUser($user, 'pass');

		$view->file_put_contents('foo.txt', 'foobar');

		$response = $this->request($view, $user, 'pass', 'PUT', '/foo.txt', 'asd');

		$this->assertEquals(Http::STATUS_NO_CONTENT, $response->getStatus());
		$this->assertEquals('asd', $view->file_get_contents('foo.txt'));

		$info = $view->getFileInfo('foo.txt');
		$this->assertInstanceOf('\OC\Files\FileInfo', $info);
		$this->assertEquals(3, $info->getSize());
	}

	public function testUploadOverWriteReadLocked(): void {
		$user = self::getUniqueID();
		$view = $this->setupUser($user, 'pass');

		$view->file_put_contents('foo.txt', 'bar');

		$view->lockFile('/foo.txt', ILockingProvider::LOCK_SHARED);

		$result = $this->request($view, $user, 'pass', 'PUT', '/foo.txt', 'asd');
		$this->assertEquals(Http::STATUS_LOCKED, $result->getStatus());
	}

	public function testUploadOverWriteWriteLocked(): void {
		$user = self::getUniqueID();
		$view = $this->setupUser($user, 'pass');
		$this->loginAsUser($user);

		$view->file_put_contents('foo.txt', 'bar');

		$view->lockFile('/foo.txt', ILockingProvider::LOCK_EXCLUSIVE);

		$result = $this->request($view, $user, 'pass', 'PUT', '/foo.txt', 'asd');
		$this->assertEquals(Http::STATUS_LOCKED, $result->getStatus());
	}
}
