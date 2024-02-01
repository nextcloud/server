<?php
/**
 * @copyright Copyright (c) 2017 Kyle Fazzari <kyrofa@ubuntu.com>
 *
 * @author Kyle Fazzari <kyrofa@ubuntu.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Test\Template;

use OC\SystemConfig;
use OC\Template\JSCombiner;
use OC\Template\JSResourceLocator;
use OCP\App\AppPathNotFoundException;
use OCP\App\IAppManager;
use OCP\Files\IAppData;
use OCP\ICacheFactory;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;

class JSResourceLocatorTest extends \Test\TestCase {
	/** @var IAppData|\PHPUnit\Framework\MockObject\MockObject */
	protected $appData;
	/** @var IURLGenerator|\PHPUnit\Framework\MockObject\MockObject */
	protected $urlGenerator;
	/** @var SystemConfig|\PHPUnit\Framework\MockObject\MockObject */
	protected $config;
	/** @var ICacheFactory|\PHPUnit\Framework\MockObject\MockObject */
	protected $cacheFactory;
	/** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
	protected $logger;
	/** @var IAppManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $appManager;

	protected function setUp(): void {
		parent::setUp();

		$this->appData = $this->createMock(IAppData::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->config = $this->createMock(SystemConfig::class);
		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->appManager = $this->createMock(IAppManager::class);
	}

	private function jsResourceLocator() {
		$jsCombiner = new JSCombiner(
			$this->appData,
			$this->urlGenerator,
			$this->cacheFactory,
			$this->config,
			$this->logger
		);
		return new JSResourceLocator(
			$this->logger,
			$jsCombiner,
			$this->appManager,
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

	private function randomString() {
		return sha1(uniqid(mt_rand(), true));
	}

	public function testFindWithAppPathSymlink() {
		$appName = 'test-js-app';

		// First create new apps path, and a symlink to it
		$apps_dirname = $this->randomString();
		$new_apps_path = sys_get_temp_dir() . '/' . $apps_dirname;
		$new_apps_path_symlink = $new_apps_path . '_link';
		$this->assertTrue((
			mkdir($new_apps_path) && symlink($apps_dirname, $new_apps_path_symlink)
		), 'Setup of apps path failed');

		// Create an app within that path
		$this->assertTrue((
			mkdir($new_apps_path . '/' . $appName) && touch($new_apps_path . '/' . $appName . '/' . 'test-file.js')
		), 'Setup of app within the new apps path failed');

		// Use the symlink as the app path
		$this->appManager->expects($this->once())
			->method('getAppPath')
			->with($appName)
			->willReturn("$new_apps_path_symlink/$appName");
		$this->appManager->expects($this->once())
			->method('getAppWebPath')
			->with($appName)
			->willReturn("/js-apps-test/$appName");

		// Run the tests
		$locator = $this->jsResourceLocator();
		$locator->find(["$appName/test-file"]);

		$resources = $locator->getResources();
		$this->assertCount(1, $resources);
		$resource = $resources[0];
		$this->assertCount(3, $resource);
		$root = $resource[0];
		$webRoot = $resource[1];
		$file = $resource[2];

		$expectedRoot = $new_apps_path . '/test-js-app';
		$expectedWebRoot = \OC::$WEBROOT . '/js-apps-test/test-js-app';
		$expectedFile = 'test-file.js';

		$this->assertEquals($expectedRoot, $root,
			'Ensure the app path symlink is resolved into the real path');
		$this->assertEquals($expectedWebRoot, $webRoot);
		$this->assertEquals($expectedFile, $file);

		unlink($new_apps_path_symlink);
		$this->rrmdir($new_apps_path);
	}

	public function testNotExistingTranslationHandledSilent() {
		$this->appManager->expects($this->once())
			->method('getAppPath')
			->with('core')
			->willThrowException(new AppPathNotFoundException());
		$this->appManager->expects($this->once())
			->method('getAppWebPath')
			->with('core')
			->willThrowException(new AppPathNotFoundException());
		// Assert logger is not called
		$this->logger->expects($this->never())
			->method('error');

		// Run the tests
		$locator = $this->jsResourceLocator();
		$locator->find(["core/l10n/en.js"]);

		$resources = $locator->getResources();
		$this->assertCount(0, $resources);
	}

	public function testFindModuleJSWithFallback() {
		// First create new apps path, and a symlink to it
		$apps_dirname = $this->randomString();
		$new_apps_path = sys_get_temp_dir() . '/' . $apps_dirname;
		mkdir($new_apps_path);

		// Create an app within that path
		mkdir("$new_apps_path/test-js-app");
		touch("$new_apps_path/test-js-app/module.mjs");
		touch("$new_apps_path/test-js-app/both.mjs");
		touch("$new_apps_path/test-js-app/both.js");
		touch("$new_apps_path/test-js-app/plain.js");

		// Use the app path
		$this->appManager->expects($this->any())
			->method('getAppPath')
			->with('test-js-app')
			->willReturn("$new_apps_path/test-js-app");

		$locator = $this->jsResourceLocator();
		$locator->find(['test-js-app/module', 'test-js-app/both', 'test-js-app/plain']);

		$resources = $locator->getResources();
		$this->assertCount(3, $resources);

		$expectedWebRoot = \OC::$WEBROOT . '/js-apps-test/test-js-app';
		$expectedFiles = ['module.mjs', 'both.mjs', 'plain.js'];

		for ($idx = 0; $idx++; $idx < 3) {
			$this->assertEquals($expectedWebRoot, $resources[$idx][1]);
			$this->assertEquals($expectedFiles[$idx], $resources[$idx][2]);
		}

		$this->rrmdir($new_apps_path);
	}
}
