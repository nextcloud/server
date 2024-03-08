<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\UpdateNotification\Tests\Controller;

use OCA\UpdateNotification\BackgroundJob\ResetToken;
use OCA\UpdateNotification\Controller\AdminController;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Security\ISecureRandom;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class AdminControllerTest extends TestCase {
	private IRequest|MockObject $request;
	private IJobList|MockObject $jobList;
	private ISecureRandom|MockObject $secureRandom;
	private IConfig|MockObject $config;
	private ITimeFactory|MockObject $timeFactory;
	private IL10N|MockObject $l10n;
	private IAppConfig|MockObject $appConfig;

	private AdminController $adminController;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->secureRandom = $this->createMock(ISecureRandom::class);
		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->l10n = $this->createMock(IL10N::class);

		$this->adminController = new AdminController(
			'updatenotification',
			$this->request,
			$this->jobList,
			$this->secureRandom,
			$this->config,
			$this->appConfig,
			$this->timeFactory,
			$this->l10n
		);
	}

	public function testCreateCredentials() {
		$this->jobList
			->expects($this->once())
			->method('add')
			->with(ResetToken::class);
		$this->secureRandom
			->expects($this->once())
			->method('generate')
			->with(64)
			->willReturn('MyGeneratedToken');
		$this->config
			->expects($this->once())
			->method('setSystemValue')
			->with('updater.secret');
		$this->timeFactory
			->expects($this->once())
			->method('getTime')
			->willReturn(12345);
		$this->appConfig
			->expects($this->once())
			->method('setValueInt')
			->with('core', 'updater.secret.created', 12345);

		$expected = new DataResponse('MyGeneratedToken');
		$this->assertEquals($expected, $this->adminController->createCredentials());
	}
}
