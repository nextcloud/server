<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Encryption\Tests\lib\Crypto;

use OCA\Encryption\Exceptions\PublicKeyMissingException;
use Test\TestCase;
use OCA\Encryption\Crypto\Encryption;

class EncryptionTest extends TestCase {

	/** @var Encryption */
	private $instance;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $keyManagerMock;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $cryptMock;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $utilMock;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $loggerMock;

	/** @var \PHPUnit_Framework_MockObject_MockObject */
	private $l10nMock;

	public function setUp() {
		parent::setUp();

		$this->cryptMock = $this->getMockBuilder('OCA\Encryption\Crypto\Crypt')
			->disableOriginalConstructor()
			->getMock();
		$this->utilMock = $this->getMockBuilder('OCA\Encryption\Util')
			->disableOriginalConstructor()
			->getMock();
		$this->keyManagerMock = $this->getMockBuilder('OCA\Encryption\KeyManager')
			->disableOriginalConstructor()
			->getMock();
		$this->loggerMock = $this->getMockBuilder('OCP\ILogger')
			->disableOriginalConstructor()
			->getMock();
		$this->l10nMock = $this->getMockBuilder('OCP\IL10N')
			->disableOriginalConstructor()
			->getMock();
		$this->l10nMock->expects($this->any())
			->method('t')
			->with($this->anything())
			->willReturnArgument(0);

		$this->instance = new Encryption(
			$this->cryptMock,
			$this->keyManagerMock,
			$this->utilMock,
			$this->loggerMock,
			$this->l10nMock
		);

	}

	/**
	 * test if public key from one of the recipients is missing
	 */
	public function testEndUser1() {
		$this->instance->begin('/foo/bar', 'user1', 'r', array(), array('users' => array('user1', 'user2', 'user3')));
		$this->endTest();
	}

	/**
	 * test if public key from owner is missing
	 *
	 * @expectedException \OCA\Encryption\Exceptions\PublicKeyMissingException
	 */
	public function testEndUser2() {
		$this->instance->begin('/foo/bar', 'user2', 'r', array(), array('users' => array('user1', 'user2', 'user3')));
		$this->endTest();
	}

	/**
	 * common part of testEndUser1 and testEndUser2
	 *
	 * @throws PublicKeyMissingException
	 */
	public function endTest() {
		// prepare internal variables
		self::invokePrivate($this->instance, 'isWriteOperation', [true]);
		self::invokePrivate($this->instance, 'writeCache', ['']);

		$this->keyManagerMock->expects($this->any())
			->method('getPublicKey')
			->will($this->returnCallback([$this, 'getPublicKeyCallback']));
		$this->keyManagerMock->expects($this->any())
			->method('addSystemKeys')
			->will($this->returnCallback([$this, 'addSystemKeysCallback']));
		$this->cryptMock->expects($this->any())
			->method('multiKeyEncrypt')
			->willReturn(true);
		$this->cryptMock->expects($this->any())
			->method('setAllFileKeys')
			->willReturn(true);

		$this->instance->end('/foo/bar');
	}


	public function getPublicKeyCallback($uid) {
		if ($uid === 'user2') {
			throw new PublicKeyMissingException($uid);
		}
		return $uid;
	}

	public function addSystemKeysCallback($accessList, $publicKeys) {
		$this->assertSame(2, count($publicKeys));
		$this->assertArrayHasKey('user1', $publicKeys);
		$this->assertArrayHasKey('user3', $publicKeys);
		return $publicKeys;
	}

	/**
	 * @dataProvider dataProviderForTestGetPathToRealFile
	 */
	public function testGetPathToRealFile($path, $expected) {
		$this->assertSame($expected,
			self::invokePrivate($this->instance, 'getPathToRealFile', array($path))
		);
	}

	public function dataProviderForTestGetPathToRealFile() {
		return array(
			array('/user/files/foo/bar.txt', '/user/files/foo/bar.txt'),
			array('/user/files/foo.txt', '/user/files/foo.txt'),
			array('/user/files_versions/foo.txt.v543534', '/user/files/foo.txt'),
			array('/user/files_versions/foo/bar.txt.v5454', '/user/files/foo/bar.txt'),
		);
	}

	/**
	 * @dataProvider dataTestBegin
	 */
	public function testBegin($mode, $header, $legacyCipher, $defaultCipher, $fileKey, $expected) {

		$this->cryptMock->expects($this->any())
			->method('getCipher')
			->willReturn($defaultCipher);
		$this->cryptMock->expects($this->any())
			->method('getLegacyCipher')
			->willReturn($legacyCipher);
		if (empty($fileKey)) {
			$this->cryptMock->expects($this->once())
				->method('generateFileKey')
				->willReturn('fileKey');
		} else {
			$this->cryptMock->expects($this->never())
				->method('generateFileKey');
		}

		$this->keyManagerMock->expects($this->once())
			->method('getFileKey')
			->willReturn($fileKey);

		$result = $this->instance->begin('/user/files/foo.txt', 'user', $mode, $header, []);

		$this->assertArrayHasKey('cipher', $result);
		$this->assertSame($expected, $result['cipher']);
		if ($mode === 'w') {
			$this->assertTrue(self::invokePrivate($this->instance, 'isWriteOperation'));
		} else {
			$this->assertFalse(self::invokePrivate($this->instance, 'isWriteOperation'));
		}
	}

	public function dataTestBegin() {
		return array(
			array('w', ['cipher' => 'myCipher'], 'legacyCipher', 'defaultCipher', 'fileKey', 'myCipher'),
			array('r', ['cipher' => 'myCipher'], 'legacyCipher', 'defaultCipher', 'fileKey', 'myCipher'),
			array('w', [], 'legacyCipher', 'defaultCipher', '', 'defaultCipher'),
			array('r', [], 'legacyCipher', 'defaultCipher', 'file_key', 'legacyCipher'),
		);
	}

	/**
	 * @dataProvider dataTestUpdate
	 *
	 * @param string $fileKey
	 * @param boolean $expected
	 */
	public function testUpdate($fileKey, $expected) {
		$this->keyManagerMock->expects($this->once())
			->method('getFileKey')->willReturn($fileKey);

		$this->keyManagerMock->expects($this->any())
			->method('getPublicKey')->willReturn('publicKey');

		$this->keyManagerMock->expects($this->any())
			->method('addSystemKeys')
			->willReturnCallback(function($accessList, $publicKeys) {
				return $publicKeys;
			});

		$this->assertSame($expected,
			$this->instance->update('path', 'user1', ['users' => ['user1']])
		);

	}

	public function dataTestUpdate() {
		return array(
			array('', false),
			array('fileKey', true)
		);
	}

	/**
	 * by default the encryption module should encrypt regular files, files in
	 * files_versions and files in files_trashbin
	 *
	 * @dataProvider dataTestShouldEncrypt
	 */
	public function testShouldEncrypt($path, $expected) {
		$this->assertSame($expected,
			$this->instance->shouldEncrypt($path)
		);
	}

	public function dataTestShouldEncrypt() {
		return array(
			array('/user1/files/foo.txt', true),
			array('/user1/files_versions/foo.txt', true),
			array('/user1/files_trashbin/foo.txt', true),
			array('/user1/some_folder/foo.txt', false),
			array('/user1/foo.txt', false),
			array('/user1/files', false),
			array('/user1/files_trashbin', false),
			array('/user1/files_versions', false),
		);
	}

	/**
	 * @expectedException \OC\Encryption\Exceptions\DecryptionFailedException
	 * @expectedExceptionMessage Can not decrypt this file, probably this is a shared file. Please ask the file owner to reshare the file with you.
	 */
	public function testDecrypt() {
		$this->instance->decrypt('abc');
	}
}
