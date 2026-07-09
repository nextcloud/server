<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\OTPProviderEmail\Test;

use OCA\OTPProviderEmail\AppInfo\Application;
use OCA\OTPProviderEmail\Listener\GetOneTimePasswordProvidersEventListener;
use OCA\OTPProviderEmail\Listener\SendOneTimePasswordEventListener;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\OneTimePassword\Events\GetOneTimePasswordProvidersEvent;
use OCP\OneTimePassword\Events\SendOneTimePasswordEvent;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group('OTP')]
class ApplicationTest extends TestCase {
	private Application $application;

	protected function setUp(): void {
		parent::setUp();

		$this->application = new Application();
	}

	public function testRegister(): void {
		$context = $this->createMock(IRegistrationContext::class);

		$expectedEvents = [
			GetOneTimePasswordProvidersEvent::class => GetOneTimePasswordProvidersEventListener::class,
			SendOneTimePasswordEvent::class => SendOneTimePasswordEventListener::class
		];

		$eventClass = null;

		$context->expects($this->exactly(2))->method('registerEventListener')->with(
			$this->callback(function ($event) use (&$eventClass) {
				$eventClass = $event;
				return true;
			}),
			$this->callback(function ($listener) use (&$eventClass, &$expectedEvents) {
				$this->assertContains($eventClass, array_keys($expectedEvents));
				$this->assertEquals($expectedEvents[$eventClass], $listener);

				unset($expectedEvents[$eventClass]);
				$eventClass = null;
				return true;
			})
		);
		$this->application->register($context);
	}
}
