<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\Tests\unit\DAV\Controller;

use OCA\DAV\BackgroundJob\GenerateBirthdayCalendarBackgroundJob;
use OCA\DAV\CalDAV\CalDavBackend;
use OCA\DAV\Controller\BirthdayCalendarController;
use OCP\AppFramework\Http\JSONResponse;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IRequest;
use OCP\IUser;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class BirthdayCalendarControllerTest extends TestCase {
	private IConfig&MockObject $config;
	private IRequest&MockObject $request;
	private IDBConnection&MockObject $db;
	private IJobList&MockObject $jobList;
	private IUserManager&MockObject $userManager;
	private CalDavBackend&MockObject $caldav;
	private BirthdayCalendarController $controller;

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

		$calls = [
			[GenerateBirthdayCalendarBackgroundJob::class, ['userId' => 'uid1']],
			[GenerateBirthdayCalendarBackgroundJob::class, ['userId' => 'uid2']],
			[GenerateBirthdayCalendarBackgroundJob::class, ['userId' => 'uid3']],
		];
		$this->jobList->expects($this->exactly(3))
			->method('add')
			->willReturnCallback(function () use (&$calls): void {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});

		$response = $this->controller->enable();
		$this->assertInstanceOf(JSONResponse::class, $response);
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
		$this->assertInstanceOf(JSONResponse::class, $response);
	}
}
