<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test;

use OC\App\AppManager;
use OC\Group\Manager;
use OC\NavigationManager;
use OC\SubAdmin;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Navigation\Events\LoadAdditionalEntriesEvent;
use OCP\Util;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

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

	protected IEVentDispatcher|MockObject $dispatcher;

	/** @var \OC\NavigationManager */
	protected $navigationManager;
	protected LoggerInterface $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->appManager = $this->createMock(AppManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->l10nFac = $this->createMock(IFactory::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->groupManager = $this->createMock(Manager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->dispatcher = $this->createMock(IEventDispatcher::class);
		$this->navigationManager = new NavigationManager(
			$this->appManager,
			$this->urlGenerator,
			$this->l10nFac,
			$this->userSession,
			$this->groupManager,
			$this->config,
			$this->logger,
			$this->dispatcher,
		);

		$this->navigationManager->clear(false);
	}

	public static function addArrayData(): array {
		return [
			[
				'entry' => [
					'id' => 'entry id',
					'name' => 'link text',
					'order' => 1,
					'icon' => 'optional',
					'href' => 'url',
					'type' => 'settings',
					'classes' => '',
					'unread' => 0
				],
				'expectedEntry' => [
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
				'entry' => [
					'id' => 'entry id',
					'name' => 'link text',
					'order' => 1,
					//'icon'	=> 'optional',
					'href' => 'url',
					'active' => true,
					'unread' => 0,
				],
				'expectedEntry' => [
					'id' => 'entry id',
					'name' => 'link text',
					'order' => 1,
					'icon' => '',
					'href' => 'url',
					'active' => false,
					'type' => 'link',
					'classes' => '',
					'unread' => 0,
					'default' => true,
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
	public function testAddArray(array $entry, array $expectedEntry): void {
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
	public function testAddClosure(array $entry, array $expectedEntry): void {
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

	public function testAddArrayClearGetAll(): void {
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

	public function testAddClosureClearGetAll(): void {
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
	public function testWithAppManager($expected, $navigation, $isAdmin = false): void {
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
		$this->appManager->expects($this->once())
			->method('getAppInfo')
			->with('test')
			->willReturn($navigation);
		$this->urlGenerator->expects($this->any())
			->method('imagePath')
			->willReturnCallback(function ($appName, $file) {
				return "/apps/$appName/img/$file";
			});
		$this->appManager->expects($this->any())
			->method('getAppIcon')
			->willReturnCallback(fn (string $appName) => "/apps/$appName/img/app.svg");
		$this->l10nFac->expects($this->any())->method('get')->willReturn($l);
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
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->willReturnCallback(function ($event): void {
				$this->assertInstanceOf(LoadAdditionalEntriesEvent::class, $event);
			});
		$entries = $this->navigationManager->getAll('all');
		$this->assertEquals($expected, $entries);
	}

	public static function providesNavigationConfig(): array {
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
			'profile' => [
				'type' => 'settings',
				'id' => 'profile',
				'order' => 1,
				'href' => '/apps/test/',
				'name' => 'View profile',
				'icon' => '',
				'active' => false,
				'classes' => '',
				'unread' => 0,
			],
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
				'href' => 'https://example.com/logout?requesttoken=' . urlencode(Util::callRegister()),
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
					['profile' => $defaults['profile']],
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
						'unread' => 0,
						'default' => true,
						'app' => 'test',
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
					['profile' => $defaults['profile']],
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
						'unread' => 0,
					]],
					['logout' => $defaults['logout']]
				),
				['navigations' => [
					'navigation' => [
						['route' => 'test.page.index', 'name' => 'Test', 'type' => 'settings']
					],
				]]
			],
			'with-multiple' => [
				array_merge(
					['profile' => $defaults['profile']],
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
						'unread' => 0,
						'default' => false,
						'app' => 'test',
					],
						'test1' => [
							'id' => 'test1',
							'order' => 50,
							'href' => '/apps/test/',
							'icon' => '/apps/test/img/app.svg',
							'name' => 'Other test',
							'active' => false,
							'type' => 'link',
							'classes' => '',
							'unread' => 0,
							'default' => true, // because of order
							'app' => 'test',
						]],
					['logout' => $defaults['logout']]
				),
				['navigations' => [
					'navigation' => [
						['route' => 'test.page.index', 'name' => 'Test'],
						['route' => 'test.page.index', 'name' => 'Other test', 'order' => 50],
					]
				]]
			],
			'admin' => [
				array_merge(
					['profile' => $defaults['profile']],
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
						'unread' => 0,
						'default' => true,
						'app' => 'test',
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
					['profile' => $defaults['profile']],
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

	public function testWithAppManagerAndApporder(): void {
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
				'default' => true,
				'app' => 'test',
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
						return json_encode(['test' => ['app' => 'test', 'order' => $testOrder]]);
					}
					return $default;
				}
			);

		$this->appManager->expects($this->any())
			->method('isEnabledForUser')
			->with('theming')
			->willReturn(true);
		$this->appManager->expects($this->once())->method('getAppInfo')->with('test')->willReturn($navigation);
		$this->appManager->expects($this->once())->method('getAppIcon')->with('test')->willReturn('/apps/test/img/app.svg');
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
		$this->dispatcher->expects($this->once())
			->method('dispatchTyped')
			->willReturnCallback(function ($event): void {
				$this->assertInstanceOf(LoadAdditionalEntriesEvent::class, $event);
			});
		$entries = $this->navigationManager->getAll();
		$this->assertEquals($expected, $entries);
	}

	public static function provideDefaultEntries(): array {
		return [
			// none specified, default to files
			[
				'',
				'',
				'{}',
				true,
				'files',
			],
			// none specified, without fallback
			[
				'',
				'',
				'{}',
				false,
				'',
			],
			// unexisting or inaccessible app specified, default to files
			[
				'unexist',
				'',
				'{}',
				true,
				'files',
			],
			// unexisting or inaccessible app specified, without fallbacks
			[
				'unexist',
				'',
				'{}',
				false,
				'',
			],
			// non-standard app
			[
				'settings',
				'',
				'{}',
				true,
				'settings',
			],
			// non-standard app, without fallback
			[
				'settings',
				'',
				'{}',
				false,
				'settings',
			],
			// non-standard app with fallback
			[
				'unexist,settings',
				'',
				'{}',
				true,
				'settings',
			],
			// system default app and user apporder
			[
				// system default is settings
				'unexist,settings',
				'',
				// apporder says default app is files (order is lower)
				'{"files_id":{"app":"files","order":1},"settings_id":{"app":"settings","order":2}}',
				true,
				// system default should override apporder
				'settings'
			],
			// user-customized defaultapp
			[
				'',
				'files',
				'',
				true,
				'files',
			],
			// user-customized defaultapp with systemwide
			[
				'unexist,settings',
				'files',
				'',
				true,
				'files',
			],
			// user-customized defaultapp with system wide and apporder
			[
				'unexist,settings',
				'files',
				'{"settings_id":{"app":"settings","order":1},"files_id":{"app":"files","order":2}}',
				true,
				'files',
			],
			// user-customized apporder fallback
			[
				'',
				'',
				'{"settings_id":{"app":"settings","order":1},"files":{"app":"files","order":2}}',
				true,
				'settings',
			],
			// user-customized apporder fallback with missing app key (entries added by closures does not always have an app key set (Nextcloud 27 spreed app for example))
			[
				'',
				'',
				'{"spreed":{"order":1},"files":{"app":"files","order":2}}',
				true,
				'files',
			],
			// user-customized apporder, but called without fallback
			[
				'',
				'',
				'{"settings":{"app":"settings","order":1},"files":{"app":"files","order":2}}',
				false,
				'',
			],
			// user-customized apporder with an app that has multiple routes
			[
				'',
				'',
				'{"settings_id":{"app":"settings","order":1},"settings_id_2":{"app":"settings","order":3},"id_files":{"app":"files","order":2}}',
				true,
				'settings',
			],
			// closure navigation entries are also resolved
			[
				'closure2',
				'',
				'',
				true,
				'closure2',
			],
			[
				'',
				'closure2',
				'',
				true,
				'closure2',
			],
			[
				'',
				'',
				'{"closure2":{"order":1,"app":"closure2","href":"/closure2"}}',
				true,
				'closure2',
			],
		];
	}

	/**
	 * @dataProvider provideDefaultEntries
	 */
	public function testGetDefaultEntryIdForUser(string $defaultApps, string $userDefaultApps, string $userApporder, bool $withFallbacks, string $expectedApp): void {
		$this->navigationManager->add([
			'id' => 'files',
		]);
		$this->navigationManager->add([
			'id' => 'settings',
		]);
		$this->navigationManager->add(static function (): array {
			return [
				'id' => 'closure1',
				'href' => '/closure1',
			];
		});
		$this->navigationManager->add(static function (): array {
			return [
				'id' => 'closure2',
				'href' => '/closure2',
			];
		});

		$this->appManager->method('getEnabledApps')->willReturn([]);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user1');

		$this->userSession->expects($this->atLeastOnce())
			->method('getUser')
			->willReturn($user);

		$this->config->expects($this->atLeastOnce())
			->method('getSystemValueString')
			->with('defaultapp', $this->anything())
			->willReturn($defaultApps);

		$this->config->expects($this->atLeastOnce())
			->method('getUserValue')
			->willReturnMap([
				['user1', 'core', 'defaultapp', '', $userDefaultApps],
				['user1', 'core', 'apporder', '[]', $userApporder],
			]);

		$this->assertEquals($expectedApp, $this->navigationManager->getDefaultEntryIdForUser(null, $withFallbacks));
	}

	public function testDefaultEntryUpdated(): void {
		$this->appManager->method('getEnabledApps')->willReturn([]);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('user1');

		$this->userSession
			->method('getUser')
			->willReturn($user);

		$this->config
			->method('getSystemValueString')
			->with('defaultapp', $this->anything())
			->willReturn('app4,app3,app2,app1');

		$this->config
			->method('getUserValue')
			->willReturnMap([
				['user1', 'core', 'defaultapp', '', ''],
				['user1', 'core', 'apporder', '[]', ''],
			]);

		$this->navigationManager->add([
			'id' => 'app1',
		]);

		$this->assertEquals('app1', $this->navigationManager->getDefaultEntryIdForUser(null, false));
		$this->assertEquals(true, $this->navigationManager->get('app1')['default']);

		$this->navigationManager->add([
			'id' => 'app3',
		]);

		$this->assertEquals('app3', $this->navigationManager->getDefaultEntryIdForUser(null, false));
		$this->assertEquals(false, $this->navigationManager->get('app1')['default']);
		$this->assertEquals(true, $this->navigationManager->get('app3')['default']);

		$this->navigationManager->add([
			'id' => 'app2',
		]);

		$this->assertEquals('app3', $this->navigationManager->getDefaultEntryIdForUser(null, false));
		$this->assertEquals(false, $this->navigationManager->get('app1')['default']);
		$this->assertEquals(false, $this->navigationManager->get('app2')['default']);
		$this->assertEquals(true, $this->navigationManager->get('app3')['default']);

		$this->navigationManager->add([
			'id' => 'app4',
		]);

		$this->assertEquals('app4', $this->navigationManager->getDefaultEntryIdForUser(null, false));
		$this->assertEquals(false, $this->navigationManager->get('app1')['default']);
		$this->assertEquals(false, $this->navigationManager->get('app2')['default']);
		$this->assertEquals(false, $this->navigationManager->get('app3')['default']);
		$this->assertEquals(true, $this->navigationManager->get('app4')['default']);
	}
}
