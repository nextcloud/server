<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Bjoern Schiessle <bjoern@schiessle.org>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Guillaume COMPAGNON <gcompagnon@outlook.com>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Joas Schilling <coding@schilljs.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Julius Haertl <jus@bitgrid.net>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Michael Weimann <mail@michael-weimann.eu>
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

use OCA\Theming\ImageManager;
use OCA\Theming\Service\BackgroundService;
use OCA\Theming\ThemingDefaults;
use OCA\Theming\Util;
use OCP\App\IAppManager;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use Test\TestCase;

class ThemingDefaultsTest extends TestCase {
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $config;
	/** @var IL10N|\PHPUnit\Framework\MockObject\MockObject */
	private $l10n;
	/** @var IUserSession|\PHPUnit\Framework\MockObject\MockObject */
	private $userSession;
	/** @var IURLGenerator|\PHPUnit\Framework\MockObject\MockObject */
	private $urlGenerator;
	/** @var \OC_Defaults|\PHPUnit\Framework\MockObject\MockObject */
	private $defaults;
	/** @var IAppData|\PHPUnit\Framework\MockObject\MockObject */
	private $appData;
	/** @var ICacheFactory|\PHPUnit\Framework\MockObject\MockObject */
	private $cacheFactory;
	/** @var ThemingDefaults */
	private $template;
	/** @var Util|\PHPUnit\Framework\MockObject\MockObject */
	private $util;
	/** @var ICache|\PHPUnit\Framework\MockObject\MockObject */
	private $cache;
	/** @var IAppManager|\PHPUnit\Framework\MockObject\MockObject */
	private $appManager;
	/** @var ImageManager|\PHPUnit\Framework\MockObject\MockObject */
	private $imageManager;
	/** @var INavigationManager|\PHPUnit\Framework\MockObject\MockObject */
	private $navigationManager;

	protected function setUp(): void {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cache = $this->createMock(ICache::class);
		$this->util = $this->createMock(Util::class);
		$this->imageManager = $this->createMock(ImageManager::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->navigationManager = $this->createMock(INavigationManager::class);
		$this->defaults = new \OC_Defaults();
		$this->urlGenerator
			->expects($this->any())
			->method('getBaseUrl')
			->willReturn('');
		$this->template = new ThemingDefaults(
			$this->config,
			$this->l10n,
			$this->userSession,
			$this->urlGenerator,
			$this->cacheFactory,
			$this->util,
			$this->imageManager,
			$this->appManager,
			$this->navigationManager
		);
	}

	public function testGetNameWithDefault() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('Nextcloud');

		$this->assertEquals('Nextcloud', $this->template->getName());
	}

	public function testGetNameWithCustom() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('MyCustomCloud');

		$this->assertEquals('MyCustomCloud', $this->template->getName());
	}

	public function testGetHTMLNameWithDefault() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('Nextcloud');

		$this->assertEquals('Nextcloud', $this->template->getHTMLName());
	}

	public function testGetHTMLNameWithCustom() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('MyCustomCloud');

		$this->assertEquals('MyCustomCloud', $this->template->getHTMLName());
	}

	public function testGetTitleWithDefault() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('Nextcloud');

		$this->assertEquals('Nextcloud', $this->template->getTitle());
	}

	public function testGetTitleWithCustom() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('MyCustomCloud');

		$this->assertEquals('MyCustomCloud', $this->template->getTitle());
	}


	public function testGetEntityWithDefault() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('Nextcloud');

		$this->assertEquals('Nextcloud', $this->template->getEntity());
	}

	public function testGetEntityWithCustom() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'name', 'Nextcloud')
			->willReturn('MyCustomCloud');

		$this->assertEquals('MyCustomCloud', $this->template->getEntity());
	}

	public function testGetBaseUrlWithDefault() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'url', $this->defaults->getBaseUrl())
			->willReturn($this->defaults->getBaseUrl());

		$this->assertEquals($this->defaults->getBaseUrl(), $this->template->getBaseUrl());
	}

	public function testGetBaseUrlWithCustom() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'url', $this->defaults->getBaseUrl())
			->willReturn('https://example.com/');

		$this->assertEquals('https://example.com/', $this->template->getBaseUrl());
	}

	public function legalUrlProvider() {
		return [
			[ '' ],
			[ 'https://example.com/legal.html']
		];
	}

	/**
	 * @param $imprintUrl
	 * @dataProvider legalUrlProvider
	 */
	public function testGetImprintURL($imprintUrl) {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'imprintUrl', '')
			->willReturn($imprintUrl);

		$this->assertEquals($imprintUrl, $this->template->getImprintUrl());
	}

	/**
	 * @param $privacyUrl
	 * @dataProvider legalUrlProvider
	 */
	public function testGetPrivacyURL($privacyUrl) {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'privacyUrl', '')
			->willReturn($privacyUrl);

		$this->assertEquals($privacyUrl, $this->template->getPrivacyUrl());
	}

	public function testGetSloganWithDefault() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'slogan', $this->defaults->getSlogan())
			->willReturn($this->defaults->getSlogan());

		$this->assertEquals($this->defaults->getSlogan(), $this->template->getSlogan());
	}

	public function testGetSloganWithCustom() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'slogan', $this->defaults->getSlogan())
			->willReturn('My custom Slogan');

		$this->assertEquals('My custom Slogan', $this->template->getSlogan());
	}

	public function testGetShortFooter() {
		$this->config
			->expects($this->exactly(5))
			->method('getAppValue')
			->willReturnMap([
				['theming', 'url', $this->defaults->getBaseUrl(), 'url'],
				['theming', 'name', 'Nextcloud', 'Name'],
				['theming', 'slogan', $this->defaults->getSlogan(), 'Slogan'],
				['theming', 'imprintUrl', '', ''],
				['theming', 'privacyUrl', '', ''],
			]);

		$this->assertEquals('<a href="url" target="_blank" rel="noreferrer noopener" class="entity-name">Name</a> – Slogan', $this->template->getShortFooter());
	}

	public function testGetShortFooterEmptyUrl() {
		$this->navigationManager->expects($this->once())->method('getAll')->with(INavigationManager::TYPE_GUEST)->willReturn([]);
		$this->config
			->expects($this->exactly(5))
			->method('getAppValue')
			->willReturnMap([
				['theming', 'url', $this->defaults->getBaseUrl(), ''],
				['theming', 'name', 'Nextcloud', 'Name'],
				['theming', 'slogan', $this->defaults->getSlogan(), 'Slogan'],
				['theming', 'imprintUrl', '', ''],
				['theming', 'privacyUrl', '', ''],
			]);

		$this->assertEquals('<span class="entity-name">Name</span> – Slogan', $this->template->getShortFooter());
	}

	public function testGetShortFooterEmptySlogan() {
		$this->navigationManager->expects($this->once())->method('getAll')->with(INavigationManager::TYPE_GUEST)->willReturn([]);
		$this->config
			->expects($this->exactly(5))
			->method('getAppValue')
			->willReturnMap([
				['theming', 'url', $this->defaults->getBaseUrl(), 'url'],
				['theming', 'name', 'Nextcloud', 'Name'],
				['theming', 'slogan', $this->defaults->getSlogan(), ''],
				['theming', 'imprintUrl', '', ''],
				['theming', 'privacyUrl', '', ''],
			]);

		$this->assertEquals('<a href="url" target="_blank" rel="noreferrer noopener" class="entity-name">Name</a>', $this->template->getShortFooter());
	}

	public function testGetShortFooterImprint() {
		$this->navigationManager->expects($this->once())->method('getAll')->with(INavigationManager::TYPE_GUEST)->willReturn([]);
		$this->config
			->expects($this->exactly(5))
			->method('getAppValue')
			->willReturnMap([
				['theming', 'url', $this->defaults->getBaseUrl(), 'url'],
				['theming', 'name', 'Nextcloud', 'Name'],
				['theming', 'slogan', $this->defaults->getSlogan(), 'Slogan'],
				['theming', 'imprintUrl', '', 'https://example.com/imprint'],
				['theming', 'privacyUrl', '', ''],
			]);

		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnArgument(0);

		$this->assertEquals('<a href="url" target="_blank" rel="noreferrer noopener" class="entity-name">Name</a> – Slogan<br/><a href="https://example.com/imprint" class="legal" target="_blank" rel="noreferrer noopener">Legal notice</a>', $this->template->getShortFooter());
	}

	public function testGetShortFooterPrivacy() {
		$this->navigationManager->expects($this->once())->method('getAll')->with(INavigationManager::TYPE_GUEST)->willReturn([]);
		$this->config
			->expects($this->exactly(5))
			->method('getAppValue')
			->willReturnMap([
				['theming', 'url', $this->defaults->getBaseUrl(), 'url'],
				['theming', 'name', 'Nextcloud', 'Name'],
				['theming', 'slogan', $this->defaults->getSlogan(), 'Slogan'],
				['theming', 'imprintUrl', '', ''],
				['theming', 'privacyUrl', '', 'https://example.com/privacy'],
			]);

		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnArgument(0);

		$this->assertEquals('<a href="url" target="_blank" rel="noreferrer noopener" class="entity-name">Name</a> – Slogan<br/><a href="https://example.com/privacy" class="legal" target="_blank" rel="noreferrer noopener">Privacy policy</a>', $this->template->getShortFooter());
	}

	public function testGetShortFooterAllLegalLinks() {
		$this->navigationManager->expects($this->once())->method('getAll')->with(INavigationManager::TYPE_GUEST)->willReturn([]);
		$this->config
			->expects($this->exactly(5))
			->method('getAppValue')
			->willReturnMap([
				['theming', 'url', $this->defaults->getBaseUrl(), 'url'],
				['theming', 'name', 'Nextcloud', 'Name'],
				['theming', 'slogan', $this->defaults->getSlogan(), 'Slogan'],
				['theming', 'imprintUrl', '', 'https://example.com/imprint'],
				['theming', 'privacyUrl', '', 'https://example.com/privacy'],
			]);

		$this->l10n
			->expects($this->any())
			->method('t')
			->willReturnArgument(0);

		$this->assertEquals('<a href="url" target="_blank" rel="noreferrer noopener" class="entity-name">Name</a> – Slogan<br/><a href="https://example.com/imprint" class="legal" target="_blank" rel="noreferrer noopener">Legal notice</a> · <a href="https://example.com/privacy" class="legal" target="_blank" rel="noreferrer noopener">Privacy policy</a>', $this->template->getShortFooter());
	}

	public function invalidLegalUrlProvider() {
		return [
			['example.com/legal'],  # missing scheme
			['https:///legal'],     # missing host
		];
	}

	/**
	 * @param $invalidImprintUrl
	 * @dataProvider invalidLegalUrlProvider
	 */
	public function testGetShortFooterInvalidImprint($invalidImprintUrl) {
		$this->navigationManager->expects($this->once())->method('getAll')->with(INavigationManager::TYPE_GUEST)->willReturn([]);
		$this->config
			->expects($this->exactly(5))
			->method('getAppValue')
			->willReturnMap([
				['theming', 'url', $this->defaults->getBaseUrl(), 'url'],
				['theming', 'name', 'Nextcloud', 'Name'],
				['theming', 'slogan', $this->defaults->getSlogan(), 'Slogan'],
				['theming', 'imprintUrl', '', $invalidImprintUrl],
				['theming', 'privacyUrl', '', ''],
			]);

		$this->assertEquals('<a href="url" target="_blank" rel="noreferrer noopener" class="entity-name">Name</a> – Slogan', $this->template->getShortFooter());
	}

	/**
	 * @param $invalidPrivacyUrl
	 * @dataProvider invalidLegalUrlProvider
	 */
	public function testGetShortFooterInvalidPrivacy($invalidPrivacyUrl) {
		$this->navigationManager->expects($this->once())->method('getAll')->with(INavigationManager::TYPE_GUEST)->willReturn([]);
		$this->config
			->expects($this->exactly(5))
			->method('getAppValue')
			->willReturnMap([
				['theming', 'url', $this->defaults->getBaseUrl(), 'url'],
				['theming', 'name', 'Nextcloud', 'Name'],
				['theming', 'slogan', $this->defaults->getSlogan(), 'Slogan'],
				['theming', 'imprintUrl', '', ''],
				['theming', 'privacyUrl', '', $invalidPrivacyUrl],
			]);

		$this->assertEquals('<a href="url" target="_blank" rel="noreferrer noopener" class="entity-name">Name</a> – Slogan', $this->template->getShortFooter());
	}

	public function testGetColorPrimaryWithDefault() {
		$this->config
			->expects($this->exactly(2))
			->method('getAppValue')
			->willReturnMap([
				['theming', 'disable-user-theming', 'no', 'no'],
				['theming', 'color', '', $this->defaults->getColorPrimary()],
			]);

		$this->assertEquals($this->defaults->getColorPrimary(), $this->template->getColorPrimary());
	}

	public function testGetColorPrimaryWithCustom() {
		$this->config
			->expects($this->exactly(2))
			->method('getAppValue')
			->willReturnMap([
				['theming', 'disable-user-theming', 'no', 'no'],
				['theming', 'color', '', '#fff'],
			]);

		$this->assertEquals('#fff', $this->template->getColorPrimary());
	}

	public function testGetColorPrimaryWithDefaultBackground() {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');
		$this->config
			->expects($this->exactly(2))
			->method('getAppValue')
			->willReturnMap([
				['theming', 'disable-user-theming', 'no', 'no'],
				['theming', 'color', '', ''],
			]);
		$this->config
			->expects($this->once())
			->method('getUserValue')
			->with('user', 'theming', 'background_color')
			->willReturn('');

		$this->assertEquals(BackgroundService::DEFAULT_COLOR, $this->template->getColorPrimary());
	}

	public function testGetColorPrimaryWithCustomBackground() {
		$backgroundIndex = 2;
		$background = array_values(BackgroundService::SHIPPED_BACKGROUNDS)[$backgroundIndex];

		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');

		$this->config
			->expects($this->once())
			->method('getUserValue')
			->with('user', 'theming', 'background_color', '')
			->willReturn($background['primary_color']);

		$this->config
			->expects($this->exactly(2))
			->method('getAppValue')
			->willReturnMap([
				['theming', 'color', '', ''],
				['theming', 'disable-user-theming', 'no', 'no'],
			]);

		$this->assertEquals($background['primary_color'], $this->template->getColorPrimary());
	}

	public function testGetColorPrimaryWithCustomBackgroundColor() {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');

		$this->config
			->expects($this->once())
			->method('getUserValue')
			->with('user', 'theming', 'background_color', '')
			->willReturn('#fff');
		$this->config
			->expects($this->exactly(2))
			->method('getAppValue')
			->willReturnMap([
				['theming', 'color', '', ''],
				['theming', 'disable-user-theming', 'no', 'no'],
			]);

		$this->assertEquals('#fff', $this->template->getColorPrimary());
	}

	public function testGetColorPrimaryWithInvalidCustomBackgroundColor() {
		$user = $this->createMock(IUser::class);
		$this->userSession->expects($this->any())
			->method('getUser')
			->willReturn($user);
		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');

		$this->config
			->expects($this->once())
			->method('getUserValue')
			->with('user', 'theming', 'background_color', '')
			->willReturn('nextcloud');
		$this->config
			->expects($this->exactly(3))
			->method('getAppValue')
			->willReturnMap([
				['theming', 'color', '', ''],
				['theming', 'disable-user-theming', 'no', 'no'],
			]);

		$this->assertEquals($this->template->getDefaultColorPrimary(), $this->template->getColorPrimary());
	}

	public function testSet() {
		$this->config
			->expects($this->exactly(2))
			->method('setAppValue')
			->withConsecutive(
				['theming', 'MySetting', 'MyValue'],
				['theming', 'cachebuster', 16],
			);
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('15');
		$this->cacheFactory
			->expects($this->exactly(2))
			->method('createDistributed')
			->withConsecutive(
				['theming-'],
				['imagePath'],
			)
			->willReturn($this->cache);
		$this->cache
			->expects($this->any())
			->method('clear')
			->with('');
		$this->template->set('MySetting', 'MyValue');
	}

	public function testUndoName() {
		$this->config
			->expects($this->once())
			->method('deleteAppValue')
			->with('theming', 'name');
		$this->config
			->expects($this->exactly(2))
			->method('getAppValue')
			->withConsecutive(
				['theming', 'cachebuster', '0'],
				['theming', 'name', 'Nextcloud'],
			)->willReturnOnConsecutiveCalls(
				'15',
				'Nextcloud',
			);
		$this->config
			->expects($this->once())
			->method('setAppValue')
			->with('theming', 'cachebuster', 16);

		$this->assertSame('Nextcloud', $this->template->undo('name'));
	}

	public function testUndoBaseUrl() {
		$this->config
			->expects($this->once())
			->method('deleteAppValue')
			->with('theming', 'url');
		$this->config
			->expects($this->exactly(2))
			->method('getAppValue')
			->withConsecutive(
				['theming', 'cachebuster', '0'],
				['theming', 'url', $this->defaults->getBaseUrl()],
			)->willReturnOnConsecutiveCalls(
				'15',
				$this->defaults->getBaseUrl(),
			);
		$this->config
			->expects($this->once())
			->method('setAppValue')
			->with('theming', 'cachebuster', 16);

		$this->assertSame($this->defaults->getBaseUrl(), $this->template->undo('url'));
	}

	public function testUndoSlogan() {
		$this->config
			->expects($this->once())
			->method('deleteAppValue')
			->with('theming', 'slogan');
		$this->config
			->expects($this->exactly(2))
			->method('getAppValue')
			->withConsecutive(
				['theming', 'cachebuster', '0'],
				['theming', 'slogan', $this->defaults->getSlogan()],
			)->willReturnOnConsecutiveCalls(
				'15',
				$this->defaults->getSlogan(),
			);
		$this->config
			->expects($this->once())
			->method('setAppValue')
			->with('theming', 'cachebuster', 16);

		$this->assertSame($this->defaults->getSlogan(), $this->template->undo('slogan'));
	}

	public function testUndoColor() {
		$this->config
			->expects($this->once())
			->method('deleteAppValue')
			->with('theming', 'color');
		$this->config
			->expects($this->exactly(2))
			->method('getAppValue')
			->withConsecutive(
				['theming', 'cachebuster', '0'],
				['theming', 'color', null],
			)->willReturnOnConsecutiveCalls(
				'15',
				$this->defaults->getColorPrimary(),
			);
		$this->config
			->expects($this->once())
			->method('setAppValue')
			->with('theming', 'cachebuster', 16);

		$this->assertSame($this->defaults->getColorPrimary(), $this->template->undo('color'));
	}

	public function testUndoDefaultAction() {
		$this->config
			->expects($this->once())
			->method('deleteAppValue')
			->with('theming', 'defaultitem');
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('15');
		$this->config
			->expects($this->once())
			->method('setAppValue')
			->with('theming', 'cachebuster', 16);

		$this->assertSame('', $this->template->undo('defaultitem'));
	}

	public function testGetBackground() {
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
		$this->config
			->expects($this->exactly(2))
			->method('getAppValue')
			->withConsecutive(
				['theming', 'logoMime'],
				['theming', 'cachebuster', '0'],
			)->willReturnOnConsecutiveCalls(
				'',
				'0'
			);
		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with('core', $withName)
			->willReturn('core-logo');
		$this->assertEquals('core-logo?v=0', $this->template->getLogo($useSvg));
	}

	public function testGetLogoDefaultWithSvg() {
		$this->getLogoHelper('logo/logo.svg', true);
	}

	public function testGetLogoDefaultWithoutSvg() {
		$this->getLogoHelper('logo/logo.png', false);
	}

	public function testGetLogoCustom() {
		$this->config
			->expects($this->exactly(2))
			->method('getAppValue')
			->withConsecutive(
				['theming', 'logoMime', false],
				['theming', 'cachebuster', '0'],
			)->willReturnOnConsecutiveCalls(
				'image/svg+xml',
				'0',
			);
		$this->urlGenerator->expects($this->once())
			->method('linkToRoute')
			->with('theming.Theming.getImage')
			->willReturn('custom-logo?v=0');
		$this->assertEquals('custom-logo' . '?v=0', $this->template->getLogo());
	}

	public function testGetScssVariablesCached() {
		$this->config->expects($this->any())->method('getAppValue')->with('theming', 'cachebuster', '0')->willReturn('1');
		$this->cacheFactory->expects($this->once())
			->method('createDistributed')
			->with('theming-1-')
			->willReturn($this->cache);
		$this->cache->expects($this->once())->method('get')->with('getScssVariables')->willReturn(['foo' => 'bar']);
		$this->assertEquals(['foo' => 'bar'], $this->template->getScssVariables());
	}

	public function testGetScssVariables() {
		$this->config
			->expects($this->any())
			->method('getAppValue')
			->willReturnMap([
				['theming', 'cachebuster', '0', '0'],
				['theming', 'logoMime', '', 'jpeg'],
				['theming', 'backgroundMime', '', 'jpeg'],
				['theming', 'logoheaderMime', '', 'jpeg'],
				['theming', 'faviconMime', '', 'jpeg'],
				['theming', 'color', '', $this->defaults->getColorPrimary()],
				['theming', 'color', $this->defaults->getColorPrimary(), $this->defaults->getColorPrimary()],
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
			'has-legal-links' => 'false'
		];
		$this->assertEquals($expected, $this->template->getScssVariables());
	}

	public function testGetDefaultAndroidURL() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'AndroidClientUrl', 'https://play.google.com/store/apps/details?id=com.nextcloud.client')
			->willReturn('https://play.google.com/store/apps/details?id=com.nextcloud.client');

		$this->assertEquals('https://play.google.com/store/apps/details?id=com.nextcloud.client', $this->template->getAndroidClientUrl());
	}

	public function testGetCustomAndroidURL() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'AndroidClientUrl', 'https://play.google.com/store/apps/details?id=com.nextcloud.client')
			->willReturn('https://play.google.com/store/apps/details?id=com.mycloud.client');

		$this->assertEquals('https://play.google.com/store/apps/details?id=com.mycloud.client', $this->template->getAndroidClientUrl());
	}

	public function testGetDefaultiOSURL() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'iOSClientUrl', 'https://geo.itunes.apple.com/us/app/nextcloud/id1125420102?mt=8')
			->willReturn('https://geo.itunes.apple.com/us/app/nextcloud/id1125420102?mt=8');

		$this->assertEquals('https://geo.itunes.apple.com/us/app/nextcloud/id1125420102?mt=8', $this->template->getiOSClientUrl());
	}

	public function testGetCustomiOSURL() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'iOSClientUrl', 'https://geo.itunes.apple.com/us/app/nextcloud/id1125420102?mt=8')
			->willReturn('https://geo.itunes.apple.com/us/app/nextcloud/id1234567890?mt=8');

		$this->assertEquals('https://geo.itunes.apple.com/us/app/nextcloud/id1234567890?mt=8', $this->template->getiOSClientUrl());
	}

	public function testGetDefaultiTunesAppId() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'iTunesAppId', '1125420102')
			->willReturn('1125420102');

		$this->assertEquals('1125420102', $this->template->getiTunesAppId());
	}

	public function testGetCustomiTunesAppId() {
		$this->config
			->expects($this->once())
			->method('getAppValue')
			->with('theming', 'iTunesAppId', '1125420102')
			->willReturn('1234567890');

		$this->assertEquals('1234567890', $this->template->getiTunesAppId());
	}

	public function dataReplaceImagePath() {
		return [
			['core', 'test.png', false],
			['core', 'manifest.json'],
			['core', 'favicon.ico'],
			['core', 'favicon-touch.png']
		];
	}

	/** @dataProvider dataReplaceImagePath */
	public function testReplaceImagePath($app, $image, $result = 'themingRoute?v=1234abcd') {
		$this->cache->expects($this->any())
			->method('get')
			->with('shouldReplaceIcons')
			->willReturn(true);
		$this->config
			->expects($this->any())
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('0');
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
}
