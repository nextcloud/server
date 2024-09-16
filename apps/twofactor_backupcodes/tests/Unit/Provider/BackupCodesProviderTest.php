<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\TwoFactorBackupCodes\Tests\Unit\Provider;

use OC\App\AppManager;
use OCA\TwoFactorBackupCodes\Provider\BackupCodesProvider;
use OCA\TwoFactorBackupCodes\Service\BackupCodeStorage;
use OCP\IInitialStateService;
use OCP\IL10N;
use OCP\IUser;
use OCP\Template;
use Test\TestCase;

class BackupCodesProviderTest extends TestCase {

	/** @var string */
	private $appName;

	/** @var BackupCodeStorage|\PHPUnit\Framework\MockObject\MockObject */
	private $storage;

	/** @var IL10N|\PHPUnit\Framework\MockObject\MockObject */
	private $l10n;

	/** @var AppManager|\PHPUnit\Framework\MockObject\MockObject */
	private $appManager;

	/** @var IInitialStateService|\PHPUnit\Framework\MockObject\MockObject */
	private $initialState;

	/** @var BackupCodesProvider */
	private $provider;

	protected function setUp(): void {
		parent::setUp();

		$this->appName = 'twofactor_backupcodes';
		$this->storage = $this->createMock(BackupCodeStorage::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->appManager = $this->createMock(AppManager::class);
		$this->initialState = $this->createMock(IInitialStateService::class);

		$this->provider = new BackupCodesProvider($this->appName, $this->storage, $this->l10n, $this->appManager, $this->initialState);
	}

	public function testGetId(): void {
		$this->assertEquals('backup_codes', $this->provider->getId());
	}

	public function testGetDisplayName(): void {
		$this->l10n->expects($this->once())
			->method('t')
			->with('Backup code')
			->willReturn('l10n backup code');
		$this->assertSame('l10n backup code', $this->provider->getDisplayName());
	}

	public function testGetDescription(): void {
		$this->l10n->expects($this->once())
			->method('t')
			->with('Use backup code')
			->willReturn('l10n use backup code');
		$this->assertSame('l10n use backup code', $this->provider->getDescription());
	}

	public function testGetTempalte(): void {
		$user = $this->getMockBuilder(IUser::class)->getMock();
		$expected = new Template('twofactor_backupcodes', 'challenge');

		$this->assertEquals($expected, $this->provider->getTemplate($user));
	}

	public function testVerfiyChallenge(): void {
		$user = $this->getMockBuilder(IUser::class)->getMock();
		$challenge = 'xyz';

		$this->storage->expects($this->once())
			->method('validateCode')
			->with($user, $challenge)
			->willReturn(false);

		$this->assertFalse($this->provider->verifyChallenge($user, $challenge));
	}

	public function testIsTwoFactorEnabledForUser(): void {
		$user = $this->getMockBuilder(IUser::class)->getMock();

		$this->storage->expects($this->once())
			->method('hasBackupCodes')
			->with($user)
			->willReturn(true);

		$this->assertTrue($this->provider->isTwoFactorAuthEnabledForUser($user));
	}

	public function testIsActiveNoProviders(): void {
		$user = $this->getMockBuilder(IUser::class)->getMock();

		$this->appManager->expects($this->once())
			->method('getEnabledAppsForUser')
			->with($user)
			->willReturn([
				'twofactor_backupcodes',
				'mail',
			]);
		$this->appManager->expects($this->once())
			->method('getAppInfo')
			->with('mail')
			->willReturn([
				'two-factor-providers' => [],
			]);

		$this->assertFalse($this->provider->isActive($user));
	}

	public function testIsActiveWithProviders(): void {
		$user = $this->getMockBuilder(IUser::class)->getMock();

		$this->appManager->expects($this->once())
			->method('getEnabledAppsForUser')
			->with($user)
			->willReturn([
				'twofactor_backupcodes',
				'twofactor_u2f',
			]);
		$this->appManager->expects($this->once())
			->method('getAppInfo')
			->with('twofactor_u2f')
			->willReturn([
				'two-factor-providers' => [
					'OCA\TwoFactorU2F\Provider\U2FProvider',
				],
			]);

		$this->assertTrue($this->provider->isActive($user));
	}

	public function testDisable(): void {
		$user = $this->getMockBuilder(IUser::class)->getMock();
		$this->storage->expects(self::once())
			->method('deleteCodes')
			->with($user);

		$this->provider->disableFor($user);
	}
}
