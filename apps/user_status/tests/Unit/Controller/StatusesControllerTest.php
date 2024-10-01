<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\Tests\Controller;

use OCA\UserStatus\Controller\StatusesController;
use OCA\UserStatus\Db\UserStatus;
use OCA\UserStatus\Service\StatusService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\IRequest;
use Test\TestCase;

class StatusesControllerTest extends TestCase {

	/** @var StatusService|\PHPUnit\Framework\MockObject\MockObject */
	private $service;

	/** @var StatusesController */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$request = $this->createMock(IRequest::class);
		$this->service = $this->createMock(StatusService::class);

		$this->controller = new StatusesController('user_status', $request, $this->service);
	}

	public function testFindAll(): void {
		$userStatus = $this->getUserStatus();

		$this->service->expects($this->once())
			->method('findAll')
			->with(20, 40)
			->willReturn([$userStatus]);

		$response = $this->controller->findAll(20, 40);
		$this->assertEquals([[
			'userId' => 'john.doe',
			'status' => 'offline',
			'icon' => '🏝',
			'message' => 'On vacation',
			'clearAt' => 60000,
		]], $response->getData());
	}

	public function testFind(): void {
		$userStatus = $this->getUserStatus();

		$this->service->expects($this->once())
			->method('findByUserId')
			->with('john.doe')
			->willReturn($userStatus);

		$response = $this->controller->find('john.doe');
		$this->assertEquals([
			'userId' => 'john.doe',
			'status' => 'offline',
			'icon' => '🏝',
			'message' => 'On vacation',
			'clearAt' => 60000,
		], $response->getData());
	}

	public function testFindDoesNotExist(): void {
		$this->service->expects($this->once())
			->method('findByUserId')
			->with('john.doe')
			->willThrowException(new DoesNotExistException(''));

		$this->expectException(OCSNotFoundException::class);
		$this->expectExceptionMessage('No status for the requested userId');

		$this->controller->find('john.doe');
	}

	private function getUserStatus(): UserStatus {
		$userStatus = new UserStatus();
		$userStatus->setId(1337);
		$userStatus->setUserId('john.doe');
		$userStatus->setStatus('invisible');
		$userStatus->setStatusTimestamp(5000);
		$userStatus->setIsUserDefined(true);
		$userStatus->setCustomIcon('🏝');
		$userStatus->setCustomMessage('On vacation');
		$userStatus->setClearAt(60000);

		return $userStatus;
	}
}
