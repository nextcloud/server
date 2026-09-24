<?php

declare(strict_types=1);
/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Appstore\Tests\Controller;

use OC\App\AppStore\Bundles\BundleFetcher;
use OC\Installer;
use OCA\Appstore\Controller\PageController;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\IConfig;
use OCP\IL10N;
use Test\TestCase;

#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
final class PageControllerTest extends TestCase {

	private PageController $pageController;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->pageController = $this->createInstance(PageController::class);

		$this->mocks[IL10N::class]->expects($this->any())
			->method('t')
			->willReturnArgument(0);
	}

	public function testViewApps(): void {
		$this->mocks[BundleFetcher::class]->expects($this->once())->method('getBundles')->willReturn([]);
		$this->mocks[Installer::class]->expects($this->any())
			->method('isUpdateAvailable')
			->willReturn(false);
		$this->mocks[IConfig::class]
			->expects($this->once())
			->method('getSystemValueBool')
			->with('appstoreenabled', true)
			->willReturn(true);

		$this->mocks[IInitialState::class]
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
		$this->mocks[Installer::class]->expects($this->any())
			->method('isUpdateAvailable')
			->willReturn(false);
		$this->mocks[BundleFetcher::class]->expects($this->once())->method('getBundles')->willReturn([]);
		$this->mocks[IConfig::class]
			->expects($this->once())
			->method('getSystemValueBool')
			->with('appstoreenabled', true)
			->willReturn(false);

		$this->mocks[IInitialState::class]
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
