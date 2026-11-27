<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Authentication\TwoFactorAuth;

use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderChallengeFailed;
use OCP\EventDispatcher\IWebhookCompatibleEvent;
use OCP\IUser;
use Test\TestCase;

class TwoFactorProviderChallengeFailedTest extends TestCase {
	public function testWebhookSerializable(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');
		$user->method('getDisplayName')->willReturn('Alice');
		$provider = $this->createMock(IProvider::class);
		$provider->method('getId')->willReturn('totp');
		$provider->method('getDisplayName')->willReturn('TOTP (Authenticator app)');

		$event = new TwoFactorProviderChallengeFailed($user, $provider);

		$this->assertInstanceOf(IWebhookCompatibleEvent::class, $event);
		$this->assertSame([
			'user' => [
				'uid' => 'alice',
				'displayName' => 'Alice',
			],
			'provider' => [
				'id' => 'totp',
				'displayName' => 'TOTP (Authenticator app)',
			],
		], $event->getWebhookSerializable());
	}
}
