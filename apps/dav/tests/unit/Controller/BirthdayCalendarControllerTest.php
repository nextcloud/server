<?php
/**
 * @copyright 2017, Georg Ehrke <oc.list@georgehrke.com>
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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

namespace OCA\DAV\Tests\Unit\DAV\Controller;

use OCA\DAV\Controller\BirthdayCalendarController;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IRequest;
use Test\TestCase;

class BirthdayCalendarControllerTest extends TestCase {

	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;

	/** @var IRequest|\PHPUnit_Framework_MockObject_MockObject */
	private $request;

	/** @var IDBConnection|\PHPUnit_Framework_MockObject_MockObject */
	private $db;

	/** @var BirthdayCalendarController|\PHPUnit_Framework_MockObject_MockObject */
	private $controller;

	public function setUp() {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->request = $this->createMock(IRequest::class);
		$this->db = $this->createMock(IDBConnection::class);

		$this->controller = new BirthdayCalendarController('dav',
			$this->request, $this->db, $this->config);
	}

	public function testEnable() {
		$this->config->expects($this->once())
			->method('setAppValue')
			->with('dav', 'generateBirthdayCalendar', 'yes');

		$response = $this->controller->enable();
		$this->assertInstanceOf('OCP\AppFramework\Http\JSONResponse', $response);
	}

	public function testDisable() {
		$this->config->expects($this->once())
			->method('setAppValue')
			->with('dav', 'generateBirthdayCalendar', 'no');

		$response = $this->controller->disable();
		$this->assertInstanceOf('OCP\AppFramework\Http\JSONResponse', $response);
	}

}
