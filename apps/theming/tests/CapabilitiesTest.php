<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Tests;

use OCA\Theming\Capabilities;
use OCA\Theming\ImageManager;
use OCA\Theming\Service\BackgroundService;
use OCA\Theming\Service\ThemesService;
use OCA\Theming\ThemingDefaults;
use OCA\Theming\Util;
use OCP\App\IAppManager;
use OCP\Config\IUserConfig;
use OCP\Files\IAppData;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUser;
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
	protected IAppConfig&MockObject $appConfig;
	protected IUserConfig&MockObject $userConfig;
	protected Util&MockObject $util;
	protected IUserSession&MockObject $userSession;
	protected ThemesService&MockObject $themesService;
	protected Capabilities $capabilities;

	protected function setUp(): void {
		parent::setUp();

		$this->theming = $this->createMock(ThemingDefaults::class);
		$this->url = $this->createMock(IURLGenerator::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->userConfig = $this->createMock(IUserConfig::class);
		$this->util = $this->createMock(Util::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->themesService = $this->createMock(ThemesService::class);
		$this->capabilities = new Capabilities(
			$this->theming,
			$this->util,
			$this->url,
			$this->appConfig,
			$this->userConfig,
			$this->userSession,
			$this->themesService,
		);
	}

	public static function dataGetCapabilities(): array {
		return [
			['name', 'url', 'slogan', '#FFFFFF', '#000000', 'logo', 'background', '#fff', '#000', 'http://absolute/', true, 'https://imprint.example.com/', 'https://privacy.example.com/', '#0082c9', [
				'name' => 'name',
				'productName' => 'name',
				'url' => 'url',
				'imprintUrl' => 'https://imprint.example.com/',
				'privacyUrl' => 'https://privacy.example.com/',
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
				'primaryColor' => '#FFFFFF',
				'backgroundColor' => '#fff',
				'defaultPrimaryColor' => '#FFFFFF',
				'defaultBackgroundColor' => '#0082c9',
				'inverted' => true,
				'cacheBuster' => 'v1',
				'enabledThemes' => ['default'],
			]],
			['name1', 'url2', 'slogan3', '#01e4a0', '#ffffff', 'logo5', 'background6', '#fff', '#000', 'http://localhost/', false, '', '', '#0082c9', [
				'name' => 'name1',
				'productName' => 'name1',
				'url' => 'url2',
				'imprintUrl' => '',
				'privacyUrl' => '',
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
				'primaryColor' => '#01e4a0',
				'backgroundColor' => '#fff',
				'defaultPrimaryColor' => '#01e4a0',
				'defaultBackgroundColor' => '#0082c9',
				'inverted' => false,
				'cacheBuster' => 'v1',
				'enabledThemes' => ['default'],
			]],
			['name1', 'url2', 'slogan3', '#000000', '#ffffff', 'logo5', 'backgroundColor', '#000000', '#ffffff', 'http://localhost/', true, '', '', '#0082c9', [
				'name' => 'name1',
				'productName' => 'name1',
				'url' => 'url2',
				'imprintUrl' => '',
				'privacyUrl' => '',
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
				'primaryColor' => '#000000',
				'backgroundColor' => '#000000',
				'defaultPrimaryColor' => '#000000',
				'defaultBackgroundColor' => '#0082c9',
				'inverted' => false,
				'cacheBuster' => 'v1',
				'enabledThemes' => ['default'],
			]],
			['name1', 'url2', 'slogan3', '#000000', '#ffffff', 'logo5', 'backgroundColor', '#000000', '#ffffff', 'http://localhost/', false, '', '', '#0082c9', [
				'name' => 'name1',
				'productName' => 'name1',
				'url' => 'url2',
				'imprintUrl' => '',
				'privacyUrl' => '',
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
				'primaryColor' => '#000000',
				'backgroundColor' => '#000000',
				'defaultPrimaryColor' => '#000000',
				'defaultBackgroundColor' => '#0082c9',
				'inverted' => false,
				'cacheBuster' => 'v1',
				'enabledThemes' => ['default'],
			]],
		];
	}

	/**
	 * @param array<string, mixed> $expected
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataGetCapabilities')]
	public function testGetCapabilities(string $name, string $url, string $slogan, string $color, string $textColor, string $logo, string $background, string $backgroundColor, string $backgroundTextColor, string $baseUrl, bool $backgroundThemed, string $imprintUrl, string $privacyUrl, string $defaultBackgroundColor, array $expected): void {
		$this->appConfig->expects($this->once())
			->method('getValueString')
			->with('theming', 'backgroundMime', '')
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
			->method('getImprintUrl')
			->willReturn($imprintUrl);
		$this->theming->expects($this->once())
			->method('getPrivacyUrl')
			->willReturn($privacyUrl);
		$this->theming->expects($this->once())
			->method('getSlogan')
			->willReturn($slogan);
		$this->theming->expects($this->once())
			->method('getColorBackground')
			->willReturn($backgroundColor);
		$this->theming->expects($this->once())
			->method('getTextColorBackground')
			->willReturn($backgroundTextColor);
		$this->theming->expects($this->once())
			->method('getDefaultColorBackground')
			->willReturn($defaultBackgroundColor);
		$this->theming->expects($this->atLeast(1))
			->method('getDefaultColorPrimary')
			->willReturn($color);
		$this->theming->expects($this->exactly(3))
			->method('getLogo')
			->willReturn($logo);

		$util = new Util($this->createMock(ServerVersion::class), $this->createMock(IConfig::class), $this->createMock(IAppManager::class), $this->createMock(IAppData::class), $this->createMock(ImageManager::class));
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
		$this->util->expects($this->once())
			->method('getCacheBuster')
			->willReturn('v1');
		$this->themesService->expects($this->once())
			->method('getEnabledThemes')
			->willReturn(['default']);

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

	public static function dataGetCapabilitiesWithUser(): array {
		return [
			'default background' => [
				BackgroundService::BACKGROUND_DEFAULT,
				false,
				'http://localhost/background',
			],
			'custom background' => [
				BackgroundService::BACKGROUND_CUSTOM,
				false,
				'http://localhost/route',
			],
			'shipped background' => [
				'jo-myoung-hee-fluid.webp',
				false,
				'http://localhost/img',
			],
			'solid color background' => [
				'solid',
				true,
				BackgroundService::DEFAULT_COLOR,
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataGetCapabilitiesWithUser')]
	public function testGetCapabilitiesWithUser(string $backgroundImage, bool $expectedBackgroundPlain, string $expectedBackground): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user1');
		$this->userSession->method('getUser')->willReturn($user);

		$userColor = '#00679e';
		$defaultColor = '#0082c9';

		$this->theming->method('getDefaultColorPrimary')->willReturn($defaultColor);
		$this->theming->method('getColorPrimary')->willReturn($userColor);
		$this->theming->method('getTextColorPrimary')->willReturn('#ffffff');
		$this->theming->method('getName')->willReturn('Name');
		$this->theming->method('getProductName')->willReturn('Name');
		$this->theming->method('getBaseUrl')->willReturn('http://example.com/');
		$this->theming->method('getImprintUrl')->willReturn('');
		$this->theming->method('getPrivacyUrl')->willReturn('');
		$this->theming->method('getSlogan')->willReturn('Slogan');
		$this->theming->method('getColorBackground')->willReturn(BackgroundService::DEFAULT_COLOR);
		$this->theming->method('getTextColorBackground')->willReturn('#ffffff');
		$this->theming->method('getDefaultColorBackground')->willReturn('#0082c9');
		$this->theming->method('getLogo')->willReturn('/logo');
		$this->theming->method('getBackground')->willReturn('/background');

		$this->appConfig->method('getValueString')->willReturn('');
		$this->userConfig->method('getValueString')->willReturn($backgroundImage);

		$this->util->method('invertTextColor')->willReturn(false);
		$this->util->method('elementColor')->willReturn($userColor);
		$this->util->method('isBackgroundThemed')->willReturn(false);
		$this->util->method('getCacheBuster')->willReturn('v1');

		$this->themesService->method('getEnabledThemes')->willReturn(['default']);

		$this->url->method('getAbsoluteURL')->willReturnCallback(fn (string $url) => 'http://localhost' . $url);
		$this->url->method('linkToRouteAbsolute')->willReturn('http://localhost/route');
		$this->url->method('linkTo')->willReturn('http://localhost/img');

		$result = $this->capabilities->getCapabilities();
		$theming = $result['theming'];

		// For logged-in users, color/primaryColor reflect getColorPrimary(), not getDefaultColorPrimary()
		$this->assertSame($userColor, $theming['color']);
		$this->assertSame($userColor, $theming['primaryColor']);
		// color-text comes from getTextColorPrimary() directly, not invertTextColor()
		$this->assertSame('#ffffff', $theming['color-text']);
		// inverted uses invertTextColor() with the user's active color
		$this->assertSame(false, $theming['inverted']);
		// defaultPrimaryColor always reflects the admin-configured default
		$this->assertSame($defaultColor, $theming['defaultPrimaryColor']);
		// Background varies by user's background_image setting
		$this->assertSame($expectedBackgroundPlain, $theming['background-plain']);
		$this->assertSame($expectedBackground, $theming['background']);
		// New fields are always present
		$this->assertSame('v1', $theming['cacheBuster']);
		$this->assertSame(['default'], $theming['enabledThemes']);
	}
}
