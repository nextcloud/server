<?php
/**
 * SPDX-FileCopyrightText: 2017-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Session;

use OC\Session\CryptoSessionData;
use OCP\ISession;
use Test\TestCase;

class CryptoWrappingTest extends TestCase {
	/** @var \PHPUnit\Framework\MockObject\MockObject|\OCP\Security\ICrypto */
	protected $crypto;

	/** @var \PHPUnit\Framework\MockObject\MockObject|\OCP\ISession */
	protected $wrappedSession;

	/** @var \OC\Session\CryptoSessionData */
	protected $instance;

	protected function setUp(): void {
		parent::setUp();

		$this->wrappedSession = $this->getMockBuilder(ISession::class)
			->disableOriginalConstructor()
			->getMock();
		$this->crypto = $this->getMockBuilder('OCP\Security\ICrypto')
			->disableOriginalConstructor()
			->getMock();
		$this->crypto->expects($this->any())
			->method('encrypt')
			->willReturnCallback(function ($input) {
				return $input;
			});
		$this->crypto->expects($this->any())
			->method('decrypt')
			->willReturnCallback(function ($input) {
				if ($input === '') {
					return '';
				}
				return substr($input, 1, -1);
			});

		$this->instance = new CryptoSessionData($this->wrappedSession, $this->crypto, 'PASS');
	}

	public function testUnwrappingGet(): void {
		$unencryptedValue = 'foobar';
		$encryptedValue = $this->crypto->encrypt($unencryptedValue);

		$this->wrappedSession->expects($this->once())
			->method('get')
			->with('encrypted_session_data')
			->willReturnCallback(function () use ($encryptedValue) {
				return $encryptedValue;
			});

		$this->assertSame($unencryptedValue, $this->wrappedSession->get('encrypted_session_data'));
	}
}
