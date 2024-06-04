<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\AdminAudit\Tests\Actions;

use OCA\AdminAudit\Actions\Security;
use OCA\AdminAudit\AuditLogger;
use OCP\Authentication\TwoFactorAuth\IProvider;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SecurityTest extends TestCase {
	private AuditLogger|MockObject $logger;

	private Security $security;

	private MockObject|IUser $user;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(AuditLogger::class);
		$this->security = new Security($this->logger);

		$this->user = $this->createMock(IUser::class);
		$this->user->method('getUID')->willReturn('myuid');
		$this->user->method('getDisplayName')->willReturn('mydisplayname');
	}

	public function testTwofactorFailed() {
		$this->logger->expects($this->once())
			->method('info')
			->with(
				$this->equalTo('Failed two factor attempt by user mydisplayname (myuid) with provider myprovider'),
				['app' => 'admin_audit']
			);

		$provider = $this->createMock(IProvider::class);
		$provider->method('getDisplayName')
			->willReturn('myprovider');

		$this->security->twofactorFailed($this->user, $provider);
	}

	public function testTwofactorSuccess() {
		$this->logger->expects($this->once())
			->method('info')
			->with(
				$this->equalTo('Successful two factor attempt by user mydisplayname (myuid) with provider myprovider'),
				['app' => 'admin_audit']
			);

		$provider = $this->createMock(IProvider::class);
		$provider->method('getDisplayName')
			->willReturn('myprovider');

		$this->security->twofactorSuccess($this->user, $provider);
	}
}
