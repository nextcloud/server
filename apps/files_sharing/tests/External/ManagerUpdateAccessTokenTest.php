<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Tests\External;

use OCA\Files_Sharing\External\ExternalShare;
use OCA\Files_Sharing\External\ExternalShareMapper;
use OCA\Files_Sharing\External\Manager;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\Exception;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Files\IRootFolder;
use OCP\Files\ISetupManager;
use OCP\Files\Storage\IStorageFactory;
use OCP\Http\Client\IClientService;
use OCP\ICertificateManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUserSession;
use OCP\Notification\IManager as INotificationManager;
use OCP\OCS\IDiscoveryService;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class ManagerUpdateAccessTokenTest extends TestCase {
	private ExternalShareMapper&MockObject $externalShareMapper;
	private LoggerInterface&MockObject $logger;
	private Manager $manager;

	protected function setUp(): void {
		parent::setUp();

		$this->externalShareMapper = $this->createMock(ExternalShareMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$userSession = $this->createMock(IUserSession::class);
		$userSession->method('getUser')->willReturn(null);

		$this->manager = new Manager(
			$this->createMock(IDBConnection::class),
			$this->createMock(\OC\Files\Mount\Manager::class),
			$this->createMock(IStorageFactory::class),
			$this->createMock(IClientService::class),
			$this->createMock(INotificationManager::class),
			$this->createMock(IDiscoveryService::class),
			$this->createMock(ICloudFederationProviderManager::class),
			$this->createMock(ICloudFederationFactory::class),
			$this->createMock(IGroupManager::class),
			$userSession,
			$this->createMock(IEventDispatcher::class),
			$this->logger,
			$this->createMock(IRootFolder::class),
			$this->createMock(ISetupManager::class),
			$this->createMock(ICertificateManager::class),
			$this->externalShareMapper,
			$this->createMock(IConfig::class),
		);
	}

	public function testUpdateAccessTokenUpdatesShareInDb(): void {
		$share = new ExternalShare();
		$share->setShareToken('refresh-token');

		$this->externalShareMapper->expects($this->once())
			->method('getShareByToken')
			->with('refresh-token')
			->willReturn($share);

		$this->externalShareMapper->expects($this->once())
			->method('update')
			->with($this->callback(function (ExternalShare $s) {
				return $s->getAccessToken() === 'new-access-token'
					&& $s->getAccessTokenExpires() === 9999;
			}));

		$this->manager->updateAccessToken('refresh-token', 'new-access-token', 9999);
	}

	public function testUpdateAccessTokenLogsWarningWhenShareNotFound(): void {
		$this->externalShareMapper->method('getShareByToken')
			->willThrowException(new DoesNotExistException('not found'));

		$this->externalShareMapper->expects($this->never())->method('update');

		$this->logger->expects($this->once())
			->method('warning')
			->with($this->stringContains('Could not find share'));

		$this->manager->updateAccessToken('missing-token', 'access', 0);
	}

	public function testUpdateAccessTokenLogsErrorOnDbException(): void {
		$this->externalShareMapper->method('getShareByToken')
			->willThrowException(new Exception('db error'));

		$this->externalShareMapper->expects($this->never())->method('update');

		$this->logger->expects($this->once())
			->method('error')
			->with($this->stringContains('Failed to update access token'));

		$this->manager->updateAccessToken('some-token', 'access', 0);
	}
}
