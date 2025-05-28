<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Encryption\Tests\Users;

use OCA\Encryption\Crypto\Crypt;
use OCA\Encryption\KeyManager;
use OCA\Encryption\Users\Setup;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SetupTest extends TestCase {

	protected Setup $instance;

	protected KeyManager&MockObject $keyManagerMock;
	protected Crypt&MockObject $cryptMock;

	protected function setUp(): void {
		parent::setUp();
		$this->cryptMock = $this->getMockBuilder(Crypt::class)
			->disableOriginalConstructor()
			->getMock();

		$this->keyManagerMock = $this->getMockBuilder(KeyManager::class)
			->disableOriginalConstructor()
			->getMock();

		$this->instance = new Setup(
			$this->cryptMock,
			$this->keyManagerMock);
	}


	public function testSetupSystem(): void {
		$this->keyManagerMock->expects($this->once())->method('validateShareKey');
		$this->keyManagerMock->expects($this->once())->method('validateMasterKey');

		$this->instance->setupSystem();
	}

	/**
	 * @dataProvider dataTestSetupUser
	 *
	 * @param bool $hasKeys
	 * @param bool $expected
	 */
	public function testSetupUser($hasKeys, $expected): void {
		$this->keyManagerMock->expects($this->once())->method('userHasKeys')
			->with('uid')->willReturn($hasKeys);

		if ($hasKeys) {
			$this->keyManagerMock->expects($this->never())->method('storeKeyPair');
		} else {
			$this->cryptMock->expects($this->once())->method('createKeyPair')->willReturn(['publicKey' => 'publicKey', 'privateKey' => 'privateKey']);
			$this->keyManagerMock->expects($this->once())->method('storeKeyPair')
				->with('uid', 'password', ['publicKey' => 'publicKey', 'privateKey' => 'privateKey'])->willReturn(true);
		}

		$this->assertSame($expected,
			$this->instance->setupUser('uid', 'password')
		);
	}

	public static function dataTestSetupUser(): array {
		return [
			[true, true],
			[false, true]
		];
	}
}
