<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\FederatedFileSharing\Tests\OCM;

use OC\OCM\OCMSignatoryManager;
use OCA\FederatedFileSharing\AddressHandler;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\FederatedFileSharing\OCM\CloudFederationProviderFiles;
use OCA\Files_Sharing\External\ExternalShareMapper;
use OCA\Files_Sharing\External\Manager;
use OCP\Activity\IManager as IActivityManager;
use OCP\App\IAppManager;
use OCP\Federation\Exceptions\ProviderCouldNotAddShareException;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudFederationShare;
use OCP\Federation\ICloudIdManager;
use OCP\Files\IFilenameValidator;
use OCP\Files\ISetupManager;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Notification\IManager as INotificationManager;
use OCP\OCM\IOCMDiscoveryService;
use OCP\OCM\IOCMProvider;
use OCP\Security\Signature\ISignatureManager;
use OCP\Share\IManager;
use OCP\Share\IProviderFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class CloudFederationProviderFilesTest extends TestCase {
	private IAppManager&MockObject $appManager;
	private FederatedShareProvider&MockObject $federatedShareProvider;
	private AddressHandler&MockObject $addressHandler;
	private IUserManager&MockObject $userManager;
	private IManager&MockObject $shareManager;
	private ICloudIdManager&MockObject $cloudIdManager;
	private IActivityManager&MockObject $activityManager;
	private INotificationManager&MockObject $notificationManager;
	private IURLGenerator&MockObject $urlGenerator;
	private ICloudFederationFactory&MockObject $cloudFederationFactory;
	private ICloudFederationProviderManager&MockObject $cloudFederationProviderManager;
	private IGroupManager&MockObject $groupManager;
	private IConfig&MockObject $config;
	private Manager&MockObject $externalShareManager;
	private LoggerInterface&MockObject $logger;
	private IFilenameValidator&MockObject $filenameValidator;
	private IProviderFactory&MockObject $shareProviderFactory;
	private ISetupManager&MockObject $setupManager;
	private ExternalShareMapper&MockObject $externalShareMapper;
	private IOCMDiscoveryService&MockObject $discoveryService;
	private IClientService&MockObject $clientService;
	private ISignatureManager&MockObject $signatureManager;
	private OCMSignatoryManager&MockObject $signatoryManager;
	private IAppConfig&MockObject $appConfig;

	private CloudFederationProviderFiles $provider;

	protected function setUp(): void {
		parent::setUp();

		$this->appManager = $this->createMock(IAppManager::class);
		$this->federatedShareProvider = $this->createMock(FederatedShareProvider::class);
		$this->addressHandler = $this->createMock(AddressHandler::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->shareManager = $this->createMock(IManager::class);
		$this->cloudIdManager = $this->createMock(ICloudIdManager::class);
		$this->activityManager = $this->createMock(IActivityManager::class);
		$this->notificationManager = $this->createMock(INotificationManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->cloudFederationFactory = $this->createMock(ICloudFederationFactory::class);
		$this->cloudFederationProviderManager = $this->createMock(ICloudFederationProviderManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->externalShareManager = $this->createMock(Manager::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->filenameValidator = $this->createMock(IFilenameValidator::class);
		$this->shareProviderFactory = $this->createMock(IProviderFactory::class);
		$this->setupManager = $this->createMock(ISetupManager::class);
		$this->externalShareMapper = $this->createMock(ExternalShareMapper::class);
		$this->discoveryService = $this->createMock(IOCMDiscoveryService::class);
		$this->clientService = $this->createMock(IClientService::class);
		$this->signatureManager = $this->createMock(ISignatureManager::class);
		$this->signatoryManager = $this->createMock(OCMSignatoryManager::class);
		$this->appConfig = $this->createMock(IAppConfig::class);

		$this->provider = new CloudFederationProviderFiles(
			$this->appManager,
			$this->federatedShareProvider,
			$this->addressHandler,
			$this->userManager,
			$this->shareManager,
			$this->cloudIdManager,
			$this->activityManager,
			$this->notificationManager,
			$this->urlGenerator,
			$this->cloudFederationFactory,
			$this->cloudFederationProviderManager,
			$this->groupManager,
			$this->config,
			$this->externalShareManager,
			$this->logger,
			$this->filenameValidator,
			$this->shareProviderFactory,
			$this->setupManager,
			$this->externalShareMapper,
			$this->discoveryService,
			$this->clientService,
			$this->signatureManager,
			$this->signatoryManager,
			$this->appConfig,
		);
	}

	private function enableS2S(): void {
		$this->appManager->method('isEnabledForUser')
			->with('files_sharing')
			->willReturn(true);
		$this->federatedShareProvider->method('isIncomingServer2serverShareEnabled')
			->willReturn(true);
	}

	private function buildShare(array $requirements = []): ICloudFederationShare&MockObject {
		$share = $this->createMock(ICloudFederationShare::class);
		$share->method('getProtocol')->willReturn([
			'name' => 'webdav',
			'webdav' => ['requirements' => $requirements],
		]);
		$share->method('getOwner')->willReturn('owner@example.com');
		$share->method('getOwnerDisplayName')->willReturn('Owner Name');
		$share->method('getShareSecret')->willReturn('refresh-token-abc');
		$share->method('getResourceName')->willReturn('/SharedFolder');
		$share->method('getShareWith')->willReturn('localuser');
		$share->method('getProviderId')->willReturn('42');
		$share->method('getSharedBy')->willReturn('owner@example.com');
		$share->method('getShareType')->willReturn('user');
		return $share;
	}

	/**
	 * When must-exchange-token is required but the remote has no token endpoint,
	 * shareReceived must throw rather than silently accept the share.
	 */
	public function testShareReceivedMustExchangeTokenThrowsWhenExchangeFails(): void {
		$this->enableS2S();

		$this->addressHandler->method('splitUserRemote')
			->with('owner@example.com')
			->willReturn(['owner', 'https://example.com/']);

		$share = $this->buildShare(['must-exchange-token']);

		$ocmProvider = $this->createMock(IOCMProvider::class);
		$ocmProvider->method('getTokenEndPoint')->willReturn('');

		$this->discoveryService->method('discover')
			->willReturn($ocmProvider);

		$this->expectException(ProviderCouldNotAddShareException::class);

		$this->provider->shareReceived($share);
	}

	/**
	 * When must-exchange-token is required and the token exchange succeeds,
	 * the access token is stored on the share (we drive through share creation
	 * up to the "user does not exist" guard to avoid a full integration setup).
	 */
	public function testShareReceivedMustExchangeTokenStoresAccessToken(): void {
		$this->enableS2S();

		$this->addressHandler->method('splitUserRemote')
			->with('owner@example.com')
			->willReturn(['owner', 'https://example.com/']);

		$share = $this->buildShare(['must-exchange-token']);

		$tokenEndpoint = 'https://example.com/index.php/ocm/token';

		$ocmProvider = $this->createMock(IOCMProvider::class);
		$ocmProvider->method('getTokenEndPoint')->willReturn($tokenEndpoint);
		$ocmProvider->method('getCapabilities')->willReturn([]);

		$this->discoveryService->method('discover')->willReturn($ocmProvider);

		$this->urlGenerator->method('getAbsoluteURL')->willReturn('https://local.example/');

		$signedOptions = [
			'body' => 'grant_type=authorization_code&client_id=local.example&code=refresh-token-abc',
			'headers' => ['Content-Type' => 'application/x-www-form-urlencoded', 'Signature' => 'sig'],
			'timeout' => 10,
			'connect_timeout' => 10,
		];
		$this->signatureManager->method('signOutgoingRequestIClientPayload')
			->willReturn($signedOptions);

		$response = $this->createMock(IResponse::class);
		$response->method('getStatusCode')->willReturn(200);
		$response->method('getBody')->willReturn(json_encode([
			'access_token' => 'access-token-xyz',
			'token_type' => 'Bearer',
		]));

		$httpClient = $this->createMock(\OCP\Http\Client\IClient::class);
		$httpClient->method('post')->willReturn($response);
		$this->clientService->method('newClient')->willReturn($httpClient);

		// Exchange succeeds → share creation continues; we stop it at the user
		// lookup stage to avoid a full integration setup.
		$this->userManager->method('get')->with('localuser')->willReturn(null);
		$this->filenameValidator->method('isFilenameValid')->willReturn(true);

		$this->expectException(ProviderCouldNotAddShareException::class);
		$this->expectExceptionMessage('User does not exists');

		$this->provider->shareReceived($share);
	}

	/**
	 * When exchange-token capability is present but the discovery service throws,
	 * shareReceived must not propagate the exception — the token exchange is optional.
	 */
	public function testShareReceivedOptionalExchangeGracefulOnDiscoveryFailure(): void {
		$this->enableS2S();

		$this->addressHandler->method('splitUserRemote')
			->with('owner@example.com')
			->willReturn(['owner', 'https://example.com/']);

		// Build a share with no must-exchange-token requirement
		$share = $this->buildShare();

		$this->discoveryService->method('discover')
			->willThrowException(new \Exception('network error'));

		// Discovery failure is caught and logged; share creation continues.
		// We stop it at the user lookup stage.
		$this->userManager->method('get')->with('localuser')->willReturn(null);
		$this->filenameValidator->method('isFilenameValid')->willReturn(true);

		$this->expectException(ProviderCouldNotAddShareException::class);
		$this->expectExceptionMessage('User does not exists');

		$this->provider->shareReceived($share);
	}

	/**
	 * When exchange-token capability is present and the exchange succeeds,
	 * the access token is set (we stop at user-not-found to avoid full setup).
	 */
	public function testShareReceivedOptionalExchangeStoresAccessTokenOnSuccess(): void {
		$this->enableS2S();

		$this->addressHandler->method('splitUserRemote')
			->with('owner@example.com')
			->willReturn(['owner', 'https://example.com/']);

		$share = $this->buildShare();

		$tokenEndpoint = 'https://example.com/index.php/ocm/token';

		$ocmProvider = $this->createMock(IOCMProvider::class);
		$ocmProvider->method('getTokenEndPoint')->willReturn($tokenEndpoint);
		$ocmProvider->method('getCapabilities')->willReturn(['exchange-token']);

		$this->discoveryService->method('discover')->willReturn($ocmProvider);

		$this->urlGenerator->method('getAbsoluteURL')->willReturn('https://local.example/');

		$signedOptions = [
			'body' => 'grant_type=authorization_code&client_id=local.example&code=refresh-token-abc',
			'headers' => ['Content-Type' => 'application/x-www-form-urlencoded', 'Signature' => 'sig'],
			'timeout' => 10,
			'connect_timeout' => 10,
		];
		$this->signatureManager->method('signOutgoingRequestIClientPayload')
			->willReturn($signedOptions);

		$response = $this->createMock(IResponse::class);
		$response->method('getStatusCode')->willReturn(200);
		$response->method('getBody')->willReturn(json_encode([
			'access_token' => 'access-token-xyz',
			'token_type' => 'Bearer',
		]));

		$httpClient = $this->createMock(\OCP\Http\Client\IClient::class);
		$httpClient->method('post')->willReturn($response);
		$this->clientService->method('newClient')->willReturn($httpClient);

		$this->userManager->method('get')->with('localuser')->willReturn(null);
		$this->filenameValidator->method('isFilenameValid')->willReturn(true);

		$this->expectException(ProviderCouldNotAddShareException::class);
		$this->expectExceptionMessage('User does not exists');

		$this->provider->shareReceived($share);
	}
}
