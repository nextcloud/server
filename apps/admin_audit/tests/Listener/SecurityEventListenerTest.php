<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\AdminAudit\Tests\Listener;

use OCA\AdminAudit\AuditLogger;
use OCA\AdminAudit\Listener\SecurityEventListener;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderChallengeFailed;
use OCP\Authentication\TwoFactorAuth\TwoFactorProviderChallengePassed;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SecurityEventListenerTest extends TestCase {
	private AuditLogger|MockObject $logger;

	private SecurityEventListener $security;

	private MockObject|IUser $user;

	/** @var IProvider&MockObject */
	private $provider;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(AuditLogger::class);
		$this->security = new SecurityEventListener($this->logger);

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn('myuid');
		$this->user->method('getDisplayName')->willReturn('mydisplayname');
		$this->provider = $this->createMock(IProvider::class);
		$this->provider->method('getDisplayName')->willReturn('myprovider');
	}

	public function testTwofactorFailed(): void {
		$this->logger->expects($this->once())
			->method('info')
			->with(
				$this->equalTo('Failed two factor attempt by user mydisplayname (myuid) with provider myprovider'),
				['app' => 'admin_audit']
			);

		$this->security->handle(new twoFactorProviderChallengeFailed($this->user, $this->provider));
	}

	public function testTwofactorSuccess(): void {
		$this->logger->expects($this->once())
			->method('info')
			->with(
				$this->equalTo('Successful two factor attempt by user mydisplayname (myuid) with provider myprovider'),
				['app' => 'admin_audit']
			);

		$this->security->handle(new TwoFactorProviderChallengePassed($this->user, $this->provider));
	}
}
