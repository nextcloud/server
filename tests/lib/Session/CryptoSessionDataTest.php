<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Session;

use OC\Session\CryptoSessionData;
use OC\Session\Memory;
use OCP\Security\ICrypto;

class CryptoSessionDataTest extends Session {
	/** @var \PHPUnit\Framework\MockObject\MockObject|\OCP\Security\ICrypto */
	protected $crypto;

	/** @var \OCP\ISession */
	protected $wrappedSession;

	protected function setUp(): void {
		parent::setUp();

		$this->wrappedSession = new Memory();
		$this->crypto = $this->createMock(ICrypto::class);
		$this->crypto->expects($this->any())
			->method('encrypt')
			->willReturnCallback(function ($input) {
				return '#' . $input . '#';
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
}
