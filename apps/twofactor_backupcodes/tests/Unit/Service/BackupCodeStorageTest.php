<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\TwoFactorBackupCodes\Tests\Unit\Service;

use OCA\TwoFactorBackupCodes\Db\BackupCode;
use OCA\TwoFactorBackupCodes\Db\BackupCodeMapper;
use OCA\TwoFactorBackupCodes\Service\BackupCodeStorage;
use OCP\IUser;
use OCP\Security\IHasher;
use OCP\Security\ISecureRandom;
use Test\TestCase;

class BackupCodeStorageTest extends TestCase {

	/** @var BackupCodeMapper|\PHPUnit_Framework_MockObject_MockObject */
	private $mapper;

	/** @var ISecureRandom|\PHPUnit_Framework_MockObject_MockObject */
	private $random;

	/** @var IHasher|\PHPUnit_Framework_MockObject_MockObject */
	private $hasher;

	/** @var BackupCodeStorage */
	private $storage;

	protected function setUp() {
		parent::setUp();

		$this->mapper = $this->getMockBuilder(BackupCodeMapper::class)
			->disableOriginalConstructor()
			->getMock();
		$this->random = $this->getMockBuilder(ISecureRandom::class)->getMock();
		$this->hasher = $this->getMockBuilder(IHasher::class)->getMock();
		$this->storage = new BackupCodeStorage($this->mapper, $this->random, $this->hasher);
	}

	public function testCreateCodes() {
		$user = $this->getMockBuilder(IUser::class)->getMock();
		$number = 5;

		$user->expects($this->once())
			->method('getUID')
			->will($this->returnValue('fritz'));
		$this->random->expects($this->exactly($number))
			->method('generate')
			->with(10, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789')
			->will($this->returnValue('CODEABCDEF'));
		$this->hasher->expects($this->exactly($number))
			->method('hash')
			->with('CODEABCDEF')
			->will($this->returnValue('HASHEDCODE'));
		$row = new BackupCode();
		$row->setUserId('fritz');
		$row->setCode('HASHEDCODE');
		$row->setUsed(0);
		$this->mapper->expects($this->exactly($number))
			->method('insert')
			->with($this->equalTo($row));

		$codes = $this->storage->createCodes($user, $number);
		$this->assertCount($number, $codes);
		foreach ($codes as $code) {
			$this->assertEquals('CODEABCDEF', $code);
		}
	}

	public function testHasBackupCodes() {
		$user = $this->getMockBuilder(IUser::class)->getMock();
		$codes = [
			new BackupCode(),
			new BackupCode(),
		];

		$this->mapper->expects($this->once())
			->method('getBackupCodes')
			->with($user)
			->will($this->returnValue($codes));

		$this->assertTrue($this->storage->hasBackupCodes($user));
	}

	public function testHasBackupCodesNoCodes() {
		$user = $this->getMockBuilder(IUser::class)->getMock();
		$codes = [];

		$this->mapper->expects($this->once())
			->method('getBackupCodes')
			->with($user)
			->will($this->returnValue($codes));

		$this->assertFalse($this->storage->hasBackupCodes($user));
	}

	public function testGetBackupCodeState() {
		$user = $this->getMockBuilder(IUser::class)->getMock();

		$code1 = new BackupCode();
		$code1->setUsed(1);
		$code2 = new BackupCode();
		$code2->setUsed('0');
		$codes = [
			$code1,
			$code2,
		];

		$this->mapper->expects($this->once())
			->method('getBackupCodes')
			->with($user)
			->will($this->returnValue($codes));

		$expected = [
			'enabled' => true,
			'total' => 2,
			'used' => 1,
		];
		$this->assertEquals($expected, $this->storage->getBackupCodesState($user));
	}

	public function testGetBackupCodeDisabled() {
		$user = $this->getMockBuilder(IUser::class)->getMock();

		$codes = [];

		$this->mapper->expects($this->once())
			->method('getBackupCodes')
			->with($user)
			->will($this->returnValue($codes));

		$expected = [
			'enabled' => false,
			'total' => 0,
			'used' => 0,
		];
		$this->assertEquals($expected, $this->storage->getBackupCodesState($user));
	}

	public function testValidateCode() {
		$user = $this->getMockBuilder(IUser::class)->getMock();
		$code = new BackupCode();
		$code->setUsed(0);
		$code->setCode('HASHEDVALUE');
		$codes = [
			$code,
		];

		$this->mapper->expects($this->once())
			->method('getBackupCodes')
			->with($user)
			->will($this->returnValue($codes));
		$this->hasher->expects($this->once())
			->method('verify')
			->with('CHALLENGE', 'HASHEDVALUE', $this->anything())
			->will($this->returnValue(true));
		$this->mapper->expects($this->once())
			->method('update')
			->with($code);

		$this->assertTrue($this->storage->validateCode($user, 'CHALLENGE'));

		$this->assertEquals(1, $code->getUsed());
	}

	public function testValidateUsedCode() {
		$user = $this->getMockBuilder(IUser::class)->getMock();
		$code = new BackupCode();
		$code->setUsed('1');
		$code->setCode('HASHEDVALUE');
		$codes = [
			$code,
		];

		$this->mapper->expects($this->once())
			->method('getBackupCodes')
			->with($user)
			->will($this->returnValue($codes));
		$this->hasher->expects($this->never())
			->method('verify');
		$this->mapper->expects($this->never())
			->method('update');

		$this->assertFalse($this->storage->validateCode($user, 'CHALLENGE'));
	}

	public function testValidateCodeWithWrongHash() {
		$user = $this->getMockBuilder(IUser::class)->getMock();
		$code = new BackupCode();
		$code->setUsed(0);
		$code->setCode('HASHEDVALUE');
		$codes = [
			$code,
		];

		$this->mapper->expects($this->once())
			->method('getBackupCodes')
			->with($user)
			->will($this->returnValue($codes));
		$this->hasher->expects($this->once())
			->method('verify')
			->with('CHALLENGE', 'HASHEDVALUE')
			->will($this->returnValue(false));
		$this->mapper->expects($this->never())
			->method('update');

		$this->assertFalse($this->storage->validateCode($user, 'CHALLENGE'));
	}

}
