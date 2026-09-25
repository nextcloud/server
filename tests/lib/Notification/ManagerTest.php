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
	/** @var ICache|MockObject */
	protected $cache;
	/** @var RegistrationContext|MockObject */
	protected $registrationContext;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->cache = $this->createMock(ICache::class);
		$this->manager = $this->createInstanceWithMocks(Manager::class);
		$this->mocks[ICacheFactory::class]->method('createDistributed')
			->with('notifications')
			->willReturn($this->cache);

		$this->registrationContext = $this->createMock(RegistrationContext::class);
		$this->mocks[Coordinator::class]->method('getRegistrationContext')
			->willReturn($this->registrationContext);
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

		$this->mocks[LoggerInterface::class]->expects($this->once())
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

		$this->mocks[LoggerInterface::class]->expects($this->once())
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
				$this->mocks[IValidator::class],
				$this->mocks[IUserManager::class],
				$this->mocks[ICacheFactory::class],
				$this->mocks[IRegistry::class],
				$this->mocks[LoggerInterface::class],
				$this->mocks[Coordinator::class],
				$this->mocks[IRichTextFormatter::class],
			])
			->onlyMethods(['getApps'])
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
				$this->mocks[IValidator::class],
				$this->mocks[IUserManager::class],
				$this->mocks[ICacheFactory::class],
				$this->mocks[IRegistry::class],
				$this->mocks[LoggerInterface::class],
				$this->mocks[Coordinator::class],
				$this->mocks[IRichTextFormatter::class],
			])
			->onlyMethods(['getApps'])
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
				$this->mocks[IValidator::class],
				$this->mocks[IUserManager::class],
				$this->mocks[ICacheFactory::class],
				$this->mocks[IRegistry::class],
				$this->mocks[LoggerInterface::class],
				$this->mocks[Coordinator::class],
				$this->mocks[IRichTextFormatter::class],
			])
			->onlyMethods(['getApps'])
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
				$this->mocks[IValidator::class],
				$this->mocks[IUserManager::class],
				$this->mocks[ICacheFactory::class],
				$this->mocks[IRegistry::class],
				$this->mocks[LoggerInterface::class],
				$this->mocks[Coordinator::class],
				$this->mocks[IRichTextFormatter::class],
			])
			->onlyMethods(['getApps'])
			->getMock();

		$manager->expects($this->once())
			->method('getApps')
			->willReturn([]);

		$manager->getCount($notification);
	}

	public static function dataIsFairUseOfFreePushService(): array {
		return [
			[true, 999, true],
			[true, 1000, true],
			[false, 999, true],
			[false, 1000, false],
		];
	}

	/**
	 * @param bool $hasValidSubscription
	 * @param int $userCount
	 * @param bool $isFair
	 */
	#[\PHPUnit\Framework\Attributes\DataProvider('dataIsFairUseOfFreePushService')]
	public function testIsFairUseOfFreePushService(bool $hasValidSubscription, int $userCount, bool $isFair): void {
		$this->mocks[IRegistry::class]->method('delegateHasValidSubscription')
			->willReturn($hasValidSubscription);

		$this->mocks[IUserManager::class]->method('countSeenUsers')
			->willReturn($userCount);

		$this->assertSame($isFair, $this->manager->isFairUseOfFreePushService());
	}
}
