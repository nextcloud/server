<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OTPProviderEmail\Test;

use OCA\OTPProviderEmail\AppInfo\Application;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\Defaults;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\OneTimePassword\IManager as IOtpManager;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group('OTP')]
class ApplicationTest extends TestCase {
	private Application $application;

	protected function setUp(): void {
		parent::setUp();

		$this->application = new Application();
	}

	public function testRegister(): void {
		$manager = $this->createMock(IOtpManager::class);
		$context = $this->createMock(IBootContext::class);

		$l10nFactory = $this->createMock(IFactory::class);
		$mailer = $this->createMock(IMailer::class);
		$defaults = $this->createMock(Defaults::class);

		$context->method('injectFn')->willReturnCallback(
			function ($fn) use ($defaults, $mailer, $l10nFactory, $manager): void {
				$fn($manager, $l10nFactory, $mailer, $defaults);
			});

		$manager->expects($this->once())
			->method('registerOTPProvider');

		$this->application->boot($context);
	}
}
