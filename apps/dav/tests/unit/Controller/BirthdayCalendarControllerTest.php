<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\Unit\DAV\Controller;

use OCA\DAV\BackgroundJob\GenerateBirthdayCalendarBackgroundJob;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\Controller\BirthdayCalendarController;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use Test\TestCase;

class BirthdayCalendarControllerTest extends TestCase {

	/** @var IConfig|\PHPUnit\Framework\MockObject\MockObject */
	private $config;

	/** @var IRequest|\PHPUnit\Framework\MockObject\MockObject */
	private $request;

	/** @var IDBConnection|\PHPUnit\Framework\MockObject\MockObject */
	private $db;

	/** @var IJobList|\PHPUnit\Framework\MockObject\MockObject */
	private $jobList;

	/** @var IUserManager|\PHPUnit\Framework\MockObject\MockObject */
	private $userManager;

	/** @var CalDavBackend|\PHPUnit\Framework\MockObject\MockObject */
	private $caldav;

	/** @var BirthdayCalendarController|\PHPUnit\Framework\MockObject\MockObject */
	private $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->request = $this->createMock(IRequest::class);
		$this->db = $this->createMock(IDBConnection::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->caldav = $this->createMock(CalDavBackend::class);

		$this->controller = new BirthdayCalendarController('dav',
			$this->request, $this->db, $this->config, $this->jobList,
			$this->userManager, $this->caldav);
	}

	public function testEnable(): void {
		$this->config->expects($this->once())
			->method('setAppValue')
			->with('dav', 'generateBirthdayCalendar', 'yes');

		$this->userManager->expects($this->once())
			->method('callForSeenUsers')
			->willReturnCallback(function ($closure): void {
				$user1 = $this->createMock(IUser::class);
				$user1->method('getUID')->willReturn('uid1');
				$user2 = $this->createMock(IUser::class);
				$user2->method('getUID')->willReturn('uid2');
				$user3 = $this->createMock(IUser::class);
				$user3->method('getUID')->willReturn('uid3');

				$closure($user1);
				$closure($user2);
				$closure($user3);
			});

		$this->jobList->expects($this->exactly(3))
			->method('add')
			->withConsecutive(
				[GenerateBirthdayCalendarBackgroundJob::class, ['userId' => 'uid1']],
				[GenerateBirthdayCalendarBackgroundJob::class, ['userId' => 'uid2']],
				[GenerateBirthdayCalendarBackgroundJob::class, ['userId' => 'uid3']],
			);

		$response = $this->controller->enable();
		$this->assertInstanceOf('OCP\AppFramework\Http\JSONResponse', $response);
	}

	public function testDisable(): void {
		$this->config->expects($this->once())
			->method('setAppValue')
			->with('dav', 'generateBirthdayCalendar', 'no');
		$this->jobList->expects($this->once())
			->method('remove')
			->with(GenerateBirthdayCalendarBackgroundJob::class);
		$this->caldav->expects($this->once())
			->method('deleteAllBirthdayCalendars');

		$response = $this->controller->disable();
		$this->assertInstanceOf('OCP\AppFramework\Http\JSONResponse', $response);
	}
}
