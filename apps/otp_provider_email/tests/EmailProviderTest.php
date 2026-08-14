<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OTPProviderEmail\Test;

use OCA\OTPProviderEmail\EmailProvider;
use OCP\IL10N;
use OCP\L10N\IFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group('OTP')]
class EmailProviderTest extends TestCase {
	private EmailProvider $emailProvider;
	private IFactory&MockObject $l10nFactory;
	private IL10N&MockObject $l10n;

	protected function setUp(): void {
		parent::setUp();
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10nFactory = $this->createMock(IFactory::class);

		$this->l10nFactory->method('get')->willReturn($this->l10n);
		$this->emailProvider = new EmailProvider($this->l10nFactory);
	}

	public static function dataTestMaskRecipient() {
		return [
			['test@someurl.tld', 't***@s*****l.tld'],
			['test@longerurl.tld', 't***@lo*****rl.tld'],
			['longuser@longerurl.tld', 'lo****er@lo*****rl.tld'],
			['someone@someurl.tld', 's*****e@s*****l.tld'],
			['test@host.tld', 't***@h***.tld'],
			['test@hostt.tld', 't***@h***t.tld'],
		];
	}

	#[DataProvider('dataTestMaskRecipient')]
	public function testMaskRecipient($recipient, $expectedMasked) {
		$this->assertEquals($expectedMasked, $this->emailProvider->maskRecipient($recipient));
	}

}
