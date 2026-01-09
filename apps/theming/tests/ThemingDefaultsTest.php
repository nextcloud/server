<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Theming\Tests;

use OCA\Theming\ConfigLexicon;
use OCA\Theming\ImageManager;
use OCA\Theming\Service\BackgroundService;
use OCA\Theming\ThemingDefaults;
use OCA\Theming\Util;
use OCP\App\IAppManager;
use OCP\AppFramework\Services\IAppConfig;
use OCP\Config\IUserConfig;
use OCP\Files\NotFoundException;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class ThemingDefaultsTest extends TestCase {
	private IAppConfig&MockObject $appConfig;
	private IUserConfig&MockObject $userConfig;
	private IL10N&MockObject $l10n;
	private IUserSession&MockObject $userSession;
	private IURLGenerator&MockObject $urlGenerator;
	private ICacheFactory&MockObject $cacheFactory;
	private Util&MockObject $util;
	private ICache&MockObject $cache;
	private IAppManager&MockObject $appManager;
	private ImageManager&MockObject $imageManager;
	private INavigationManager&MockObject $navigationManager;
	private BackgroundService&MockObject $backgroundService;

	private \OC_Defaults $defaults;
	private ThemingDefaults $template;

	protected function setUp(): void {
		parent::setUp();
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->userConfig = $this->createMock(IUserConfig::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cache = $this->createMock(ICache::class);
		$this->util = $this->createMock(Util::class);
		$this->imageManager = $this->createMock(ImageManager::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->navigationManager = $this->createMock(INavigationManager::class);
		$this->backgroundService = $this->createMock(BackgroundService::class);
		$this->defaults = new \OC_Defaults();
		$this->urlGenerator
			->expects($this->any())
			->method('getBaseUrl')
			->willReturn('');
		$this->template = new ThemingDefaults(
			$this->appConfig,
			$this->userConfig,
			$this->l10n,
			$this->userSession,
			$this->urlGenerator,
			$this->cacheFactory,
			$this->util,
			$this->imageManager,
			$this->appManager,
			$this->navigationManager,
			$this->backgroundService,
		);
	}

	public function testGetNameWithDefault(): void {
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('name', 'Nextcloud')
			->willReturn('Nextcloud');

		$this->assertEquals('Nextcloud', $this->template->getName());
	}

	public function testGetNameWithCustom(): void {
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('name', 'Nextcloud')
			->willReturn('MyCustomCloud');

		$this->assertEquals('MyCustomCloud', $this->template->getName());
	}

	public function testGetHTMLNameWithDefault(): void {
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('name', 'Nextcloud')
			->willReturn('Nextcloud');

		$this->assertEquals('Nextcloud', $this->template->getHTMLName());
	}

	public function testGetHTMLNameWithCustom(): void {
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('name', 'Nextcloud')
			->willReturn('MyCustomCloud');

		$this->assertEquals('MyCustomCloud', $this->template->getHTMLName());
	}

	public function testGetTitleWithDefault(): void {
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('name', 'Nextcloud')
			->willReturn('Nextcloud');

		$this->assertEquals('Nextcloud', $this->template->getTitle());
	}

	public function testGetTitleWithCustom(): void {
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('name', 'Nextcloud')
			->willReturn('MyCustomCloud');

		$this->assertEquals('MyCustomCloud', $this->template->getTitle());
	}


	public function testGetEntityWithDefault(): void {
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('name', 'Nextcloud')
			->willReturn('Nextcloud');

		$this->assertEquals('Nextcloud', $this->template->getEntity());
	}

	public function testGetEntityWithCustom(): void {
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('name', 'Nextcloud')
			->willReturn('MyCustomCloud');

		$this->assertEquals('MyCustomCloud', $this->template->getEntity());
	}

	public function testGetBaseUrlWithDefault(): void {
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('url', $this->defaults->getBaseUrl())
			->willReturn($this->defaults->getBaseUrl());

		$this->assertEquals($this->defaults->getBaseUrl(), $this->template->getBaseUrl());
	}

	public function testGetBaseUrlWithCustom(): void {
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('url', $this->defaults->getBaseUrl())
			->willReturn('https://example.com/');

		$this->assertEquals('https://example.com/', $this->template->getBaseUrl());
	}

	public static function legalUrlProvider(): array {
		return [
			[''],
			['https://example.com/legal.html'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'legalUrlProvider')]
	public function testGetImprintURL(string $imprintUrl): void {
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('imprintUrl', '')
			->willReturn($imprintUrl);

		$this->assertEquals($imprintUrl, $this->template->getImprintUrl());
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'legalUrlProvider')]
	public function testGetPrivacyURL(string $privacyUrl): void {
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('privacyUrl', '')
			->willReturn($privacyUrl);

		$this->assertEquals($privacyUrl, $this->template->getPrivacyUrl());
	}

	public function testGetSloganWithDefault(): void {
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('slogan', $this->defaults->getSlogan())
			->willReturn($this->defaults->getSlogan());

		$this->assertEquals($this->defaults->getSlogan(), $this->template->getSlogan());
	}

	public function testGetSloganWithCustom(): void {
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('slogan', $this->defaults->getSlogan())
			->willReturn('My custom Slogan');

		$this->assertEquals('My custom Slogan', $this->template->getSlogan());
	}

	public function testGetShortFooter(): void {
		$this->appConfig
			->expects($this->exactly(5))
			->method('getAppValueString')
			->willReturnMap([
				['url', $this->defaults->getBaseUrl(), 'url'],
				['name', 'Nextcloud', 'Name'],
				['slogan', $this->defaults->getSlogan(), 'Slogan'],
				['imprintUrl', '', ''],
				['privacyUrl', '', ''],
			]);

		$this->assertEquals('<a href="url" target="_blank" rel="noreferrer noopener" class="entity-name">Name</a> – Slogan', $this->template->getShortFooter());
	}

	public function testGetShortFooterEmptyUrl(): void {
		$this->navigationManager->expects($this->once())->method('getAll')->with(INavigationManager::TYPE_GUEST)->willReturn([]);
		$this->appConfig
			->expects($this->exactly(5))
			->method('getAppValueString')
			->willReturnMap([
				['url', $this->defaults->getBaseUrl(), ''],
				['name', 'Nextcloud', 'Name'],
				['slogan', $this->defaults->getSlogan(), 'Slogan'],
				['imprintUrl', '', ''],
				['privacyUrl', '', ''],
			]);

		$this->assertEquals('<span class="entity-name">Name</span> – Slogan', $this->template->getShortFooter());
	}

	public function testGetShortFooterEmptySlogan(): void {
		$this->navigationManager->expects($this->once())->method('getAll')->with(INavigationManager::TYPE_GUEST)->willReturn([]);
		$this->appConfig
			->expects($this->exactly(5))
			->method('getAppValueString')
			->willReturnMap([
				['url', $this->defaults->getBaseUrl(), 'url'],
				['name', 'Nextcloud', 'Name'],
				['slogan', $this->defaults->getSlogan(), ''],
				['imprintUrl', '', ''],
				['privacyUrl', '', ''],
			]);

		$this->assertEquals('<a href="url" target="_blank" rel="noreferrer noopener" class="entity-name">Name</a>', $this->template->getShortFooter());
	}

	public function testGetShortFooterImprint(): void {
		$this->navigationManager->expects($this->once())->method('getAll')->with(INavigationManager::TYPE_GUEST)->willReturn([]);
		$this->appConfig
			->expects($this->exactly(5))
			->method('getAppValueString')
			->willReturnMap([
				['url', $this->defaults->getBaseUrl(), 'url'],
				['name', 'Nextcloud', 'Name'],
				['slogan', $this->defaults->getSlogan(), 'Slogan'],
				['imprintUrl', '', 'https://example.com/imprint'],
				['privacyUrl', '', ''],
			]);

		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnArgument(0);

		$this->assertEquals('<a href="url" target="_blank" rel="noreferrer noopener" class="entity-name">Name</a> – Slogan<br/><span class="footer__legal-links"><a href="https://example.com/imprint" class="legal" target="_blank" rel="noreferrer noopener">Legal notice</a></span>', $this->template->getShortFooter());
	}

	public function testGetShortFooterPrivacy(): void {
		$this->navigationManager->expects($this->once())->method('getAll')->with(INavigationManager::TYPE_GUEST)->willReturn([]);
		$this->appConfig
			->expects($this->exactly(5))
			->method('getAppValueString')
			->willReturnMap([
				['url', $this->defaults->getBaseUrl(), 'url'],
				['name', 'Nextcloud', 'Name'],
				['slogan', $this->defaults->getSlogan(), 'Slogan'],
				['imprintUrl', '', ''],
				['privacyUrl', '', 'https://example.com/privacy'],
			]);

		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnArgument(0);

		$this->assertEquals('<a href="url" target="_blank" rel="noreferrer noopener" class="entity-name">Name</a> – Slogan<br/><span class="footer__legal-links"><a href="https://example.com/privacy" class="legal" target="_blank" rel="noreferrer noopener">Privacy policy</a></span>', $this->template->getShortFooter());
	}

	public function testGetShortFooterAllLegalLinks(): void {
		$this->navigationManager->expects($this->once())->method('getAll')->with(INavigationManager::TYPE_GUEST)->willReturn([]);
		$this->appConfig
			->expects($this->exactly(5))
			->method('getAppValueString')
			->willReturnMap([
				['url', $this->defaults->getBaseUrl(), 'url'],
				['name', 'Nextcloud', 'Name'],
				['slogan', $this->defaults->getSlogan(), 'Slogan'],
				['imprintUrl', '', 'https://example.com/imprint'],
				['privacyUrl', '', 'https://example.com/privacy'],
			]);

		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnArgument(0);

		$this->assertEquals('<a href="url" target="_blank" rel="noreferrer noopener" class="entity-name">Name</a> – Slogan<br/><span class="footer__legal-links"><a href="https://example.com/imprint" class="legal" target="_blank" rel="noreferrer noopener">Legal notice</a> · <a href="https://example.com/privacy" class="legal" target="_blank" rel="noreferrer noopener">Privacy policy</a></span>', $this->template->getShortFooter());
	}

	public static function invalidLegalUrlProvider(): array {
		return [
			['example.com/legal'],  # missing scheme
			['https:///legal'],     # missing host
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'invalidLegalUrlProvider')]
	public function testGetShortFooterInvalidImprint(string $invalidImprintUrl): void {
		$this->navigationManager->expects($this->once())->method('getAll')->with(INavigationManager::TYPE_GUEST)->willReturn([]);
		$this->appConfig
			->expects($this->exactly(5))
			->method('getAppValueString')
			->willReturnMap([
				['url', $this->defaults->getBaseUrl(), 'url'],
				['name', 'Nextcloud', 'Name'],
				['slogan', $this->defaults->getSlogan(), 'Slogan'],
				['imprintUrl', '', $invalidImprintUrl],
				['privacyUrl', '', ''],
			]);

		$this->assertEquals('<a href="url" target="_blank" rel="noreferrer noopener" class="entity-name">Name</a> – Slogan', $this->template->getShortFooter());
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'invalidLegalUrlProvider')]
	public function testGetShortFooterInvalidPrivacy(string $invalidPrivacyUrl): void {
		$this->navigationManager->expects($this->once())->method('getAll')->with(INavigationManager::TYPE_GUEST)->willReturn([]);
		$this->appConfig
			->expects($this->exactly(5))
			->method('getAppValueString')
			->willReturnMap([
				['url', $this->defaults->getBaseUrl(), 'url'],
				['name', 'Nextcloud', 'Name'],
				['slogan', $this->defaults->getSlogan(), 'Slogan'],
				['imprintUrl', '', ''],
				['privacyUrl', '', $invalidPrivacyUrl],
			]);

		$this->assertEquals('<a href="url" target="_blank" rel="noreferrer noopener" class="entity-name">Name</a> – Slogan', $this->template->getShortFooter());
	}

	public function testGetColorPrimaryWithDefault(): void {
		$this->appConfig
			->expects(self::once())
			->method('getAppValueBool')
			->with('disable-user-theming')
			->willReturn(false);
		$this->appConfig
			->expects(self::once())
			->method('getAppValueString')
			->with('primary_color', '')
			->willReturn($this->defaults->getColorPrimary());

		$this->assertEquals($this->defaults->getColorPrimary(), $this->template->getColorPrimary());
	}

	public function testGetColorPrimaryWithCustom(): void {
		$this->appConfig
			->expects(self::once())
			->method('getAppValueBool')
			->with('disable-user-theming')
			->willReturn(false);
		$this->appConfig
			->expects(self::once())
			->method('getAppValueString')
			->with('primary_color', '')
			->willReturn('#fff');

		$this->assertEquals('#fff', $this->template->getColorPrimary());
	}

	public static function dataGetColorPrimary(): array {
		return [
			'with fallback default' => [
				'disableTheming' => false,
				'primaryColor' => '',
				'userPrimaryColor' => '',
				'expected' => BackgroundService::DEFAULT_COLOR,
			],
			'with custom admin primary' => [
				'disableTheming' => false,
				'primaryColor' => '#aaa',
				'userPrimaryColor' => '',
				'expected' => '#aaa',
			],
			'with custom invalid admin primary' => [
				'disableTheming' => false,
				'primaryColor' => 'invalid',
				'userPrimaryColor' => '',
				'expected' => BackgroundService::DEFAULT_COLOR,
			],
			'with custom invalid user primary' => [
				'disableTheming' => false,
				'primaryColor' => '',
				'userPrimaryColor' => 'invalid-name',
				'expected' => BackgroundService::DEFAULT_COLOR,
			],
			'with custom user primary' => [
				'disableTheming' => false,
				'primaryColor' => '',
				'userPrimaryColor' => '#bbb',
				'expected' => '#bbb',
			],
			'with disabled user theming primary' => [
				'disableTheming' => true,
				'primaryColor' => '#aaa',
				'userPrimaryColor' => '#bbb',
				'expected' => '#aaa',
			],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataGetColorPrimary')]
	public function testGetColorPrimary(bool $disableTheming, string $primaryColor, string $userPrimaryColor, string $expected): void {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');
		$this->appConfig
			->expects(self::any())
			->method('getAppValueBool')
			->with('disable-user-theming')
			->willReturn($disableTheming);
		$this->appConfig
			->expects(self::any())
			->method('getAppValueString')
			->with('primary_color', '')
			->willReturn($primaryColor);
		$this->userConfig
			->expects($this->any())
			->method('getValueString')
			->with('user', 'theming', 'primary_color', '')
			->willReturn($userPrimaryColor);

		$this->assertEquals($expected, $this->template->getColorPrimary());
	}

	public function testSet(): void {
		$this->appConfig
			->expects($this->once())
			->method('setAppValueInt')
			->with('cachebuster', 16);
		$this->appConfig
			->expects($this->once())
			->method('setAppValueString')
			->with('MySetting', 'MyValue');
		$this->appConfig
			->expects($this->once())
			->method('getAppValueInt')
			->with('cachebuster')
			->willReturn(15);
		$this->cacheFactory
			->expects($this->exactly(2))
			->method('createDistributed')
			->willReturnMap([
				['theming-', $this->cache],
				['imagePath', $this->cache],
			]);
		$this->cache
			->expects($this->any())
			->method('clear')
			->with('');
		$this->template->set('MySetting', 'MyValue');
	}

	public function testUndoName(): void {
		$this->appConfig
			->expects($this->once())
			->method('deleteAppValue')
			->with('name');
		$this->appConfig
			->expects($this->once())
			->method('getAppValueInt')
			->with('cachebuster')
			->willReturn(15);
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('name', 'Nextcloud')
			->willReturn('Nextcloud');
		$this->appConfig
			->expects($this->once())
			->method('setAppValueInt')
			->with('cachebuster', 16);

		$this->assertSame('Nextcloud', $this->template->undo('name'));
	}

	public function testUndoBaseUrl(): void {
		$this->appConfig
			->expects($this->once())
			->method('deleteAppValue')
			->with('url');
		$this->appConfig
			->expects($this->once())
			->method('getAppValueInt')
			->with('cachebuster')
			->willReturn(15);
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('url', $this->defaults->getBaseUrl())
			->willReturn($this->defaults->getBaseUrl());
		$this->appConfig
			->expects($this->once())
			->method('setAppValueInt')
			->with('cachebuster', 16);

		$this->assertSame($this->defaults->getBaseUrl(), $this->template->undo('url'));
	}

	public function testUndoSlogan(): void {
		$this->appConfig
			->expects($this->once())
			->method('deleteAppValue')
			->with('slogan');
		$this->appConfig
			->expects($this->once())
			->method('getAppValueInt')
			->with('cachebuster')
			->willReturn(15);
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('slogan', $this->defaults->getSlogan())
			->willReturn($this->defaults->getSlogan());
		$this->appConfig
			->expects($this->once())
			->method('setAppValueInt')
			->with('cachebuster', 16);

		$this->assertSame($this->defaults->getSlogan(), $this->template->undo('slogan'));
	}

	public function testUndoPrimaryColor(): void {
		$this->appConfig
			->expects($this->once())
			->method('deleteAppValue')
			->with('primary_color');
		$this->appConfig
			->expects($this->once())
			->method('getAppValueInt')
			->with('cachebuster')
			->willReturn(15);
		$this->appConfig
			->expects($this->once())
			->method('setAppValueInt')
			->with('cachebuster', 16);

		$this->assertSame($this->defaults->getColorPrimary(), $this->template->undo('primary_color'));
	}

	public function testUndoDefaultAction(): void {
		$this->appConfig
			->expects($this->once())
			->method('deleteAppValue')
			->with('defaultitem');
		$this->appConfig
			->expects($this->once())
			->method('getAppValueInt')
			->with('cachebuster', '0')
			->willReturn(15);
		$this->appConfig
			->expects($this->once())
			->method('setAppValueInt')
			->with('cachebuster', 16);

		$this->assertSame('', $this->template->undo('defaultitem'));
	}

	public function testGetBackground(): void {
		$this->imageManager
			->expects($this->once())
			->method('getImageUrl')
			->with('background')
			->willReturn('custom-background?v=0');
		$this->assertEquals('custom-background?v=0', $this->template->getBackground());
	}

	private function getLogoHelper($withName, $useSvg) {
		$this->imageManager->expects($this->any())
			->method('getImage')
			->with('logo')
			->willThrowException(new NotFoundException());
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('logoMime', '')
			->willReturn('');
		$this->appConfig
			->expects($this->once())
			->method('getAppValueInt')
			->with('cachebuster')
			->willReturn(0);
		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('core', $withName)
			->willReturn('core-logo');
		$this->assertEquals('core-logo?v=0', $this->template->getLogo($useSvg));
	}

	public function testGetLogoDefaultWithSvg(): void {
		$this->getLogoHelper('logo/logo.svg', true);
	}

	public function testGetLogoDefaultWithoutSvg(): void {
		$this->getLogoHelper('logo/logo.png', false);
	}

	public function testGetLogoCustom(): void {
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('logoMime', '')
			->willReturn('image/svg+xml');
		$this->appConfig
			->expects($this->once())
			->method('getAppValueInt')
			->with('cachebuster')
			->willReturn(0);
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('theming.Theming.getImage')
			->willReturn('custom-logo?v=0');
		$this->assertEquals('custom-logo' . '?v=0', $this->template->getLogo());
	}

	public function testGetScssVariablesCached(): void {
		$this->appConfig->expects($this->any())
			->method('getAppValueInt')
			->with('cachebuster')
			->willReturn(1);
		$this->cacheFactory->expects($this->once())
			->method('createDistributed')
			->with('theming-1-')
			->willReturn($this->cache);
		$this->cache->expects($this->once())->method('get')->with('getScssVariables')->willReturn(['foo' => 'bar']);
		$this->assertEquals(['foo' => 'bar'], $this->template->getScssVariables());
	}

	public function testGetScssVariables(): void {
		$this->appConfig->expects($this->any())
			->method('getAppValueInt')
			->with('cachebuster')
			->willReturn(0);
		$this->appConfig
			->expects($this->any())
			->method('getAppValueString')
			->willReturnMap([
				['imprintUrl', '', ''],
				['privacyUrl', '', ''],
				['logoMime', '', 'jpeg'],
				['backgroundMime', '', 'jpeg'],
				['logoheaderMime', '', 'jpeg'],
				['faviconMime', '', 'jpeg'],
				['primary_color', '', false, $this->defaults->getColorPrimary()],
				['primary_color', $this->defaults->getColorPrimary(), false, $this->defaults->getColorPrimary()],
			]);

		$this->util->expects($this->any())->method('invertTextColor')->with($this->defaults->getColorPrimary())->willReturn(false);
		$this->util->expects($this->any())->method('elementColor')->with($this->defaults->getColorPrimary())->willReturn('#aaaaaa');
		$this->cacheFactory->expects($this->once())
			->method('createDistributed')
			->with('theming-0-')
			->willReturn($this->cache);
		$this->cache->expects($this->once())->method('get')->with('getScssVariables')->willReturn(null);
		$this->imageManager->expects($this->exactly(4))
			->method('getImageUrl')
			->willReturnMap([
				['logo', 'custom-logo?v=0'],
				['logoheader', 'custom-logoheader?v=0'],
				['favicon', 'custom-favicon?v=0'],
				['background', 'custom-background?v=0'],
			]);

		$expected = [
			'theming-cachebuster' => '\'0\'',
			'theming-logo-mime' => '\'jpeg\'',
			'theming-background-mime' => '\'jpeg\'',
			'image-logo' => "url('custom-logo?v=0')",
			'image-login-background' => "url('custom-background?v=0')",
			'color-primary' => $this->defaults->getColorPrimary(),
			'color-primary-text' => '#ffffff',
			'image-login-plain' => 'false',
			'color-primary-element' => '#aaaaaa',
			'theming-logoheader-mime' => '\'jpeg\'',
			'theming-favicon-mime' => '\'jpeg\'',
			'image-logoheader' => "url('custom-logoheader?v=0')",
			'image-favicon' => "url('custom-favicon?v=0')",
			'has-legal-links' => 'false',
		];
		$this->assertEquals($expected, $this->template->getScssVariables());
	}

	public function testGetDefaultAndroidURL(): void {
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('AndroidClientUrl', 'https://play.google.com/store/apps/details?id=com.nextcloud.client')
			->willReturn('https://play.google.com/store/apps/details?id=com.nextcloud.client');

		$this->assertEquals('https://play.google.com/store/apps/details?id=com.nextcloud.client', $this->template->getAndroidClientUrl());
	}

	public function testGetCustomAndroidURL(): void {
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('AndroidClientUrl', 'https://play.google.com/store/apps/details?id=com.nextcloud.client')
			->willReturn('https://play.google.com/store/apps/details?id=com.mycloud.client');

		$this->assertEquals('https://play.google.com/store/apps/details?id=com.mycloud.client', $this->template->getAndroidClientUrl());
	}

	public function testGetDefaultiOSURL(): void {
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('iOSClientUrl', 'https://geo.itunes.apple.com/us/app/nextcloud/id1125420102?mt=8')
			->willReturn('https://geo.itunes.apple.com/us/app/nextcloud/id1125420102?mt=8');

		$this->assertEquals('https://geo.itunes.apple.com/us/app/nextcloud/id1125420102?mt=8', $this->template->getiOSClientUrl());
	}

	public function testGetCustomiOSURL(): void {
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('iOSClientUrl', 'https://geo.itunes.apple.com/us/app/nextcloud/id1125420102?mt=8')
			->willReturn('https://geo.itunes.apple.com/us/app/nextcloud/id1234567890?mt=8');

		$this->assertEquals('https://geo.itunes.apple.com/us/app/nextcloud/id1234567890?mt=8', $this->template->getiOSClientUrl());
	}

	public function testGetDefaultiTunesAppId(): void {
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('iTunesAppId', '1125420102')
			->willReturn('1125420102');

		$this->assertEquals('1125420102', $this->template->getiTunesAppId());
	}

	public function testGetCustomiTunesAppId(): void {
		$this->appConfig
			->expects($this->once())
			->method('getAppValueString')
			->with('iTunesAppId', '1125420102')
			->willReturn('1234567890');

		$this->assertEquals('1234567890', $this->template->getiTunesAppId());
	}

	public static function dataReplaceImagePath(): array {
		return [
			['core', 'test.png', false],
			['core', 'manifest.json'],
			['core', 'favicon.ico'],
			['core', 'favicon-touch.png'],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataReplaceImagePath')]
	public function testReplaceImagePath(string $app, string $image, string|bool $result = 'themingRoute?v=1234abcd'): void {
		$this->cache->expects($this->any())
			->method('get')
			->with('shouldReplaceIcons')
			->willReturn(true);
		$this->appConfig
			->expects($this->any())
			->method('getAppValueInt')
			->with('cachebuster')
			->willReturn(0);
		$this->urlGenerator
			->expects($this->any())
			->method('linkToRoute')
			->willReturn('themingRoute');
		if ($result) {
			$this->util
				->expects($this->once())
				->method('getCacheBuster')
				->willReturn('1234abcd');
		}
		$this->assertEquals($result, $this->template->replaceImagePath($app, $image));
	}

	public static function setTypesProvider(): array {
		return [
			[ConfigLexicon::BASE_URL, 'example.com', 'example.com'],
			[ConfigLexicon::USER_THEMING_DISABLED, 'no', false],
			[ConfigLexicon::USER_THEMING_DISABLED, 'true', true],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'setTypesProvider')]
	public function testSetTypes(string $setting, string $value, mixed $expected): void {
		$setValue = null;
		$cb = function ($setting, $value) use (&$setValue) {
			if ($setting !== ConfigLexicon::CACHE_BUSTER) {
				$setValue = $value;
			}
			return true;
		};
		$this->appConfig
			->method('setAppValueBool')
			->willReturnCallback($cb);
		$this->appConfig
			->method('setAppValueString')
			->willReturnCallback($cb);
		$this->appConfig
			->method('setAppValueInt')
			->willReturnCallback($cb);

		$this->template->set($setting, $value);
		$this->assertEquals($expected, $setValue);
	}
}
