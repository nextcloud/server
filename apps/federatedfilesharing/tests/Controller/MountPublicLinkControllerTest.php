<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\FederatedFileSharing\Tests\Controller;

use OC\Federation\CloudIdManager;
use OC\Share20\Share;
use OCA\FederatedFileSharing\AddressHandler;
use OCA\FederatedFileSharing\Controller\MountPublicLinkController;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCP\AppFramework\Http;
use OCP\Contacts\IManager as IContactsManager;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Federation\ICloudIdManager;
use OCP\Files\IRootFolder;
use OCP\HintException;
use OCP\Http\Client\IClientService;
use OCP\ICacheFactory;
use OCP\IL10N;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\Share\IManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class MountPublicLinkControllerTest extends \Test\TestCase {
	protected IContactsManager&MockObject $contactsManager;
	private IRequest&MockObject $request;
	private FederatedShareProvider&MockObject $federatedShareProvider;
	private IManager&MockObject $shareManager;
	private AddressHandler&MockObject $addressHandler;
	private IRootFolder&MockObject $rootFolder;
	private IUserManager&MockObject $userManager;
	private ISession&MockObject $session;
	private IL10N&MockObject $l10n;
	private IUserSession&MockObject $userSession;
	private IClientService&MockObject $clientService;
	private IShare $share;
	private ICloudIdManager $cloudIdManager;
	private MountPublicLinkController $controller;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->federatedShareProvider = $this->createMock(FederatedShareProvider::class);
		$this->shareManager = $this->createMock(IManager::class);
		$this->addressHandler = $this->createMock(AddressHandler::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->share = new Share($this->rootFolder, $this->userManager);
		$this->session = $this->createMock(ISession::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->clientService = $this->createMock(IClientService::class);
		$this->contactsManager = $this->createMock(IContactsManager::class);
		$this->cloudIdManager = new CloudIdManager(
			$this->contactsManager,
			$this->createMock(IURLGenerator::class),
			$this->userManager,
			$this->createMock(ICacheFactory::class),
			$this->createMock(IEventDispatcher::class)
		);

		$this->controller = new MountPublicLinkController(
			'federatedfilesharing', $this->request,
			$this->federatedShareProvider,
			$this->shareManager,
			$this->addressHandler,
			$this->session,
			$this->l10n,
			$this->userSession,
			$this->clientService,
			$this->cloudIdManager,
			$this->createMock(LoggerInterface::class),
		);
	}

	/**
	 * @dataProvider dataTestCreateFederatedShare
	 */
	public function testCreateFederatedShare(
		string $shareWith,
		bool $outgoingSharesAllowed,
		bool $validShareWith,
		string $token,
		bool $validToken,
		bool $createSuccessful,
		string $expectedReturnData,
		int $permissions,
	): void {
		$this->federatedShareProvider->expects($this->any())
			->method('isOutgoingServer2serverShareEnabled')
			->willReturn($outgoingSharesAllowed);

		$this->addressHandler->expects($this->any())->method('splitUserRemote')
			->with($shareWith)
			->willReturnCallback(
				function ($shareWith) use ($validShareWith, $expectedReturnData) {
					if ($validShareWith) {
						return ['user', 'server'];
					}
					throw new HintException($expectedReturnData, $expectedReturnData);
				}
			);

		$share = $this->share;
		$share->setPermissions($permissions);

		$this->shareManager->expects($this->any())->method('getShareByToken')
			->with($token)
			->willReturnCallback(
				function ($token) use ($validToken, $share, $expectedReturnData) {
					if ($validToken) {
						return $share;
					}
					throw new HintException($expectedReturnData, $expectedReturnData);
				}
			);

		$this->federatedShareProvider->expects($this->any())->method('create')
			->with($share)
			->willReturnCallback(
				function (IShare $share) use ($createSuccessful, $shareWith, $expectedReturnData) {
					$this->assertEquals($shareWith, $share->getSharedWith());
					if ($createSuccessful) {
						return $share;
					}
					throw new HintException($expectedReturnData, $expectedReturnData);
				}
			);

		$result = $this->controller->createFederatedShare($shareWith, $token);

		$errorCase = !$validShareWith || !$validToken || !$createSuccessful || !$outgoingSharesAllowed;

		if ($errorCase) {
			$this->assertSame(Http::STATUS_BAD_REQUEST, $result->getStatus());
			$this->assertTrue(isset($result->getData()['message']));
			$this->assertSame($expectedReturnData, $result->getData()['message']);
		} else {
			$this->assertSame(Http::STATUS_OK, $result->getStatus());
			$this->assertTrue(isset($result->getData()['remoteUrl']));
			$this->assertSame($expectedReturnData, $result->getData()['remoteUrl']);
		}
	}

	public static function dataTestCreateFederatedShare(): array {
		return [
			//shareWith, outgoingSharesAllowed, validShareWith, token, validToken, createSuccessful, expectedReturnData
			['user@server', true, true, 'token', true, true, 'server', 31],
			['user@server', true, true, 'token', false, false, 'server', 4],
			['user@server', true, false, 'token', true, true, 'invalid federated cloud id', 31],
			['user@server', true, false, 'token', false, true, 'invalid federated cloud id', 31],
			['user@server', true, false, 'token', false, false, 'invalid federated cloud id', 31],
			['user@server', true, false, 'token', true, false, 'invalid federated cloud id', 31],
			['user@server', true, true, 'token', false, true, 'invalid token', 31],
			['user@server', true, true, 'token', false, false, 'invalid token', 31],
			['user@server', true, true, 'token', true, false, 'can not create share', 31],
			['user@server', false, true, 'token', true, true, 'This server doesn\'t support outgoing federated shares', 31],
		];
	}
}
