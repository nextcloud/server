<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Listener;

use OCA\AdminAudit\IAuditLogger;
use OCA\AdminAudit\Listener\SecurityEventListener;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderChallengeFailed;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderChallengePassed;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserRegistered;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderForUserUnregistered;
use OCP\EventDispatcher\Event;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SecurityEventListenerTest extends TestCase {
	private IAuditLogger&MockObject $logger;

	private SecurityEventListener $listener;

	private MockObject&IUser $user;

	private IProvider&MockObject $provider;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(IAuditLogger::class);
		$this->listener = new SecurityEventListener($this->logger);

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn('myuid');
		$this->user->method('getDisplayName')->willReturn('mydisplayname');
		$this->provider = $this->createMock(IProvider::class);
		$this->provider->method('getDisplayName')->willReturn('myprovider');
	}

	public function testTwoFactorFailed(): void {
		$this->logger->expects($this->once())
			->method('info')
			->with(
				$this->equalTo('Failed two factor attempt by user mydisplayname (myuid) with provider myprovider'),
				['app' => 'admin_audit', 'operation' => 'auth.twofactor.failed']
			);

		$this->listener->handle(new twoFactorProviderChallengeFailed($this->user, $this->provider));
	}

	public function testTwoFactorSuccess(): void {
		$this->logger->expects($this->once())
			->method('info')
			->with(
				$this->equalTo('Successful two factor attempt by user mydisplayname (myuid) with provider myprovider'),
				['app' => 'admin_audit', 'operation' => 'auth.twofactor.passed']
			);

		$this->listener->handle(new TwoFactorProviderChallengePassed($this->user, $this->provider));
	}

	public function testUnrelatedEventIsIgnored(): void {
		$this->logger->expects($this->never())
			->method($this->anything());

		$this->listener->handle(new Event());
	}

	public function testTwoFactorProviderForUserRegistered(): void {
		$this->provider->method('getId')->willReturn('totp');

		$this->logger->expects($this->once())
			->method('info')
			->with(
				'Two-factor provider totp was enabled for user myuid',
				$this->callback(fn (array $context): bool => $context['app'] === 'admin_audit' && $context['operation'] === 'auth.twofactor_provider.enabled'),
			);

		$this->listener->handle(new TwoFactorProviderForUserRegistered($this->user, $this->provider));
	}

	public function testTwoFactorProviderForUserUnregistered(): void {
		$this->provider->method('getId')->willReturn('totp');

		$this->logger->expects($this->once())
			->method('info')
			->with(
				'Two-factor provider totp was disabled for user myuid',
				$this->callback(fn (array $context): bool => $context['app'] === 'admin_audit' && $context['operation'] === 'auth.twofactor_provider.disabled'),
			);

		$this->listener->handle(new TwoFactorProviderForUserUnregistered($this->user, $this->provider));
	}
}
