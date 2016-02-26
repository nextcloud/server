<?php
/**
 * Copyright (c) 2016 Robin Appelman <icewind@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Files_Sharing\Tests\External;

use OC\AppFramework\Http;
use OC\Files\View;
use Test\Connector\Sabre\RequestTest\RequestTest;

class OwnerPropagation extends RequestTest {
	public function testBasicUpload() {
		$user = $this->getUniqueID();
		$userView = $this->setupUser($user, 'pass');

		$userView->mkdir('/a/b/share');

		$subView = new View('/' . $user . '/files/a/b/share');
		$this->assertTrue($subView->is_dir(''));

		$oldInfos = [
			$userView->getFileInfo(''),
			$userView->getFileInfo('a'),
			$userView->getFileInfo('a/b'),
			$userView->getFileInfo('a/b/share'),
		];

		$this->assertFalse($subView->file_exists('foo.txt'));
		$response = $this->request($subView, $user, 'pass', 'PUT', '/foo.txt', 'asd');

		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
		$this->assertTrue($subView->file_exists('foo.txt'));
		$this->assertEquals('asd', $subView->file_get_contents('foo.txt'));

		$newInfos = [
			$userView->getFileInfo(''),
			$userView->getFileInfo('a'),
			$userView->getFileInfo('a/b'),
			$userView->getFileInfo('a/b/share'),
		];

		foreach($oldInfos as $i => $oldInfo) {
			$this->assertNotEquals($oldInfo->getEtag(), $newInfos[$i]->getEtag(), 'Etag for ' . $oldInfo->getPath());
		}
	}
}
