<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationApi\Tests;

use NCU\Security\Signature\ISignatureManager;
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
use OCP\Federation\ICloudFederationFactory;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudIdManager;
use OCP\IAppConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
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
	private ISignatureManager&MockObject $signatureManager;
	private OCMSignatoryManager&MockObject $signatoryManager;
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
		$this->signatureManager = $this->createMock(ISignatureManager::class);
		$this->signatoryManager = $this->createMock(OCMSignatoryManager::class);
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
			$this->signatureManager,
			$this->signatoryManager,
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
}
