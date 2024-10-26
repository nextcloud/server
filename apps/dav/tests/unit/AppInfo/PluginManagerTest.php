<?php
/**
 * SPDX-FileCopyrightText: 2016 ownCloud GmbH.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Tests\unit\AppInfo;

use OC\App\AppManager;
use OC\ServerContainer;
use OCA\DAV\AppInfo\PluginManager;
use OCA\DAV\CalDAV\AppCalendar\AppCalendarPlugin;
use OCA\DAV\CalDAV\Integration\ICalendarProvider;
use Sabre\DAV\Collection;
use Sabre\DAV\ServerPlugin;
use Test\TestCase;

/**
 * Class PluginManagerTest
 *
 * @package OCA\DAV\Tests\Unit\AppInfo
 */
class PluginManagerTest extends TestCase {
	public function test(): void {
		$server = $this->createMock(ServerContainer::class);

		$appManager = $this->createMock(AppManager::class);
		$appManager->method('getInstalledApps')
			->willReturn(['adavapp', 'adavapp2']);

		$appInfo1 = [
			'types' => ['dav'],
			'sabre' => [
				'plugins' => [
					'plugin' => [
						'\OCA\DAV\ADavApp\PluginOne',
						'\OCA\DAV\ADavApp\PluginTwo',
					],
				],
				'calendar-plugins' => [
					'plugin' => [
						'\OCA\DAV\ADavApp\CalendarPluginOne',
						'\OCA\DAV\ADavApp\CalendarPluginTwo',
					],
				],
				'collections' => [
					'collection' => [
						'\OCA\DAV\ADavApp\CollectionOne',
						'\OCA\DAV\ADavApp\CollectionTwo',
					]
				],
			],
		];
		$appInfo2 = [
			'types' => ['logging', 'dav'],
			'sabre' => [
				'plugins' => [
					'plugin' => '\OCA\DAV\ADavApp2\PluginOne',
				],
				'calendar-plugins' => [
					'plugin' => '\OCA\DAV\ADavApp2\CalendarPluginOne',
				],
				'collections' => [
					'collection' => '\OCA\DAV\ADavApp2\CollectionOne',
				],
			],
		];

		$appManager->method('getAppInfo')
			->willReturnMap([
				['adavapp', false, null, $appInfo1],
				['adavapp2', false, null, $appInfo2],
			]);

		$pluginManager = new PluginManager($server, $appManager);

		$appCalendarPlugin = $this->createMock(AppCalendarPlugin::class);
		$calendarPlugin1 = $this->createMock(ICalendarProvider::class);
		$calendarPlugin2 = $this->createMock(ICalendarProvider::class);
		$calendarPlugin3 = $this->createMock(ICalendarProvider::class);

		$dummyPlugin1 = $this->createMock(ServerPlugin::class);
		$dummyPlugin2 = $this->createMock(ServerPlugin::class);
		$dummy2Plugin1 = $this->createMock(ServerPlugin::class);

		$dummyCollection1 = $this->createMock(Collection::class);
		$dummyCollection2 = $this->createMock(Collection::class);
		$dummy2Collection1 = $this->createMock(Collection::class);

		$server->method('get')
			->willReturnMap([
				[AppCalendarPlugin::class, $appCalendarPlugin],
				['\OCA\DAV\ADavApp\PluginOne', $dummyPlugin1],
				['\OCA\DAV\ADavApp\PluginTwo', $dummyPlugin2],
				['\OCA\DAV\ADavApp\CalendarPluginOne', $calendarPlugin1],
				['\OCA\DAV\ADavApp\CalendarPluginTwo', $calendarPlugin2],
				['\OCA\DAV\ADavApp\CollectionOne', $dummyCollection1],
				['\OCA\DAV\ADavApp\CollectionTwo', $dummyCollection2],
				['\OCA\DAV\ADavApp2\PluginOne', $dummy2Plugin1],
				['\OCA\DAV\ADavApp2\CalendarPluginOne', $calendarPlugin3],
				['\OCA\DAV\ADavApp2\CollectionOne', $dummy2Collection1],
			]);

		$expectedPlugins = [
			$dummyPlugin1,
			$dummyPlugin2,
			$dummy2Plugin1,
		];
		$expectedCalendarPlugins = [
			$appCalendarPlugin,
			$calendarPlugin1,
			$calendarPlugin2,
			$calendarPlugin3,
		];
		$expectedCollections = [
			$dummyCollection1,
			$dummyCollection2,
			$dummy2Collection1,
		];

		$this->assertEquals($expectedPlugins, $pluginManager->getAppPlugins());
		$this->assertEquals($expectedCalendarPlugins, $pluginManager->getCalendarPlugins());
		$this->assertEquals($expectedCollections, $pluginManager->getAppCollections());
	}
}
