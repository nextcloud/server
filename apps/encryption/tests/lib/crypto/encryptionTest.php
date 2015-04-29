<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
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

namespace OCA\Encryption\Tests\Crypto;

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

		$this->instance = new Encryption(
			$this->cryptMock,
			$this->keyManagerMock,
			$this->utilMock,
			$this->loggerMock
		);
	}

	/**
	 * @dataProvider dataProviderForTestGetPathToRealFile
	 */
	public function testGetPathToRealFile($path, $expected) {
		$this->assertSame($expected,
			\Test_Helper::invokePrivate($this->instance, 'getPathToRealFile', array($path))
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
			$this->assertTrue(\Test_Helper::invokePrivate($this->instance, 'isWriteOperation'));
		} else {
			$this->assertFalse(\Test_Helper::invokePrivate($this->instance, 'isWriteOperation'));
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

}
