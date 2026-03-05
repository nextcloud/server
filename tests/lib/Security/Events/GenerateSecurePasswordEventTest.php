<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\Events;

use OCP\Security\Events\GenerateSecurePasswordEvent;
use OCP\Security\PasswordContext;

class GenerateSecurePasswordEventTest extends \Test\TestCase {

	public function testDefaultProperties(): void {
		$event = new GenerateSecurePasswordEvent();
		$this->assertNull($event->getPassword());
		$this->assertEquals(PasswordContext::ACCOUNT, $event->getContext());
	}

	public function testSettingPassword(): void {
		$event = new GenerateSecurePasswordEvent();
		$event->setPassword('example');
		$this->assertEquals('example', $event->getPassword());
	}

	public function testSettingContext(): void {
		$event = new GenerateSecurePasswordEvent(PasswordContext::SHARING);
		$this->assertEquals(PasswordContext::SHARING, $event->getContext());
	}
}
