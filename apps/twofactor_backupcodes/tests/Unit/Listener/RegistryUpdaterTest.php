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
use OCP\EventDispatcher\Event;
use OCP\IUser;
use Test\TestCase;

class RegistryUpdaterTest extends TestCase {

	/** @var IRegistry */
	private $registry;

	/** @var BackupCodesProvider */
	private $provider;

	/** @var RegistryUpdater */
	private $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->registry = $this->createMock(IRegistry::class);
		$this->provider = $this->createMock(BackupCodesProvider::class);

		$this->listener = new RegistryUpdater($this->registry, $this->provider);
	}

	public function testHandleGenericEvent(): void {
		$event = $this->createMock(Event::class);
		$this->registry->expects($this->never())
			->method('enableProviderFor');

		$this->listener->handle($event);
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
