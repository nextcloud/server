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

		$this->instance = new Encryption($this->cryptMock, $this->keyManagerMock, $this->utilMock);
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
	public function testBegin($mode, $header, $legacyCipher, $defaultCipher, $expected) {

		$this->cryptMock->expects($this->any())
			->method('getCipher')
			->willReturn($defaultCipher);
		$this->cryptMock->expects($this->any())
			->method('getLegacyCipher')
			->willReturn($legacyCipher);

		$result = $this->instance->begin('/user/files/foo.txt', 'user', $mode, $header, []);

		$this->assertArrayHasKey('cipher', $result);
		$this->assertSame($expected, $result['cipher']);
	}

	public function dataTestBegin() {
		return array(
			array('w', ['cipher' => 'myCipher'], 'legacyCipher', 'defaultCipher', 'myCipher'),
			array('r', ['cipher' => 'myCipher'], 'legacyCipher', 'defaultCipher', 'myCipher'),
			array('w', [], 'legacyCipher', 'defaultCipher', 'defaultCipher'),
			array('r', [], 'legacyCipher', 'defaultCipher', 'legacyCipher'),
		);
	}


}