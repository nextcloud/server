<?php

/**
 * ownCloud
 *
 * @copyright (C) 2015 ownCloud, Inc.
 *
 * @author Bjoern Schiessle <schiessle@owncloud.com>
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Test\Encryption\Keys;

use OC\Encryption\Keys\Storage;
use Test\TestCase;

class StorageTest extends TestCase {

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $util;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	protected $view;

	public function setUp() {
		parent::setUp();

		$this->util = $this->getMockBuilder('OC\Encryption\Util')
			->disableOriginalConstructor()
			->getMock();

		$this->view = $this->getMockBuilder('OC\Files\View')
			->disableOriginalConstructor()
			->getMock();

	}

	public function testSetFileKey() {
		$this->util->expects($this->any())
			->method('getUidAndFilename')
			->willReturn(array('user1', '/files/foo.txt'));
		$this->util->expects($this->any())
			->method('stripPartialFileExtension')
			->willReturnArgument(0);
		$this->util->expects($this->any())
			->method('isSystemWideMountPoint')
			->willReturn(false);
		$this->view->expects($this->once())
			->method('file_put_contents')
			->with($this->equalTo('/user1/files_encryption/keys/files/foo.txt/encModule/fileKey'),
				$this->equalTo('key'))
			->willReturn(strlen('key'));

		$storage = new Storage('encModule', $this->view, $this->util);

		$this->assertTrue(
			$storage->setFileKey('user1/files/foo.txt', 'fileKey', 'key')
		);
	}

	public function testGetFileKey() {
		$this->util->expects($this->any())
			->method('getUidAndFilename')
			->willReturn(array('user1', '/files/foo.txt'));
		$this->util->expects($this->any())
			->method('stripPartialFileExtension')
			->willReturnArgument(0);
		$this->util->expects($this->any())
			->method('isSystemWideMountPoint')
			->willReturn(false);
		$this->view->expects($this->once())
			->method('file_get_contents')
			->with($this->equalTo('/user1/files_encryption/keys/files/foo.txt/encModule/fileKey'))
			->willReturn('key');
		$this->view->expects($this->once())
			->method('file_exists')
			->with($this->equalTo('/user1/files_encryption/keys/files/foo.txt/encModule/fileKey'))
			->willReturn(true);

		$storage = new Storage('encModule', $this->view, $this->util);

		$this->assertSame('key',
			$storage->getFileKey('user1/files/foo.txt', 'fileKey')
		);
	}

	public function testSetFileKeySystemWide() {
		$this->util->expects($this->any())
			->method('getUidAndFilename')
			->willReturn(array('user1', '/files/foo.txt'));
		$this->util->expects($this->any())
			->method('isSystemWideMountPoint')
			->willReturn(true);
		$this->util->expects($this->any())
			->method('stripPartialFileExtension')
			->willReturnArgument(0);
		$this->view->expects($this->once())
			->method('file_put_contents')
			->with($this->equalTo('/files_encryption/keys/files/foo.txt/encModule/fileKey'),
				$this->equalTo('key'))
			->willReturn(strlen('key'));

		$storage = new Storage('encModule', $this->view, $this->util);

		$this->assertTrue(
			$storage->setFileKey('user1/files/foo.txt', 'fileKey', 'key')
		);
	}

	public function testGetFileKeySystemWide() {
		$this->util->expects($this->any())
			->method('getUidAndFilename')
			->willReturn(array('user1', '/files/foo.txt'));
		$this->util->expects($this->any())
			->method('stripPartialFileExtension')
			->willReturnArgument(0);
		$this->util->expects($this->any())
			->method('isSystemWideMountPoint')
			->willReturn(true);
		$this->view->expects($this->once())
			->method('file_get_contents')
			->with($this->equalTo('/files_encryption/keys/files/foo.txt/encModule/fileKey'))
			->willReturn('key');
		$this->view->expects($this->once())
			->method('file_exists')
			->with($this->equalTo('/files_encryption/keys/files/foo.txt/encModule/fileKey'))
			->willReturn(true);

		$storage = new Storage('encModule', $this->view, $this->util);

		$this->assertSame('key',
			$storage->getFileKey('user1/files/foo.txt', 'fileKey')
		);
	}

	public function testSetSystemUserKey() {
		$this->view->expects($this->once())
			->method('file_put_contents')
			->with($this->equalTo('/files_encryption/encModule/shareKey_56884'),
				$this->equalTo('key'))
			->willReturn(strlen('key'));

		$storage = new Storage('encModule', $this->view, $this->util);

		$this->assertTrue(
			$storage->setSystemUserKey('shareKey_56884', 'key')
		);
	}

	public function testSetUserKey() {
		$this->view->expects($this->once())
			->method('file_put_contents')
			->with($this->equalTo('/user1/files_encryption/encModule/user1.publicKey'),
				$this->equalTo('key'))
			->willReturn(strlen('key'));

		$storage = new Storage('encModule', $this->view, $this->util);

		$this->assertTrue(
			$storage->setUserKey('user1', 'publicKey', 'key')
		);
	}

	public function testGetSystemUserKey() {
		$this->view->expects($this->once())
			->method('file_get_contents')
			->with($this->equalTo('/files_encryption/encModule/shareKey_56884'))
			->willReturn('key');
		$this->view->expects($this->once())
			->method('file_exists')
			->with($this->equalTo('/files_encryption/encModule/shareKey_56884'))
			->willReturn(true);

		$storage = new Storage('encModule', $this->view, $this->util);

		$this->assertSame('key',
			$storage->getSystemUserKey('shareKey_56884')
		);
	}

	public function testGetUserKey() {
		$this->view->expects($this->once())
			->method('file_get_contents')
			->with($this->equalTo('/user1/files_encryption/encModule/user1.publicKey'))
			->willReturn('key');
		$this->view->expects($this->once())
			->method('file_exists')
			->with($this->equalTo('/user1/files_encryption/encModule/user1.publicKey'))
			->willReturn(true);

		$storage = new Storage('encModule', $this->view, $this->util);

		$this->assertSame('key',
			$storage->getUserKey('user1', 'publicKey')
		);
	}

	public function testDeleteUserKey() {
		$this->view->expects($this->once())
			->method('unlink')
			->with($this->equalTo('/user1/files_encryption/encModule/user1.publicKey'))
			->willReturn(true);

		$storage = new Storage('encModule', $this->view, $this->util);

		$this->assertTrue(
			$storage->deleteUserKey('user1', 'publicKey')
		);
	}

	public function testDeleteSystemUserKey() {
		$this->view->expects($this->once())
			->method('unlink')
			->with($this->equalTo('/files_encryption/encModule/shareKey_56884'))
			->willReturn(true);

		$storage = new Storage('encModule', $this->view, $this->util);

		$this->assertTrue(
			$storage->deleteSystemUserKey('shareKey_56884')
		);
	}

	public function testDeleteFileKeySystemWide() {
		$this->util->expects($this->any())
			->method('getUidAndFilename')
			->willReturn(array('user1', '/files/foo.txt'));
		$this->util->expects($this->any())
			->method('stripPartialFileExtension')
			->willReturnArgument(0);
		$this->util->expects($this->any())
			->method('isSystemWideMountPoint')
			->willReturn(true);
		$this->view->expects($this->once())
			->method('unlink')
			->with($this->equalTo('/files_encryption/keys/files/foo.txt/encModule/fileKey'))
			->willReturn(true);

		$storage = new Storage('encModule', $this->view, $this->util);

		$this->assertTrue(
			$storage->deleteFileKey('user1/files/foo.txt', 'fileKey')
		);
	}

	public function testDeleteFileKey() {
		$this->util->expects($this->any())
			->method('getUidAndFilename')
			->willReturn(array('user1', '/files/foo.txt'));
		$this->util->expects($this->any())
			->method('stripPartialFileExtension')
			->willReturnArgument(0);
		$this->util->expects($this->any())
			->method('isSystemWideMountPoint')
			->willReturn(false);
		$this->view->expects($this->once())
			->method('unlink')
			->with($this->equalTo('/user1/files_encryption/keys/files/foo.txt/encModule/fileKey'))
			->willReturn(true);

		$storage = new Storage('encModule', $this->view, $this->util);

		$this->assertTrue(
			$storage->deleteFileKey('user1/files/foo.txt', 'fileKey')
		);
	}

}
