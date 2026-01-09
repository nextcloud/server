<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\UpdateNotification\Tests\Notification;

use OCA\UpdateNotification\Notification\Notifier;
use OCP\App\IAppManager;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Notification\AlreadyProcessedException;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use OCP\ServerVersion;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class NotifierTest extends TestCase {

	protected IURLGenerator&MockObject $urlGenerator;
	protected IAppConfig&MockObject $appConfig;
	protected IManager&MockObject $notificationManager;
	protected IFactory&MockObject $l10nFactory;
	protected IUserSession&MockObject $userSession;
	protected IGroupManager&MockObject $groupManager;
	protected IAppManager&MockObject $appManager;
	protected ServerVersion&MockObject $serverVersion;

	protected function setUp(): void {
		parent::setUp();

		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->notificationManager = $this->createMock(IManager::class);
		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->serverVersion = $this->createMock(ServerVersion::class);
	}

	/**
	 * @param array $methods
	 */
	protected function getNotifier(array $methods = []): Notifier|MockObject {
		if (empty($methods)) {
			return new Notifier(
				$this->urlGenerator,
				$this->appConfig,
				$this->notificationManager,
				$this->l10nFactory,
				$this->userSession,
				$this->groupManager,
				$this->appManager,
				$this->serverVersion,
			);
		}
		{
			return $this->getMockBuilder(Notifier::class)
				->setConstructorArgs([
					$this->urlGenerator,
					$this->appConfig,
					$this->notificationManager,
					$this->l10nFactory,
					$this->userSession,
					$this->groupManager,
					$this->appManager,
					$this->serverVersion,
				])
				->onlyMethods($methods)
				->getMock();
		}
	}

	public static function dataUpdateAlreadyInstalledCheck(): array {
		return [
			['1.1.0', '1.0.0', false],
			['1.1.0', '1.1.0', true],
			['1.1.0', '1.2.0', true],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataUpdateAlreadyInstalledCheck')]
	public function testUpdateAlreadyInstalledCheck(string $versionNotification, string $versionInstalled, bool $exception): void {
		$notifier = $this->getNotifier();

		$notification = $this->createMock(INotification::class);
		$notification->expects($this->once())
			->method('getObjectId')
			->willReturn($versionNotification);

		try {
			self::invokePrivate($notifier, 'updateAlreadyInstalledCheck', [$notification, $versionInstalled]);
			$this->assertFalse($exception);
		} catch (\Exception $e) {
			$this->assertTrue($exception);
			$this->assertInstanceOf(AlreadyProcessedException::class, $e);
		}
	}
}
