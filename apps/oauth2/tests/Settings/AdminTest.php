<?php
/**
 * @copyright Copyright (c) 2017 Lukas Reschke <lukas@statuscode.ch>
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

namespace OCA\OAuth2\Tests\Settings;

use OCA\OAuth2\Db\ClientMapper;
use OCA\OAuth2\Settings\Admin;
use OCP\AppFramework\Http\TemplateResponse;
use Test\TestCase;

class AdminTest extends TestCase {
	/** @var ClientMapper|\PHPUnit_Framework_MockObject_MockObject */
	private $clientMapper;
	/** @var Admin|\PHPUnit_Framework_MockObject_MockObject */
	private $admin;

	public function setUp() {
		parent::setUp();

		$this->clientMapper = $this->createMock(ClientMapper::class);
		$this->admin = new Admin($this->clientMapper);
	}

	public function testGetForm() {
		$this->clientMapper
			->expects($this->once())
			->method('getClients')
			->willReturn(['MyClients']);

		$expected = new TemplateResponse(
			'oauth2',
			'admin',
			[
				'clients' => ['MyClients'],
			],
			''
		);
		$this->assertEquals($expected, $this->admin->getForm());
	}

	public function testGetSection() {
		$this->assertSame('security', $this->admin->getSection());
	}

	public function testGetPriority() {
		$this->assertSame(0, $this->admin->getPriority());
	}
}
