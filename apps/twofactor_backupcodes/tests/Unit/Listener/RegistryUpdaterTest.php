<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Tests\Unit\Listener;

use OCA\TwoFactorBackupCodes\Event\CodesGenerated;
use OCA\TwoFactorBackupCodes\Listener\RegistryUpdater;
use OCA\TwoFactorBackupCodes\Provider\BackupCodesProvider;
use OCP\Authentication\TwoFactorAuth\IRegistry;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class RegistryUpdaterTest extends TestCase {
	private IRegistry&MockObject $registry;
	private BackupCodesProvider&MockObject $provider;
	private RegistryUpdater $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->registry = $this->createMock(IRegistry::class);
		$this->provider = $this->createMock(BackupCodesProvider::class);

		$this->listener = new RegistryUpdater($this->registry, $this->provider);
	}

	public function testHandleCodesGeneratedEvent(): void {
		$user = $this->createMock(IUser::class);
		$event = new CodesGenerated($user);
		$this->registry->expects($this->once())
			->method('enableProviderFor')
			->with(
				$this->provider,
				$user
			);

		$this->listener->handle($event);
	}
}
