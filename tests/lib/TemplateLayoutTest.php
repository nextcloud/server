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
use OCP\IConfig;

class TemplateLayoutTest extends \Test\TestCase {


	/** @dataProvider dataVersionHash */
	public function testVersionHash($path, $file, $installed, $debug, $expected): void {
		$appManager = $this->createMock(IAppManager::class);
		$appManager->expects(self::any())
			->method('getAppVersion')
			->willReturnCallback(fn ($appId) => match ($appId) {
				'shippedApp' => 'shipped_1',
				'otherApp' => 'other_2',
				default => "$appId",
			});
		$appManager->expects(self::any())
			->method('isShipped')
			->willReturnCallback(fn (string $app) => $app === 'shippedApp');

		$config = $this->createMock(IConfig::class);
		$config->expects(self::atLeastOnce())
			->method('getSystemValueBool')
			->willReturnMap([
				['installed', false, $installed],
				['debug', false, $debug],
			]);
		$config->expects(self::any())
			->method('getAppValue')
			->with('theming', 'cachebuster', '0')
			->willReturn('42');

		$initialState = $this->createMock(InitialStateService::class);

		$this->overwriteService(IConfig::class, $config);
		$this->overwriteService(IAppManager::class, $appManager);
		$this->overwriteService(InitialStateService::class, $initialState);

		$layout = $this->getMockBuilder(TemplateLayout::class)
			->onlyMethods(['getAppNamefromPath'])
			->setConstructorArgs([TemplateResponse::RENDER_AS_ERROR])
			->getMock();

		self::invokePrivate(TemplateLayout::class, 'versionHash', ['version_hash']);

		$layout->expects(self::any())
			->method('getAppNamefromPath')
			->willReturnCallback(fn ($appName) => match($appName) {
				'apps/shipped' => 'shippedApp',
				'other/app.css' => 'otherApp',
				default => false,
			});

		$hash = self::invokePrivate($layout, 'getVersionHashSuffix', [$path, $file]);
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
