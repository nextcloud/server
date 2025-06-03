<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\Connector\Sabre\RequestTest;

use OCP\AppFramework\Http;

/**
 * Class DeleteTest
 *
 * @group DB
 *
 * @package OCA\DAV\Tests\unit\Connector\Sabre\RequestTest
 */
class DeleteTest extends RequestTestCase {
	public function testBasicUpload(): void {
		$user = self::getUniqueID();
		$view = $this->setupUser($user, 'pass');

		$view->file_put_contents('foo.txt', 'asd');
		$mount = $view->getMount('foo.txt');
		$internalPath = $view->getAbsolutePath();

		// create a ghost file
		$mount->getStorage()->unlink($mount->getInternalPath($internalPath));

		// cache entry still exists
		$this->assertInstanceOf(\OCP\Files\FileInfo::class, $view->getFileInfo('foo.txt'));

		$response = $this->request($view, $user, 'pass', 'DELETE', '/foo.txt');

		$this->assertEquals(Http::STATUS_NO_CONTENT, $response->getStatus());

		// no longer in the cache
		$this->assertFalse($view->getFileInfo('foo.txt'));
	}
}
