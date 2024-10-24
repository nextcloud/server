<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Notification;

use OC\AppFramework\Bootstrap\Coordinator;
use OC\AppFramework\Bootstrap\RegistrationContext;
use OC\AppFramework\Bootstrap\ServiceRegistration;
use OC\Notification\Manager;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IUserManager;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use OCP\RichObjectStrings\IRichTextFormatter;
use OCP\RichObjectStrings\IValidator;
use OCP\Support\Subscription\IRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class ManagerTest extends TestCase {
	/** @var IManager */
	protected $manager;

	protected IValidator&MockObject $validator;
	protected IRichTextFormatter&MockObject $richTextFormatter;
	/** @var IUserManager|MockObject */
	protected $userManager;
	/** @var ICacheFactory|MockObject */
	protected $cacheFactory;
	/** @var ICache|MockObject */
	protected $cache;
	/** @var IRegistry|MockObject */
	protected $subscriptionRegistry;
	/** @var LoggerInterface|MockObject */
	protected $logger;
	/** @var Coordinator|MockObject */
	protected $coordinator;
	/** @var RegistrationContext|MockObject */
	protected $registrationContext;

	protected function setUp(): void {
		parent::setUp();

		$this->validator = $this->createMock(IValidator::class);
		$this->richTextFormatter = $this->createMock(IRichTextFormatter::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->cache = $this->createMock(ICache::class);
		$this->subscriptionRegistry = $this->createMock(IRegistry::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cacheFactory->method('createDistributed')
			->with('notifications')
			->willReturn($this->cache);

		$this->registrationContext = $this->createMock(RegistrationContext::class);
		$this->coordinator = $this->createMock(Coordinator::class);
		$this->coordinator->method('getRegistrationContext')
			->willReturn($this->registrationContext);

		$this->manager = new Manager(
			$this->validator,
			$this->userManager,
			$this->cacheFactory,
			$this->subscriptionRegistry,
			$this->logger,
			$this->coordinator,
			$this->richTextFormatter,
		);
	}

	public function testRegisterApp(): void {
		$this->assertEquals([], self::invokePrivate($this->manager, 'getApps'));

		$this->manager->registerApp(DummyApp::class);

		$this->assertCount(1, self::invokePrivate($this->manager, 'getApps'));
		$this->assertCount(1, self::invokePrivate($this->manager, 'getApps'));

		$this->manager->registerApp(DummyApp::class);

		$this->assertCount(2, self::invokePrivate($this->manager, 'getApps'));
	}

	public function testRegisterAppInvalid(): void {
		$this->manager->registerApp(DummyNotifier::class);

		$this->logger->expects($this->once())
			->method('error');
		self::invokePrivate($this->manager, 'getApps');
	}

	public function testRegisterNotifier(): void {
		$this->assertEquals([], self::invokePrivate($this->manager, 'getNotifiers'));

		$this->manager->registerNotifierService(DummyNotifier::class);

		$this->assertCount(1, self::invokePrivate($this->manager, 'getNotifiers'));
		$this->assertCount(1, self::invokePrivate($this->manager, 'getNotifiers'));

		$this->manager->registerNotifierService(DummyNotifier::class);

		$this->assertCount(2, self::invokePrivate($this->manager, 'getNotifiers'));
	}

	public function testRegisterNotifierBootstrap(): void {
		$this->registrationContext->method('getNotifierServices')
			->willReturn([
				new ServiceRegistration('app', DummyNotifier::class),
			]);

		$this->assertCount(1, self::invokePrivate($this->manager, 'getNotifiers'));
		$this->assertCount(1, self::invokePrivate($this->manager, 'getNotifiers'));
	}

	public function testRegisterNotifierInvalid(): void {
		$this->manager->registerNotifierService(DummyApp::class);

		$this->logger->expects($this->once())
			->method('error');
		self::invokePrivate($this->manager, 'getNotifiers');
	}

	public function testCreateNotification(): void {
		$action = $this->manager->createNotification();
		$this->assertInstanceOf(INotification::class, $action);
	}

	public function testNotify(): void {
		/** @var INotification|MockObject $notification */
		$notification = $this->getMockBuilder(INotification::class)
			->disableOriginalConstructor()
			->getMock();
		$notification->expects($this->once())
			->method('isValid')
			->willReturn(true);

		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->validator,
				$this->userManager,
				$this->cacheFactory,
				$this->subscriptionRegistry,
				$this->logger,
				$this->coordinator,
				$this->richTextFormatter,
			])
			->setMethods(['getApps'])
			->getMock();

		$manager->expects($this->once())
			->method('getApps')
			->willReturn([]);

		$manager->notify($notification);
	}


	public function testNotifyInvalid(): void {
		$this->expectException(\InvalidArgumentException::class);

		/** @var INotification|MockObject $notification */
		$notification = $this->getMockBuilder(INotification::class)
			->disableOriginalConstructor()
			->getMock();
		$notification->expects($this->once())
			->method('isValid')
			->willReturn(false);

		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->validator,
				$this->userManager,
				$this->cacheFactory,
				$this->subscriptionRegistry,
				$this->logger,
				$this->coordinator,
				$this->richTextFormatter,
			])
			->setMethods(['getApps'])
			->getMock();

		$manager->expects($this->never())
			->method('getApps');

		$manager->notify($notification);
	}

	public function testMarkProcessed(): void {
		/** @var INotification|MockObject $notification */
		$notification = $this->getMockBuilder(INotification::class)
			->disableOriginalConstructor()
			->getMock();

		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->validator,
				$this->userManager,
				$this->cacheFactory,
				$this->subscriptionRegistry,
				$this->logger,
				$this->coordinator,
				$this->richTextFormatter,
			])
			->setMethods(['getApps'])
			->getMock();

		$manager->expects($this->once())
			->method('getApps')
			->willReturn([]);

		$manager->markProcessed($notification);
	}

	public function testGetCount(): void {
		/** @var INotification|MockObject $notification */
		$notification = $this->getMockBuilder(INotification::class)
			->disableOriginalConstructor()
			->getMock();

		$manager = $this->getMockBuilder(Manager::class)
			->setConstructorArgs([
				$this->validator,
				$this->userManager,
				$this->cacheFactory,
				$this->subscriptionRegistry,
				$this->logger,
				$this->coordinator,
				$this->richTextFormatter,
			])
			->setMethods(['getApps'])
			->getMock();

		$manager->expects($this->once())
			->method('getApps')
			->willReturn([]);

		$manager->getCount($notification);
	}

	public function dataIsFairUseOfFreePushService(): array {
		return [
			[true, 999, true],
			[true, 1000, true],
			[false, 999, true],
			[false, 1000, false],
		];
	}

	/**
	 * @dataProvider dataIsFairUseOfFreePushService
	 * @param bool $hasValidSubscription
	 * @param int $userCount
	 * @param bool $isFair
	 */
	public function testIsFairUseOfFreePushService(bool $hasValidSubscription, int $userCount, bool $isFair): void {
		$this->subscriptionRegistry->method('delegateHasValidSubscription')
			->willReturn($hasValidSubscription);

		$this->userManager->method('countSeenUsers')
			->willReturn($userCount);

		$this->assertSame($isFair, $this->manager->isFairUseOfFreePushService());
	}
}
