<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\InitialStateService;
use OC\TemplateLayout;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\INavigationManager;
use OCP\ServerVersion;
use OCP\Template\ITemplateManager;
use PHPUnit\Framework\MockObject\MockObject;

class TemplateLayoutTest extends \Test\TestCase {
	private IConfig&MockObject $config;
	private IAppConfig&MockObject $appConfig;
	private IAppManager&MockObject $appManager;
	private InitialStateService&MockObject $initialState;
	private INavigationManager&MockObject $navigationManager;
	private ITemplateManager&MockObject $templateManager;
	private ServerVersion&MockObject $serverVersion;

	private TemplateLayout $templateLayout;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->initialState = $this->createMock(InitialStateService::class);
		$this->navigationManager = $this->createMock(INavigationManager::class);
		$this->templateManager = $this->createMock(ITemplateManager::class);
		$this->serverVersion = $this->createMock(ServerVersion::class);
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('dataVersionHash')]
	public function testVersionHash(
		string|false $path,
		string|false $file,
		bool $installed,
		bool $debug,
		string $expected,
	): void {
		$this->appManager->expects(self::any())
			->method('getAppVersion')
			->willReturnCallback(fn ($appId) => match ($appId) {
				'shippedApp' => 'shipped_1',
				'otherApp' => 'other_2',
				default => "$appId",
			});
		$this->appManager->expects(self::any())
			->method('isShipped')
			->willReturnCallback(fn (string $app) => $app === 'shippedApp');

		$this->config->expects(self::atLeastOnce())
			->method('getSystemValueBool')
			->willReturnMap([
				['installed', $installed],
				['debug', $debug],
			]);
		$this->config->expects(self::any())
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('42');

		$this->templateLayout = $this->getMockBuilder(TemplateLayout::class)
			->onlyMethods(['getAppNamefromPath'])
			->setConstructorArgs([
				$this->config,
				$this->appConfig,
				$this->appManager,
				$this->initialState,
				$this->navigationManager,
				$this->templateManager,
				$this->serverVersion,
			])
			->getMock();

		$layout = $this->templateLayout->getPageTemplate(TemplateResponse::RENDER_AS_ERROR, '');

		self::invokePrivate(TemplateLayout::class, 'versionHash', ['version_hash']);

		$this->templateLayout->expects(self::any())
			->method('getAppNamefromPath')
			->willReturnCallback(fn ($appName) => match($appName) {
				'apps/shipped' => 'shippedApp',
				'other/app.css' => 'otherApp',
				default => false,
			});

		$hash = self::invokePrivate($this->templateLayout, 'getVersionHashSuffix', [$path, $file]);
		self::assertEquals($expected, $hash);
	}

	public static function dataVersionHash() {
		return [
			'no hash if in debug mode' => ['apps/shipped', 'style.css', true, true, ''],
			'only version hash if not installed' => ['apps/shipped', 'style.css', false, false, '?v=version_hash'],
			'version hash with cache buster if app not found' => ['unknown/path', '', true, false, '?v=version_hash-42'],
			'version hash with cache buster if neither path nor file provided' => [false, false, true, false, '?v=version_hash-42'],
			'app version hash if external app' => ['', 'other/app.css', true, false, '?v=' . substr(md5('other_2'), 0, 8) . '-42'],
			'app version and version hash if shipped app' => ['apps/shipped', 'style.css', true, false, '?v=' . substr(md5('shipped_1-version_hash'), 0, 8) . '-42'],
			'prefer path over file' => ['apps/shipped', 'other/app.css', true, false, '?v=' . substr(md5('shipped_1-version_hash'), 0, 8) . '-42'],
		];
	}

}
