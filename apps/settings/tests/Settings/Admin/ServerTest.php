<?php
/**
 * @copyright Copyright (c) 2016 Lukas Reschke <lukas@statuscode.ch>
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
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

namespace OCA\Settings\Tests\Settings\Admin;

use OCA\Settings\Admin\Server;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use Test\TestCase;

class ServerTest extends TestCase {
	/** @var Server */
	private $admin;
	/** @var IConfig */
	private $config;

	public function setUp() {
		parent::setUp();
		$this->config = $this->createMock(IConfig::class);

		$this->admin = new Server(
			$this->config
		);
	}

	public function testGetForm() {
		$this->config
			->expects($this->at(0))
			->method('getAppValue')
			->with('core', 'backgroundjobs_mode', 'ajax')
			->willReturn('ajax');
		$this->config
			->expects($this->at(1))
			->method('getAppValue')
			->with('core', 'lastcron', false)
			->willReturn(false);
		$this->config
			->expects($this->at(2))
			->method('getAppValue')
			->with('core', 'cronErrors')
			->willReturn('');
		$expected = new TemplateResponse(
			'settings',
			'settings/admin/server',
			[
				'backgroundjobs_mode' => 'ajax',
				'lastcron'            => false,
				'cronErrors'		  => '',
				'cli_based_cron_possible' => true,
				'cli_based_cron_user' => function_exists('posix_getpwuid') ? posix_getpwuid(fileowner(\OC::$configDir . 'config.php'))['name'] : '', // to not explode here because of posix extension not being disabled - which is already checked in the line above
			],
			''
		);

		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection() {
		$this->assertSame('server', $this->admin->getSection());
	}

	public function testGetPriority() {
		$this->assertSame(0, $this->admin->getPriority());
	}
}
