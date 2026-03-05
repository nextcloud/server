<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Tests\Unit\Service;

use OCA\TwoFactorBackupCodes\Db\BackupCode;
use OCA\TwoFactorBackupCodes\Db\BackupCodeMapper;
use OCA\TwoFactorBackupCodes\Event\CodesGenerated;
use OCA\TwoFactorBackupCodes\Service\BackupCodeStorage;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IUser;
use OCP\Security\IHasher;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class BackupCodeStorageTest extends TestCase {
	private BackupCodeMapper&MockObject $mapper;
	private ISecureRandom&MockObject $random;
	private IHasher&MockObject $hasher;
	private IEventDispatcher&MockObject $eventDispatcher;
	private BackupCodeStorage $storage;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(BackupCodeMapper::class);
		$this->random = $this->createMock(ISecureRandom::class);
		$this->hasher = $this->createMock(IHasher::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$this->storage = new BackupCodeStorage($this->mapper, $this->random, $this->hasher, $this->eventDispatcher);
	}

	public function testCreateCodes(): void {
		$user = $this->createMock(IUser::class);
		$number = 5;
		$user->method('getUID')->willReturn('fritz');
		$this->random->expects($this->exactly($number))
			->method('generate')
			->with(16, ISecureRandom::CHAR_HUMAN_READABLE)
			->willReturn('CODEABCDEF');
		$this->hasher->expects($this->exactly($number))
			->method('hash')
			->with('CODEABCDEF')
			->willReturn('HASHEDCODE');
		$row = new BackupCode();
		$row->setUserId('fritz');
		$row->setCode('HASHEDCODE');
		$row->setUsed(0);
		$this->mapper->expects($this->exactly($number))
			->method('insert')
			->with($this->equalTo($row));
		$this->eventDispatcher->expects($this->once())
			->method('dispatchTyped')
			->with(
				$this->equalTo(new CodesGenerated($user))
			);

		$codes = $this->storage->createCodes($user, $number);
		$this->assertCount($number, $codes);
		foreach ($codes as $code) {
			$this->assertEquals('CODEABCDEF', $code);
		}
	}

	public function testHasBackupCodes(): void {
		$user = $this->createMock(IUser::class);
		$codes = [
			new BackupCode(),
			new BackupCode(),
		];

		$this->mapper->expects($this->once())
			->method('getBackupCodes')
			->with($user)
			->willReturn($codes);

		$this->assertTrue($this->storage->hasBackupCodes($user));
	}

	public function testHasBackupCodesNoCodes(): void {
		$user = $this->createMock(IUser::class);
		$codes = [];

		$this->mapper->expects($this->once())
			->method('getBackupCodes')
			->with($user)
			->willReturn($codes);

		$this->assertFalse($this->storage->hasBackupCodes($user));
	}

	public function testGetBackupCodeState(): void {
		$user = $this->createMock(IUser::class);

		$code1 = new BackupCode();
		$code1->setUsed(1);
		$code2 = new BackupCode();
		$code2->setUsed(0);
		$codes = [
			$code1,
			$code2,
		];

		$this->mapper->expects($this->once())
			->method('getBackupCodes')
			->with($user)
			->willReturn($codes);

		$expected = [
			'enabled' => true,
			'total' => 2,
			'used' => 1,
		];
		$this->assertEquals($expected, $this->storage->getBackupCodesState($user));
	}

	public function testGetBackupCodeDisabled(): void {
		$user = $this->createMock(IUser::class);

		$codes = [];

		$this->mapper->expects($this->once())
			->method('getBackupCodes')
			->with($user)
			->willReturn($codes);

		$expected = [
			'enabled' => false,
			'total' => 0,
			'used' => 0,
		];
		$this->assertEquals($expected, $this->storage->getBackupCodesState($user));
	}

	public function testValidateCode(): void {
		$user = $this->createMock(IUser::class);
		$code = new BackupCode();
		$code->setUsed(0);
		$code->setCode('HASHEDVALUE');
		$codes = [
			$code,
		];

		$this->mapper->expects($this->once())
			->method('getBackupCodes')
			->with($user)
			->willReturn($codes);
		$this->hasher->expects($this->once())
			->method('verify')
			->with('CHALLENGE', 'HASHEDVALUE', $this->anything())
			->willReturn(true);
		$this->mapper->expects($this->once())
			->method('update')
			->with($code);

		$this->assertTrue($this->storage->validateCode($user, 'CHALLENGE'));

		$this->assertEquals(1, $code->getUsed());
	}

	public function testValidateUsedCode(): void {
		$user = $this->createMock(IUser::class);
		$code = new BackupCode();
		$code->setUsed(1);
		$code->setCode('HASHEDVALUE');
		$codes = [
			$code,
		];

		$this->mapper->expects($this->once())
			->method('getBackupCodes')
			->with($user)
			->willReturn($codes);
		$this->hasher->expects($this->never())
			->method('verify');
		$this->mapper->expects($this->never())
			->method('update');

		$this->assertFalse($this->storage->validateCode($user, 'CHALLENGE'));
	}

	public function testValidateCodeWithWrongHash(): void {
		$user = $this->createMock(IUser::class);
		$code = new BackupCode();
		$code->setUsed(0);
		$code->setCode('HASHEDVALUE');
		$codes = [
			$code,
		];

		$this->mapper->expects($this->once())
			->method('getBackupCodes')
			->with($user)
			->willReturn($codes);
		$this->hasher->expects($this->once())
			->method('verify')
			->with('CHALLENGE', 'HASHEDVALUE')
			->willReturn(false);
		$this->mapper->expects($this->never())
			->method('update');

		$this->assertFalse($this->storage->validateCode($user, 'CHALLENGE'));
	}

	public function testDeleteCodes(): void {
		$user = $this->createMock(IUser::class);
		$this->mapper->expects($this->once())
			->method('deleteCodes')
			->with($user);

		$this->storage->deleteCodes($user);
	}
}
