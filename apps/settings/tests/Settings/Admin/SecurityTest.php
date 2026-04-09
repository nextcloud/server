<?php

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Settings\Tests\Settings\Admin;

use OC\Authentication\TwoFactorAuth\MandatoryTwoFactor;
use OC\Encryption\Manager;
use OCA\Settings\Settings\Admin\Security;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IURLGenerator;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SecurityTest extends TestCase {
	private Manager&MockObject $manager;
	private IUserManager&MockObject $userManager;
	private MandatoryTwoFactor&MockObject $mandatoryTwoFactor;
	private IInitialState&MockObject $initialState;
	private Security $admin;

	protected function setUp(): void {
		parent::setUp();
		$this->manager = $this->createMock(Manager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->mandatoryTwoFactor = $this->createMock(MandatoryTwoFactor::class);
		$this->initialState = $this->createMock(IInitialState::class);

		$this->admin = new Security(
			$this->manager,
			$this->userManager,
			$this->mandatoryTwoFactor,
			$this->initialState,
			$this->createMock(IURLGenerator::class)
		);
	}

	public static function encryptionSettingsProvider(): array {
		return [
			[true],
			[false],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'encryptionSettingsProvider')]
	public function testGetFormWithOnlyOneBackend(bool $enabled): void {
		$this->manager
			->expects($this->once())
			->method('isEnabled')
			->willReturn($enabled);
		$this->manager
			->expects($this->once())
			->method('isReady')
			->willReturn($enabled);
		$this->manager
			->expects($this->once())
			->method('getEncryptionModules')
			->willReturn([]);
		$this->userManager
			->expects($this->once())
			->method('getBackends')
			->willReturn(['entry']);
		$expected = new TemplateResponse(
			'settings',
			'settings/admin/security',
			[],
			''
		);
		$this->assertEquals($expected, $this->admin->getForm());
	}

	/**
	 * @param bool $enabled
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'encryptionSettingsProvider')]
	public function testGetFormWithMultipleBackends($enabled): void {
		$this->manager
			->expects($this->once())
			->method('isEnabled')
			->willReturn($enabled);
		$this->manager
			->expects($this->once())
			->method('isReady')
			->willReturn($enabled);
		$this->manager
			->expects($this->once())
			->method('getEncryptionModules')
			->willReturn([]);
		$this->userManager
			->expects($this->once())
			->method('getBackends')
			->willReturn(['entry', 'entry']);
		$expected = new TemplateResponse(
			'settings',
			'settings/admin/security',
			[ ],
			''
		);
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection(): void {
		$this->assertSame('security', $this->admin->getSection());
	}

	public function testGetPriority(): void {
		$this->assertSame(10, $this->admin->getPriority());
	}
}
