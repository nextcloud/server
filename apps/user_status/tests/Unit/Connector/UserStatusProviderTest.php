<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\Tests\Connector;

use OCA\UserStatus\Connector\UserStatusProvider;
use OCA\UserStatus\Db\UserStatus;
use OCA\UserStatus\Service\StatusService;
use Test\TestCase;

class UserStatusProviderTest extends TestCase {

	/** @var \PHPUnit\Framework\MockObject\MockObject */
	private $service;

	/** @var UserStatusProvider */
	private $provider;

	protected function setUp(): void {
		parent::setUp();

		$this->service = $this->createMock(StatusService::class);
		$this->provider = new UserStatusProvider($this->service);
	}

	public function testGetUserStatuses(): void {
		$userStatus2 = new UserStatus();
		$userStatus2->setUserId('userId2');
		$userStatus2->setStatus('dnd');
		$userStatus2->setStatusTimestamp(5000);
		$userStatus2->setIsUserDefined(true);
		$userStatus2->setCustomIcon('💩');
		$userStatus2->setCustomMessage('Do not disturb');
		$userStatus2->setClearAt(50000);

		$userStatus3 = new UserStatus();
		$userStatus3->setUserId('userId3');
		$userStatus3->setStatus('away');
		$userStatus3->setStatusTimestamp(5000);
		$userStatus3->setIsUserDefined(false);
		$userStatus3->setCustomIcon('🏝');
		$userStatus3->setCustomMessage('On vacation');
		$userStatus3->setClearAt(60000);

		$this->service->expects($this->once())
			->method('findByUserIds')
			->with(['userId1', 'userId2', 'userId3'])
			->willReturn([$userStatus2, $userStatus3]);

		$actual = $this->provider->getUserStatuses(['userId1', 'userId2', 'userId3']);

		$this->assertCount(2, $actual);
		$status2 = $actual['userId2'];
		$this->assertEquals('userId2', $status2->getUserId());
		$this->assertEquals('dnd', $status2->getStatus());
		$this->assertEquals('Do not disturb', $status2->getMessage());
		$this->assertEquals('💩', $status2->getIcon());
		$dateTime2 = $status2->getClearAt();
		$this->assertInstanceOf(\DateTimeImmutable::class, $dateTime2);
		$this->assertEquals('50000', $dateTime2->format('U'));

		$status3 = $actual['userId3'];
		$this->assertEquals('userId3', $status3->getUserId());
		$this->assertEquals('away', $status3->getStatus());
		$this->assertEquals('On vacation', $status3->getMessage());
		$this->assertEquals('🏝', $status3->getIcon());
		$dateTime3 = $status3->getClearAt();
		$this->assertInstanceOf(\DateTimeImmutable::class, $dateTime3);
		$this->assertEquals('60000', $dateTime3->format('U'));
	}
}
