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
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Settings\IDelegatedSettings;
use OCP\Settings\ISettings;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class SecurityTest extends TestCase {
	/** @var Security */
	private $admin;
	/** @var Manager */
	private $manager;
	/** @var IUserManager */
	private $userManager;
	/** @var MandatoryTwoFactor|MockObject */
	private $mandatoryTwoFactor;
	/** @var IInitialState|MockObject */
	private $initialState;
	private IL10N&MockObject $l10n;

	protected function setUp(): void {
		parent::setUp();
		$this->manager = $this->getMockBuilder(Manager::class)->disableOriginalConstructor()->getMock();
		$this->userManager = $this->getMockBuilder(IUserManager::class)->getMock();
		$this->mandatoryTwoFactor = $this->createMock(MandatoryTwoFactor::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->l10n = $this->createMock(IL10N::class);

		$this->admin = new Security(
			$this->manager,
			$this->userManager,
			$this->mandatoryTwoFactor,
			$this->initialState,
			$this->createMock(IURLGenerator::class),
			$this->l10n,
		);
	}

	/**
	 * @return array
	 */
	public function encryptionSettingsProvider() {
		return [
			[true],
			[false],
		];
	}

	/**
	 * @dataProvider encryptionSettingsProvider
	 * @param bool $enabled
	 */
	public function testGetFormWithOnlyOneBackend($enabled): void {
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
	 * @dataProvider encryptionSettingsProvider
	 * @param bool $enabled
	 */
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

	public function testGetName(): void {
		$translatedName = 'Two-Factor Authentication';
		$this->l10n->expects($this->once())
			->method('t')
			->with('Two-Factor Authentication')
			->willReturn($translatedName);

		$this->assertSame($translatedName, $this->admin->getName());
	}

	public function testGetAuthorizedAppConfig(): void {
		$this->assertEquals([], $this->admin->getAuthorizedAppConfig());
		$this->assertIsArray($this->admin->getAuthorizedAppConfig());
	}

	public function testImplementsIDelegatedSettings(): void {
		$this->assertInstanceOf(IDelegatedSettings::class, $this->admin);
		$this->assertInstanceOf(ISettings::class, $this->admin);
	}

	public function testGetNameReturnsString(): void {
		$this->l10n->expects($this->once())
			->method('t')
			->with('Two-Factor Authentication')
			->willReturn('Translated Name');

		$name = $this->admin->getName();
		$this->assertIsString($name);
		$this->assertNotEmpty($name);
	}
}
