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

use OC\Files\AppData\Factory;
use OCP\Files\IAppData;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\ILogger;
use OCP\IURLGenerator;
use OCP\IConfig;
use OCA\Theming\ThemingDefaults;
use OCP\ICache;
use OC\Template\SCSSCacher;
use OC\Template\CSSResourceLocator;

class CSSResourceLocatorTest extends \Test\TestCase {
	/** @var IAppData|\PHPUnit_Framework_MockObject_MockObject */
	protected $appData;
	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	protected $urlGenerator;
	/** @var SystemConfig|\PHPUnit_Framework_MockObject_MockObject */
	protected $config;
	/** @var ThemingDefaults|\PHPUnit_Framework_MockObject_MockObject */
	protected $themingDefaults;
	/** @var ICache|\PHPUnit_Framework_MockObject_MockObject */
	protected $depsCache;
	/** @var ILogger|\PHPUnit_Framework_MockObject_MockObject */
	protected $logger;
	protected $appname;
	protected $appdir;
	protected $appdirLink;
	protected $appurl;

	protected function setUp() {
		parent::setUp();

		$this->logger = $this->createMock(ILogger::class);
		$this->appData = $this->createMock(IAppData::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->config = $this->createMock(IConfig::class);
		$this->depsCache = $this->createMock(ICache::class);
		$this->themingDefaults = $this->createMock(ThemingDefaults::class);

		$this->appdir = null;
		$this->themingDefaults
			->expects($this->any())
			->method('getScssVariables')
			->willReturn([]);
	}

	protected function tearDown() {
		if (!is_null($this->appdir)) {
			array_pop(\OC::$APPSROOTS);
        	        unlink($this->appdirLink);
			$this->rrmdir($this->appdir);
		}
	}

	private function cssResourceLocator() {
		/** @var Factory|\PHPUnit_Framework_MockObject_MockObject $factory */
                $factory = $this->createMock(Factory::class);
                $factory->method('get')->with('css')->willReturn($this->appData);
		$scssCacher = new SCSSCacher(
			$this->logger,
			$factory,
			$this->urlGenerator,
			$this->config,
			$this->themingDefaults,
			\OC::$SERVERROOT,
			$this->depsCache
		);
		return new CSSResourceLocator(
			$this->logger,
			'theme',
			array('core'=>'map'),
			array('3rd'=>'party'),
			$scssCacher
		);
	}

	private function rrmdir($directory) {
		$files = array_diff(scandir($directory), array('.','..'));
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

	private function setupAppDir() {
		$this->appname = 'test-app-'.$this->randomString();
		$folder = $this->createMock(ISimpleFolder::class);
		$this->appData->method('getFolder')
			->with($this->appname)
			->willReturn($folder);

		$file = $this->createMock(ISimpleFile::class);
		$folder->method('getFile')
			->will($this->returnCallback(function($path) use ($file) {
				return $file;
			}));

		$this->urlGenerator
			->method('linkToRoute')
			->willReturn(\OC::$WEBROOT . '/test-file');

		// First create new apps path, and a symlink to it
		$apps_dirname = $this->randomString();
		$this->appdir = sys_get_temp_dir() . '/' . $apps_dirname;
		$this->appdirLink = $this->appdir . '_link';
		mkdir($this->appdir);
		symlink($apps_dirname, $this->appdirLink);

		// Create an app within that path
		mkdir($this->appdir . '/' . $this->appname);

		$this->appurl = 'css-apps-test';

		// Use the symlink as the app path
		\OC::$APPSROOTS[] = [
                        'path' => $this->appdirLink,
                        'url' => '/' . $this->appurl,
                        'writable' => false,
                ];
	}

	public function testConstructor() {
		$locator = $this->cssResourceLocator();
		$this->assertAttributeEquals('theme', 'theme', $locator);
		$this->assertAttributeEquals('core', 'serverroot', $locator);
		$this->assertAttributeEquals(array('core'=>'map','3rd'=>'party'), 'mapping', $locator);
		$this->assertAttributeEquals('3rd', 'thirdpartyroot', $locator);
		$this->assertAttributeEquals('map', 'webroot', $locator);
		$this->assertAttributeEquals(array(), 'resources', $locator);
	}

	public function testFindCSSWithAppPathSymlink() {
		$this->setupAppDir();

		$locator = $this->cssResourceLocator();
		$locator->find(array($this->appname . '/test-file'));

		$resources = $locator->getResources();
		$this->assertCount(1, $resources);
		$resource = $resources[0];
		$this->assertCount(3, $resource);
		$root = $resource[0];
		$webRoot = $resource[1];
		$file = $resource[2];

		$expectedRoot = $this->appdir . '/' . $this->appname;
		$expectedWebRoot = \OC::$WEBROOT . '/' . $this->appurl . '/' . $this->appname;
		$expectedFile = 'test-file.css';

		$this->assertEquals($expectedRoot, $root,
			'Ensure the app path symlink is resolved into the real path');
		$this->assertEquals($expectedWebRoot, $webRoot);
		$this->assertEquals($expectedFile, $file);
	}

	public function testFindSCSSWithAppPathSymlink() {
		$this->setupAppDir();

		// Create an SCSS file there
		touch($this->appdir . '/' . $this->appname . '/test-file.scss');

		$locator = $this->cssResourceLocator();
		$locator->find(array($this->appname . '/test-file'));

		$resources = $locator->getResources();
		$this->assertCount(1, $resources);
		$resource = $resources[0];
		$this->assertCount(3, $resource);
		$root = $resource[0];
		$webRoot = $resource[1];
		$file = $resource[2];

		$expectedRoot = '';
		$expectedWebRoot = '';
		$expectedFile = 'test-file';

		$this->assertEquals($expectedRoot, $root,
			'Ensure the app path symlink is resolved into the real path');
		$this->assertEquals($expectedWebRoot, $webRoot);
		$this->assertEquals($expectedFile, $file);
	}
}
