<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Theming\Tests;

use OCP\AppFramework\App;
use Test\TestCase;

/**
 * Class ServicesTest
 *
 * @group DB
 * @package OCA\Theming\Tests
 */
class ServicesTest extends TestCase  {
	/** @var \OCA\Activity\AppInfo\Application */
	protected $app;

	/** @var \OCP\AppFramework\IAppContainer */
	protected $container;

	protected function setUp() {
		parent::setUp();
		$this->app = new App('theming');
		$this->container = $this->app->getContainer();
	}

	public function queryData() {
		return [
			['OCP\IL10N'],

			// lib/
			['OCA\Theming\Capabilities'],
			['OCA\Theming\Capabilities', 'OCP\Capabilities\ICapability'],
			['OCA\Theming\ThemingDefaults'],
			['OCA\Theming\ThemingDefaults', 'OC_Defaults'],
			['OCA\Theming\Util'],

			// Controller
			['OCA\Theming\Controller\ThemingController'],

			// Settings
			['OCA\Theming\Settings\Admin'],
			['OCA\Theming\Settings\Admin', 'OCP\Settings\ISettings'],
			['OCA\Theming\Settings\Section'],
			['OCA\Theming\Settings\Section', 'OCP\Settings\ISection'],
		];
	}

	/**
	 * @dataProvider queryData
	 * @param string $service
	 * @param string $expected
	 */
	public function testContainerQuery($service, $expected = null) {
		if ($expected === null) {
			$expected = $service;
		}
		$this->assertTrue($this->container->query($service) instanceof $expected);
	}
}
