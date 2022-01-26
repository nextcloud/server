<?php
/**
 * @copyright Copyright (c) 2016, ownCloud GmbH.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Vincent Petry <vincent@nextcloud.com>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\DAV\Tests\unit\AppInfo;

use Exception;
use OC\App\AppManager;
use OC\ServerContainer;
use OCA\DAV\AppInfo\PluginManager;
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
	/**
	 * @throws Exception
	 */
	public function test() {
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
