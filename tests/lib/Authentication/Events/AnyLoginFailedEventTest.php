<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\Events;

use OCP\Authentication\Events\AnyLoginFailedEvent;
use OCP\EventDispatcher\IWebhookCompatibleEvent;
use Test\TestCase;

class AnyLoginFailedEventTest extends TestCase {
	public function testWebhookSerializable(): void {
		$event = new AnyLoginFailedEvent('alice', 'secret-password');

		$this->assertInstanceOf(IWebhookCompatibleEvent::class, $event);
		$this->assertSame(['loginName' => 'alice'], $event->getWebhookSerializable());
	}

	public function testWebhookSerializableNeverContainsPassword(): void {
		$event = new AnyLoginFailedEvent('alice', 'secret-password');

		$this->assertStringNotContainsString('secret-password', json_encode($event->getWebhookSerializable(), JSON_THROW_ON_ERROR));
	}
}
