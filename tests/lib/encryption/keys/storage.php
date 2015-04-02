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

	/** @var Storage */
	protected $storage;

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

		$this->storage = new Storage('encModule', $this->view, $this->util);

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

		$this->assertTrue(
			$this->storage->setFileKey('user1/files/foo.txt', 'fileKey', 'key')
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

		$this->assertSame('key',
			$this->storage->getFileKey('user1/files/foo.txt', 'fileKey')
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

		$this->assertTrue(
			$this->storage->setFileKey('user1/files/foo.txt', 'fileKey', 'key')
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

		$this->assertSame('key',
			$this->storage->getFileKey('user1/files/foo.txt', 'fileKey')
		);
	}

	public function testSetSystemUserKey() {
		$this->view->expects($this->once())
			->method('file_put_contents')
			->with($this->equalTo('/files_encryption/encModule/shareKey_56884'),
				$this->equalTo('key'))
			->willReturn(strlen('key'));

		$this->assertTrue(
			$this->storage->setSystemUserKey('shareKey_56884', 'key')
		);
	}

	public function testSetUserKey() {
		$this->view->expects($this->once())
			->method('file_put_contents')
			->with($this->equalTo('/user1/files_encryption/encModule/user1.publicKey'),
				$this->equalTo('key'))
			->willReturn(strlen('key'));

		$this->assertTrue(
			$this->storage->setUserKey('user1', 'publicKey', 'key')
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

		$this->assertSame('key',
			$this->storage->getSystemUserKey('shareKey_56884')
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

		$this->assertSame('key',
			$this->storage->getUserKey('user1', 'publicKey')
		);
	}

	public function testDeleteUserKey() {
		$this->view->expects($this->once())
			->method('unlink')
			->with($this->equalTo('/user1/files_encryption/encModule/user1.publicKey'))
			->willReturn(true);

		$this->assertTrue(
			$this->storage->deleteUserKey('user1', 'publicKey')
		);
	}

	public function testDeleteSystemUserKey() {
		$this->view->expects($this->once())
			->method('unlink')
			->with($this->equalTo('/files_encryption/encModule/shareKey_56884'))
			->willReturn(true);

		$this->assertTrue(
			$this->storage->deleteSystemUserKey('shareKey_56884')
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

		$this->assertTrue(
			$this->storage->deleteFileKey('user1/files/foo.txt', 'fileKey')
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

		$this->assertTrue(
			$this->storage->deleteFileKey('user1/files/foo.txt', 'fileKey')
		);
	}

	/**
	 * @dataProvider dataProviderCopyRename
	 */
	public function testRenameKeys($source, $target, $systemWideMount, $expectedSource, $expectedTarget) {
		$this->view->expects($this->any())
			->method('file_exists')
			->willReturn(true);
		$this->view->expects($this->any())
			->method('is_dir')
			->willReturn(true);
		$this->view->expects($this->once())
			->method('rename')
			->with(
				$this->equalTo($expectedSource),
				$this->equalTo($expectedTarget))
			->willReturn(true);
		$this->util->expects($this->any())
			->method('getUidAndFilename')
			->will($this->returnCallback(array($this, 'getUidAndFilenameCallback')));
		$this->util->expects($this->any())
			->method('isSystemWideMountPoint')
			->willReturn($systemWideMount);

		$this->storage->renameKeys($source, $target);
	}

	/**
	 * @dataProvider dataProviderCopyRename
	 */
	public function testCopyKeys($source, $target, $systemWideMount, $expectedSource, $expectedTarget) {
		$this->view->expects($this->any())
			->method('file_exists')
			->willReturn(true);
		$this->view->expects($this->any())
			->method('is_dir')
			->willReturn(true);
		$this->view->expects($this->once())
			->method('copy')
			->with(
				$this->equalTo($expectedSource),
				$this->equalTo($expectedTarget))
			->willReturn(true);
		$this->util->expects($this->any())
			->method('getUidAndFilename')
			->will($this->returnCallback(array($this, 'getUidAndFilenameCallback')));
		$this->util->expects($this->any())
			->method('isSystemWideMountPoint')
			->willReturn($systemWideMount);

		$this->storage->copyKeys($source, $target);
	}

	public function getUidAndFilenameCallback() {
		$args = func_get_args();

		$path = $args[0];
		$parts = explode('/', $path);

		return array($parts[1], '/' . implode('/', array_slice($parts, 2)));
	}

	public function dataProviderCopyRename() {
		return array(
			array('/user1/files/foo.txt', '/user1/files/bar.txt', false,
				'/user1/files_encryption/keys/files/foo.txt/', '/user1/files_encryption/keys/files/bar.txt/'),
				array('/user1/files/foo/foo.txt', '/user1/files/bar.txt', false,
				'/user1/files_encryption/keys/files/foo/foo.txt/', '/user1/files_encryption/keys/files/bar.txt/'),
			array('/user1/files/foo.txt', '/user1/files/foo/bar.txt', false,
				'/user1/files_encryption/keys/files/foo.txt/', '/user1/files_encryption/keys/files/foo/bar.txt/'),
			array('/user1/files/foo.txt', '/user1/files/foo/bar.txt', true,
				'/files_encryption/keys/files/foo.txt/', '/files_encryption/keys/files/foo/bar.txt/'),
		);
	}

	public function testKeySetPreparation() {
		$this->view->expects($this->any())
			->method('file_exists')
			->willReturn(false);
		$this->view->expects($this->any())
			->method('is_dir')
			->willReturn(false);
		$this->view->expects($this->any())
			->method('mkdir')
			->will($this->returnCallback(array($this, 'mkdirCallback')));

		$this->mkdirStack = array(
			'/user1/files_encryption/keys/foo',
			'/user1/files_encryption/keys',
			'/user1/files_encryption',
			'/user1');

		\Test_Helper::invokePrivate($this->storage, 'keySetPreparation', array('/user1/files_encryption/keys/foo'));
	}

	public function mkdirCallback() {
		$args = func_get_args();
		$expected = array_pop($this->mkdirStack);
		$this->assertSame($expected, $args[0]);
	}

}
