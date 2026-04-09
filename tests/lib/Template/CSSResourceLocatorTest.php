<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Template;

use OC\AppConfig;
use OC\Files\AppData\AppData;
use OC\Files\AppData\Factory;
use OC\Template\CSSResourceLocator;
use OCA\Theming\ThemingDefaults;
use OCP\App\IAppManager;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Files\IAppData;
use OCP\ICacheFactory;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class CSSResourceLocatorTest extends \Test\TestCase {
	private IAppData&MockObject $appData;
	private IURLGenerator&MockObject $urlGenerator;
	private IConfig&MockObject $config;
	private ThemingDefaults&MockObject $themingDefaults;
	private ICacheFactory&MockObject $cacheFactory;
	private LoggerInterface&MockObject $logger;
	private ITimeFactory&MockObject $timeFactory;
	private AppConfig&MockObject $appConfig;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->appData = $this->createMock(AppData::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->config = $this->createMock(IConfig::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->themingDefaults = $this->createMock(ThemingDefaults::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->appConfig = $this->createMock(AppConfig::class);
	}

	private function cssResourceLocator() {
		/** @var Factory|\PHPUnit\Framework\MockObject\MockObject $factory */
		$factory = $this->createMock(Factory::class);
		$factory->method('get')->with('css')->willReturn($this->appData);
		return new CSSResourceLocator(
			$this->logger,
			$this->createMock(IConfig::class),
			Server::get(IAppManager::class),
			'theme',
			['core' => 'map'],
			['3rd' => 'party'],
		);
	}

	private function rrmdir($directory) {
		$files = array_diff(scandir($directory), ['.','..']);
		foreach ($files as $file) {
			if (is_dir($directory . '/' . $file)) {
				$this->rrmdir($directory . '/' . $file);
			} else {
				unlink($directory . '/' . $file);
			}
		}
		return rmdir($directory);
	}

	private function randomString(): string {
		return sha1(random_bytes(10));
	}

	public function testFindWithAppPathSymlink(): void {
		// First create new apps path, and a symlink to it
		$apps_dirname = $this->randomString();
		$new_apps_path = sys_get_temp_dir() . '/' . $apps_dirname;
		$new_apps_path_symlink = $new_apps_path . '_link';
		mkdir($new_apps_path);
		symlink($apps_dirname, $new_apps_path_symlink);

		// Create an app within that path
		mkdir($new_apps_path . '/' . 'test_css_app');

		// Use the symlink as the app path
		\OC::$APPSROOTS[] = [
			'path' => $new_apps_path_symlink,
			'url' => '/css-apps-test',
			'writable' => false,
		];

		$locator = $this->cssResourceLocator();
		$locator->find(['test_css_app/test-file']);

		$resources = $locator->getResources();
		$this->assertCount(1, $resources);
		$resource = $resources[0];
		$this->assertCount(3, $resource);
		$root = $resource[0];
		$webRoot = $resource[1];
		$file = $resource[2];

		$expectedRoot = $new_apps_path . '/test_css_app';
		$expectedWebRoot = \OC::$WEBROOT . '/css-apps-test/test_css_app';
		$expectedFile = 'test-file.css';

		$this->assertEquals($expectedRoot, $root,
			'Ensure the app path symlink is resolved into the real path');
		$this->assertEquals($expectedWebRoot, $webRoot);
		$this->assertEquals($expectedFile, $file);

		array_pop(\OC::$APPSROOTS);
		unlink($new_apps_path_symlink);
		$this->rrmdir($new_apps_path);
	}
}
