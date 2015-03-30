<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Settings\Controller;

use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\TemplateResponse;
use Test\TestCase;
use OCP\IRequest;
use OCP\IL10N;
use OCP\IConfig;
use OCP\ICache;
use OCP\INavigationManager;
use OCP\App\IAppManager;
use OC\OCSClient;

/**
 * Class AppSettingsControllerTest
 *
 * @package OC\Settings\Controller
 */
class AppSettingsControllerTest extends TestCase {
	/** @var AppSettingsController */
	private $appSettingsController;
	/** @var IRequest */
	private $request;
	/** @var IL10N */
	private $l10n;
	/** @var IConfig */
	private $config;
	/** @var ICache */
	private $cache;
	/** @var INavigationManager */
	private $navigationManager;
	/** @var IAppManager */
	private $appManager;
	/** @var OCSClient */
	private $ocsClient;

	public function setUp() {
		parent::setUp();

		$this->request = $this->getMockBuilder('\OCP\IRequest')
			->disableOriginalConstructor()->getMock();
		$this->l10n = $this->getMockBuilder('\OCP\IL10N')
			->disableOriginalConstructor()->getMock();
		$this->l10n->expects($this->any())
			->method('t')
			->will($this->returnArgument(0));
		$this->config = $this->getMockBuilder('\OCP\IConfig')
			->disableOriginalConstructor()->getMock();
		$cacheFactory = $this->getMockBuilder('\OCP\ICacheFactory')
			->disableOriginalConstructor()->getMock();
		$this->cache = $this->getMockBuilder('\OCP\ICache')
			->disableOriginalConstructor()->getMock();
		$cacheFactory
			->expects($this->once())
			->method('create')
			->with('settings')
			->will($this->returnValue($this->cache));

		$this->navigationManager = $this->getMockBuilder('\OCP\INavigationManager')
			->disableOriginalConstructor()->getMock();
		$this->appManager = $this->getMockBuilder('\OCP\App\IAppManager')
			->disableOriginalConstructor()->getMock();
		$this->ocsClient = $this->getMockBuilder('\OC\OCSClient')
			->disableOriginalConstructor()->getMock();

		$this->appSettingsController = new AppSettingsController(
			'settings',
			$this->request,
			$this->l10n,
			$this->config,
			$cacheFactory,
			$this->navigationManager,
			$this->appManager,
			$this->ocsClient
		);
	}

	public function testChangeExperimentalConfigStateTrue() {
		$this->config
			->expects($this->once())
			->method('setSystemValue')
			->with('appstore.experimental.enabled', true);
		$this->appManager
			->expects($this->once())
			->method('clearAppsCache');
		$this->assertEquals(new DataResponse(), $this->appSettingsController->changeExperimentalConfigState(true));
	}

	public function testChangeExperimentalConfigStateFalse() {
		$this->config
			->expects($this->once())
			->method('setSystemValue')
			->with('appstore.experimental.enabled', false);
		$this->appManager
			->expects($this->once())
			->method('clearAppsCache');
		$this->assertEquals(new DataResponse(), $this->appSettingsController->changeExperimentalConfigState(false));
	}

	public function testListCategoriesCached() {
		$this->cache
			->expects($this->exactly(2))
			->method('get')
			->with('listCategories')
			->will($this->returnValue(['CachedArray']));
		$this->assertSame(['CachedArray'], $this->appSettingsController->listCategories());
	}

	public function testListCategoriesNotCachedWithoutAppStore() {
		$expected = [
			[
				'id' => 0,
				'displayName' => 'Enabled',
			],
			[
				'id' => 1,
				'displayName' => 'Not enabled',
			],
		];
		$this->cache
			->expects($this->once())
			->method('get')
			->with('listCategories')
			->will($this->returnValue(null));
		$this->cache
			->expects($this->once())
			->method('set')
			->with('listCategories', $expected, 3600);


		$this->assertSame($expected, $this->appSettingsController->listCategories());
	}

	public function testListCategoriesNotCachedWithAppStore() {
		$expected = [
			[
				'id' => 0,
				'displayName' => 'Enabled',
			],
			[
				'id' => 1,
				'displayName' => 'Not enabled',
			],
			[
				'id' => 0,
				'displayName' => 'Tools',
			],
			[
				'id' => 1,
				'displayName' => 'Awesome Games',
			],
			[
				'id' => 2,
				'displayName' => 'PIM',
			],
			[
				'id' => 3,
				'displayName' => 'Papershop',
			],
		];

		$this->cache
			->expects($this->once())
			->method('get')
			->with('listCategories')
			->will($this->returnValue(null));
		$this->cache
			->expects($this->once())
			->method('set')
			->with('listCategories', $expected, 3600);

		$this->ocsClient
			->expects($this->once())
			->method('isAppStoreEnabled')
			->will($this->returnValue(true));
		$this->ocsClient
			->expects($this->once())
			->method('getCategories')
			->will($this->returnValue(
				[
					'ownCloud Tools',
					'Awesome Games',
					'ownCloud PIM',
					'Papershop',
				]
			));

		$this->assertSame($expected, $this->appSettingsController->listCategories());
	}

	public function testViewApps() {
		$this->config
			->expects($this->once())
			->method('getSystemValue')
			->with('appstore.experimental.enabled', false);
		$this->navigationManager
			->expects($this->once())
			->method('setActiveEntry')
			->with('core_apps');

		$policy = new ContentSecurityPolicy();
		$policy->addAllowedImageDomain('https://apps.owncloud.com');

		$expected = new TemplateResponse('settings', 'apps', ['experimentalEnabled' => false], 'user');
		$expected->setContentSecurityPolicy($policy);

		$this->assertEquals($expected, $this->appSettingsController->viewApps());
	}
}
