<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
namespace OCA\Theming\Tests\Settings;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\ImageManager;
use OCA\Theming\Service\BackgroundService;
use OCA\Theming\Service\ThemesService;
use OCA\Theming\Settings\Personal;
use OCA\Theming\Themes\DarkHighContrastTheme;
use OCA\Theming\Themes\DarkTheme;
use OCA\Theming\Themes\DefaultTheme;
use OCA\Theming\Themes\DyslexiaFont;
use OCA\Theming\Themes\HighContrastTheme;
use OCA\Theming\Themes\LightTheme;
use OCA\Theming\ThemingDefaults;
use OCA\Theming\Util;
use OCA\Theming\ITheme;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserSession;
use Test\TestCase;

class PersonalTest extends TestCase {
	private IConfig $config;
	private ThemesService $themesService;
	private IInitialState $initialStateService;
	private ThemingDefaults $themingDefaults;
	private IAppManager $appManager;
	private Personal $admin;
	private BackgroundService $backgroundService;

	/** @var ITheme[] */
	private $themes;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->themesService = $this->createMock(ThemesService::class);
		$this->initialStateService = $this->createMock(IInitialState::class);
		$this->themingDefaults = $this->createMock(ThemingDefaults::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->backgroundService = $this->createMock(BackgroundService::class);

		$this->initThemes();

		$this->themesService
			->expects($this->any())
			->method('getThemes')
			->willReturn($this->themes);

		$this->admin = new Personal(
			Application::APP_ID,
			'admin',
			$this->config,
			$this->themesService,
			$this->initialStateService,
			$this->themingDefaults,
			$this->appManager,
		);
	}


	public function dataTestGetForm() {
		return [
			['', [
				$this->formatThemeForm('default'),
				$this->formatThemeForm('light'),
				$this->formatThemeForm('dark'),
				$this->formatThemeForm('light-highcontrast'),
				$this->formatThemeForm('dark-highcontrast'),
				$this->formatThemeForm('opendyslexic'),
			]],
			['dark', [
				$this->formatThemeForm('dark'),
				$this->formatThemeForm('opendyslexic'),
			]],
		];
	}

	/**
	 * @dataProvider dataTestGetForm
	 *
	 * @param string $toEnable
	 * @param string[] $enabledThemes
	 */
	public function testGetForm(string $enforcedTheme, $themesState) {
		$this->config->expects($this->once())
			->method('getSystemValueString')
			->with('enforce_theme', '')
			->willReturn($enforcedTheme);

		$this->config->expects($this->once())
			->method('getUserValue')
			->with('admin', 'core', 'apporder')
			->willReturn('[]');

		$this->appManager->expects($this->once())
			->method('getDefaultAppForUser')
			->willReturn('forcedapp');

		$this->initialStateService->expects($this->exactly(4))
			->method('provideInitialState')
			->withConsecutive(
				['themes', $themesState],
				['enforceTheme', $enforcedTheme],
				['isUserThemingDisabled', false],
				['navigationBar', ['userAppOrder' => [], 'enforcedDefaultApp' => 'forcedapp']],
			);

		$expected = new TemplateResponse('theming', 'settings-personal');
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection() {
		$this->assertSame('theming', $this->admin->getSection());
	}

	public function testGetPriority() {
		$this->assertSame(40, $this->admin->getPriority());
	}

	private function initThemes() {
		$util = $this->createMock(Util::class);
		$themingDefaults = $this->createMock(ThemingDefaults::class);
		$userSession = $this->createMock(IUserSession::class);
		$urlGenerator = $this->createMock(IURLGenerator::class);
		$imageManager = $this->createMock(ImageManager::class);
		$config = $this->createMock(IConfig::class);
		$l10n = $this->createMock(IL10N::class);
		$appManager = $this->createMock(IAppManager::class);

		$themingDefaults->expects($this->any())
			->method('getColorPrimary')
			->willReturn('#0082c9');

		$themingDefaults->expects($this->any())
			->method('getDefaultColorPrimary')
			->willReturn('#0082c9');

		$this->themes = [
			'default' => new DefaultTheme(
				$util,
				$themingDefaults,
				$userSession,
				$urlGenerator,
				$imageManager,
				$config,
				$l10n,
				$appManager,
				$backgroundService,
			),
			'light' => new LightTheme(
				$util,
				$themingDefaults,
				$userSession,
				$urlGenerator,
				$imageManager,
				$config,
				$l10n,
				$appManager,
			),
			'dark' => new DarkTheme(
				$util,
				$themingDefaults,
				$userSession,
				$urlGenerator,
				$imageManager,
				$config,
				$l10n,
				$appManager,
			),
			'light-highcontrast' => new HighContrastTheme(
				$util,
				$themingDefaults,
				$userSession,
				$urlGenerator,
				$imageManager,
				$config,
				$l10n,
				$appManager,
			),
			'dark-highcontrast' => new DarkHighContrastTheme(
				$util,
				$themingDefaults,
				$userSession,
				$urlGenerator,
				$imageManager,
				$config,
				$l10n,
				$appManager,
			),
			'opendyslexic' => new DyslexiaFont(
				$util,
				$themingDefaults,
				$userSession,
				$urlGenerator,
				$imageManager,
				$config,
				$l10n,
				$appManager,
			),
		];
	}

	private function formatThemeForm(string $themeId): array {
		$this->initThemes();

		$theme = $this->themes[$themeId];
		return [
			'id' => $theme->getId(),
			'type' => $theme->getType(),
			'title' => $theme->getTitle(),
			'enableLabel' => $theme->getEnableLabel(),
			'description' => $theme->getDescription(),
			'enabled' => false,
		];
	}
}
