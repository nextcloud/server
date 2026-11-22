<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\UpdateNotification\Tests\Controller;

use OCA\UpdateNotification\BackgroundJob\ResetToken;
use OCA\UpdateNotification\Controller\AdminController;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use OCP\Security\ISecureRandom;
use OCP\ServerVersion;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class AdminControllerTest extends TestCase {
	protected IRequest&MockObject $request;
	protected IJobList&MockObject $jobList;
	protected ISecureRandom&MockObject $secureRandom;
	protected IConfig&MockObject $config;
	protected ITimeFactory&MockObject $timeFactory;
	protected IL10N&MockObject $l10n;
	protected IAppConfig&MockObject $appConfig;
	protected LoggerInterface&MockObject $logger;
	protected ServerVersion&MockObject $serverVersion;
	protected IEventDispatcher&MockObject $eventDispatcher;

	protected AdminController $adminController;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->secureRandom = $this->createMock(ISecureRandom::class);
		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->serverVersion = $this->createMock(ServerVersion::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$this->adminController = new AdminController(
			'updatenotification',
			$this->request,
			$this->jobList,
			$this->secureRandom,
			$this->config,
			$this->appConfig,
			$this->timeFactory,
			$this->l10n,
			$this->logger,
			$this->serverVersion,
			$this->eventDispatcher,
		);
	}

	public function testCreateCredentials(): void {
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
		$this->eventDispatcher
			->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $event): bool
				=> $event->getOperation() === 'updatenotification.updater_token.created'
				&& $event->getLogMessage() === 'Web updater token was created'
				&& $event->getParameters() === []));

		$expected = new DataResponse('MyGeneratedToken');
		$this->assertEquals($expected, $this->adminController->createCredentials());
	}

	public function testCreateCredentialsAndWebUpdaterDisabled(): void {
		$this->config
			->expects($this->once())
			->method('getSystemValueBool')
			->with('upgrade.disable-web')
			->willReturn(true);
		$this->jobList
			->expects($this->never())
			->method('add');
		$this->secureRandom
			->expects($this->never())
			->method('generate');
		$this->config
			->expects($this->never())
			->method('setSystemValue');
		$this->timeFactory
			->expects($this->never())
			->method('getTime');
		$this->appConfig
			->expects($this->never())
			->method('setValueInt');
		$this->eventDispatcher
			->expects($this->never())
			->method('dispatchTyped');

		$this->adminController->createCredentials();
	}

	public function testSetChannel(): void {
		$this->serverVersion
			->expects($this->once())
			->method('setChannel')
			->with('beta');
		$this->appConfig
			->expects($this->once())
			->method('setValueInt')
			->with('core', 'lastupdatedat', 0);
		$this->eventDispatcher
			->expects($this->once())
			->method('dispatchTyped')
			->with($this->callback(fn (CriticalActionPerformedEvent $event): bool
				=> $event->getOperation() === 'updatenotification.channel.updated'
				&& $event->getLogMessage() === 'Update channel was changed to "%s"'
				&& $event->getParameters() === ['channel' => 'beta']));

		$this->adminController->setChannel('beta');
	}
}
