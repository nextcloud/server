<?php
/**
 * @copyright 2016, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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

namespace OCA\BruteForceSettings\Tests\Controller;

use OCA\BruteForceSettings\Controller\IPWhitelistController;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IRequest;
use Test\TestCase;

class IPWhitelistControllerTest extends TestCase {

	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var IPWhitelistController */
	private $controller;

	public function setUp() {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->controller = new IPWhitelistController(
			'bruteforce',
			$this->createMock(IRequest::class),
			$this->config
		);
	}

	public function testGetAll() {
		$this->config->method('getAppKeys')
			->with($this->equalTo('bruteForce'))
			->willReturn([
				'foobar',
				'whitelist_0',
				'whitelist_99',
			]);

		$this->config->method('getAppValue')
			->will($this->returnCallback(function($app, $key) {
				if ($app !== 'bruteForce') {
					$this->fail();
				}
				if ($key === 'whitelist_0') {
					return '192.168.2.0/24';
				} else if ($key === 'whitelist_99') {
					return 'dead:beef:cafe::/92';
				}
				$this->fail();
			}));

		$expected = new JSONResponse([
			[
				'id' => 0,
				'ip' => '192.168.2.0',
				'mask' => '24',
			],
			[
				'id' => 99,
				'ip' => 'dead:beef:cafe::',
				'mask' => '92',
			]
		]);

		$this->assertEquals($expected, $this->controller->getAll());
	}

	public function dataAdd() {
		return [
			['8.500.2.3', 24, false],
			['1.2.3.4', 24, true],
			['1.2.3.4', -1, false],
			['1.2.3.4', 33, false],

			['dead:nope::8', 24, false],
			['1234:567:abef::1a2b', 24, true],
			['1234:567:abef::1a2b', -1, false],
			['1234:567:abef::1a2b', 129, false],
		];
	}

	/**
	 * @dataProvider dataAdd
	 *
	 * @param string $ip
	 * @param int $mask
	 * @param bool $valid
	 */
	public function testAdd($ip, $mask, $valid) {
		if (!$valid) {
			$expected = new JSONResponse([], Http::STATUS_BAD_REQUEST);
		} else {
			$this->config->method('getAppKeys')
				->with($this->equalTo('bruteForce'))
				->willReturn([
					'foobar',
					'whitelist_0',
					'whitelist_99',
				]);

			$this->config->expects($this->once())
				->method('setAppValue')
				->with(
					$this->equalTo('bruteForce'),
					$this->equalTo('whitelist_100'),
					$this->equalTo($ip.'/'.$mask)
				);

			$expected = new JSONResponse([
				'id' => 100,
				'ip' => $ip,
				'mask' => $mask,
			]);
		}

		$this->assertEquals($expected, $this->controller->add($ip, $mask));
	}

	public function testRemove() {
		$this->config->expects($this->once())
			->method('deleteAppValue')
			->with(
				$this->equalTo('bruteForce'),
				$this->equalTo('whitelist_42')
			);

		$expected = new JSONResponse([]);
		$this->assertEquals($expected, $this->controller->remove(42));
	}
}
