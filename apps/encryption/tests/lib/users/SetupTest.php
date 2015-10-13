<?php
/**
 * @author Björn Schießle <schiessle@owncloud.com>
 * @author Clark Tomlinson <fallen013@gmail.com>
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


namespace OCA\Encryption\Tests\Users;


use OCA\Encryption\Users\Setup;
use Test\TestCase;

class SetupTest extends TestCase {
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $keyManagerMock;
	/**
	 * @var \PHPUnit_Framework_MockObject_MockObject
	 */
	private $cryptMock;
	/**
	 * @var Setup
	 */
	private $instance;

	public function testSetupServerSide() {
		$this->keyManagerMock->expects($this->exactly(2))->method('validateShareKey');
		$this->keyManagerMock->expects($this->exactly(2))->method('validateMasterKey');
		$this->keyManagerMock->expects($this->exactly(2))
			->method('userHasKeys')
			->with('admin')
			->willReturnOnConsecutiveCalls(true, false);

		$this->assertTrue($this->instance->setupServerSide('admin',
			'password'));

		$this->keyManagerMock->expects($this->once())
			->method('storeKeyPair')
			->with('admin', 'password')
			->willReturn(false);

		$this->assertFalse($this->instance->setupServerSide('admin',
			'password'));
	}

	protected function setUp() {
		parent::setUp();
		$logMock = $this->getMock('OCP\ILogger');
		$userSessionMock = $this->getMockBuilder('OCP\IUserSession')
			->disableOriginalConstructor()
			->getMock();
		$this->cryptMock = $this->getMockBuilder('OCA\Encryption\Crypto\Crypt')
			->disableOriginalConstructor()
			->getMock();

		$this->keyManagerMock = $this->getMockBuilder('OCA\Encryption\KeyManager')
			->disableOriginalConstructor()
			->getMock();

		$this->instance = new Setup($logMock,
			$userSessionMock,
			$this->cryptMock,
			$this->keyManagerMock);
	}

}
