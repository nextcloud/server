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
use OCP\IURLGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class StatusesControllerTest extends TestCase {
	private StatusService&MockObject $service;
	private IRequest&MockObject $request;
	private IURLGenerator&MockObject $urlGenerator;
	private StatusesController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->service = $this->createMock(StatusService::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);

		$this->controller = new StatusesController('user_status', $this->request, $this->service, $this->urlGenerator);
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
		$this->assertArrayNotHasKey('Link', $response->getHeaders());
	}

	public function testFindAllWithLastId(): void {
		$userStatus = $this->getUserStatus();

		$this->service->expects($this->once())
			->method('findAllAfterId')
			->with(20, 1336)
			->willReturn([$userStatus]);
		$this->service->expects($this->never())
			->method('findAll');

		$response = $this->controller->findAll(20, null, 1336);
		$this->assertEquals([[
			'userId' => 'john.doe',
			'status' => 'offline',
			'icon' => '🏝',
			'message' => 'On vacation',
			'clearAt' => 60000,
		]], $response->getData());
		$this->assertArrayNotHasKey('Link', $response->getHeaders());
	}

	public function testFindAllWithLastIdHasMoreResults(): void {
		$userStatus = $this->getUserStatus();

		$this->service->expects($this->once())
			->method('findAllAfterId')
			->with(1, 1336)
			->willReturn([$userStatus]);

		$this->request->method('getRequestUri')
			->willReturn('/ocs/v2.php/apps/user_status/api/v1/statuses?limit=1&lastId=1336');
		$this->urlGenerator->method('getAbsoluteURL')
			->with('/ocs/v2.php/apps/user_status/api/v1/statuses')
			->willReturn('https://cloud.example.com/ocs/v2.php/apps/user_status/api/v1/statuses');

		$response = $this->controller->findAll(1, null, 1336);
		$this->assertSame(
			'<https://cloud.example.com/ocs/v2.php/apps/user_status/api/v1/statuses?limit=1&lastId=1337>; rel="next"',
			$response->getHeaders()['Link']
		);
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
