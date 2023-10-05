<?php
/**
 * ownCloud
 *
 * @author Joas Schilling
 * @copyright 2015 Joas Schilling nickvergessen@owncloud.com
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace Test;

use OC\App\AppManager;
use OC\Group\Manager;
use OC\NavigationManager;
use OC\SubAdmin;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\L10N\IFactory;

class NavigationManagerTest extends TestCase {
	/** @var AppManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $appManager;
	/** @var IURLGenerator|\PHPUnit\Framework\MockObject\MockObject */
	protected $urlGenerator;
	/** @var IFactory|\PHPUnit\Framework\MockObject\MockObject */
	protected $l10nFac;
	/** @var IUserSession|\PHPUnit\Framework\MockObject\MockObject */
	protected $userSession;
	/** @var IGroupManager|\PHPUnit\Framework\MockObject\MockObject */
	protected $groupManager;
	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	protected $config;

	/** @var \OC\NavigationManager */
	protected $navigationManager;

	protected function setUp(): void {
		parent::setUp();

		$this->appManager = $this->createMock(AppManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->l10nFac = $this->createMock(IFactory::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->groupManager = $this->createMock(Manager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->navigationManager = new NavigationManager(
			$this->appManager,
			$this->urlGenerator,
			$this->l10nFac,
			$this->userSession,
			$this->groupManager,
			$this->config
		);

		$this->navigationManager->clear(false);
	}

	public function addArrayData() {
		return [
			[
				'entry id' => [
					'id' => 'entry id',
					'name' => 'link text',
					'order' => 1,
					'icon' => 'optional',
					'href' => 'url',
					'type' => 'settings',
					'classes' => '',
					'unread' => 0
				],
				'entry id2' => [
					'id' => 'entry id',
					'name' => 'link text',
					'order' => 1,
					'icon' => 'optional',
					'href' => 'url',
					'active' => false,
					'type' => 'settings',
					'classes' => '',
					'unread' => 0
				]
			],
			[
				'entry id' => [
					'id' => 'entry id',
					'name' => 'link text',
					'order' => 1,
					//'icon'	=> 'optional',
					'href' => 'url',
					'active' => true,
					'unread' => 0
				],
				'entry id2' => [
					'id' => 'entry id',
					'name' => 'link text',
					'order' => 1,
					'icon' => '',
					'href' => 'url',
					'active' => false,
					'type' => 'link',
					'classes' => '',
					'unread' => 0
				]
			]
		];
	}

	/**
	 * @dataProvider addArrayData
	 *
	 * @param array $entry
	 * @param array $expectedEntry
	 */
	public function testAddArray(array $entry, array $expectedEntry) {
		$this->assertEmpty($this->navigationManager->getAll('all'), 'Expected no navigation entry exists');
		$this->navigationManager->add($entry);

		$navigationEntries = $this->navigationManager->getAll('all');
		$this->assertCount(1, $navigationEntries, 'Expected that 1 navigation entry exists');
		$this->assertEquals($expectedEntry, $navigationEntries['entry id']);

		$this->navigationManager->clear(false);
		$this->assertEmpty($this->navigationManager->getAll('all'), 'Expected no navigation entry exists after clear()');
	}

	/**
	 * @dataProvider addArrayData
	 *
	 * @param array $entry
	 * @param array $expectedEntry
	 */
	public function testAddClosure(array $entry, array $expectedEntry) {
		global $testAddClosureNumberOfCalls;
		$testAddClosureNumberOfCalls = 0;

		$this->navigationManager->add(function () use ($entry) {
			global $testAddClosureNumberOfCalls;
			$testAddClosureNumberOfCalls++;

			return $entry;
		});

		$this->assertEquals(0, $testAddClosureNumberOfCalls, 'Expected that the closure is not called by add()');

		$navigationEntries = $this->navigationManager->getAll('all');
		$this->assertEquals(1, $testAddClosureNumberOfCalls, 'Expected that the closure is called by getAll()');
		$this->assertCount(1, $navigationEntries, 'Expected that 1 navigation entry exists');
		$this->assertEquals($expectedEntry, $navigationEntries['entry id']);

		$navigationEntries = $this->navigationManager->getAll('all');
		$this->assertEquals(1, $testAddClosureNumberOfCalls, 'Expected that the closure is only called once for getAll()');
		$this->assertCount(1, $navigationEntries, 'Expected that 1 navigation entry exists');
		$this->assertEquals($expectedEntry, $navigationEntries['entry id']);

		$this->navigationManager->clear(false);
		$this->assertEmpty($this->navigationManager->getAll('all'), 'Expected no navigation entry exists after clear()');
	}

	public function testAddArrayClearGetAll() {
		$entry = [
			'id' => 'entry id',
			'name' => 'link text',
			'order' => 1,
			'icon' => 'optional',
			'href' => 'url'
		];

		$this->assertEmpty($this->navigationManager->getAll(), 'Expected no navigation entry exists');
		$this->navigationManager->add($entry);
		$this->navigationManager->clear(false);
		$this->assertEmpty($this->navigationManager->getAll(), 'Expected no navigation entry exists after clear()');
	}

	public function testAddClosureClearGetAll() {
		$this->assertEmpty($this->navigationManager->getAll(), 'Expected no navigation entry exists');

		$entry = [
			'id' => 'entry id',
			'name' => 'link text',
			'order' => 1,
			'icon' => 'optional',
			'href' => 'url'
		];

		global $testAddClosureNumberOfCalls;
		$testAddClosureNumberOfCalls = 0;

		$this->navigationManager->add(function () use ($entry) {
			global $testAddClosureNumberOfCalls;
			$testAddClosureNumberOfCalls++;

			return $entry;
		});

		$this->assertEquals(0, $testAddClosureNumberOfCalls, 'Expected that the closure is not called by add()');
		$this->navigationManager->clear(false);
		$this->assertEquals(0, $testAddClosureNumberOfCalls, 'Expected that the closure is not called by clear()');
		$this->assertEmpty($this->navigationManager->getAll(), 'Expected no navigation entry exists after clear()');
		$this->assertEquals(0, $testAddClosureNumberOfCalls, 'Expected that the closure is not called by getAll()');
	}

	/**
	 * @dataProvider providesNavigationConfig
	 */
	public function testWithAppManager($expected, $navigation, $isAdmin = false) {
		$l = $this->createMock(IL10N::class);
		$l->expects($this->any())->method('t')->willReturnCallback(function ($text, $parameters = []) {
			return vsprintf($text, $parameters);
		});

		/* Return default value */
		$this->config->method('getUserValue')
			->willReturnArgument(3);

		$this->appManager->expects($this->any())
		   ->method('isEnabledForUser')
		   ->with('theming')
		   ->willReturn(true);
		$this->appManager->expects($this->once())->method('getAppInfo')->with('test')->willReturn($navigation);
		/*
		$this->appManager->expects($this->any())
				   ->method('getAppInfo')
				   ->will($this->returnValueMap([
					   ['test', null, null, $navigation],
					   ['theming', null, null, null],
					]));
		 */
		$this->l10nFac->expects($this->any())->method('get')->willReturn($l);
		$this->urlGenerator->expects($this->any())->method('imagePath')->willReturnCallback(function ($appName, $file) {
			return "/apps/$appName/img/$file";
		});
		$this->urlGenerator->expects($this->any())->method('linkToRoute')->willReturnCallback(function ($route) {
			if ($route === 'core.login.logout') {
				return 'https://example.com/logout';
			}
			return '/apps/test/';
		});
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())->method('getUID')->willReturn('user001');
		$this->userSession->expects($this->any())->method('getUser')->willReturn($user);
		$this->userSession->expects($this->any())->method('isLoggedIn')->willReturn(true);
		$this->appManager->expects($this->any())
			 ->method('getEnabledAppsForUser')
			 ->with($user)
			 ->willReturn(['test']);
		$this->groupManager->expects($this->any())->method('isAdmin')->willReturn($isAdmin);
		$subadmin = $this->createMock(SubAdmin::class);
		$subadmin->expects($this->any())->method('isSubAdmin')->with($user)->willReturn(false);
		$this->groupManager->expects($this->any())->method('getSubAdmin')->willReturn($subadmin);

		$this->navigationManager->clear();
		$entries = $this->navigationManager->getAll('all');
		$this->assertEquals($expected, $entries);
	}

	public function providesNavigationConfig() {
		$apps = [
			'core_apps' => [
				'id' => 'core_apps',
				'order' => 5,
				'href' => '/apps/test/',
				'icon' => '/apps/settings/img/apps.svg',
				'name' => 'Apps',
				'active' => false,
				'type' => 'settings',
				'classes' => '',
				'unread' => 0
			]
		];
		$defaults = [
			'accessibility_settings' => [
				'type' => 'settings',
				'id' => 'accessibility_settings',
				'order' => 2,
				'href' => '/apps/test/',
				'name' => 'Appearance and accessibility',
				'icon' => '/apps/theming/img/accessibility-dark.svg',
				'active' => false,
				'classes' => '',
				'unread' => 0,
			],
			'settings' => [
				'id' => 'settings',
				'order' => 3,
				'href' => '/apps/test/',
				'icon' => '/apps/settings/img/admin.svg',
				'name' => 'Settings',
				'active' => false,
				'type' => 'settings',
				'classes' => '',
				'unread' => 0
			],
			'logout' => [
				'id' => 'logout',
				'order' => 99999,
				'href' => 'https://example.com/logout?requesttoken='. urlencode(\OCP\Util::callRegister()),
				'icon' => '/apps/core/img/actions/logout.svg',
				'name' => 'Log out',
				'active' => false,
				'type' => 'settings',
				'classes' => '',
				'unread' => 0
			]
		];
		$adminSettings = [
			'accessibility_settings' => $defaults['accessibility_settings'],
			'settings' => [
				'id' => 'settings',
				'order' => 3,
				'href' => '/apps/test/',
				'icon' => '/apps/settings/img/personal.svg',
				'name' => 'Personal settings',
				'active' => false,
				'type' => 'settings',
				'classes' => '',
				'unread' => 0
			],
			'admin_settings' => [
				'id' => 'admin_settings',
				'order' => 4,
				'href' => '/apps/test/',
				'icon' => '/apps/settings/img/admin.svg',
				'name' => 'Administration settings',
				'active' => false,
				'type' => 'settings',
				'classes' => '',
				'unread' => 0
			]
		];

		return [
			'minimalistic' => [
				array_merge(
					['accessibility_settings' => $defaults['accessibility_settings']],
					['settings' => $defaults['settings']],
					['test' => [
						'id' => 'test',
						'order' => 100,
						'href' => '/apps/test/',
						'icon' => '/apps/test/img/app.svg',
						'name' => 'Test',
						'active' => false,
						'type' => 'link',
						'classes' => '',
						'unread' => 0
					]],
					['logout' => $defaults['logout']]
				),
				['navigations' => [
					'navigation' => [
						['route' => 'test.page.index', 'name' => 'Test']
					]
				]]
			],
			'minimalistic-settings' => [
				array_merge(
					['accessibility_settings' => $defaults['accessibility_settings']],
					['settings' => $defaults['settings']],
					['test' => [
						'id' => 'test',
						'order' => 100,
						'href' => '/apps/test/',
						'icon' => '/apps/test/img/app.svg',
						'name' => 'Test',
						'active' => false,
						'type' => 'settings',
						'classes' => '',
						'unread' => 0
					]],
					['logout' => $defaults['logout']]
				),
				['navigations' => [
					'navigation' => [
						['route' => 'test.page.index', 'name' => 'Test', 'type' => 'settings']
					],
				]]
			],
			'admin' => [
				array_merge(
					$adminSettings,
					$apps,
					['test' => [
						'id' => 'test',
						'order' => 100,
						'href' => '/apps/test/',
						'icon' => '/apps/test/img/app.svg',
						'name' => 'Test',
						'active' => false,
						'type' => 'link',
						'classes' => '',
						'unread' => 0
					]],
					['logout' => $defaults['logout']]
				),
				['navigations' => [
					'navigation' => [
						['@attributes' => ['role' => 'admin'], 'route' => 'test.page.index', 'name' => 'Test']
					],
				]],
				true
			],
			'no name' => [
				array_merge(
					$adminSettings,
					$apps,
					['logout' => $defaults['logout']]
				),
				['navigations' => [
					'navigation' => [
						['@attributes' => ['role' => 'admin'], 'route' => 'test.page.index']
					],
				]],
				true
			],
			'no admin' => [
				$defaults,
				['navigations' => [
					'navigation' => [
						['@attributes' => ['role' => 'admin'], 'route' => 'test.page.index', 'name' => 'Test']
					],
				]],
			]
		];
	}

	public function testWithAppManagerAndApporder() {
		$l = $this->createMock(IL10N::class);
		$l->expects($this->any())->method('t')->willReturnCallback(function ($text, $parameters = []) {
			return vsprintf($text, $parameters);
		});

		$testOrder = 12;
		$expected = [
			'test' => [
				'type' => 'link',
				'id' => 'test',
				'order' => $testOrder,
				'href' => '/apps/test/',
				'name' => 'Test',
				'icon' => '/apps/test/img/app.svg',
				'active' => false,
				'classes' => '',
				'unread' => 0,
			],
		];
		$navigation = ['navigations' => [
			'navigation' => [
				['route' => 'test.page.index', 'name' => 'Test']
			],
		]];

		$this->config->method('getUserValue')
			->willReturnCallback(
				function (string $userId, string $appName, string $key, mixed $default = '') use ($testOrder) {
					$this->assertEquals('user001', $userId);
					if ($key === 'apporder') {
						return json_encode(['test' => [$testOrder]]);
					}
					return $default;
				}
			);

		$this->appManager->expects($this->any())
		   ->method('isEnabledForUser')
		   ->with('theming')
		   ->willReturn(true);
		$this->appManager->expects($this->once())->method('getAppInfo')->with('test')->willReturn($navigation);
		$this->l10nFac->expects($this->any())->method('get')->willReturn($l);
		$this->urlGenerator->expects($this->any())->method('imagePath')->willReturnCallback(function ($appName, $file) {
			return "/apps/$appName/img/$file";
		});
		$this->urlGenerator->expects($this->any())->method('linkToRoute')->willReturnCallback(function ($route) {
			if ($route === 'core.login.logout') {
				return 'https://example.com/logout';
			}
			return '/apps/test/';
		});
		$user = $this->createMock(IUser::class);
		$user->expects($this->any())->method('getUID')->willReturn('user001');
		$this->userSession->expects($this->any())->method('getUser')->willReturn($user);
		$this->userSession->expects($this->any())->method('isLoggedIn')->willReturn(true);
		$this->appManager->expects($this->any())
			 ->method('getEnabledAppsForUser')
			 ->with($user)
			 ->willReturn(['test']);
		$this->groupManager->expects($this->any())->method('isAdmin')->willReturn(false);
		$subadmin = $this->createMock(SubAdmin::class);
		$subadmin->expects($this->any())->method('isSubAdmin')->with($user)->willReturn(false);
		$this->groupManager->expects($this->any())->method('getSubAdmin')->willReturn($subadmin);

		$this->navigationManager->clear();
		$entries = $this->navigationManager->getAll();
		$this->assertEquals($expected, $entries);
	}
}
