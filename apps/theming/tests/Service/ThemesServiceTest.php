<?php
/**
 * @copyright Copyright (c) 2022 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Theming\Tests\Service;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\ImageManager;
use OCA\Theming\ITheme;
use OCA\Theming\Themes\DarkHighContrastTheme;
use OCA\Theming\Themes\DarkTheme;
use OCA\Theming\Themes\DefaultTheme;
use OCA\Theming\Themes\DyslexiaFont;
use OCA\Theming\Themes\HighContrastTheme;
use OCA\Theming\Service\ThemesService;
use OCA\Theming\Themes\LightTheme;
use OCA\Theming\ThemingDefaults;
use OCA\Theming\Util;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ThemesServiceTest extends TestCase {
	/** @var ThemesService */
	private $themesService;

	/** @var IUserSession|MockObject */
	private $userSession;
	/** @var IConfig|MockObject */
	private $config;
	/** @var ThemingDefaults|MockObject */
	private $themingDefaults;

	/** @var ITheme[] */
	private $themes;

	protected function setUp(): void {
		$this->userSession = $this->createMock(IUserSession::class);
		$this->config = $this->createMock(IConfig::class);
		$this->themingDefaults = $this->createMock(ThemingDefaults::class);

		$this->themingDefaults->expects($this->any())
			->method('getColorPrimary')
			->willReturn('#0082c9');

		$this->initThemes();

		$this->themesService = new ThemesService(
			$this->userSession,
			$this->config,
			...array_values($this->themes)
		);

		parent::setUp();
	}

	public function testGetThemes() {
		$expected = [
			'default',
			'light',
			'dark',
			'highcontrast',
			'dark-highcontrast',
			'opendyslexic',
		];
		$this->assertEquals($expected, array_keys($this->themesService->getThemes()));
	}


	public function dataTestEnableTheme() {
		return [
			['default', [], ['default']],
			['dark', [], ['dark']],
			['dark', ['dark'], ['dark']],
			['opendyslexic', ['dark'], ['dark', 'opendyslexic']],
			['dark', ['highcontrast', 'opendyslexic'], ['opendyslexic', 'dark']],
		];
	}

	/**
	 * @dataProvider dataTestEnableTheme
	 *
	 * @param string $toEnable
	 * @param string[] $enabledThemes
	 * @param string[] $expectedEnabled
	 */
	public function testEnableTheme(string $toEnable, array $enabledThemes, array $expectedEnabled) {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');

		$this->config->expects($this->once())
			->method('getUserValue')
			->with('user', Application::APP_ID, 'enabled-themes', '[]')
			->willReturn(json_encode($enabledThemes));

		$this->assertEquals($expectedEnabled, $this->themesService->enableTheme($this->themes[$toEnable]));
	}


	public function dataTestDisableTheme() {
		return [
			['dark', [], []],
			['dark', ['dark'], []],
			['opendyslexic', ['dark', 'opendyslexic'], ['dark'], ],
			['highcontrast', ['opendyslexic'], ['opendyslexic']],
		];
	}

	/**
	 * @dataProvider dataTestDisableTheme
	 *
	 * @param string $toEnable
	 * @param string[] $enabledThemes
	 * @param string[] $expectedEnabled
	 */
	public function testDisableTheme(string $toDisable, array $enabledThemes, array $expectedEnabled) {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');

		$this->config->expects($this->once())
			->method('getUserValue')
			->with('user', Application::APP_ID, 'enabled-themes', '[]')
			->willReturn(json_encode($enabledThemes));
	

		$this->assertEquals($expectedEnabled, $this->themesService->disableTheme($this->themes[$toDisable]));
	}


	public function dataTestIsEnabled() {
		return [
			['dark', [], false],
			['dark', ['dark'], true],
			['opendyslexic', ['dark', 'opendyslexic'], true],
			['highcontrast', ['opendyslexic'], false],
		];
	}

	/**
	 * @dataProvider dataTestIsEnabled
	 *
	 * @param string $toEnable
	 * @param string[] $enabledThemes
	 */
	public function testIsEnabled(string $themeId, array $enabledThemes, $expected) {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');

		$this->config->expects($this->once())
			->method('getUserValue')
			->with('user', Application::APP_ID, 'enabled-themes', '[]')
			->willReturn(json_encode($enabledThemes));
	

		$this->assertEquals($expected, $this->themesService->isEnabled($this->themes[$themeId]));
	}

	public function testGetEnabledThemes() {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');


		$this->config->expects($this->once())
			->method('getUserValue')
			->with('user', Application::APP_ID, 'enabled-themes', '[]')
			->willReturn(json_encode([]));
		$this->config->expects($this->once())
			->method('getSystemValueString')
			->with('enforce_theme', '')
			->willReturn('');

		$this->assertEquals([], $this->themesService->getEnabledThemes());
	}

	public function testGetEnabledThemesEnforced() {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');


		$this->config->expects($this->once())
			->method('getUserValue')
			->with('user', Application::APP_ID, 'enabled-themes', '[]')
			->willReturn(json_encode([]));
		$this->config->expects($this->once())
			->method('getSystemValueString')
			->with('enforce_theme', '')
			->willReturn('light');

		$this->assertEquals(['light'], $this->themesService->getEnabledThemes());
	}


	public function dataTestSetEnabledThemes() {
		return [
			[[], []],
			[['light'], ['light']],
			[['dark'], ['dark']],
			[['dark', 'dark', 'opendyslexic'], ['dark', 'opendyslexic']],
		];
	}

	/**
	 * @dataProvider dataTestSetEnabledThemes
	 *
	 * @param string[] $enabledThemes
	 * @param string[] $expected
	 */
	public function testSetEnabledThemes(array $enabledThemes, array $expected) {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');

		$this->config->expects($this->once())
			->method('setUserValue')
			->with('user', Application::APP_ID, 'enabled-themes', json_encode($expected));

		$this->invokePrivate($this->themesService, 'setEnabledThemes', [$enabledThemes]);
	}

	private function initThemes() {
		$util = $this->createMock(Util::class);
		$urlGenerator = $this->createMock(IURLGenerator::class);
		$imageManager = $this->createMock(ImageManager::class);
		$l10n = $this->createMock(IL10N::class);

		$this->themes = [
			'default' => new DefaultTheme(
				$util,
				$this->themingDefaults,
				$urlGenerator,
				$imageManager,
				$this->config,
				$l10n,
			),
			'light' => new LightTheme(
				$util,
				$this->themingDefaults,
				$urlGenerator,
				$imageManager,
				$this->config,
				$l10n,
			),
			'dark' => new DarkTheme(
				$util,
				$this->themingDefaults,
				$urlGenerator,
				$imageManager,
				$this->config,
				$l10n,
			),
			'highcontrast' => new HighContrastTheme(
				$util,
				$this->themingDefaults,
				$urlGenerator,
				$imageManager,
				$this->config,
				$l10n,
			),
			'dark-highcontrast' => new DarkHighContrastTheme(
				$util,
				$this->themingDefaults,
				$urlGenerator,
				$imageManager,
				$this->config,
				$l10n,
			),
			'opendyslexic' => new DyslexiaFont(
				$util,
				$this->themingDefaults,
				$urlGenerator,
				$imageManager,
				$this->config,
				$l10n,
			),
		];
	}
}
