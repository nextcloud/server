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

namespace Test\Settings\Admin;

use Doctrine\DBAL\Platforms\SqlitePlatform;
use OC\Settings\Admin\Server;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Lock\ILockingProvider;
use Test\TestCase;

class ServerTest extends TestCase {
	/** @var Server */
	private $admin;
	/** @var IDBConnection */
	private $dbConnection;
	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	private $request;
	/** @var IConfig */
	private $config;
	/** @var ILockingProvider */
	private $lockingProvider;
	/** @var IL10N */
	private $l10n;

	public function setUp() {
		parent::setUp();
		$this->config = $this->getMockBuilder(IConfig::class)->getMock();
		$this->request = $this->createMock(IRequest::class);
		$this->dbConnection = $this->getMockBuilder('\OCP\IDBConnection')->getMock();
		$this->lockingProvider = $this->getMockBuilder('\OCP\Lock\ILockingProvider')->getMock();
		$this->l10n = $this->getMockBuilder(IL10N::class)->getMock();

		$this->admin = new Server(
			$this->dbConnection,
			$this->request,
			$this->config,
			$this->lockingProvider,
			$this->l10n
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
