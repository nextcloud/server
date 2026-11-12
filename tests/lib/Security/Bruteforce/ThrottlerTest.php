<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\Bruteforce;

use OC\Security\Bruteforce\Backend\IBackend;
use OC\Security\Bruteforce\Throttler;
use OC\Security\Ip\BruteforceAllowList;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use Test\TestCase;

class ThrottlerTest extends TestCase {
	private Throttler $throttler;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->throttler = $this->createInstanceWithMocks(Throttler::class);
	}

	public function testRegisterAttemptFromAllowListedIp(): void {
		$this->getAutoMock(IConfig::class)->method('getSystemValueBool')
			->with('auth.bruteforce.protection.enabled', true)
			->willReturn(true);
		$this->getAutoMock(BruteforceAllowList::class)->method('isBypassListed')
			->with('10.10.10.10')
			->willReturn(true);

		$this->getAutoMock(IEventDispatcher::class)->expects($this->once())
			->method('dispatchTyped')
			->with(new CriticalActionPerformedEvent(
				'Bruteforce attempt from "{ip}" detected for action "{action}" not throttled due to allow-list.',
				['ip' => '10.10.10.10', 'action' => 'login'],
				false,
				'auth.bruteforce.bypassed',
			));
		$this->getAutoMock(IBackend::class)->expects($this->never())
			->method('registerAttempt');

		$this->throttler->registerAttempt('login', '10.10.10.10');
	}

	public function testRegisterAttempt(): void {
		$this->getAutoMock(IConfig::class)->method('getSystemValueBool')
			->with('auth.bruteforce.protection.enabled', true)
			->willReturn(true);
		$this->getAutoMock(BruteforceAllowList::class)->method('isBypassListed')
			->with('10.10.10.10')
			->willReturn(false);

		$this->getAutoMock(IEventDispatcher::class)->expects($this->never())
			->method('dispatchTyped');
		$this->getAutoMock(IBackend::class)->expects($this->once())
			->method('registerAttempt')
			->with('10.10.10.10', '10.10.10.10/32', $this->anything(), 'login', []);

		$this->throttler->registerAttempt('login', '10.10.10.10');
	}

	public function testRegisterAttemptWithProtectionDisabled(): void {
		$this->getAutoMock(IConfig::class)->method('getSystemValueBool')
			->with('auth.bruteforce.protection.enabled', true)
			->willReturn(false);

		$this->getAutoMock(IEventDispatcher::class)->expects($this->never())
			->method('dispatchTyped');
		$this->getAutoMock(IBackend::class)->expects($this->never())
			->method('registerAttempt');

		$this->throttler->registerAttempt('login', '10.10.10.10');
	}
}
