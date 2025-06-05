<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
use OCP\ServerVersion;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

/**
 * Class CapabilitiesTest
 *
 * @package OCA\Theming\Tests
 */
class CapabilitiesTest extends TestCase {
	protected ThemingDefaults&MockObject $theming;
	protected IURLGenerator&MockObject $url;
	protected IConfig&MockObject $config;
	protected Util&MockObject $util;
	protected IUserSession $userSession;
	protected Capabilities $capabilities;

	protected function setUp(): void {
		parent::setUp();

		$this->theming = $this->createMock(ThemingDefaults::class);
		$this->url = $this->createMock(IURLGenerator::class);
		$this->config = $this->createMock(IConfig::class);
		$this->util = $this->createMock(Util::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->capabilities = new Capabilities(
			$this->theming,
			$this->util,
			$this->url,
			$this->config,
			$this->userSession,
		);
	}

	public static function dataGetCapabilities(): array {
		return [
			['name', 'url', 'slogan', '#FFFFFF', '#000000', 'logo', 'background', '#fff', '#000', 'http://absolute/', true, [
				'name' => 'name',
				'productName' => 'name',
				'url' => 'url',
				'slogan' => 'slogan',
				'color' => '#FFFFFF',
				'color-text' => '#000000',
				'color-element' => '#b3b3b3',
				'color-element-bright' => '#b3b3b3',
				'color-element-dark' => '#FFFFFF',
				'logo' => 'http://absolute/logo',
				'background' => 'http://absolute/background',
				'background-text' => '#000',
				'background-plain' => false,
				'background-default' => false,
				'logoheader' => 'http://absolute/logo',
				'favicon' => 'http://absolute/logo',
			]],
			['name1', 'url2', 'slogan3', '#01e4a0', '#ffffff', 'logo5', 'background6', '#fff', '#000', 'http://localhost/', false, [
				'name' => 'name1',
				'productName' => 'name1',
				'url' => 'url2',
				'slogan' => 'slogan3',
				'color' => '#01e4a0',
				'color-text' => '#ffffff',
				'color-element' => '#01e4a0',
				'color-element-bright' => '#01e4a0',
				'color-element-dark' => '#01e4a0',
				'logo' => 'http://localhost/logo5',
				'background' => 'http://localhost/background6',
				'background-text' => '#000',
				'background-plain' => false,
				'background-default' => true,
				'logoheader' => 'http://localhost/logo5',
				'favicon' => 'http://localhost/logo5',
			]],
			['name1', 'url2', 'slogan3', '#000000', '#ffffff', 'logo5', 'backgroundColor', '#000000', '#ffffff', 'http://localhost/', true, [
				'name' => 'name1',
				'productName' => 'name1',
				'url' => 'url2',
				'slogan' => 'slogan3',
				'color' => '#000000',
				'color-text' => '#ffffff',
				'color-element' => '#4d4d4d',
				'color-element-bright' => '#4d4d4d',
				'color-element-dark' => '#4d4d4d',
				'logo' => 'http://localhost/logo5',
				'background' => '#000000',
				'background-text' => '#ffffff',
				'background-plain' => true,
				'background-default' => false,
				'logoheader' => 'http://localhost/logo5',
				'favicon' => 'http://localhost/logo5',
			]],
			['name1', 'url2', 'slogan3', '#000000', '#ffffff', 'logo5', 'backgroundColor', '#000000', '#ffffff', 'http://localhost/', false, [
				'name' => 'name1',
				'productName' => 'name1',
				'url' => 'url2',
				'slogan' => 'slogan3',
				'color' => '#000000',
				'color-text' => '#ffffff',
				'color-element' => '#4d4d4d',
				'color-element-bright' => '#4d4d4d',
				'color-element-dark' => '#4d4d4d',
				'logo' => 'http://localhost/logo5',
				'background' => '#000000',
				'background-text' => '#ffffff',
				'background-plain' => true,
				'background-default' => true,
				'logoheader' => 'http://localhost/logo5',
				'favicon' => 'http://localhost/logo5',
			]],
		];
	}

	/**
	 * @dataProvider dataGetCapabilities
	 * @param non-empty-array<string, string> $expected
	 */
	public function testGetCapabilities(string $name, string $url, string $slogan, string $color, string $textColor, string $logo, string $background, string $backgroundColor, string $backgroundTextColor, string $baseUrl, bool $backgroundThemed, array $expected): void {
		$this->config->expects($this->once())
			->method('getAppValue')
			->willReturn($background);
		$this->theming->expects($this->once())
			->method('getName')
			->willReturn($name);
		$this->theming->expects($this->once())
			->method('getProductName')
			->willReturn($name);
		$this->theming->expects($this->once())
			->method('getBaseUrl')
			->willReturn($url);
		$this->theming->expects($this->once())
			->method('getSlogan')
			->willReturn($slogan);
		$this->theming->expects($this->once())
			->method('getColorBackground')
			->willReturn($backgroundColor);
		$this->theming->expects($this->once())
			->method('getTextColorBackground')
			->willReturn($backgroundTextColor);
		$this->theming->expects($this->atLeast(1))
			->method('getDefaultColorPrimary')
			->willReturn($color);
		$this->theming->expects($this->exactly(3))
			->method('getLogo')
			->willReturn($logo);

		$util = new Util($this->createMock(ServerVersion::class), $this->config, $this->createMock(IAppManager::class), $this->createMock(IAppData::class), $this->createMock(ImageManager::class));
		$this->util->expects($this->exactly(3))
			->method('elementColor')
			->with($color)
			->willReturnCallback(static function (string $color, ?bool $brightBackground = null) use ($util) {
				return $util->elementColor($color, $brightBackground);
			});

		$this->util->expects($this->any())
			->method('invertTextColor')
			->willReturnCallback(fn () => $textColor === '#000000');
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
