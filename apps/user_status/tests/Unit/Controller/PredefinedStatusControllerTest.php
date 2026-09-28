<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\Tests\Controller;

use OCA\UserStatus\Controller\PredefinedStatusController;
use OCA\UserStatus\Service\PredefinedStatusService;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class PredefinedStatusControllerTest extends TestCase {
	private PredefinedStatusService&MockObject $service;
	private PredefinedStatusController $controller;

	protected function setUp(): void {
		parent::setUp();

		$request = $this->createMock(IRequest::class);
		$this->service = $this->createMock(PredefinedStatusService::class);

		$this->controller = new PredefinedStatusController('user_status', $request, $this->service);
	}

	public function testFindAll(): void {
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
