<?php
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Tests\Settings;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\ImageManager;
use OCA\Theming\Settings\Admin;
use OCA\Theming\ThemingDefaults;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use Test\TestCase;

class AdminTest extends TestCase {
	private Admin $admin;
	private IConfig $config;
	private ThemingDefaults $themingDefaults;
	private IInitialState $initialState;
	private IURLGenerator $urlGenerator;
	private ImageManager $imageManager;
	private IL10N $l10n;
	private INavigationManager $navigationManager;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->themingDefaults = $this->createMock(ThemingDefaults::class);
		$this->initialState = $this->createMock(IInitialState::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->imageManager = $this->createMock(ImageManager::class);
		$this->navigationManager = $this->createMock(INavigationManager::class);

		$this->admin = new Admin(
			Application::APP_ID,
			$this->config,
			$this->l10n,
			$this->themingDefaults,
			$this->initialState,
			$this->urlGenerator,
			$this->imageManager,
			$this->navigationManager,
		);
	}

	public function testGetFormNoErrors(): void {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('theme', '')
			->willReturn('');
		$this->themingDefaults
			->expects($this->once())
			->method('getEntity')
			->willReturn('MyEntity');
		$this->themingDefaults
			->expects($this->once())
			->method('getBaseUrl')
			->willReturn('https://example.com');
		$this->themingDefaults
			->expects($this->once())
			->method('getImprintUrl')
			->willReturn('');
		$this->themingDefaults
			->expects($this->once())
			->method('getPrivacyUrl')
			->willReturn('');
		$this->themingDefaults
			->expects($this->once())
			->method('getSlogan')
			->willReturn('MySlogan');
		$this->themingDefaults
			->expects($this->once())
			->method('getDefaultColorPrimary')
			->willReturn('#fff');

		$expected = new TemplateResponse('theming', 'settings-admin');
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetFormWithErrors(): void {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('theme', '')
			->willReturn('MyCustomTheme');
		$this->l10n
			->expects($this->once())
			->method('t')
			->with('You are already using a custom theme. Theming app settings might be overwritten by that.')
			->willReturn('You are already using a custom theme. Theming app settings might be overwritten by that.');
		$this->themingDefaults
			->expects($this->once())
			->method('getEntity')
			->willReturn('MyEntity');
		$this->themingDefaults
			->expects($this->once())
			->method('getBaseUrl')
			->willReturn('https://example.com');
		$this->themingDefaults
			->expects($this->once())
			->method('getImprintUrl')
			->willReturn('');
		$this->themingDefaults
			->expects($this->once())
			->method('getPrivacyUrl')
			->willReturn('');
		$this->themingDefaults
			->expects($this->once())
			->method('getSlogan')
			->willReturn('MySlogan');
		$this->themingDefaults
			->expects($this->once())
			->method('getDefaultColorPrimary')
			->willReturn('#fff');

		$expected = new TemplateResponse('theming', 'settings-admin');
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection(): void {
		$this->assertSame('theming', $this->admin->getSection());
	}

	public function testGetPriority(): void {
		$this->assertSame(5, $this->admin->getPriority());
	}
}
