<?php
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Tests\Controller;

use OCA\Theming\AppInfo\Application;
use OCA\Theming\Controller\UserThemeController;
use OCA\Theming\ITheme;
use OCA\Theming\Service\BackgroundService;
use OCA\Theming\Service\ThemesService;
use OCA\Theming\Themes\DarkHighContrastTheme;
use OCA\Theming\Themes\DarkTheme;
use OCA\Theming\Themes\DefaultTheme;
use OCA\Theming\Themes\DyslexiaFont;
use OCA\Theming\Themes\HighContrastTheme;
use OCA\Theming\Themes\LightTheme;
use OCA\Theming\ThemingDefaults;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class UserThemeControllerTest extends TestCase {
	/** @var UserThemeController */
	private $userThemeController;

	/** @var IRequest|MockObject */
	private $request;
	/** @var IConfig|MockObject */
	private $config;
	/** @var IUserSession|MockObject */
	private $userSession;
	/** @var ThemeService|MockObject */
	private $themesService;
	/** @var ThemingDefaults */
	private $themingDefaults;
	/** @var BackgroundService|MockObject */
	private $backgroundService;


	/** @var ITheme[] */
	private $themes;

	protected function setUp(): void {
		$this->request = $this->createMock(IRequest::class);
		$this->config = $this->createMock(IConfig::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->themesService = $this->createMock(ThemesService::class);
		$this->themingDefaults = $this->createMock(ThemingDefaults::class);
		$this->backgroundService = $this->createMock(BackgroundService::class);

		$this->themes = [
			'default' => $this->createMock(DefaultTheme::class),
			'light' => $this->createMock(LightTheme::class),
			'dark' => $this->createMock(DarkTheme::class),
			'light-highcontrast' => $this->createMock(HighContrastTheme::class),
			'dark-highcontrast' => $this->createMock(DarkHighContrastTheme::class),
			'opendyslexic' => $this->createMock(DyslexiaFont::class),
		];

		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');

		$this->userThemeController = new UserThemeController(
			Application::APP_ID,
			$this->request,
			$this->config,
			$this->userSession,
			$this->themesService,
			$this->themingDefaults,
			$this->backgroundService,
		);

		parent::setUp();
	}

	public function dataTestThemes() {
		return [
			['default'],
			['light'],
			['dark'],
			['light-highcontrast'],
			['dark-highcontrast'],
			['opendyslexic'],
			['', OCSBadRequestException::class],
			['badTheme', OCSBadRequestException::class],
		];
	}

	/**
	 * @dataProvider dataTestThemes
	 *
	 * @param string $themeId
	 * @param string $exception
	 */
	public function testEnableTheme($themeId, ?string $exception = null) {
		$this->themesService
			->expects($this->any())
			->method('getThemes')
			->willReturn($this->themes);

		if ($exception) {
			$this->expectException($exception);
		}

		$expected = new DataResponse();
		$this->assertEquals($expected, $this->userThemeController->enableTheme($themeId));
	}

	/**
	 * @dataProvider dataTestThemes
	 *
	 * @param string $themeId
	 * @param string $exception
	 */
	public function testDisableTheme($themeId, ?string $exception = null) {
		$this->themesService
			->expects($this->any())
			->method('getThemes')
			->willReturn($this->themes);

		if ($exception) {
			$this->expectException($exception);
		}

		$expected = new DataResponse();
		$this->assertEquals($expected, $this->userThemeController->disableTheme($themeId));
	}
}
