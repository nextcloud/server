<?php

declare(strict_types=1);
/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Appstore\Tests\Controller;

use OC\App\AppManager;
use OC\App\AppStore\Bundles\BundleFetcher;
use OC\Installer;
use OCA\Appstore\Controller\PageController;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IL10N;
use OCP\INavigationManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group('DB')]
class PageControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private IL10N&MockObject $l10n;
	private IConfig&MockObject $config;
	private INavigationManager&MockObject $navigationManager;
	private AppManager&MockObject $appManager;
	private BundleFetcher&MockObject $bundleFetcher;
	private Installer&MockObject $installer;
	private IURLGenerator&MockObject $urlGenerator;
	private IInitialState&MockObject $initialState;
	private PageController $pageController;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->expects($this->any())
			->method('t')
			->willReturnArgument(0);
		$this->config = $this->createMock(IConfig::class);
		$this->navigationManager = $this->createMock(INavigationManager::class);
		$this->appManager = $this->createMock(AppManager::class);
		$this->bundleFetcher = $this->createMock(BundleFetcher::class);
		$this->installer = $this->createMock(Installer::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->initialState = $this->createMock(IInitialState::class);

		$this->pageController = new PageController(
			$this->request,
			$this->l10n,
			$this->config,
			$this->installer,
			$this->appManager,
			$this->urlGenerator,
			$this->initialState,
			$this->bundleFetcher,
			$this->navigationManager,
		);
	}

	public function testViewApps(): void {
		$this->bundleFetcher->expects($this->once())->method('getBundles')->willReturn([]);
		$this->installer->expects($this->any())
			->method('isUpdateAvailable')
			->willReturn(false);
		$this->config
			->expects($this->once())
			->method('getSystemValueBool')
			->with('appstoreenabled', true)
			->willReturn(true);
		$this->navigationManager
			->expects($this->once())
			->method('setActiveEntry')
			->with('core_apps');

		$this->initialState
			->expects($this->exactly(4))
			->method('provideInitialState');

		$policy = new ContentSecurityPolicy();
		$policy->addAllowedImageDomain('https://usercontent.apps.nextcloud.com');

		$expected = new TemplateResponse('appstore',
			'empty',
			[
				'pageTitle' => 'App store'
			],
			'user');
		$expected->setContentSecurityPolicy($policy);

		$this->assertEquals($expected, $this->pageController->viewApps());
	}

	public function testViewAppsAppstoreNotEnabled(): void {
		$this->installer->expects($this->any())
			->method('isUpdateAvailable')
			->willReturn(false);
		$this->bundleFetcher->expects($this->once())->method('getBundles')->willReturn([]);
		$this->config
			->expects($this->once())
			->method('getSystemValueBool')
			->with('appstoreenabled', true)
			->willReturn(false);
		$this->navigationManager
			->expects($this->once())
			->method('setActiveEntry')
			->with('core_apps');

		$this->initialState
			->expects($this->exactly(4))
			->method('provideInitialState');

		$policy = new ContentSecurityPolicy();
		$policy->addAllowedImageDomain('https://usercontent.apps.nextcloud.com');

		$expected = new TemplateResponse('appstore',
			'empty',
			[
				'pageTitle' => 'App store'
			],
			'user');
		$expected->setContentSecurityPolicy($policy);

		$this->assertEquals($expected, $this->pageController->viewApps());
	}
}
