<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\FederatedFileSharing\Tests;

use OCA\FederatedFileSharing\AddressHandler;
use OCA\FederatedFileSharing\BackgroundJob\RetryJob;
use OCA\FederatedFileSharing\Notifications;
use OCP\BackgroundJob\IJobList;
use OCP\Constants;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudFederationShare;
use OCP\Http\Client\IClientService;
use OCP\OCS\IDiscoveryService;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class NotificationsTest extends \Test\TestCase {
	private AddressHandler&MockObject $addressHandler;
	private IClientService&MockObject $httpClientService;
	private IDiscoveryService&MockObject $discoveryService;
	private IJobList&MockObject $jobList;
	private ICloudFederationProviderManager&MockObject $cloudFederationProviderManager;
	private ICloudFederationFactory&MockObject $cloudFederationFactory;
	private IEventDispatcher&MockObject $eventDispatcher;
	private LoggerInterface&MockObject $logger;

	protected function setUp(): void {
		parent::setUp();

		$this->jobList = $this->createMock(IJobList::class);
		$this->discoveryService = $this->createMock(IDiscoveryService::class);
		$this->httpClientService = $this->createMock(IClientService::class);
		$this->addressHandler = $this->createMock(AddressHandler::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->cloudFederationProviderManager = $this->createMock(ICloudFederationProviderManager::class);
		$this->cloudFederationFactory = $this->createMock(ICloudFederationFactory::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
	}

	/**
	 * @return Notifications|MockObject
	 */
	private function getInstance(array $mockedMethods = []) {
		if (empty($mockedMethods)) {
			return new Notifications(
				$this->addressHandler,
				$this->httpClientService,
				$this->discoveryService,
				$this->jobList,
				$this->cloudFederationProviderManager,
				$this->cloudFederationFactory,
				$this->eventDispatcher,
				$this->logger,
			);
		}

		return $this->getMockBuilder(Notifications::class)
			->setConstructorArgs(
				[
					$this->addressHandler,
					$this->httpClientService,
					$this->discoveryService,
					$this->jobList,
					$this->cloudFederationProviderManager,
					$this->cloudFederationFactory,
					$this->eventDispatcher,
					$this->logger,
				]
			)
			->onlyMethods($mockedMethods)
			->getMock();
	}

	public function testSendRemoteSharePermissions(): void {
		$permissions = Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE | Constants::PERMISSION_SHARE;
		$this->addressHandler->method('splitUserRemote')->willReturn(['recipient', 'https://remote.example']);
		$this->addressHandler->method('generateRemoteURL')->willReturn('https://local.example');
		$this->addressHandler->method('urlContainProtocol')->willReturn(true);
		$share = $this->createMock(ICloudFederationShare::class);
		$this->cloudFederationFactory->expects($this->once())
			->method('getCloudFederationShare')
			->with(
				'recipient@https://remote.example',
				'document.odt',
				'',
				'123',
				'owner@local.example',
				'Owner',
				'owner@local.example',
				'Owner',
				'share-secret',
				IShare::TYPE_REMOTE,
				'file',
				$permissions,
			)
			->willReturn($share);
		$this->cloudFederationProviderManager->expects($this->once())
			->method('sendShare')->with($share)->willReturn([]);
		$this->httpClientService->expects($this->never())->method('newClient');

		$this->assertTrue($this->getInstance()->sendRemoteShare(
			'share-secret', 'recipient@remote.example', 'document.odt', '123',
			'Owner', 'owner@local.example', 'Owner', 'owner@local.example',
			IShare::TYPE_REMOTE, $permissions,
		));
	}

	public static function legacyPermissionsProvider(): array {
		return [
			'new share' => ['share', ['token' => 'share-secret']],
			'permission update' => ['permissions', ['token' => 'share-secret', 'permissions' => Constants::PERMISSION_READ]],
		];
	}

	#[\PHPUnit\Framework\Attributes\DataProvider('legacyPermissionsProvider')]
	public function testLegacyPermissions(string $action, array $expectedFields): void {
		$this->addressHandler->method('urlContainProtocol')->willReturn(true);
		$fields = ['token' => 'share-secret', 'permissions' => Constants::PERMISSION_READ];
		$result = ['success' => true, 'result' => ''];
		$instance = $this->getInstance(['tryOCMEndPoint', 'tryLegacyEndPoint']);
		$instance->expects($this->once())->method('tryOCMEndPoint')
			->with('https://remote.example', $fields, $action)->willReturn(false);
		$instance->expects($this->once())->method('tryLegacyEndPoint')
			->with('https://remote.example', '', $expectedFields)->willReturn($result);

		$this->assertSame($result, $this->invokePrivate($instance, 'tryHttpPostToShareEndpoint', ['https://remote.example', '', $fields, $action]));
	}

	#[\PHPUnit\Framework\Attributes\DataProvider(methodName: 'dataTestSendUpdateToRemote')]
	public function testSendUpdateToRemote(int $try, array $httpRequestResult, bool $expected): void {
		$remote = 'http://remote';
		$id = 42;
		$timestamp = 63576;
		$token = 'token';
		$action = 'unshare';
		$instance = $this->getInstance(['tryHttpPostToShareEndpoint', 'getTimestamp']);

		$instance->expects($this->any())->method('getTimestamp')->willReturn($timestamp);

		$instance->expects($this->once())->method('tryHttpPostToShareEndpoint')
			->with($remote, '/' . $id . '/unshare', ['token' => $token, 'data1Key' => 'data1Value', 'remoteId' => $id], $action)
			->willReturn($httpRequestResult);

		// only add background job on first try
		if ($try === 0 && $expected === false) {
			$this->jobList->expects($this->once())->method('add')
				->with(
					RetryJob::class,
					[
						'remote' => $remote,
						'remoteId' => $id,
						'action' => 'unshare',
						'data' => json_encode(['data1Key' => 'data1Value']),
						'token' => $token,
						'try' => $try,
						'lastRun' => $timestamp
					]
				);
		} else {
			$this->jobList->expects($this->never())->method('add');
		}

		$this->assertSame($expected,
			$instance->sendUpdateToRemote($remote, $id, $token, $action, ['data1Key' => 'data1Value'], $try)
		);
	}

	public static function dataTestSendUpdateToRemote(): array {
		return [
			// test if background job is added correctly
			[0, ['success' => true, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 200]]])], true],
			[1, ['success' => true, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 200]]])], true],
			[0, ['success' => false, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 200]]])], false],
			[1, ['success' => false, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 200]]])], false],
			// test all combinations of 'statuscode' and 'success'
			[0, ['success' => true, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 200]]])], true],
			[0, ['success' => true, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 100]]])], true],
			[0, ['success' => true, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 400]]])], false],
			[0, ['success' => false, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 200]]])], false],
			[0, ['success' => false, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 100]]])], false],
			[0, ['success' => false, 'result' => json_encode(['ocs' => ['meta' => ['statuscode' => 400]]])], false],
		];
	}
}
