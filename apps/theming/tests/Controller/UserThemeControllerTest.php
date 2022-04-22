<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Haertl <jus@bitgrid.net>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Kyle Fazzari <kyrofa@ubuntu.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Michael Weimann <mail@michael-weimann.eu>
 * @author rakekniven <mark.ziegler@rakekniven.de>
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
namespace OCA\Theming\Tests\Controller;

use OCA\Theming\Controller\UserThemeController;
use OCA\Theming\ITheme;
use OCA\Theming\Themes\DarkHighContrastTheme;
use OCA\Theming\Themes\DarkTheme;
use OCA\Theming\Themes\DefaultTheme;
use OCA\Theming\Themes\DyslexiaFont;
use OCA\Theming\Themes\HighContrastTheme;
use OCA\Theming\Service\ThemesService;
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

	/** @var ITheme[] */
	private $themes;

	protected function setUp(): void {
		$this->request = $this->createMock(IRequest::class);
		$this->config = $this->createMock(IConfig::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->themesService = $this->createMock(ThemesService::class);

		$this->themes = [
			'default' => $this->createMock(DefaultTheme::class),
			'dark' => $this->createMock(DarkTheme::class),
			'highcontrast' => $this->createMock(HighContrastTheme::class),
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
			'theming',
			$this->request,
			$this->config,
			$this->userSession,
			$this->themesService,
		);

		parent::setUp();
	}

	public function dataTestThemes() {
		return [
			['default'],
			['dark'],
			['highcontrast'],
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
	public function testEnableTheme($themeId, string $exception = null) {
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
	public function testDisableTheme($themeId, string $exception = null) {
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
