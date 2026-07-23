<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files_Sharing\Tests\Sharing;

use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use OC\Core\Sharing\Permission\ReshareSharePermissionType;
use OC\Core\Sharing\Property\ExpirationDateSharePropertyType;
use OC\Core\Sharing\Property\LabelSharePropertyType;
use OC\Core\Sharing\Property\NoteSharePropertyType;
use OC\Core\Sharing\Recipient\EmailShareRecipientType;
use OC\Core\Sharing\Recipient\GroupShareRecipientType;
use OC\Core\Sharing\Recipient\TeamShareRecipientType;
use OC\Core\Sharing\Recipient\TokenShareRecipientType;
use OC\Core\Sharing\Recipient\UserShareRecipientType;
use OC\Sharing\ISharingLegacyBackend;
use OCA\Circles\CirclesManager;
use OCA\Circles\Service\CircleService;
use OCA\Files\Sharing\Permission\NodeCreateSharePermissionType;
use OCA\Files\Sharing\Permission\NodeDeleteSharePermissionType;
use OCA\Files\Sharing\Permission\NodeDownloadSharePermissionType;
use OCA\Files\Sharing\Permission\NodeReadSharePermissionType;
use OCA\Files\Sharing\Permission\NodeUpdateSharePermissionType;
use OCA\Files\Sharing\Source\NodeShareSourceType;
use OCA\Files_Sharing\Sharing\LegacyBackend;
use OCP\Constants;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Server;
use OCP\Share\IManager;
use OCP\Share\IShare;
use OCP\Sharing\ISharingManager;
use OCP\Sharing\Permission\SharePermission;
use OCP\Sharing\Property\ShareProperty;
use OCP\Sharing\Recipient\ShareRecipient;
use OCP\Sharing\Share;
use OCP\Sharing\ShareState;
use OCP\Sharing\ShareUser;
use OCP\Sharing\Source\ShareSource;
use OCP\Snowflake\ISnowflakeGenerator;
use PHPUnit\Framework\Attributes\Group;
use Test\TestCase;

#[Group(name: 'DB')]
final class LegacyBackendTest extends TestCase {
	private IManager $legacyManager;

	private IDBConnection $dbConnection;

	private ISharingLegacyBackend $legacyBackend;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();

		$this->legacyManager = Server::get(IManager::class);
		$this->dbConnection = Server::get(IDBConnection::class);

		$this->legacyBackend = new LegacyBackend(
			Server::get(IFactory::class),
			$this->dbConnection,
			Server::get(IRootFolder::class),
			$this->legacyManager,
			Server::get(ISnowflakeGenerator::class),
			Server::get(ISharingManager::class),
		);
	}

	#[\Override]
	protected function tearDown(): void {
		foreach ([
			'share',
			'share_external',
			'share_legacy_mapping',
		] as $table) {
			$qb = $this->dbConnection->getQueryBuilder();
			$qb
				->select($qb->func()->count('*'))
				->from($table);
			$this->assertEquals(0, $qb->executeQuery()->fetchOne(), $table);
		}

		parent::tearDown();
	}

	public function testUpdateShare(): void {
		$userManager = Server::get(IUserManager::class);
		$groupManager = Server::get(IGroupManager::class);

		$owner = $userManager->createUser('owner', 'password');
		$this->assertNotFalse($owner);
		$this->assertTrue($owner->setDisplayName('Owner'));

		$user = $userManager->createUser('user', 'password');
		$this->assertNotFalse($user);
		$this->assertTrue($user->setDisplayName('User'));

		$group = $groupManager->createGroup('group');
		$this->assertNotNull($group);
		$this->assertTrue($group->setDisplayName('Group'));
		// Clear display name cache, because setting the display name on the group doesn't update it in the cache of the manager
		self::invokePrivate(self::invokePrivate($groupManager, 'displayNameCache'), 'clear');

		$circle = null;
		if (class_exists(CirclesManager::class)) {
			$circlesManager = Server::get(CirclesManager::class);
			$circlesManager->startSession($circlesManager->getLocalFederatedUser($owner->getUID()));
			/** @psalm-suppress MixedAssignment */
			$circle = $circlesManager->createCircle('circle');
			/** @psalm-suppress MixedMethodCall, UndefinedClass */
			Server::get(CircleService::class)->updateName($circle->getSingleId(), 'Circle');
			/** @psalm-suppress MixedMethodCall, MixedAssignment */
			$circle = $circlesManager->getCircle($circle->getSingleId());
		}

		$node1 = Server::get(IRootFolder::class)->getUserFolder($owner->getUID())->newFolder('foo');

		$node2 = Server::get(IRootFolder::class)->getUserFolder($owner->getUID())->newFile('foo.txt');

		// TODO: Accept arbitrary expiration dates.
		$expirationDate = (new DateTimeImmutable())->add(new DateInterval('P1D'))->setTime(23, 59, 59)->format(DateTimeInterface::ATOM);

		// TODO: Test federated owner.
		/** @psalm-suppress MixedMethodCall, MixedArgument */
		$share = new Share(
			'123',
			new ShareUser(
				$owner->getUID(),
				null,
			),
			0,
			ShareState::Active,
			[
				new ShareSource(
					NodeShareSourceType::class,
					(string)$node1->getId(),
				),
				new ShareSource(
					NodeShareSourceType::class,
					(string)$node2->getId(),
				),
			],
			array_merge(
				[
					// TODO: Test federation
					new ShareRecipient(
						UserShareRecipientType::class,
						$user->getUID(),
						null,
						'secret',
						new ShareUser(
							$owner->getUID(),
							null,
						),
					),
					// TODO: Test federation
					new ShareRecipient(
						GroupShareRecipientType::class,
						$group->getGID(),
						null,
						'secret',
						new ShareUser(
							$owner->getUID(),
							null,
						),
					),
					new ShareRecipient(
						TokenShareRecipientType::class,
						'token',
						null,
						'secret',
						new ShareUser(
							$owner->getUID(),
							null,
						),
					),
					new ShareRecipient(
						EmailShareRecipientType::class,
						'example@example.com',
						null,
						'secret',
						new ShareUser(
							$owner->getUID(),
							null,
						),
					),
				],
				$circle !== null ? [
					new ShareRecipient(
						TeamShareRecipientType::class,
						$circle->getSingleId(),
						null,
						'secret',
						new ShareUser(
							$owner->getUID(),
							null,
						),
					),
				] : [],
			),
			[
				NoteSharePropertyType::class => new ShareProperty(
					NoteSharePropertyType::class,
					'Note',
				),
				ExpirationDateSharePropertyType::class => new ShareProperty(
					ExpirationDateSharePropertyType::class,
					$expirationDate,
				),
				LabelSharePropertyType::class => new ShareProperty(
					LabelSharePropertyType::class,
					'Label',
				),
				// TODO: Test all property types
			],
			[
				NodeReadSharePermissionType::class => new SharePermission(
					NodeReadSharePermissionType::class,
					true,
				),
				NodeUpdateSharePermissionType::class => new SharePermission(
					NodeUpdateSharePermissionType::class,
					true,
				),
				NodeCreateSharePermissionType::class => new SharePermission(
					NodeCreateSharePermissionType::class,
					true,
				),
				NodeDeleteSharePermissionType::class => new SharePermission(
					NodeDeleteSharePermissionType::class,
					true,
				),
				ReshareSharePermissionType::class => new SharePermission(
					ReshareSharePermissionType::class,
					true,
				),
				NodeDownloadSharePermissionType::class => new SharePermission(
					NodeDownloadSharePermissionType::class,
					true,
				),
			],
		);

		$this->legacyBackend->updateShare($share);

		$legacyIds = $this->getLegacyIds($share->id);
		usort($legacyIds, static fn (string $a, string $b): int => explode(':', $a)[1] <=> explode(':', $b)[1]);
		$legacyShares = array_map(fn (string $legacyId): array => $this->formatLegacyShare($this->legacyManager->getShareById($legacyId)), $legacyIds);
		/** @psalm-suppress MixedMethodCall, MixedOperand */
		$this->assertEquals(array_merge(
			[
				[
					'id' => explode(':', $legacyIds[0])[1],
					'full_id' => $legacyIds[0],
					'node_id' => $node1->getId(),
					'node_type' => 'folder',
					'share_type' => IShare::TYPE_USER,
					'shared_with' => $user->getUID(),
					'shared_with_display_name' => $user->getDisplayName(),
					'shared_with_avatar' => null,
					'permissions' => Constants::PERMISSION_ALL,
					'attributes' => [
						[
							'scope' => 'permissions',
							'key' => 'download',
							'value' => true,
						]
					],
					'status' => IShare::STATUS_ACCEPTED,
					'note' => 'Note',
					'expiration_date' => $expirationDate,
					'no_expiration_date' => false,
					'label' => '',
					'shared_by' => 'owner',
					'share_owner' => 'owner',
					'password' => null,
					'password_expiration_time' => null,
					'send_password_by_talk' => false,
					'token' => null,
					'parent' => null,
					'original_target' => null,
					'target' => '/foo',
					'mail_send' => false,
					'hide_download' => false,
					'reminder_sent' => false,
				],
				[
					'id' => explode(':', $legacyIds[1])[1],
					'full_id' => $legacyIds[1],
					'node_id' => $node2->getId(),
					'node_type' => 'file',
					'share_type' => IShare::TYPE_USER,
					'shared_with' => $user->getUID(),
					'shared_with_display_name' => $user->getDisplayName(),
					'shared_with_avatar' => null,
					'permissions' => Constants::PERMISSION_ALL & ~Constants::PERMISSION_CREATE & ~Constants::PERMISSION_DELETE,
					'attributes' => [
						[
							'scope' => 'permissions',
							'key' => 'download',
							'value' => true,
						]
					],
					'status' => IShare::STATUS_ACCEPTED,
					'note' => 'Note',
					'expiration_date' => $expirationDate,
					'no_expiration_date' => false,
					'label' => '',
					'shared_by' => 'owner',
					'share_owner' => 'owner',
					'password' => null,
					'password_expiration_time' => null,
					'send_password_by_talk' => false,
					'token' => null,
					'parent' => null,
					'original_target' => null,
					'target' => '/foo.txt',
					'mail_send' => false,
					'hide_download' => false,
					'reminder_sent' => false,
				],
				[
					'id' => explode(':', $legacyIds[2])[1],
					'full_id' => $legacyIds[2],
					'node_id' => $node1->getId(),
					'node_type' => 'folder',
					'share_type' => IShare::TYPE_GROUP,
					'shared_with' => $group->getGID(),
					'shared_with_display_name' => $group->getDisplayName(),
					'shared_with_avatar' => null,
					'permissions' => Constants::PERMISSION_ALL,
					'attributes' => [
						[
							'scope' => 'permissions',
							'key' => 'download',
							'value' => true,
						]
					],
					'status' => IShare::STATUS_PENDING,
					'note' => 'Note',
					'expiration_date' => $expirationDate,
					'no_expiration_date' => false,
					'label' => '',
					'shared_by' => 'owner',
					'share_owner' => 'owner',
					'password' => null,
					'password_expiration_time' => null,
					'send_password_by_talk' => false,
					'token' => null,
					'parent' => null,
					'original_target' => null,
					'target' => '/foo',
					'mail_send' => false,
					'hide_download' => false,
					'reminder_sent' => false,
				],
				[
					'id' => explode(':', $legacyIds[3])[1],
					'full_id' => $legacyIds[3],
					'node_id' => $node2->getId(),
					'node_type' => 'file',
					'share_type' => IShare::TYPE_GROUP,
					'shared_with' => $group->getGID(),
					'shared_with_display_name' => $group->getDisplayName(),
					'shared_with_avatar' => null,
					'permissions' => Constants::PERMISSION_ALL & ~Constants::PERMISSION_CREATE & ~Constants::PERMISSION_DELETE,
					'attributes' => [
						[
							'scope' => 'permissions',
							'key' => 'download',
							'value' => true,
						]
					],
					'status' => IShare::STATUS_PENDING,
					'note' => 'Note',
					'expiration_date' => $expirationDate,
					'no_expiration_date' => false,
					'label' => '',
					'shared_by' => 'owner',
					'share_owner' => 'owner',
					'password' => null,
					'password_expiration_time' => null,
					'send_password_by_talk' => false,
					'token' => null,
					'parent' => null,
					'original_target' => null,
					'target' => '/foo.txt',
					'mail_send' => false,
					'hide_download' => false,
					'reminder_sent' => false,
				],
				[
					'id' => explode(':', $legacyIds[4])[1],
					'full_id' => $legacyIds[4],
					'node_id' => $node1->getId(),
					'node_type' => 'folder',
					'share_type' => IShare::TYPE_LINK,
					'shared_with' => null,
					'shared_with_display_name' => '',
					'shared_with_avatar' => null,
					'permissions' => Constants::PERMISSION_ALL,
					'attributes' => null,
					'status' => IShare::STATUS_PENDING,
					'note' => 'Note',
					'expiration_date' => $expirationDate,
					'no_expiration_date' => false,
					'label' => 'Label',
					'shared_by' => 'owner',
					'share_owner' => 'owner',
					'password' => null,
					'password_expiration_time' => null,
					'send_password_by_talk' => false,
					'token' => 'secret',
					'parent' => null,
					'original_target' => null,
					'target' => '/foo',
					'mail_send' => false,
					'hide_download' => false,
					'reminder_sent' => false,
				],
				[
					'id' => explode(':', $legacyIds[5])[1],
					'full_id' => $legacyIds[5],
					'node_id' => $node2->getId(),
					'node_type' => 'file',
					'share_type' => IShare::TYPE_LINK,
					'shared_with' => null,
					'shared_with_display_name' => '',
					'shared_with_avatar' => null,
					'permissions' => Constants::PERMISSION_ALL & ~Constants::PERMISSION_CREATE & ~Constants::PERMISSION_DELETE,
					'attributes' => null,
					'status' => IShare::STATUS_PENDING,
					'note' => 'Note',
					'expiration_date' => $expirationDate,
					'no_expiration_date' => false,
					'label' => 'Label',
					'shared_by' => 'owner',
					'share_owner' => 'owner',
					'password' => null,
					'password_expiration_time' => null,
					'send_password_by_talk' => false,
					'token' => 'secret',
					'parent' => null,
					'original_target' => null,
					'target' => '/foo.txt',
					'mail_send' => false,
					'hide_download' => false,
					'reminder_sent' => false,
				],
				[
					'id' => explode(':', $legacyIds[6])[1],
					'full_id' => $legacyIds[6],
					'node_id' => $node1->getId(),
					'node_type' => 'folder',
					'share_type' => IShare::TYPE_EMAIL,
					'shared_with' => 'example@example.com',
					'shared_with_display_name' => '',
					'shared_with_avatar' => null,
					'permissions' => Constants::PERMISSION_ALL,
					'attributes' => null,
					'status' => null,
					'note' => 'Note',
					'expiration_date' => $expirationDate,
					'no_expiration_date' => false,
					'label' => 'Label',
					'shared_by' => 'owner',
					'share_owner' => 'owner',
					'password' => null,
					'password_expiration_time' => null,
					'send_password_by_talk' => false,
					'token' => 'secret',
					'parent' => null,
					'original_target' => null,
					'target' => null,
					'mail_send' => false,
					'hide_download' => false,
					'reminder_sent' => false,
				],
				[
					'id' => explode(':', $legacyIds[7])[1],
					'full_id' => $legacyIds[7],
					'node_id' => $node2->getId(),
					'node_type' => 'file',
					'share_type' => IShare::TYPE_EMAIL,
					'shared_with' => 'example@example.com',
					'shared_with_display_name' => '',
					'shared_with_avatar' => null,
					'permissions' => Constants::PERMISSION_ALL & ~Constants::PERMISSION_CREATE & ~Constants::PERMISSION_DELETE,
					'attributes' => null,
					'status' => null,
					'note' => 'Note',
					'expiration_date' => $expirationDate,
					'no_expiration_date' => false,
					'label' => 'Label',
					'shared_by' => 'owner',
					'share_owner' => 'owner',
					'password' => null,
					'password_expiration_time' => null,
					'send_password_by_talk' => false,
					'token' => 'secret',
					'parent' => null,
					'original_target' => null,
					'target' => null,
					'mail_send' => false,
					'hide_download' => false,
					'reminder_sent' => false,
				],
			],
			$circle !== null ? [
				[
					'id' => explode(':', $legacyIds[8])[1],
					'full_id' => $legacyIds[8],
					'node_id' => $node1->getId(),
					'node_type' => 'folder',
					'share_type' => IShare::TYPE_CIRCLE,
					'shared_with' => $circle->getSingleId(),
					'shared_with_display_name' => $circle->getDisplayName() . ' (Team owned by ' . $owner->getDisplayName() . ')',
					'shared_with_avatar' => 'http://localhost/apps/circles/img/circles.svg',
					'permissions' => Constants::PERMISSION_ALL,
					'attributes' => [
						[
							'scope' => 'permissions',
							'key' => 'download',
							'value' => true,
						]
					],
					'status' => IShare::STATUS_ACCEPTED,
					'note' => 'Note',
					'expiration_date' => $expirationDate,
					'no_expiration_date' => false,
					'label' => '',
					'shared_by' => 'owner',
					'share_owner' => 'owner',
					'password' => null,
					'password_expiration_time' => null,
					'send_password_by_talk' => false,
					'token' => '',
					'parent' => null,
					'original_target' => null,
					'target' => '/foo',
					'mail_send' => true,
					'hide_download' => false,
					'reminder_sent' => false,
				],
				[
					'id' => explode(':', $legacyIds[9])[1],
					'full_id' => $legacyIds[9],
					'node_id' => $node2->getId(),
					'node_type' => 'file',
					'share_type' => IShare::TYPE_CIRCLE,
					'shared_with' => $circle->getSingleId(),
					'shared_with_display_name' => $circle->getDisplayName() . ' (Team owned by ' . $owner->getDisplayName() . ')',
					'shared_with_avatar' => 'http://localhost/apps/circles/img/circles.svg',
					'permissions' => Constants::PERMISSION_ALL & ~Constants::PERMISSION_CREATE & ~Constants::PERMISSION_DELETE,
					'attributes' => [
						[
							'scope' => 'permissions',
							'key' => 'download',
							'value' => true,
						]
					],
					'status' => IShare::STATUS_ACCEPTED,
					'note' => 'Note',
					'expiration_date' => $expirationDate,
					'no_expiration_date' => false,
					'label' => '',
					'shared_by' => 'owner',
					'share_owner' => 'owner',
					'password' => null,
					'password_expiration_time' => null,
					'send_password_by_talk' => false,
					'token' => '',
					'parent' => null,
					'original_target' => null,
					'target' => '/foo.txt',
					'mail_send' => true,
					'hide_download' => false,
					'reminder_sent' => false,
				],
			] : [],
		), $legacyShares);

		$this->legacyBackend->deleteShare($share->id);
		if (class_exists(CirclesManager::class) && $circle !== null) {
			$circlesManager = Server::get(CirclesManager::class);
			$circlesManager->startSession($circlesManager->getLocalFederatedUser($owner->getUID()));
			/** @psalm-suppress MixedMethodCall */
			$circlesManager->destroyCircle($circle->getSingleId());
		}

		$this->assertTrue($group->delete());
		$this->assertTrue($user->delete());
		$this->assertTrue($owner->delete());
	}

	/**
	 * @return array<string, string|int|bool|mixed[]|null>
	 */
	private function formatLegacyShare(IShare $legacyShare): array {
		// Omitted, because it is not predictable
		$this->assertGreaterThan(0, $legacyShare->getShareTime()->getTimestamp());

		return [
			'id' => $legacyShare->getId(),
			'full_id' => $legacyShare->getFullId(),
			'node_id' => $legacyShare->getNodeId(),
			'node_type' => $legacyShare->getNodeType(),
			'share_type' => $legacyShare->getShareType(),
			'shared_with' => $legacyShare->getSharedWith(),
			'shared_with_display_name' => $legacyShare->getSharedWithDisplayName(),
			'shared_with_avatar' => $legacyShare->getSharedWithAvatar(),
			'permissions' => $legacyShare->getPermissions(),
			'attributes' => $legacyShare->getAttributes()?->toArray(),
			'status' => $legacyShare->getStatus(),
			'note' => $legacyShare->getNote(),
			'expiration_date' => $legacyShare->getExpirationDate()?->format(DateTimeInterface::ATOM),
			'no_expiration_date' => $legacyShare->getNoExpirationDate(),
			'label' => $legacyShare->getLabel(),
			'shared_by' => $legacyShare->getSharedBy(),
			'share_owner' => $legacyShare->getShareOwner(),
			'password' => $legacyShare->getPassword(),
			'password_expiration_time' => $legacyShare->getPasswordExpirationTime()?->format(DateTimeInterface::ATOM),
			'send_password_by_talk' => $legacyShare->getSendPasswordByTalk(),
			'token' => $legacyShare->getToken(),
			'parent' => $legacyShare->getParent(),
			'original_target' => $legacyShare->getOriginalTarget(),
			'target' => $legacyShare->getTarget(),
			'mail_send' => $legacyShare->getMailSend(),
			'hide_download' => $legacyShare->getHideDownload(),
			'reminder_sent' => $legacyShare->getReminderSent(),
		];
	}

	/**
	 * @return list<string>
	 */
	private function getLegacyIds(string $id): array {
		$qb = $this->dbConnection->getQueryBuilder();
		$result = $qb
			->select('legacy_id')
			->from('share_legacy_mapping')
			->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
			->executeQuery();
		/** @var list<string> $ids */
		$ids = $result->fetchFirstColumn();
		return $ids;
	}
}
