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
		$result = \Test_Helper::invokePrivate($this->instance, 'getPathToRealFile', array($path));
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


}