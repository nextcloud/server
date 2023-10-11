<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Guillaume COMPAGNON <gcompagnon@outlook.com>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
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
namespace OCA\Theming\Tests;

use OCA\Theming\Capabilities;
use OCA\Theming\ImageManager;
use OCA\Theming\ThemingDefaults;
use OCA\Theming\Util;
use OCP\App\IAppManager;
use OCP\Files\IAppData;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUserSession;
use Test\TestCase;

/**
 * Class CapabilitiesTest
 *
 * @package OCA\Theming\Tests
 */
class CapabilitiesTest extends TestCase {
	/** @var ThemingDefaults|\PHPUnit\Framework\MockObject\MockObject */
	protected $theming;

	/** @var IURLGenerator|\PHPUnit\Framework\MockObject\MockObject */
	protected $url;

	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	protected $config;

	/** @var Util|\PHPUnit\Framework\MockObject\MockObject */
	protected $util;

	protected IUserSession $userSession;

	/** @var Capabilities */
	protected $capabilities;

	protected function setUp(): void {
		parent::setUp();

		$this->theming = $this->createMock(ThemingDefaults::class);
		$this->url = $this->getMockBuilder(IURLGenerator::class)->getMock();
		$this->config = $this->createMock(IConfig::class);
		$this->util = $this->createMock(Util::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->capabilities = new Capabilities($this->theming, $this->util, $this->url, $this->config, $this->userSession);
	}

	public function dataGetCapabilities() {
		return [
			['name', 'url', 'slogan', '#FFFFFF', '#000000', 'logo', 'background', 'http://absolute/', true, [
				'name' => 'name',
				'url' => 'url',
				'slogan' => 'slogan',
				'color' => '#FFFFFF',
				'color-text' => '#000000',
				'color-element' => '#b3b3b3',
				'color-element-bright' => '#b3b3b3',
				'color-element-dark' => '#FFFFFF',
				'logo' => 'http://absolute/logo',
				'background' => 'http://absolute/background',
				'background-plain' => false,
				'background-default' => false,
				'logoheader' => 'http://absolute/logo',
				'favicon' => 'http://absolute/logo',
			]],
			['name1', 'url2', 'slogan3', '#01e4a0', '#ffffff', 'logo5', 'background6', 'http://localhost/', false, [
				'name' => 'name1',
				'url' => 'url2',
				'slogan' => 'slogan3',
				'color' => '#01e4a0',
				'color-text' => '#ffffff',
				'color-element' => '#01e4a0',
				'color-element-bright' => '#01e4a0',
				'color-element-dark' => '#01e4a0',
				'logo' => 'http://localhost/logo5',
				'background' => 'http://localhost/background6',
				'background-plain' => false,
				'background-default' => true,
				'logoheader' => 'http://localhost/logo5',
				'favicon' => 'http://localhost/logo5',
			]],
			['name1', 'url2', 'slogan3', '#000000', '#ffffff', 'logo5', 'backgroundColor', 'http://localhost/', true, [
				'name' => 'name1',
				'url' => 'url2',
				'slogan' => 'slogan3',
				'color' => '#000000',
				'color-text' => '#ffffff',
				'color-element' => '#4d4d4d',
				'color-element-bright' => '#4d4d4d',
				'color-element-dark' => '#4d4d4d',
				'logo' => 'http://localhost/logo5',
				'background' => '#000000',
				'background-plain' => true,
				'background-default' => false,
				'logoheader' => 'http://localhost/logo5',
				'favicon' => 'http://localhost/logo5',
			]],
			['name1', 'url2', 'slogan3', '#000000', '#ffffff', 'logo5', 'backgroundColor', 'http://localhost/', false, [
				'name' => 'name1',
				'url' => 'url2',
				'slogan' => 'slogan3',
				'color' => '#000000',
				'color-text' => '#ffffff',
				'color-element' => '#4d4d4d',
				'color-element-bright' => '#4d4d4d',
				'color-element-dark' => '#4d4d4d',
				'logo' => 'http://localhost/logo5',
				'background' => '#000000',
				'background-plain' => true,
				'background-default' => true,
				'logoheader' => 'http://localhost/logo5',
				'favicon' => 'http://localhost/logo5',
			]],
		];
	}

	/**
	 * @dataProvider dataGetCapabilities
	 * @param string $name
	 * @param string $url
	 * @param string $slogan
	 * @param string $color
	 * @param string $textColor
	 * @param string $logo
	 * @param string $background
	 * @param string $baseUrl
	 * @param bool $backgroundThemed
	 * @param string[] $expected
	 */
	public function testGetCapabilities($name, $url, $slogan, $color, $textColor, $logo, $background, $baseUrl, $backgroundThemed, array $expected) {
		$this->config->expects($this->once())
			->method('getAppValue')
			->willReturn($background);
		$this->theming->expects($this->once())
			->method('getName')
			->willReturn($name);
		$this->theming->expects($this->once())
			->method('getBaseUrl')
			->willReturn($url);
		$this->theming->expects($this->once())
			->method('getSlogan')
			->willReturn($slogan);
		$this->theming->expects($this->atLeast(1))
			->method('getDefaultColorPrimary')
			->willReturn($color);
		$this->theming->expects($this->exactly(3))
			->method('getLogo')
			->willReturn($logo);
		$this->theming->expects($this->once())
			->method('getDefaultTextColorPrimary')
			->willReturn($textColor);

		$util = new Util($this->config, $this->createMock(IAppManager::class), $this->createMock(IAppData::class), $this->createMock(ImageManager::class));
		$this->util->expects($this->exactly(3))
			->method('elementColor')
			->with($color)
			->willReturnCallback(static function (string $color, ?bool $brightBackground = null) use ($util) {
				return $util->elementColor($color, $brightBackground);
			});

		$this->util->expects($this->once())
			->method('isBackgroundThemed')
			->willReturn($backgroundThemed);

		if ($background !== 'backgroundColor') {
			$this->theming->expects($this->once())
				->method('getBackground')
				->willReturn($background);
			$this->url->expects($this->exactly(4))
				->method('getAbsoluteURL')
				->willReturnCallback(function ($url) use ($baseUrl) {
					return $baseUrl . $url;
				});
		} else {
			$this->url->expects($this->exactly(3))
				->method('getAbsoluteURL')
				->willReturnCallback(function ($url) use ($baseUrl) {
					return $baseUrl . $url;
				});
		}

		$this->assertEquals(['theming' => $expected], $this->capabilities->getCapabilities());
	}
}
