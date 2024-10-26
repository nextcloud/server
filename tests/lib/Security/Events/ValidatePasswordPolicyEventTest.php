<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\Events;

use OCP\Security\Events\ValidatePasswordPolicyEvent;
use OCP\Security\PasswordContext;

class ValidatePasswordPolicyEventTest extends \Test\TestCase {

	public function testDefaultProperties(): void {
		$password = 'example';
		$event = new ValidatePasswordPolicyEvent($password);
		$this->assertEquals($password, $event->getPassword());
		$this->assertEquals(PasswordContext::ACCOUNT, $event->getContext());
	}

	public function testSettingContext(): void {
		$event = new ValidatePasswordPolicyEvent('example', PasswordContext::SHARING);
		$this->assertEquals(PasswordContext::SHARING, $event->getContext());
	}
}
