<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\UserStatus\Tests\Controller;

use OCA\UserStatus\Controller\PredefinedStatusController;
use OCA\UserStatus\Service\PredefinedStatusService;
use OCP\IRequest;
use Test\TestCase;

class PredefinedStatusControllerTest extends TestCase {

	/** @var PredefinedStatusService|\PHPUnit\Framework\MockObject\MockObject */
	private $service;

	/** @var PredefinedStatusController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$request = $this->createMock(IRequest::class);
		$this->service = $this->createMock(PredefinedStatusService::class);

		$this->controller = new PredefinedStatusController('user_status', $request,
			$this->service);
	}

	public function testFindAll() {
		$this->service->expects($this->once())
			->method('getDefaultStatuses')
			->with()
			->willReturn([
				[
					'id' => 'predefined-status-one',
				],
				[
					'id' => 'predefined-status-two',
				],
			]);

		$actual = $this->controller->findAll();
		$this->assertEquals([
			[
				'id' => 'predefined-status-one',
			],
			[
				'id' => 'predefined-status-two',
			],
		], $actual->getData());
	}
}
