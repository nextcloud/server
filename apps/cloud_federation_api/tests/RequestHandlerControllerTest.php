<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationApi\Tests;

use OC\OCM\OCMSignatoryManager;
use OCA\CloudFederationAPI\Config;
use OCA\CloudFederationAPI\Controller\RequestHandlerController;
use OCA\CloudFederationAPI\Db\FederatedInvite;
use OCA\CloudFederationAPI\Db\FederatedInviteMapper;
use OCA\FederatedFileSharing\AddressHandler;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\Exceptions\BadRequestException;
use OCP\Federation\Exceptions\ProviderCouldNotAddShareException;
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProvider;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudFederationShare;
use OCP\Federation\ICloudId;
use OCP\Federation\ICloudIdManager;
use OCP\Federation\IValidationAwareCloudFederationProvider;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\OCM\IOCMDiscoveryService;
use OCP\Security\Signature\ISignatureManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Test\TestCase;

class RequestHandlerControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private LoggerInterface&MockObject $logger;
	private IUserManager&MockObject $userManager;
	private IGroupManager&MockObject $groupManager;
	private IURLGenerator&MockObject $urlGenerator;
	private ICloudFederationProviderManager&MockObject $cloudFederationProviderManager;
	private Config&MockObject $config;
	private IEventDispatcher&MockObject $eventDispatcher;
	private FederatedInviteMapper&MockObject $federatedInviteMapper;
	private AddressHandler&MockObject $addressHandler;
	private IAppConfig&MockObject $appConfig;

	private ICloudFederationFactory&MockObject $cloudFederationFactory;
	private ICloudIdManager&MockObject $cloudIdManager;
	private IOCMDiscoveryService&MockObject $discoveryService;
	private ISignatureManager&MockObject $signatureManager;
	private ITimeFactory&MockObject $timeFactory;

	private RequestHandlerController $requestHandlerController;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->cloudFederationProviderManager = $this->createMock(ICloudFederationProviderManager::class);
		$this->config = $this->createMock(Config::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);
		$this->federatedInviteMapper = $this->createMock(FederatedInviteMapper::class);
		$this->addressHandler = $this->createMock(AddressHandler::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->cloudFederationFactory = $this->createMock(ICloudFederationFactory::class);
		$this->cloudIdManager = $this->createMock(ICloudIdManager::class);
		$this->discoveryService = $this->createMock(IOCMDiscoveryService::class);
		$this->signatureManager = $this->createMock(ISignatureManager::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);

		$this->requestHandlerController = new RequestHandlerController(
			'cloud_federation_api',
			$this->request,
			$this->logger,
			$this->userManager,
			$this->groupManager,
			$this->urlGenerator,
			$this->cloudFederationProviderManager,
			$this->config,
			$this->eventDispatcher,
			$this->federatedInviteMapper,
			$this->addressHandler,
			$this->appConfig,
			$this->cloudFederationFactory,
			$this->cloudIdManager,
			$this->discoveryService,
			$this->signatureManager,
			$this->timeFactory,
		);
	}

	public function testInviteAccepted(): void {
		$token = 'token';
		$userId = 'userId';
		$invite = new FederatedInvite();
		$invite->setCreatedAt(1);
		$invite->setUserId($userId);
		$invite->setToken($token);

		$this->federatedInviteMapper->expects(self::once())
			->method('findByToken')
			->with($token)
			->willReturn($invite);

		$this->federatedInviteMapper->expects(self::once())
			->method('update')
			->willReturnArgument(0);

		$user = $this->createMock(IUser::class);
		$user->method('getUID')
			->willReturn($userId);
		$user->method('getEMailAddress')
			->willReturn('email');
		$user->method('getDisplayName')
			->willReturn('displayName');

		$this->userManager->expects(self::once())
			->method('get')
			->with($userId)
			->willReturn($user);

		$recipientProvider = 'http://127.0.0.1';
		$recipientId = 'remote';
		$recipientEmail = 'remote@example.org';
		$recipientName = 'Remote Remoteson';
		$response = ['userID' => $userId, 'email' => 'email', 'name' => 'displayName'];
		$json = new JSONResponse($response, Http::STATUS_OK);

		$this->assertEquals($json, $this->requestHandlerController->inviteAccepted($recipientProvider, $token, $recipientId, $recipientEmail, $recipientName));
	}

	public static function addShareProtocolDataProvider(): array {
		return [
			'legacy single-protocol' => [
				['name' => 'webdav', 'options' => ['sharedSecret' => 'secret-legacy']],
			],
			'multi envelope, one inner protocol' => [
				['name' => 'multi', 'webdav' => ['sharedSecret' => 'secret-multi-single', 'uri' => 'https://sender/webdav/']],
			],
			'multi envelope, multiple inner protocols' => [
				[
					'name' => 'multi',
					'webdav' => ['sharedSecret' => 'secret-webdav', 'uri' => 'https://sender/webdav/'],
					'webapp' => ['sharedSecret' => 'secret-webapp', 'uri' => 'https://sender/launch'],
				],
			],
			'multi envelope, sharedSecret only on non-webdav entry' => [
				[
					'name' => 'multi',
					'webapp' => ['sharedSecret' => 'secret-webapp-only', 'uri' => 'https://sender/launch'],
				],
			],
			'multi envelope, no sharedSecret (provider-validated)' => [
				['name' => 'multi', 'webdav' => ['uri' => 'https://sender/webdav/']],
			],
		];
	}

	/**
	 * @dataProvider addShareProtocolDataProvider
	 */
	public function testAddShareForwardsProtocolToProvider(array $protocol): void {
		$this->appConfig->method('getValueBool')
			->with('core', OCMSignatoryManager::APPCONFIG_SIGN_DISABLED, true)
			->willReturn(true);

		$cloudId = $this->createMock(ICloudId::class);
		$cloudId->method('getUser')->willReturn('bob');
		$this->cloudIdManager->method('resolveCloudId')->willReturn($cloudId);

		$user = $this->createMock(IUser::class);
		$user->method('getDisplayName')->willReturn('Bob');
		$user->method('getUID')->willReturn('bob');
		$this->userManager->method('userExists')->with('bob')->willReturn(true);
		$this->userManager->method('get')->with('bob')->willReturn($user);

		$this->config->method('getSupportedShareTypes')->with('file')->willReturn(['user']);

		$capturedShare = null;
		$share = $this->createMock(ICloudFederationShare::class);
		$share->expects(self::once())
			->method('setProtocol')
			->willReturnCallback(function (array $p) use (&$capturedShare): void {
				$capturedShare = $p;
			});
		$this->cloudFederationFactory->method('getCloudFederationShare')->willReturn($share);

		$provider = $this->createMock(ICloudFederationProvider::class);
		$provider->expects(self::once())->method('shareReceived')->with($share);
		$this->cloudFederationProviderManager->method('getCloudFederationProvider')->with('file')->willReturn($provider);

		$response = $this->requestHandlerController->addShare(
			shareWith: 'bob@receiver.example.org',
			name: 'doc.odt',
			description: null,
			providerId: 'abc',
			owner: 'alice@sender.example.org',
			ownerDisplayName: 'Alice',
			sharedBy: 'alice@sender.example.org',
			sharedByDisplayName: 'Alice',
			protocol: $protocol,
			shareType: 'user',
			resourceType: 'file',
		);

		self::assertSame(Http::STATUS_CREATED, $response->getStatus());
		self::assertSame($protocol, $capturedShare);
	}

	/**
	 * Returns the standard mock wiring (signing disabled, cloud-id, user, supported share types).
	 * Tests that exercise the controller's dispatch path call this and then plug in the share/provider.
	 *
	 * @return ICloudFederationShare&MockObject
	 */
	private function setUpHappyPathMocks(string $resourceType = 'file'): ICloudFederationShare {
		$this->appConfig->method('getValueBool')
			->with('core', OCMSignatoryManager::APPCONFIG_SIGN_DISABLED, true)
			->willReturn(true);

		$cloudId = $this->createMock(ICloudId::class);
		$cloudId->method('getUser')->willReturn('bob');
		$this->cloudIdManager->method('resolveCloudId')->willReturn($cloudId);

		$user = $this->createMock(IUser::class);
		$user->method('getDisplayName')->willReturn('Bob');
		$user->method('getUID')->willReturn('bob');
		$this->userManager->method('userExists')->with('bob')->willReturn(true);
		$this->userManager->method('get')->with('bob')->willReturn($user);

		$this->config->method('getSupportedShareTypes')->with($resourceType)->willReturn(['user']);

		$share = $this->createMock(ICloudFederationShare::class);
		$this->cloudFederationFactory->method('getCloudFederationShare')->willReturn($share);
		return $share;
	}

	public function testAddShareCallsValidateShareOnValidationAwareProvider(): void {
		$share = $this->setUpHappyPathMocks();

		$provider = $this->createMock(IValidationAwareCloudFederationProvider::class);
		$order = [];
		$provider->expects(self::once())
			->method('validateShare')
			->with($share)
			->willReturnCallback(function () use (&$order): void {
				$order[] = 'validate';
			});
		$provider->expects(self::once())
			->method('shareReceived')
			->with($share)
			->willReturnCallback(function () use (&$order): string {
				$order[] = 'receive';
				return '';
			});
		$this->cloudFederationProviderManager->method('getCloudFederationProvider')->with('file')->willReturn($provider);

		$response = $this->requestHandlerController->addShare(
			shareWith: 'bob@receiver.example.org',
			name: 'doc.odt',
			description: null,
			providerId: 'abc',
			owner: 'alice@sender.example.org',
			ownerDisplayName: 'Alice',
			sharedBy: 'alice@sender.example.org',
			sharedByDisplayName: 'Alice',
			protocol: ['name' => 'webdav', 'options' => ['sharedSecret' => 's']],
			shareType: 'user',
			resourceType: 'file',
		);

		self::assertSame(Http::STATUS_CREATED, $response->getStatus());
		self::assertSame(['validate', 'receive'], $order);
	}

	public function testAddShareMapsBadRequestExceptionToFourHundred(): void {
		$share = $this->setUpHappyPathMocks();

		$provider = $this->createMock(IValidationAwareCloudFederationProvider::class);
		$provider->expects(self::once())
			->method('validateShare')
			->willThrowException(new BadRequestException(['protocol.webdav.sharedSecret']));
		$provider->expects(self::never())->method('shareReceived');
		$this->cloudFederationProviderManager->method('getCloudFederationProvider')->with('file')->willReturn($provider);

		$response = $this->requestHandlerController->addShare(
			shareWith: 'bob@receiver.example.org',
			name: 'doc.odt',
			description: null,
			providerId: 'abc',
			owner: 'alice@sender.example.org',
			ownerDisplayName: 'Alice',
			sharedBy: 'alice@sender.example.org',
			sharedByDisplayName: 'Alice',
			protocol: ['name' => 'webdav'],
			shareType: 'user',
			resourceType: 'file',
		);

		self::assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$body = $response->getData();
		self::assertSame('RESOURCE_NOT_FOUND', $body['message']);
		self::assertSame([
			['name' => 'protocol.webdav.sharedSecret', 'message' => 'NOT_FOUND'],
		], $body['validationErrors']);
	}

	public function testAddShareSurfacesProviderExceptionCodeAsHttpStatus(): void {
		$this->setUpHappyPathMocks();

		$provider = $this->createMock(ICloudFederationProvider::class);
		$provider->expects(self::once())
			->method('shareReceived')
			->willThrowException(new ProviderCouldNotAddShareException('Server does not support federated cloud sharing', '', Http::STATUS_SERVICE_UNAVAILABLE));
		$this->cloudFederationProviderManager->method('getCloudFederationProvider')->with('file')->willReturn($provider);

		$response = $this->requestHandlerController->addShare(
			shareWith: 'bob@receiver.example.org',
			name: 'doc.odt',
			description: null,
			providerId: 'abc',
			owner: 'alice@sender.example.org',
			ownerDisplayName: 'Alice',
			sharedBy: 'alice@sender.example.org',
			sharedByDisplayName: 'Alice',
			protocol: ['name' => 'webdav', 'options' => ['sharedSecret' => 's']],
			shareType: 'user',
			resourceType: 'file',
		);

		self::assertSame(Http::STATUS_SERVICE_UNAVAILABLE, $response->getStatus());
		self::assertSame('Server does not support federated cloud sharing', $response->getData()['message']);
	}

	public function testAddShareRejectsProtocolMissingName(): void {
		$this->appConfig->method('getValueBool')
			->with('core', OCMSignatoryManager::APPCONFIG_SIGN_DISABLED, true)
			->willReturn(true);

		$this->cloudFederationProviderManager->expects(self::never())
			->method('getCloudFederationProvider');

		$response = $this->requestHandlerController->addShare(
			shareWith: 'bob@receiver.example.org',
			name: 'doc.odt',
			description: null,
			providerId: 'abc',
			owner: 'alice@sender.example.org',
			ownerDisplayName: 'Alice',
			sharedBy: 'alice@sender.example.org',
			sharedByDisplayName: 'Alice',
			protocol: ['webdav' => ['sharedSecret' => 'secret', 'uri' => 'https://sender/webdav/']],
			shareType: 'user',
			resourceType: 'file',
		);

		self::assertSame(Http::STATUS_BAD_REQUEST, $response->getStatus());
		self::assertSame('Missing arguments', $response->getData()['message']);
	}
}
