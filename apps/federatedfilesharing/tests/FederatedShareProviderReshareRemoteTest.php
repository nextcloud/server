<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\FederatedFileSharing\Tests;

use LogicException;
use OC\Federation\CloudId;
use OC\Share20\Share;
use OCA\FederatedFileSharing\AddressHandler;
use OCA\FederatedFileSharing\FederatedShareProvider;
use OCA\FederatedFileSharing\Notifications;
use OCA\FederatedFileSharing\TokenHandler;
use OCP\Constants;
use OCP\DB\IResult;
use OCP\DB\QueryBuilder\IExpressionBuilder;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\Federation\ICloudFederationProviderManager;
use OCP\Federation\ICloudIdManager;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\GlobalScale\IConfig as GlobalScaleConfig;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUserManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class FederatedShareProviderReshareRemoteTest extends \Test\TestCase {
	private IDBConnection&MockObject $connection;
	private AddressHandler&MockObject $addressHandler;
	private Notifications&MockObject $notifications;
	private TokenHandler&MockObject $tokenHandler;
	private IL10N&MockObject $l10n;
	private IRootFolder&MockObject $rootFolder;
	private IConfig&MockObject $config;

	private IUserManager&MockObject $userManager;
	private ICloudIdManager&MockObject $cloudIdManager;
	private GlobalScaleConfig&MockObject $gsConfig;
	private ICloudFederationProviderManager&MockObject $cloudFederationProviderManager;
	private LoggerInterface $logger;
	private FederatedShareProvider $shareProvider;


	protected function setUp(): void {
		$this->connection = $this->createMock(IDBConnection::class);
		$this->addressHandler = $this->createMock(AddressHandler::class);
		$this->notifications = $this->createMock(Notifications::class);
		$this->tokenHandler = $this->createMock(TokenHandler::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->config = $this->createMock(IConfig::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->cloudIdManager = $this->createMock(ICloudIdManager::class);
		$this->gsConfig = $this->createMock(GlobalScaleConfig::class);
		$this->cloudFederationProviderManager = $this->createMock(ICloudFederationProviderManager::class);
		$this->logger = new NullLogger();

		$this->shareProvider = new FederatedShareProvider(
			$this->connection,
			$this->addressHandler,
			$this->notifications,
			$this->tokenHandler,
			$this->l10n,
			$this->rootFolder,
			$this->config,
			$this->userManager,
			$this->cloudIdManager,
			$this->gsConfig,
			$this->cloudFederationProviderManager,
			$this->logger,
		);
	}

	/**
	 * This test case validates that requestReShare is called when creating a federated share.
	 *
	 * We have three actors:
	 *
	 * jane@https://origin.test
	 * alice@https://local.test
	 * bob@https://destination.test
	 *
	 * Jane shared the folder with Alice which re-shares the folder with Bob.
	 *
	 * The expected outcome is, that Alice sends a request to Jane to share the folder with Bob.
	 */
	public function testCreateRemoteOwner(): void {
		$permissions = Constants::PERMISSION_READ | Constants::PERMISSION_CREATE | Constants::PERMISSION_UPDATE | Constants::PERMISSION_DELETE;

		$node = $this->createMock(Folder::class);
		$node->method('getId')->willReturn(1000);
		$node->method('getName')->willReturn('Share 1');

		/*
		 * Mocks getSharedWith ($alreadyShared and $alreadySharedGroup).
		 * The share we are going to create does not already exist.
		 */
		$expr1 = $this->createMock(IExpressionBuilder::class);
		$expr1->method('in')->willReturn('');
		$expr1->method('eq')->willReturn('');

		$result1 = $this->createMock(IResult::class);
		$result1->method('fetchAssociative')->willReturn(false);

		$qb1 = $this->createMock(IQueryBuilder::class);
		$qb1->method('select')->willReturnSelf();
		$qb1->method('from')->willReturnSelf();
		$qb1->method('where')->willReturnSelf();
		$qb1->method('expr')->willReturn($expr1);
		$qb1->method('createNamedParameter')->willReturn('');
		$qb1->method('executeQuery')->willReturn($result1);

		/*
		 * Mocks for getShareFromExternalShareTable.
		 * The share we are going to create is an external share.
		 */
		$expr2 = $this->createMock(IExpressionBuilder::class);
		$expr2->method('eq')->willReturn('');

		$result2 = $this->createMock(IResult::class);
		$result2->method('fetchAllAssociative')->willReturn([
			[
				'id' => 100000,
				'parent' => -1,
				'share_type' => 0,
				'remote' => 'https://origin.test/',
				'remote_id' => '10',
				'share_token' => 'share_token1',
				'password' => '',
				'name' => '/Share1',
				'owner' => 'jane', // owner in share_external is the user on the remote instance
				'user' => 'alice', // user in share_external is the receiver on the current instance
				'mountpoint' => '/Share1',
				'mountpoint_hash' => '94ee935396a30e27953838d0f65d1e17', // md5(mountpoint)
				'accepted' => 1,
			],
		]);

		$qb2 = $this->createMock(IQueryBuilder::class);
		$qb2->method('select')->willReturnSelf();
		$qb2->method('from')->willReturnSelf();
		$qb2->method('where')->willReturnSelf();
		$qb2->method('expr')->willReturn($expr2);
		$qb2->method('createNamedParameter')->willReturn('');
		$qb2->method('executeQuery')->willReturn($result2);

		/*
		 * Mocks for addShareToDB.
		 * The record on the local instance for the outgoing share.
		 */
		$expr3 = $this->createMock(IExpressionBuilder::class);
		$expr3->method('eq')->willReturn('');

		$result3 = $this->createMock(IResult::class);
		$result3->method('fetchAllAssociative')->willReturn([
			[
				'id' => 100000,
				'parent' => -1,
				'share_type' => 0,
				'remote' => 'https://origin.test/',
				'remote_id' => '10',
				'share_token' => 'share_token2',
				'password' => '',
				'name' => '/Share1',
				'owner' => 'jane', // owner in share_external is the user on the remote instance
				'user' => 'alice', // user in share_external is the receiver on the current instance
				'mountpoint' => '/Share1',
				'mountpoint_hash' => '94ee935396a30e27953838d0f65d1e17', // md5(mountpoint)
				'accepted' => 1,
			],
		]);

		$qb3 = $this->createMock(IQueryBuilder::class);
		$qb3->method('insert')->willReturnSelf();
		$qb3->method('setValue')->willReturnSelf();
		$qb3->method('getLastInsertId')->willReturn(2000);

		/*
		 * Mocks for updateSuccessfulReShare
		 */
		$expr4 = $this->createMock(IExpressionBuilder::class);
		$expr4->method('eq')->willReturn('');

		$qb4 = $this->createMock(IQueryBuilder::class);
		$qb4->method('update')->willReturnSelf();
		$qb4->method('where')->willReturnSelf();
		$qb4->method('expr')->willReturn($expr4);
		$qb4->method('set')->willReturnSelf();
		$qb4->method('createNamedParameter')->willReturn('');

		/*
		 * Mocks for storeRemoteId.
		 */
		$qb5 = $this->createMock(IQueryBuilder::class);
		$qb5->method('insert')->willReturnSelf();
		$qb5->method('values')->willReturnSelf();

		/*
		 * Mocks for getRawShare.
		 */
		$expr6 = $this->createMock(IExpressionBuilder::class);
		$expr6->method('eq')->willReturn('');

		$result6 = $this->createMock(IResult::class);
		$result6->method('fetchAssociative')->willReturn([
			'id' => 20000,
			'share_type' => IShare::TYPE_REMOTE,
			'share_with' => 'bob@https://destination.test',
			'password' => null,
			'uid_owner' => 'jane@origin.test',
			'uid_initiator' => 'alice',
			'parent' => null,
			'item_type' => 'folder',
			'item_source' => (string)$node->getId(),
			'item_target' => null,
			'file_source' => $node->getId(),
			'file_target' => '',
			'permissions' => $permissions,
			'stime' => 0,
			'accepted' => 0,
			'expiration' => null,
			'token' => 'share_token3',
			'mail_send' => 0,
			'share_name' => null,
			'password_by_talk' => 0,
			'note' => null,
			'hide_download' => 0,
			'label' => null,
			'attributes' => null,
			'password_expiration_time' => null,
			'reminder_sent' => 0,
		]);

		$qb6 = $this->createMock(IQueryBuilder::class);
		$qb6->method('select')->willReturnSelf();
		$qb6->method('from')->willReturnSelf();
		$qb6->method('where')->willReturnSelf();
		$qb6->method('expr')->willReturn($expr6);
		$qb6->method('createNamedParameter')->willReturn('');
		$qb6->method('executeQuery')->willReturn($result6);


		$queryBuilderMatcher = $this->exactly(8);
		$this->connection
			->expects($queryBuilderMatcher)
			->method('getQueryBuilder')
			->willReturnCallback(function () use ($queryBuilderMatcher, $qb1, $qb2, $qb3, $qb4, $qb5, $qb6) {
				return match ($queryBuilderMatcher->numberOfInvocations()) {
					1, 2 => $qb1,
					3, 5 => $qb2,
					4 => $qb3,
					6 => $qb4,
					7 => $qb5,
					8 => $qb6,
					default => throw new LogicException('Unexpected number of invocations for getQueryBuilder')
				};
			});

		// the cloud id for the recipient
		$this->cloudIdManager->method('resolveCloudId')
			->with('bob@https://destination.test')
			->willReturn(new CloudId(
				'bob@https://destination.test',
				'bob',
				'https://destination.test',
				'Bob', // is usually null in prod, setting it here to avoid additional mocking
			));

		$this->addressHandler->method('generateRemoteURL')
			->willReturn('https://local.test');
		$this->addressHandler->method('compareAddresses')
			->willReturn(false);

		// the cloud id of the actual owner
		$this->cloudIdManager->method('getCloudId')
			->willReturn(new CloudId(
				'jane@https://origin.test',
				'jane',
				'https://origin.test',
				'Jane', // is usually null in prod, setting it here to avoid additional mocking
			));

		$this->notifications->expects($this->once())
			->method('requestReShare')
			->with(
				$this->equalTo('share_token1'),
				$this->equalTo('10'),
				$this->equalTo('2000'),
				$this->equalTo('https://origin.test/'),
				$this->equalTo('bob@https://destination.test'),
				$this->equalTo($permissions),
				$this->equalTo('Share 1'),
				$this->equalTo(IShare::TYPE_REMOTE),
			)
			->willReturn(['share_token2', '20']);

		$share = new Share($this->rootFolder, $this->userManager);
		$share
			->setSharedWith('bob@https://destination.test')
			->setShareOwner('alice')
			->setSharedBy('alice')
			->setPermissions($permissions)
			->setShareType(IShare::TYPE_REMOTE)
			->setNode($node)
			->setTarget('/Share1');

		$this->shareProvider->create($share);
	}

	/**
	 * This test case validates that sendPermission is called when updating a federated share.
	 *
	 * We have three actors:
	 *
	 * jane@https://origin.test
	 * alice@https://local.test
	 * bob@https://destination.test
	 *
	 * Jane shared the folder with Alice which re-shared the folder with Bob.
	 * Alice is now changing the permissions for the share.
	 *
	 * The expected outcome is, that Alice sends a request to Jane to change the share.
	 */
	public function testUpdateRemoteOwner(): void {
		$permissions = Constants::PERMISSION_READ;

		$node = $this->createMock(Folder::class);
		$node->method('getId')->willReturn(1000);
		$node->method('getName')->willReturn('Share 1');

		/*
		 * Mocks update share.
		 */
		$expr1 = $this->createMock(IExpressionBuilder::class);
		$expr1->method('eq')->willReturn('');

		$qb1 = $this->createMock(IQueryBuilder::class);
		$qb1->method('update')->willReturnSelf();
		$qb1->method('where')->willReturnSelf();
		$qb1->method('expr')->willReturn($expr1);
		$qb1->method('createNamedParameter')->willReturn('');
		$qb1->method('set')->willReturnSelf();

		/*
		 * Mocks getRemoteId.
		 */
		$expr2 = $this->createMock(IExpressionBuilder::class);
		$expr2->method('eq')->willReturn('');

		$result2 = $this->createMock(IResult::class);
		$result2->method('fetchAssociative')->willReturn([
			'share_id' => 3000,
			'remote_id' => '10',
		]);

		$qb2 = $this->createMock(IQueryBuilder::class);
		$qb2->method('select')->willReturnSelf();
		$qb2->method('from')->willReturnSelf();
		$qb2->method('where')->willReturnSelf();
		$qb2->method('expr')->willReturn($expr2);
		$qb2->method('createNamedParameter')->willReturn('');
		$qb2->method('executeQuery')->willReturn($result2);

		$queryBuilderMatcher = $this->exactly(2);
		$this->connection
			->expects($queryBuilderMatcher)
			->method('getQueryBuilder')
			->willReturnCallback(function () use ($queryBuilderMatcher, $qb1, $qb2) {
				return match ($queryBuilderMatcher->numberOfInvocations()) {
					1 => $qb1,
					2 => $qb2,
					default => throw new LogicException('Unexpected number of invocations for getQueryBuilder')
				};
			});

		$this->userManager->method('userExists')
			->willReturnMap([
				['jane@https://origin.test', false],
				['alice', true],
			]);

		$this->addressHandler->method('splitUserRemote')
			->willReturn(['jane', 'https://origin.test']);

		$this->notifications->expects($this->once())
			->method('sendPermissionChange')
			->with(
				$this->equalTo('https://origin.test'),
				$this->equalTo('10'),
				$this->equalTo('share_token3'),
				$this->equalTo($permissions),
			);

		$share = new Share($this->rootFolder, $this->userManager);
		$share
			->setId('3000')
			->setToken('share_token3')
			->setSharedWith('bob@https://destination.test')
			->setShareOwner('jane@https://origin.test')
			->setSharedBy('alice')
			->setPermissions($permissions)
			->setShareType(IShare::TYPE_REMOTE)
			->setNode($node)
			->setTarget('/Share1');

		$this->shareProvider->update($share);
	}
}
