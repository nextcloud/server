<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Mail;

use OC\Mail\EmailValidator;
use OCP\IAppConfig;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class EmailValidatorTest extends TestCase {
	private EmailValidator $emailValidator;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->emailValidator = $this->createInstanceWithMocks(EmailValidator::class);
	}

	public static function mailAddressProvider(): array {
		return [
			['lukas@nextcloud.com', true, false],
			['lukas@localhost', true, false],
			['lukas@192.168.1.1', true, false],
			['lukas@éxämplè.com', true, false],
			['asdf', false, false],
			['', false, false],
			['lukas@nextcloud.org@nextcloud.com', false, false],
			['test@localhost', true, false],
			['test@localhost', false, true],
		];
	}

	#[DataProvider('mailAddressProvider')]
	public function testIsValid($email, $expected, $strict): void {
		$this->mocks[IAppConfig::class]
			->expects($this->atMost(1))
			->method('getValueString')
			->with('core', 'enforce_strict_email_check', 'yes')
			->willReturn($strict ? 'yes' : 'no');
		$this->assertSame($expected, $this->emailValidator->isValid($email));
	}
}
