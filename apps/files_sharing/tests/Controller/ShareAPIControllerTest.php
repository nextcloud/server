<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Files_Sharing\Tests\Controller;

use OC\Files\Storage\Wrapper\Wrapper;
use OCA\Federation\TrustedServers;
use OCA\Files_Sharing\Controller\ShareAPIController;
use OCA\Files_Sharing\External\Storage;
use OCA\Files_Sharing\SharedStorage;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\Constants;
use OCP\Files\Cache\ICache;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Mount\IShareOwnerlessMount;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IStorage;
use OCP\IAppConfig;
use OCP\IConfig;
use OCP\IDateTimeZone;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IPreview;
use OCP\IRequest;
use OCP\ITagManager;
use OCP\ITags;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Lock\ILockingProvider;
use OCP\Lock\LockedException;
use OCP\Mail\IMailer;
use OCP\Server;
use OCP\Share\Exceptions\GenericShareException;
use OCP\Share\Exceptions\ShareNotFound;
use OCP\Share\IAttributes as IShareAttributes;
use OCP\Share\IManager;
use OCP\Share\IProviderFactory;
use OCP\Share\IShare;
use OCP\UserStatus\IManager as IUserStatusManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Test\TestCase;
use Test\Traits\EmailValidatorTrait;

/**
 * Class ShareAPIControllerTest
 *
 * @package OCA\Files_Sharing\Tests\Controller
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class ShareAPIControllerTest extends TestCase {
	use EmailValidatorTrait;

	private string $appName = 'files_sharing';
	private string $currentUser;

	private ShareAPIController $ocs;

	private IManager&MockObject $shareManager;
	private IGroupManager&MockObject $groupManager;
	private IUserManager&MockObject $userManager;
	private IRequest&MockObject $request;
	private IRootFolder&MockObject $rootFolder;
	private IURLGenerator&MockObject $urlGenerator;
	private IL10N&MockObject $l;
	private IConfig&MockObject $config;
	private IAppConfig&MockObject $appConfig;
	private IAppManager&MockObject $appManager;
	private ContainerInterface&MockObject $serverContainer;
	private IUserStatusManager&MockObject $userStatusManager;
	private IPreview&MockObject $previewManager;
	private IDateTimeZone&MockObject $dateTimeZone;
	private LoggerInterface&MockObject $logger;
	private IProviderFactory&MockObject $factory;
	private IMailer&MockObject $mailer;
	private ITagManager&MockObject $tagManager;
	private TrustedServers&MockObject $trustedServers;

	protected function setUp(): void {
		$this->shareManager = $this->createMock(IManager::class);
		$this->shareManager
			->expects($this->any())
			->method('shareApiEnabled')
			->willReturn(true);
		$this->shareManager
			->expects($this->any())
			->method('shareProviderExists')->willReturn(true);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->request = $this->createMock(IRequest::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->currentUser = 'currentUser';

		$this->l = $this->createMock(IL10N::class);
		$this->l->method('t')
			->willReturnCallback(function ($text, $parameters = []) {
				return vsprintf($text, $parameters);
			});
		$this->config = $this->createMock(IConfig::class);
		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->appManager = $this->createMock(IAppManager::class);
		$this->serverContainer = $this->createMock(ContainerInterface::class);
		$this->userStatusManager = $this->createMock(IUserStatusManager::class);
		$this->previewManager = $this->createMock(IPreview::class);
		$this->previewManager->method('isAvailable')
			->willReturnCallback(function ($fileInfo) {
				return $fileInfo->getMimeType() === 'mimeWithPreview';
			});
		$this->dateTimeZone = $this->createMock(IDateTimeZone::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->factory = $this->createMock(IProviderFactory::class);
		$this->mailer = $this->createMock(IMailer::class);
		$this->tagManager = $this->createMock(ITagManager::class);
		$this->trustedServers = $this->createMock(TrustedServers::class);

		$this->ocs = new ShareAPIController(
			$this->appName,
			$this->request,
			$this->shareManager,
			$this->groupManager,
			$this->userManager,
			$this->rootFolder,
			$this->urlGenerator,
			$this->l,
			$this->config,
			$this->appConfig,
			$this->appManager,
			$this->serverContainer,
			$this->userStatusManager,
			$this->previewManager,
			$this->dateTimeZone,
			$this->logger,
			$this->factory,
			$this->mailer,
			$this->tagManager,
			$this->getEmailValidatorWithStrictEmailCheck(),
			$this->trustedServers,
			$this->currentUser,
		);

	}

	/**
	 * @return ShareAPIController&MockObject
	 */
	private function mockFormatShare() {
		return $this->getMockBuilder(ShareAPIController::class)
			->setConstructorArgs([
				$this->appName,
				$this->request,
				$this->shareManager,
				$this->groupManager,
				$this->userManager,
				$this->rootFolder,
				$this->urlGenerator,
				$this->l,
				$this->config,
				$this->appConfig,
				$this->appManager,
				$this->serverContainer,
				$this->userStatusManager,
				$this->previewManager,
				$this->dateTimeZone,
				$this->logger,
				$this->factory,
				$this->mailer,
				$this->tagManager,
				$this->getEmailValidatorWithStrictEmailCheck(),
				$this->trustedServers,
				$this->currentUser,
			])->onlyMethods(['formatShare'])
			->getMock();
	}

	private function newShare() {
		return Server::get(IManager::class)->newShare();
	}


	private function mockShareAttributes() {
		$formattedShareAttributes = [
			[
				'scope' => 'permissions',
				'key' => 'download',
				'value' => true
			]
		];

		$shareAttributes = $this->createMock(IShareAttributes::class);
		$shareAttributes->method('toArray')->willReturn($formattedShareAttributes);
		$shareAttributes->method('getAttribute')->with('permissions', 'download')->willReturn(true);

		// send both IShare attributes class and expected json string
		return [$shareAttributes, \json_encode($formattedShareAttributes)];
	}

	public function testDeleteShareShareNotFound(): void {
		$this->expectException(OCSNotFoundException::class);
		$this->expectExceptionMessage('Wrong share ID, share does not exist');

		$this->shareManager
			->expects($this->exactly(6))
			->method('getShareById')
			->willReturnCallback(function ($id): void {
				if ($id === 'ocinternal:42' || $id === 'ocRoomShare:42' || $id === 'ocFederatedSharing:42' || $id === 'ocCircleShare:42' || $id === 'ocMailShare:42' || $id === 'deck:42') {
					throw new ShareNotFound();
				} else {
					throw new \Exception();
				}
			});

		$this->shareManager->method('outgoingServer2ServerSharesAllowed')->willReturn(true);

		$this->ocs->deleteShare(42);
	}

	public function testDeleteShare(): void {
		$node = $this->getMockBuilder(File::class)->getMock();

		$share = $this->newShare();
		$share->setSharedBy($this->currentUser)
			->setNode($node);
		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with('ocinternal:42')
			->willReturn($share);
		$this->shareManager
			->expects($this->once())
			->method('deleteShare')
			->with($share);

		$node->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$expected = new DataResponse();
		$result = $this->ocs->deleteShare(42);

		$this->assertInstanceOf(get_class($expected), $result);
		$this->assertEquals($expected->getData(), $result->getData());
	}


	public function testDeleteShareLocked(): void {
		$this->expectException(OCSNotFoundException::class);
		$this->expectExceptionMessage('Could not delete share');

		$node = $this->getMockBuilder(File::class)->getMock();
		$node->method('getId')->willReturn(1);

		$share = $this->newShare();
		$share->setNode($node);

		$userFolder = $this->getMockBuilder(Folder::class)->getMock();
		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$userFolder->method('getById')
			->with($share->getNodeId())
			->willReturn([$node]);

		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with('ocinternal:42')
			->willReturn($share);

		$this->shareManager
			->expects($this->never())
			->method('deleteShare')
			->with($share);

		$node->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED)
			->willThrowException(new LockedException('mypath'));

		$this->assertFalse($this->invokePrivate($this->ocs, 'canDeleteFromSelf', [$share]));
		$this->assertFalse($this->invokePrivate($this->ocs, 'canDeleteShare', [$share]));

		$this->ocs->deleteShare(42);
	}

	/**
	 * You can always remove a share that was shared with you
	 */
	public function testDeleteShareWithMe(): void {
		$node = $this->getMockBuilder(File::class)->getMock();

		$share = $this->newShare();
		$share->setSharedWith($this->currentUser)
			->setShareType(IShare::TYPE_USER)
			->setNode($node);

		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with('ocinternal:42')
			->willReturn($share);

		$this->shareManager
			->expects($this->once())
			->method('deleteShare')
			->with($share);

		$node->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$this->assertFalse($this->invokePrivate($this->ocs, 'canDeleteFromSelf', [$share]));
		$this->assertTrue($this->invokePrivate($this->ocs, 'canDeleteShare', [$share]));

		$this->ocs->deleteShare(42);
	}

	/**
	 * You can always delete a share you own
	 */
	public function testDeleteShareOwner(): void {
		$node = $this->getMockBuilder(File::class)->getMock();

		$share = $this->newShare();
		$share->setSharedBy($this->currentUser)
			->setNode($node);

		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with('ocinternal:42')
			->willReturn($share);

		$this->shareManager
			->expects($this->once())
			->method('deleteShare')
			->with($share);

		$node->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$this->assertFalse($this->invokePrivate($this->ocs, 'canDeleteFromSelf', [$share]));
		$this->assertTrue($this->invokePrivate($this->ocs, 'canDeleteShare', [$share]));

		$this->ocs->deleteShare(42);
	}

	/**
	 * You can always delete a share when you own
	 * the file path it belong to
	 */
	public function testDeleteShareFileOwner(): void {
		$node = $this->getMockBuilder(File::class)->getMock();
		$node->method('getId')->willReturn(1);

		$share = $this->newShare();
		$share->setShareOwner($this->currentUser)
			->setNode($node);

		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with('ocinternal:42')
			->willReturn($share);

		$this->shareManager
			->expects($this->once())
			->method('deleteShare')
			->with($share);

		$node->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$this->assertFalse($this->invokePrivate($this->ocs, 'canDeleteFromSelf', [$share]));
		$this->assertTrue($this->invokePrivate($this->ocs, 'canDeleteShare', [$share]));

		$this->ocs->deleteShare(42);
	}

	/**
	 * You can remove (the mountpoint, not the share)
	 * a share if you're in the group the share is shared with
	 */
	public function testDeleteSharedWithMyGroup(): void {
		$node = $this->getMockBuilder(File::class)->getMock();
		$node->method('getId')->willReturn(1);

		$share = $this->newShare();
		$share->setShareType(IShare::TYPE_GROUP)
			->setSharedWith('group')
			->setNode($node);

		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with('ocinternal:42')
			->willReturn($share);

		// canDeleteShareFromSelf
		$user = $this->createMock(IUser::class);
		$group = $this->getMockBuilder(IGroup::class)->getMock();
		$this->groupManager
			->method('get')
			->with('group')
			->willReturn($group);
		$this->userManager
			->method('get')
			->with($this->currentUser)
			->willReturn($user);
		$group->method('inGroup')
			->with($user)
			->willReturn(true);

		$node->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$userFolder = $this->getMockBuilder(Folder::class)->getMock();
		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$userFolder->method('getById')
			->with($share->getNodeId())
			->willReturn([$share->getNode()]);

		$this->shareManager->expects($this->once())
			->method('deleteFromSelf')
			->with($share, $this->currentUser);

		$this->shareManager->expects($this->never())
			->method('deleteShare');

		$this->assertTrue($this->invokePrivate($this->ocs, 'canDeleteShareFromSelf', [$share]));
		$this->assertFalse($this->invokePrivate($this->ocs, 'canDeleteShare', [$share]));

		$this->ocs->deleteShare(42);
	}

	/**
	 * You cannot remove a share if you're not
	 * in the group the share is shared with
	 */
	public function testDeleteSharedWithGroupIDontBelongTo(): void {
		$this->expectException(OCSNotFoundException::class);
		$this->expectExceptionMessage('Wrong share ID, share does not exist');

		$node = $this->getMockBuilder(File::class)->getMock();
		$node->method('getId')->willReturn(42);

		$share = $this->newShare();
		$share->setShareType(IShare::TYPE_GROUP)
			->setSharedWith('group')
			->setNode($node);

		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with('ocinternal:42')
			->willReturn($share);

		// canDeleteShareFromSelf
		$user = $this->createMock(IUser::class);
		$group = $this->getMockBuilder(IGroup::class)->getMock();
		$this->groupManager
			->method('get')
			->with('group')
			->willReturn($group);
		$this->userManager
			->method('get')
			->with($this->currentUser)
			->willReturn($user);
		$group->method('inGroup')
			->with($user)
			->willReturn(false);

		$node->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$userFolder = $this->getMockBuilder(Folder::class)->getMock();
		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$userFolder->method('getById')
			->with($share->getNodeId())
			->willReturn([$share->getNode()]);

		$this->shareManager->expects($this->never())
			->method('deleteFromSelf');

		$this->shareManager->expects($this->never())
			->method('deleteShare');

		$this->assertFalse($this->invokePrivate($this->ocs, 'canDeleteShareFromSelf', [$share]));
		$this->assertFalse($this->invokePrivate($this->ocs, 'canDeleteShare', [$share]));

		$this->ocs->deleteShare(42);
	}

	public function testDeleteShareOwnerless(): void {
		$ocs = $this->mockFormatShare();

		$mount = $this->createMock(IShareOwnerlessMount::class);

		$file = $this->createMock(File::class);
		$file
			->expects($this->exactly(2))
			->method('getPermissions')
			->willReturn(Constants::PERMISSION_SHARE);
		$file
			->expects($this->once())
			->method('getMountPoint')
			->willReturn($mount);

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')
			->with(2)
			->willReturn([$file]);
		$userFolder->method('getFirstNodeById')
			->with(2)
			->willReturn($file);

		$this->rootFolder
			->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$share = $this->createMock(IShare::class);
		$share
			->expects($this->once())
			->method('getNode')
			->willReturn($file);
		$share
			->expects($this->exactly(2))
			->method('getNodeId')
			->willReturn(2);
		$share
			->expects($this->exactly(2))
			->method('getPermissions')
			->willReturn(Constants::PERMISSION_SHARE);

		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with('ocinternal:1', $this->currentUser)
			->willReturn($share);

		$this->shareManager
			->expects($this->once())
			->method('deleteShare')
			->with($share);

		$result = $ocs->deleteShare(1);
		$this->assertInstanceOf(DataResponse::class, $result);
	}

	/*
	 * FIXME: Enable once we have a federated Share Provider

	public function testGetGetShareNotExists() {
		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with('ocinternal:42', 'currentUser')
			->will($this->throwException(new \OC\Share20\Exception\ShareNotFound()));

		$expected = new \OC\OCS\Result(null, 404, 'wrong share ID, share does not exist.');
		$this->assertEquals($expected, $this->ocs->getShare(42));
	}
	*/

	public function createShare(
		int $id,
		int $shareType,
		?string $sharedWith,
		string $sharedBy,
		string $shareOwner,
		File|Folder|null $node,
		int $permissions,
		int $shareTime,
		?\DateTime $expiration,
		int $parent,
		string $target,
		int $mail_send,
		string $note = '',
		?string $token = null,
		?string $password = null,
		string $label = '',
		?IShareAttributes $attributes = null,
	): MockObject {
		$share = $this->createMock(IShare::class);
		$share->method('getId')->willReturn($id);
		$share->method('getShareType')->willReturn($shareType);
		$share->method('getSharedWith')->willReturn($sharedWith);
		$share->method('getSharedBy')->willReturn($sharedBy);
		$share->method('getShareOwner')->willReturn($shareOwner);
		$share->method('getNode')->willReturn($node);
		$share->method('getPermissions')->willReturn($permissions);
		$share->method('getNote')->willReturn($note);
		$share->method('getLabel')->willReturn($label);
		$share->method('getAttributes')->willReturn($attributes);
		$time = new \DateTime();
		$time->setTimestamp($shareTime);
		$share->method('getShareTime')->willReturn($time);
		$share->method('getExpirationDate')->willReturn($expiration);
		$share->method('getTarget')->willReturn($target);
		$share->method('getMailSend')->willReturn($mail_send);
		$share->method('getToken')->willReturn($token);
		$share->method('getPassword')->willReturn($password);

		if ($shareType === IShare::TYPE_USER
			|| $shareType === IShare::TYPE_GROUP
			|| $shareType === IShare::TYPE_LINK) {
			$share->method('getFullId')->willReturn('ocinternal:' . $id);
		}

		return $share;
	}

	public static function dataGetShare(): array {
		$data = [];

		$file = [
			'class' => File::class,
			'id' => 1,
			'path' => 'file',
			'mimeType' => 'myMimeType',
		];

		$folder = [
			'class' => Folder::class,
			'id' => 2,
			'path' => 'folder',
			'mimeType' => 'myFolderMimeType',
		];

		// File shared with user
		$share = [
			100,
			IShare::TYPE_USER,
			'userId',
			'initiatorId',
			'ownerId',
			$file,
			4,
			5,
			null,
			6,
			'target',
			0,
			'personal note',
			null,
			null,
			'',
			[],
		];
		$expected = [
			'id' => 100,
			'share_type' => IShare::TYPE_USER,
			'share_with' => 'userId',
			'share_with_displayname' => 'userDisplay',
			'share_with_displayname_unique' => 'userId@example.com',
			'uid_owner' => 'initiatorId',
			'displayname_owner' => 'initiatorDisplay',
			'item_type' => 'file',
			'item_source' => 1,
			'file_source' => 1,
			'file_target' => 'target',
			'file_parent' => 3,
			'token' => null,
			'expiration' => null,
			'permissions' => 4,
			'stime' => 5,
			'parent' => null,
			'storage_id' => 'STORAGE',
			'path' => 'file',
			'storage' => 101,
			'mail_send' => 0,
			'uid_file_owner' => 'ownerId',
			'note' => 'personal note',
			'label' => '',
			'displayname_file_owner' => 'ownerDisplay',
			'mimetype' => 'myMimeType',
			'has_preview' => false,
			'hide_download' => 0,
			'can_edit' => false,
			'can_delete' => false,
			'item_size' => 123465,
			'item_mtime' => 1234567890,
			'item_permissions' => 4,
			'is-mount-root' => false,
			'mount-type' => '',
		];
		$data['File shared with user'] = [$share, $expected, true];

		// Folder shared with group
		$share = [
			101,
			IShare::TYPE_GROUP,
			'groupId',
			'initiatorId',
			'ownerId',
			$folder,
			4,
			5,
			null,
			6,
			'target',
			0,
			'personal note',
			null,
			null,
			'',
			[],
		];
		$expected = [
			'id' => 101,
			'share_type' => IShare::TYPE_GROUP,
			'share_with' => 'groupId',
			'share_with_displayname' => 'groupId',
			'uid_owner' => 'initiatorId',
			'displayname_owner' => 'initiatorDisplay',
			'item_type' => 'folder',
			'item_source' => 2,
			'file_source' => 2,
			'file_target' => 'target',
			'file_parent' => 3,
			'token' => null,
			'expiration' => null,
			'permissions' => 4,
			'stime' => 5,
			'parent' => null,
			'storage_id' => 'STORAGE',
			'path' => 'folder',
			'storage' => 101,
			'mail_send' => 0,
			'uid_file_owner' => 'ownerId',
			'note' => 'personal note',
			'label' => '',
			'displayname_file_owner' => 'ownerDisplay',
			'mimetype' => 'myFolderMimeType',
			'has_preview' => false,
			'hide_download' => 0,
			'can_edit' => false,
			'can_delete' => false,
			'item_size' => 123465,
			'item_mtime' => 1234567890,
			'item_permissions' => 4,
			'is-mount-root' => false,
			'mount-type' => '',
		];
		$data['Folder shared with group'] = [$share, $expected, true];

		// File shared by link with Expire
		$expire = \DateTime::createFromFormat('Y-m-d h:i:s', '2000-01-02 01:02:03');
		$share = [
			101,
			IShare::TYPE_LINK,
			null,
			'initiatorId',
			'ownerId',
			$folder,
			4,
			5,
			$expire,
			6,
			'target',
			0,
			'personal note',
			'token',
			'password',
			'first link share'
		];
		$expected = [
			'id' => 101,
			'share_type' => IShare::TYPE_LINK,
			'password' => 'password',
			'share_with' => 'password',
			'share_with_displayname' => '(Shared link)',
			'send_password_by_talk' => false,
			'uid_owner' => 'initiatorId',
			'displayname_owner' => 'initiatorDisplay',
			'item_type' => 'folder',
			'item_source' => 2,
			'file_source' => 2,
			'file_target' => 'target',
			'file_parent' => 3,
			'token' => 'token',
			'expiration' => '2000-01-02 00:00:00',
			'permissions' => 4,
			'attributes' => null,
			'stime' => 5,
			'parent' => null,
			'storage_id' => 'STORAGE',
			'path' => 'folder',
			'storage' => 101,
			'mail_send' => 0,
			'url' => 'url',
			'uid_file_owner' => 'ownerId',
			'note' => 'personal note',
			'label' => 'first link share',
			'displayname_file_owner' => 'ownerDisplay',
			'mimetype' => 'myFolderMimeType',
			'has_preview' => false,
			'hide_download' => 0,
			'can_edit' => false,
			'can_delete' => false,
			'item_size' => 123465,
			'item_mtime' => 1234567890,
			'item_permissions' => 4,
			'is-mount-root' => false,
			'mount-type' => '',
		];
		$data['File shared by link with Expire'] = [$share, $expected, false];

		return $data;
	}

	#[DataProvider(methodName: 'dataGetShare')]
	public function testGetShare(array $shareParams, array $result, bool $attributes): void {

		$cache = $this->createMock(ICache::class);
		$cache->method('getNumericStorageId')->willReturn(101);

		$storage = $this->createMock(IStorage::class);
		$storage->method('getId')->willReturn('STORAGE');
		$storage->method('getCache')->willReturn($cache);

		$parentFolder = $this->createMock(Folder::class);
		$parentFolder->method('getId')->willReturn(3);

		$mountPoint = $this->createMock(IMountPoint::class);
		$mountPoint->method('getMountType')->willReturn('');

		$nodeParams = $shareParams[5];
		$node = $this->createMock($nodeParams['class']);
		$node->method('getId')->willReturn($nodeParams['id']);
		$node->method('getPath')->willReturn($nodeParams['path']);
		$node->method('getStorage')->willReturn($storage);
		$node->method('getParent')->willReturn($parentFolder);
		$node->method('getSize')->willReturn(123465);
		$node->method('getMTime')->willReturn(1234567890);
		$node->method('getMimeType')->willReturn($nodeParams['mimeType']);
		$node->method('getMountPoint')->willReturn($mountPoint);

		$shareParams[5] = $node;

		if ($attributes) {
			[$shareAttributes, $shareAttributesReturnJson] = $this->mockShareAttributes();
			$result['attributes'] = $shareAttributesReturnJson;
			$shareParams[16] = $shareAttributes;
		}

		$share = $this->createShare(...$shareParams);
		/** @var ShareAPIController&MockObject $ocs */
		$ocs = $this->getMockBuilder(ShareAPIController::class)
			->setConstructorArgs([
				$this->appName,
				$this->request,
				$this->shareManager,
				$this->groupManager,
				$this->userManager,
				$this->rootFolder,
				$this->urlGenerator,
				$this->l,
				$this->config,
				$this->appConfig,
				$this->appManager,
				$this->serverContainer,
				$this->userStatusManager,
				$this->previewManager,
				$this->dateTimeZone,
				$this->logger,
				$this->factory,
				$this->mailer,
				$this->tagManager,
				$this->getEmailValidatorWithStrictEmailCheck(),
				$this->trustedServers,
				$this->currentUser,
			])
			->onlyMethods(['canAccessShare'])
			->getMock();

		$ocs->expects($this->any())
			->method('canAccessShare')
			->willReturn(true);

		$this->shareManager
			->expects($this->any())
			->method('getShareById')
			->with($share->getFullId(), 'currentUser')
			->willReturn($share);

		$userFolder = $this->getMockBuilder(Folder::class)->getMock();
		$userFolder
			->method('getRelativePath')
			->willReturnArgument(0);

		$userFolder->method('getById')
			->with($share->getNodeId())
			->willReturn([$share->getNode()]);
		$userFolder->method('getFirstNodeById')
			->with($share->getNodeId())
			->willReturn($share->getNode());

		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$this->urlGenerator
			->method('linkToRouteAbsolute')
			->willReturn('url');

		$initiator = $this->getMockBuilder(IUser::class)->getMock();
		$initiator->method('getUID')->willReturn('initiatorId');
		$initiator->method('getDisplayName')->willReturn('initiatorDisplay');

		$owner = $this->getMockBuilder(IUser::class)->getMock();
		$owner->method('getUID')->willReturn('ownerId');
		$owner->method('getDisplayName')->willReturn('ownerDisplay');

		$user = $this->getMockBuilder(IUser::class)->getMock();
		$user->method('getUID')->willReturn('userId');
		$user->method('getDisplayName')->willReturn('userDisplay');
		$user->method('getSystemEMailAddress')->willReturn('userId@example.com');

		$group = $this->getMockBuilder(IGroup::class)->getMock();
		$group->method('getGID')->willReturn('groupId');

		$this->userManager->method('get')->willReturnMap([
			['userId', $user],
			['initiatorId', $initiator],
			['ownerId', $owner],
		]);
		$this->groupManager->method('get')->willReturnMap([
			['group', $group],
		]);
		$this->dateTimeZone->method('getTimezone')->willReturn(new \DateTimeZone('UTC'));

		$data = $ocs->getShare((string)$share->getId())->getData()[0];
		$this->assertEquals($result, $data);
	}


	public function testGetShareInvalidNode(): void {
		$this->expectException(OCSNotFoundException::class);
		$this->expectExceptionMessage('Wrong share ID, share does not exist');

		$share = Server::get(IManager::class)->newShare();
		$share->setSharedBy('initiator')
			->setSharedWith('recipient')
			->setShareOwner('owner');

		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with('ocinternal:42', 'currentUser')
			->willReturn($share);

		$userFolder = $this->getMockBuilder(Folder::class)->getMock();
		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$this->ocs->getShare('42');
	}

	public static function dataGetShares(): array {
		$file1 = [
			'class' => File::class,
			'methods' => [
				'getName' => 'file1',
			]
		];
		$file2 = [
			'class' => File::class,
			'methods' => [
				'getName' => 'file2',
			]
		];

		$folder = [
			'class' => Folder::class,
			'methods' => [
				'getDirectoryListing' => [$file1, $file2]
			]
		];

		$file1UserShareOwner = [
			'type' => IShare::TYPE_USER,
			'sharedWith' => 'recipient',
			'sharedBy' => 'initiator',
			'owner' => 'currentUser',
			'node' => $file1,
			'id' => 4,
		];

		$file1UserShareOwnerExpected = [
			'id' => 4,
			'share_type' => IShare::TYPE_USER,
		];

		$file1UserShareInitiator = [
			'type' => IShare::TYPE_USER,
			'sharedWith' => 'recipient',
			'sharedBy' => 'currentUser',
			'owner' => 'owner',
			'node' => $file1,
			'id' => 8,
		];

		$file1UserShareInitiatorExpected = [
			'id' => 8,
			'share_type' => IShare::TYPE_USER,
		];

		$file1UserShareRecipient = [
			'type' => IShare::TYPE_USER,
			'sharedWith' => 'currentUser',
			'sharedBy' => 'initiator',
			'owner' => 'owner',
			'node' => $file1,
			'id' => 15,
		];

		$file1UserShareRecipientExpected = [
			'id' => 15,
			'share_type' => IShare::TYPE_USER,
		];

		$file1UserShareOther = [
			'type' => IShare::TYPE_USER,
			'sharedWith' => 'recipient',
			'sharedBy' => 'initiator',
			'owner' => 'owner',
			'node' => $file1,
			'id' => 16,
		];

		$file1UserShareOtherExpected = [
			'id' => 16,
			'share_type' => IShare::TYPE_USER,
		];

		$file1GroupShareOwner = [
			'type' => IShare::TYPE_GROUP,
			'sharedWith' => 'recipient',
			'sharedBy' => 'initiator',
			'owner' => 'currentUser',
			'node' => $file1,
			'id' => 23,
		];

		$file1GroupShareOwnerExpected = [
			'id' => 23,
			'share_type' => IShare::TYPE_GROUP,
		];

		$file1GroupShareRecipient = [
			'type' => IShare::TYPE_GROUP,
			'sharedWith' => 'currentUserGroup',
			'sharedBy' => 'initiator',
			'owner' => 'owner',
			'node' => $file1,
			'id' => 42,
		];

		$file1GroupShareRecipientExpected = [
			'id' => 42,
			'share_type' => IShare::TYPE_GROUP,
		];

		$file1GroupShareOther = [
			'type' => IShare::TYPE_GROUP,
			'sharedWith' => 'recipient',
			'sharedBy' => 'initiator',
			'owner' => 'owner',
			'node' => $file1,
			'id' => 108,
		];

		$file1LinkShareOwner = [
			'type' => IShare::TYPE_LINK,
			'sharedWith' => 'recipient',
			'sharedBy' => 'initiator',
			'owner' => 'currentUser',
			'node' => $file1,
			'id' => 415,
		];

		$file1LinkShareOwnerExpected = [
			'id' => 415,
			'share_type' => IShare::TYPE_LINK,
		];

		$file1EmailShareOwner = [
			'type' => IShare::TYPE_EMAIL,
			'sharedWith' => 'recipient',
			'sharedBy' => 'initiator',
			'owner' => 'currentUser',
			'node' => $file1,
			'id' => 416,
		];

		$file1EmailShareOwnerExpected = [
			'id' => 416,
			'share_type' => IShare::TYPE_EMAIL,
		];

		$file1CircleShareOwner = [
			'type' => IShare::TYPE_CIRCLE,
			'sharedWith' => 'recipient',
			'sharedBy' => 'initiator',
			'owner' => 'currentUser',
			'node' => $file1,
			'id' => 423,
		];

		$file1CircleShareOwnerExpected = [
			'id' => 423,
			'share_type' => IShare::TYPE_CIRCLE,
		];

		$file1RoomShareOwner = [
			'type' => IShare::TYPE_ROOM,
			'sharedWith' => 'recipient',
			'sharedBy' => 'initiator',
			'owner' => 'currentUser',
			'node' => $file1,
			'id' => 442,
		];

		$file1RoomShareOwnerExpected = [
			'id' => 442,
			'share_type' => IShare::TYPE_ROOM,
		];

		$file1RemoteShareOwner = [
			'type' => IShare::TYPE_REMOTE,
			'sharedWith' => 'recipient',
			'sharedBy' => 'initiator',
			'owner' => 'currentUser',
			'expirationDate' => new \DateTime('2000-01-01T01:02:03'),
			'node' => $file1,
			'id' => 815,
		];

		$file1RemoteShareOwnerExpected = [
			'id' => 815,
			'share_type' => IShare::TYPE_REMOTE,
		];

		$file1RemoteGroupShareOwner = [
			'type' => IShare::TYPE_REMOTE_GROUP,
			'sharedWith' => 'recipient',
			'sharedBy' => 'initiator',
			'owner' => 'currentUser',
			'expirationDate' => new \DateTime('2000-01-01T01:02:03'),
			'node' => $file1,
			'id' => 816,
		];

		$file1RemoteGroupShareOwnerExpected = [
			'id' => 816,
			'share_type' => IShare::TYPE_REMOTE_GROUP,
		];

		$file2UserShareOwner = [
			'type' => IShare::TYPE_USER,
			'sharedWith' => 'recipient',
			'sharedBy' => 'initiator',
			'owner' => 'currentUser',
			'node' => $file2,
			'id' => 823,
		];

		$file2UserShareOwnerExpected = [
			'id' => 823,
			'share_type' => IShare::TYPE_USER,
		];

		$data = [
			[
				[
					'node' => $file1,
				],
				[
					'file1' => [
						IShare::TYPE_USER => [$file1UserShareOwner, $file1UserShareOwner, $file1UserShareOwner],
					],
				],
				[
				],
				[
					$file1UserShareOwnerExpected
				]
			],
			[
				[
					'node' => $file1,
				],
				[
					'file1' => [
						IShare::TYPE_USER => [$file1UserShareOwner, $file1UserShareRecipient],
					],
				],
				[
				],
				[
					$file1UserShareOwnerExpected,
				]
			],
			[
				[
					'node' => $file1,
				],
				[
					'file1' => [
						IShare::TYPE_USER => [$file1UserShareOwner, $file1UserShareRecipient, $file1UserShareInitiator, $file1UserShareOther],
					],
				],
				[
				],
				[
					$file1UserShareOwnerExpected,
					$file1UserShareInitiatorExpected,
					$file1UserShareOtherExpected,
				]
			],
			[
				[
					'node' => $file1,
				],
				[
					'file1' => [
						IShare::TYPE_USER => [$file1UserShareRecipient, $file1UserShareInitiator, $file1UserShareOther],
					],
				],
				[
				],
				[
					$file1UserShareInitiatorExpected,
				]
			],
			[
				[
					'node' => $file1,
				],
				[
					'file1' => [
						IShare::TYPE_USER => [$file1UserShareOwner],
						IShare::TYPE_GROUP => [$file1GroupShareRecipient],
					],
				],
				[
				],
				[
					$file1UserShareOwnerExpected,
					$file1GroupShareRecipientExpected,
				]
			],
			[
				[
					'node' => $file1,
				],
				[
					'file1' => [
						IShare::TYPE_USER => [$file1UserShareOwner],
						IShare::TYPE_GROUP => [$file1GroupShareOwner],
						IShare::TYPE_LINK => [$file1LinkShareOwner],
						IShare::TYPE_EMAIL => [$file1EmailShareOwner],
						IShare::TYPE_CIRCLE => [$file1CircleShareOwner],
						IShare::TYPE_ROOM => [$file1RoomShareOwner],
						IShare::TYPE_REMOTE => [$file1RemoteShareOwner],
						IShare::TYPE_REMOTE_GROUP => [$file1RemoteGroupShareOwner],
					],
				],
				[
				],
				[
					$file1UserShareOwnerExpected,
					$file1GroupShareOwnerExpected,
					$file1LinkShareOwnerExpected,
					$file1EmailShareOwnerExpected,
					$file1CircleShareOwnerExpected,
					$file1RoomShareOwnerExpected,
				]
			],
			[
				[
					'node' => $file1,
				],
				[
					'file1' => [
						IShare::TYPE_USER => [$file1UserShareOwner],
						IShare::TYPE_GROUP => [$file1GroupShareOwner],
						IShare::TYPE_LINK => [$file1LinkShareOwner],
						IShare::TYPE_EMAIL => [$file1EmailShareOwner],
						IShare::TYPE_CIRCLE => [$file1CircleShareOwner],
						IShare::TYPE_ROOM => [$file1RoomShareOwner],
						IShare::TYPE_REMOTE => [$file1RemoteShareOwner],
						IShare::TYPE_REMOTE_GROUP => [$file1RemoteGroupShareOwner],
					],
				],
				[
					IShare::TYPE_REMOTE => true,
					IShare::TYPE_REMOTE_GROUP => true,
				],
				[
					$file1UserShareOwnerExpected,
					$file1GroupShareOwnerExpected,
					$file1LinkShareOwnerExpected,
					$file1EmailShareOwnerExpected,
					$file1CircleShareOwnerExpected,
					$file1RoomShareOwnerExpected,
					$file1RemoteShareOwnerExpected,
					$file1RemoteGroupShareOwnerExpected,
				]
			],
			[
				[
					'node' => $folder,
					'subfiles' => 'true',
				],
				[
					'file1' => [
						IShare::TYPE_USER => [$file1UserShareOwner],
					],
					'file2' => [
						IShare::TYPE_USER => [$file2UserShareOwner],
					],
				],
				[
				],
				[
					$file1UserShareOwnerExpected,
					$file2UserShareOwnerExpected,
				]
			],
			[
				[
					'node' => $folder,
					'subfiles' => 'true',
				],
				[
					'file1' => [
						IShare::TYPE_USER => [$file1UserShareOwner, $file1UserShareOwner, $file1UserShareOwner],
					],
				],
				[
				],
				[
					$file1UserShareOwnerExpected,
				]
			],
			[
				[
					'node' => $folder,
					'subfiles' => 'true',
				],
				[
					'file1' => [
						IShare::TYPE_USER => [$file1UserShareOwner, $file1UserShareRecipient],
					],
				],
				[
				],
				[
					$file1UserShareOwnerExpected
				]
			],
			[
				[
					'node' => $folder,
					'subfiles' => 'true',
				],
				[
					'file1' => [
						IShare::TYPE_USER => [$file1UserShareRecipient, $file1UserShareInitiator, $file1UserShareOther],
					],
					'file2' => [
						IShare::TYPE_USER => [$file2UserShareOwner],
					],
				],
				[
				],
				[
					$file1UserShareInitiatorExpected,
					$file1UserShareOtherExpected,
					$file2UserShareOwnerExpected,
				]
			],
			// This might not happen in a real environment, as the combination
			// of shares does not seem to be possible on a folder without
			// resharing rights; if the folder has resharing rights then the
			// share with others would be included too in the results.
			[
				[
					'node' => $folder,
					'subfiles' => 'true',
				],
				[
					'file1' => [
						IShare::TYPE_USER => [$file1UserShareRecipient, $file1UserShareInitiator, $file1UserShareOther],
					],
				],
				[
				],
				[
					$file1UserShareInitiatorExpected,
				]
			],
			[
				[
					'node' => $folder,
					'subfiles' => 'true',
				],
				[
					'file1' => [
						IShare::TYPE_USER => [$file1UserShareOwner],
						IShare::TYPE_GROUP => [$file1GroupShareRecipient],
					],
				],
				[
				],
				[
					$file1UserShareOwnerExpected,
					$file1GroupShareRecipientExpected,
				]
			],
			[
				[
					'node' => $folder,
					'subfiles' => 'true',
				],
				[
					'file1' => [
						IShare::TYPE_USER => [$file1UserShareOwner],
						IShare::TYPE_GROUP => [$file1GroupShareOwner],
						IShare::TYPE_LINK => [$file1LinkShareOwner],
						IShare::TYPE_EMAIL => [$file1EmailShareOwner],
						IShare::TYPE_CIRCLE => [$file1CircleShareOwner],
						IShare::TYPE_ROOM => [$file1RoomShareOwner],
						IShare::TYPE_REMOTE => [$file1RemoteShareOwner],
						IShare::TYPE_REMOTE_GROUP => [$file1RemoteGroupShareOwner],
					],
				],
				[
				],
				[
					$file1UserShareOwnerExpected,
					$file1GroupShareOwnerExpected,
					$file1LinkShareOwnerExpected,
					$file1EmailShareOwnerExpected,
					$file1CircleShareOwnerExpected,
					$file1RoomShareOwnerExpected,
				]
			],
			[
				[
					'node' => $folder,
					'subfiles' => 'true',
				],
				[
					'file1' => [
						IShare::TYPE_USER => [$file1UserShareOwner],
						IShare::TYPE_GROUP => [$file1GroupShareOwner],
						IShare::TYPE_LINK => [$file1LinkShareOwner],
						IShare::TYPE_EMAIL => [$file1EmailShareOwner],
						IShare::TYPE_CIRCLE => [$file1CircleShareOwner],
						IShare::TYPE_ROOM => [$file1RoomShareOwner],
						IShare::TYPE_REMOTE => [$file1RemoteShareOwner],
						IShare::TYPE_REMOTE_GROUP => [$file1RemoteGroupShareOwner],
					],
				],
				[
					IShare::TYPE_REMOTE => true,
					IShare::TYPE_REMOTE_GROUP => true,
				],
				[
					$file1UserShareOwnerExpected,
					$file1GroupShareOwnerExpected,
					$file1LinkShareOwnerExpected,
					$file1EmailShareOwnerExpected,
					$file1CircleShareOwnerExpected,
					$file1RoomShareOwnerExpected,
					$file1RemoteShareOwnerExpected,
					$file1RemoteGroupShareOwnerExpected,
				]
			],
		];

		return $data;
	}

	private function mockSimpleNode(string $class, array $methods): MockObject {
		$node = $this->createMock($class);
		foreach ($methods as $method => $return) {
			if ($method === 'getDirectoryListing') {
				$return = array_map(
					fn ($nodeParams) => $this->mockSimpleNode(...$nodeParams),
					$return
				);
			}
			$node->method($method)->willReturn($return);
		}
		return $node;
	}

	#[DataProvider(methodName: 'dataGetShares')]
	public function testGetShares(array $getSharesParameters, array $shares, array $extraShareTypes, array $expected): void {
		$shares = array_map(
			fn ($sharesByType) => array_map(
				fn ($shareList) => array_map(
					function (array $shareParams): IShare {
						$share = Server::get(IManager::class)->newShare();
						$share->setShareType($shareParams['type'])
							->setSharedBy($shareParams['sharedBy'])
							->setShareOwner($shareParams['owner'])
							->setPermissions(Constants::PERMISSION_READ)
							->setId($shareParams['id']);
						if (isset($shareParams['sharedWith'])) {
							$share->setSharedWith($shareParams['sharedWith']);
						}
						if (isset($shareParams['sharedWithDisplayName'])) {
							$share->setSharedWithDisplayName($shareParams['sharedWithDisplayName']);
						}
						if (isset($shareParams['sharedWithAvatar'])) {
							$share->setSharedWithAvatar($shareParams['sharedWithAvatar']);
						}
						if (isset($shareParams['attributes'])) {
							$shareAttributes = $this->createMock(IShareAttributes::class);
							$shareAttributes->method('toArray')->willReturn($shareParams['attributes']);
							$shareAttributes->method('getAttribute')->with('permissions', 'download')->willReturn(true);
							$share->setAttributes($shareAttributes);

							$expects['attributes'] = \json_encode($shareParams['attributes']);
						}
						if (isset($shareParams['node'])) {
							$node = $this->mockSimpleNode(...$shareParams['node']);
							$share->setNode($node);
						}
						if (isset($shareParams['note'])) {
							$share->setNote($shareParams['note']);
						}
						if (isset($shareParams['expirationDate'])) {
							$share->setExpirationDate($shareParams['expirationDate']);
						}
						if (isset($shareParams['token'])) {
							$share->setToken($shareParams['token']);
						}
						if (isset($shareParams['label'])) {
							$share->setLabel($shareParams['label']);
						}
						if (isset($shareParams['password'])) {
							$share->setPassword($shareParams['password']);
						}
						if (isset($shareParams['sendPasswordByTalk'])) {
							$share->setSendPasswordByTalk($shareParams['sendPasswordByTalk']);
						}
						return $share;
					},
					$shareList
				),
				$sharesByType
			),
			$shares
		);

		/** @var ShareAPIController&MockObject $ocs */
		$ocs = $this->getMockBuilder(ShareAPIController::class)
			->setConstructorArgs([
				$this->appName,
				$this->request,
				$this->shareManager,
				$this->groupManager,
				$this->userManager,
				$this->rootFolder,
				$this->urlGenerator,
				$this->l,
				$this->config,
				$this->appConfig,
				$this->appManager,
				$this->serverContainer,
				$this->userStatusManager,
				$this->previewManager,
				$this->dateTimeZone,
				$this->logger,
				$this->factory,
				$this->mailer,
				$this->tagManager,
				$this->getEmailValidatorWithStrictEmailCheck(),
				$this->trustedServers,
				$this->currentUser,
			])
			->onlyMethods(['formatShare'])
			->getMock();

		$ocs->method('formatShare')
			->willReturnCallback(
				function ($share) {
					return [
						'id' => $share->getId(),
						'share_type' => $share->getShareType()
					];
				}
			);

		$userFolder = $this->getMockBuilder(Folder::class)->getMock();
		$userFolder->method('get')
			->with('path')
			->willReturn($this->mockSimpleNode(...$getSharesParameters['node']));

		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$this->shareManager
			->method('getSharesBy')
			->willReturnCallback(
				function ($user, $shareType, $node) use ($shares) {
					if (!isset($shares[$node->getName()]) || !isset($shares[$node->getName()][$shareType])) {
						return [];
					}
					return $shares[$node->getName()][$shareType];
				}
			);

		$this->shareManager
			->method('outgoingServer2ServerSharesAllowed')
			->willReturn($extraShareTypes[ISHARE::TYPE_REMOTE] ?? false);

		$this->shareManager
			->method('outgoingServer2ServerGroupSharesAllowed')
			->willReturn($extraShareTypes[ISHARE::TYPE_REMOTE_GROUP] ?? false);

		$this->groupManager
			->method('isInGroup')
			->willReturnCallback(
				function ($user, $group) {
					return $group === 'currentUserGroup';
				}
			);

		$result = $ocs->getShares(
			$getSharesParameters['sharedWithMe'] ?? 'false',
			$getSharesParameters['reshares'] ?? 'false',
			$getSharesParameters['subfiles'] ?? 'false',
			'path'
		);

		$this->assertEquals($expected, $result->getData());
	}

	public function testCanAccessShareAsOwner(): void {
		$share = $this->createMock(IShare::class);
		$share->method('getShareOwner')->willReturn($this->currentUser);
		$this->assertTrue($this->invokePrivate($this->ocs, 'canAccessShare', [$share]));
	}

	public function testCanAccessShareAsSharer(): void {
		$share = $this->createMock(IShare::class);
		$share->method('getSharedBy')->willReturn($this->currentUser);
		$this->assertTrue($this->invokePrivate($this->ocs, 'canAccessShare', [$share]));
	}

	public function testCanAccessShareAsSharee(): void {
		$share = $this->createMock(IShare::class);
		$share->method('getShareType')->willReturn(IShare::TYPE_USER);
		$share->method('getSharedWith')->willReturn($this->currentUser);
		$this->assertTrue($this->invokePrivate($this->ocs, 'canAccessShare', [$share]));
	}

	public function testCannotAccessLinkShare(): void {
		$share = $this->createMock(IShare::class);
		$share->method('getShareType')->willReturn(IShare::TYPE_LINK);
		$share->method('getNodeId')->willReturn(42);

		$userFolder = $this->createMock(Folder::class);
		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$this->assertFalse($this->invokePrivate($this->ocs, 'canAccessShare', [$share]));
	}

	#[DataProvider(methodName: 'dataCanAccessShareWithPermissions')]
	public function testCanAccessShareWithPermissions(int $permissions, bool $expected): void {
		$share = $this->createMock(IShare::class);
		$share->method('getShareType')->willReturn(IShare::TYPE_USER);
		$share->method('getSharedWith')->willReturn($this->createMock(IUser::class));
		$share->method('getNodeId')->willReturn(42);

		$file = $this->createMock(File::class);

		$userFolder = $this->getMockBuilder(Folder::class)->getMock();
		$userFolder->method('getFirstNodeById')
			->with($share->getNodeId())
			->willReturn($file);
		$userFolder->method('getById')
			->with($share->getNodeId())
			->willReturn([$file]);
		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$file->method('getPermissions')
			->willReturn($permissions);

		if ($expected) {
			$this->assertTrue($this->invokePrivate($this->ocs, 'canAccessShare', [$share]));
		} else {
			$this->assertFalse($this->invokePrivate($this->ocs, 'canAccessShare', [$share]));
		}
	}

	public static function dataCanAccessShareWithPermissions(): array {
		return [
			[Constants::PERMISSION_SHARE, true],
			[Constants::PERMISSION_READ, false],
			[Constants::PERMISSION_READ | Constants::PERMISSION_SHARE, true],
		];
	}

	#[DataProvider(methodName: 'dataCanAccessShareAsGroupMember')]
	public function testCanAccessShareAsGroupMember(string $group, bool $expected): void {
		$share = $this->createMock(IShare::class);
		$share->method('getShareType')->willReturn(IShare::TYPE_GROUP);
		$share->method('getSharedWith')->willReturn($group);
		$share->method('getNodeId')->willReturn(42);

		$file = $this->createMock(File::class);

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getFirstNodeById')
			->with($share->getNodeId())
			->willReturn($file);
		$userFolder->method('getById')
			->with($share->getNodeId())
			->willReturn([$file]);
		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$user = $this->createMock(IUser::class);
		$this->userManager->method('get')
			->with($this->currentUser)
			->willReturn($user);

		$group = $this->createMock(IGroup::class);
		$group->method('inGroup')->with($user)->willReturn(true);
		$group2 = $this->createMock(IGroup::class);
		$group2->method('inGroup')->with($user)->willReturn(false);

		$this->groupManager->method('get')->willReturnMap([
			['group', $group],
			['group2', $group2],
			['group-null', null],
		]);

		if ($expected) {
			$this->assertTrue($this->invokePrivate($this->ocs, 'canAccessShare', [$share]));
		} else {
			$this->assertFalse($this->invokePrivate($this->ocs, 'canAccessShare', [$share]));
		}
	}

	public static function dataCanAccessShareAsGroupMember(): array {
		return [
			['group', true],
			['group2', false],
			['group-null', false],
		];
	}

	public static function dataCanAccessRoomShare(): array {
		return [
			[false, false, false],
			[false, false, true],
			[true, true, true],
			[false, true, false],
		];
	}

	#[DataProvider(methodName: 'dataCanAccessRoomShare')]
	public function testCanAccessRoomShare(
		bool $expected,
		bool $helperAvailable,
		bool $canAccessShareByHelper,
	): void {
		$share = $this->createMock(IShare::class);
		$share->method('getShareType')->willReturn(IShare::TYPE_ROOM);
		$share->method('getSharedWith')->willReturn('recipientRoom');

		$userFolder = $this->getMockBuilder(Folder::class)->getMock();
		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$userFolder->method('getById')
			->with($share->getNodeId())
			->willReturn([$share->getNode()]);

		if (!$helperAvailable) {
			$this->appManager->method('isEnabledForUser')
				->with('spreed')
				->willReturn(false);
		} else {
			$this->appManager->method('isEnabledForUser')
				->with('spreed')
				->willReturn(true);

			// This is not possible anymore with PHPUnit 10+
			// as `setMethods` was removed and now real reflection is used, thus the class needs to exist.
			// $helper = $this->getMockBuilder('\OCA\Talk\Share\Helper\ShareAPIController')
			$helper = $this->getMockBuilder(\stdClass::class)
				->addMethods(['canAccessShare'])
				->getMock();
			$helper->method('canAccessShare')
				->with($share, $this->currentUser)
				->willReturn($canAccessShareByHelper);

			$this->serverContainer->method('get')
				->with('\OCA\Talk\Share\Helper\ShareAPIController')
				->willReturn($helper);
		}

		$this->assertEquals($expected, $this->invokePrivate($this->ocs, 'canAccessShare', [$share]));
	}


	public function testCreateShareNoPath(): void {
		$this->expectException(OCSNotFoundException::class);
		$this->expectExceptionMessage('Please specify a file or folder path');

		$this->ocs->createShare();
	}


	public function testCreateShareInvalidPath(): void {
		$this->expectException(OCSNotFoundException::class);
		$this->expectExceptionMessage('Wrong path, file/folder does not exist');

		$userFolder = $this->getMockBuilder(Folder::class)->getMock();
		$this->rootFolder->expects($this->once())
			->method('getUserFolder')
			->with('currentUser')
			->willReturn($userFolder);

		$userFolder->expects($this->once())
			->method('get')
			->with('invalid-path')
			->willThrowException(new NotFoundException());

		$this->ocs->createShare('invalid-path');
	}

	public function testCreateShareInvalidShareType(): void {
		$this->expectException(OCSBadRequestException::class);
		$this->expectExceptionMessage('Unknown share type');

		$share = $this->newShare();
		$this->shareManager->method('newShare')->willReturn($share);

		[$userFolder, $file] = $this->getNonSharedUserFile();
		$this->rootFolder->expects($this->atLeastOnce())
			->method('getUserFolder')
			->with('currentUser')
			->willReturn($userFolder);

		$userFolder->expects($this->atLeastOnce())
			->method('get')
			->with('valid-path')
			->willReturn($file);
		$userFolder->method('getById')
			->willReturn([]);

		$file->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$this->ocs->createShare('valid-path', 31);
	}

	public function testCreateShareUserNoShareWith(): void {
		$this->expectException(OCSNotFoundException::class);
		$this->expectExceptionMessage('Please specify a valid account to share with');

		$share = $this->newShare();
		$this->shareManager->method('newShare')->willReturn($share);

		[$userFolder, $path] = $this->getNonSharedUserFile();
		$this->rootFolder->method('getUserFolder')
			->with('currentUser')
			->willReturn($userFolder);

		$userFolder->expects($this->once())
			->method('get')
			->with('valid-path')
			->willReturn($path);
		$userFolder->method('getById')
			->willReturn([]);

		$path->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$this->ocs->createShare('valid-path', Constants::PERMISSION_ALL, IShare::TYPE_USER);
	}


	public function testCreateShareUserNoValidShareWith(): void {
		$this->expectException(OCSNotFoundException::class);
		$this->expectExceptionMessage('Please specify a valid account to share with');

		$share = $this->newShare();
		$this->shareManager->method('newShare')->willReturn($share);

		[$userFolder, $path] = $this->getNonSharedUserFile();
		$this->rootFolder->method('getUserFolder')
			->with('currentUser')
			->willReturn($userFolder);

		$userFolder->expects($this->once())
			->method('get')
			->with('valid-path')
			->willReturn($path);
		$userFolder->method('getById')
			->willReturn([]);
		$path->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);
		$this->userManager->method('userExists')
			->with('invalidUser')
			->willReturn(false);

		$this->ocs->createShare('valid-path', Constants::PERMISSION_ALL, IShare::TYPE_USER, 'invalidUser');
	}

	public function testCreateShareUser(): void {
		$share = $this->newShare();
		$this->shareManager->method('newShare')->willReturn($share);

		/** @var ShareAPIController $ocs */
		$ocs = $this->getMockBuilder(ShareAPIController::class)
			->setConstructorArgs([
				$this->appName,
				$this->request,
				$this->shareManager,
				$this->groupManager,
				$this->userManager,
				$this->rootFolder,
				$this->urlGenerator,
				$this->l,
				$this->config,
				$this->appConfig,
				$this->appManager,
				$this->serverContainer,
				$this->userStatusManager,
				$this->previewManager,
				$this->dateTimeZone,
				$this->logger,
				$this->factory,
				$this->mailer,
				$this->tagManager,
				$this->getEmailValidatorWithStrictEmailCheck(),
				$this->trustedServers,
				$this->currentUser,
			])->onlyMethods(['formatShare'])
			->getMock();

		[$userFolder, $path] = $this->getNonSharedUserFile();
		$this->rootFolder->expects($this->exactly(2))
			->method('getUserFolder')
			->with('currentUser')
			->willReturn($userFolder);

		$userFolder->expects($this->once())
			->method('get')
			->with('valid-path')
			->willReturn($path);
		$userFolder->method('getById')
			->willReturn([]);

		$this->userManager->method('userExists')->with('validUser')->willReturn(true);

		$path->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$this->shareManager->method('createShare')
			->with($this->callback(function (IShare $share) use ($path) {
				return $share->getNode() === $path
					&& $share->getPermissions() === (
						Constants::PERMISSION_ALL
						& ~Constants::PERMISSION_DELETE
						& ~Constants::PERMISSION_CREATE
					)
					&& $share->getShareType() === IShare::TYPE_USER
					&& $share->getSharedWith() === 'validUser'
					&& $share->getSharedBy() === 'currentUser';
			}))
			->willReturnArgument(0);

		$expected = new DataResponse([]);
		$result = $ocs->createShare('valid-path', Constants::PERMISSION_ALL, IShare::TYPE_USER, 'validUser');

		$this->assertInstanceOf(get_class($expected), $result);
		$this->assertEquals($expected->getData(), $result->getData());
	}


	public function testCreateShareGroupNoValidShareWith(): void {
		$this->expectException(OCSNotFoundException::class);
		$this->expectExceptionMessage('Please specify a valid group');

		$share = $this->newShare();
		$this->shareManager->method('newShare')->willReturn($share);
		$this->shareManager->method('createShare')->willReturnArgument(0);
		$this->shareManager->method('allowGroupSharing')->willReturn(true);

		[$userFolder, $path] = $this->getNonSharedUserFile();
		$this->rootFolder->method('getUserFolder')
			->with('currentUser')
			->willReturn($userFolder);

		$userFolder->expects($this->once())
			->method('get')
			->with('valid-path')
			->willReturn($path);
		$userFolder->method('getById')
			->willReturn([]);

		$path->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$this->ocs->createShare('valid-path', Constants::PERMISSION_ALL, IShare::TYPE_GROUP, 'invalidGroup');
	}

	public function testCreateShareGroup(): void {
		$share = $this->newShare();
		$this->shareManager->method('newShare')->willReturn($share);

		/** @var ShareAPIController&MockObject $ocs */
		$ocs = $this->getMockBuilder(ShareAPIController::class)
			->setConstructorArgs([
				$this->appName,
				$this->request,
				$this->shareManager,
				$this->groupManager,
				$this->userManager,
				$this->rootFolder,
				$this->urlGenerator,
				$this->l,
				$this->config,
				$this->appConfig,
				$this->appManager,
				$this->serverContainer,
				$this->userStatusManager,
				$this->previewManager,
				$this->dateTimeZone,
				$this->logger,
				$this->factory,
				$this->mailer,
				$this->tagManager,
				$this->getEmailValidatorWithStrictEmailCheck(),
				$this->trustedServers,
				$this->currentUser,
			])->onlyMethods(['formatShare'])
			->getMock();

		$this->request
			->method('getParam')
			->willReturnMap([
				['path', null, 'valid-path'],
				['permissions', null, Constants::PERMISSION_ALL],
				['shareType', '-1', IShare::TYPE_GROUP],
				['shareWith', null, 'validGroup'],
			]);

		[$userFolder, $path] = $this->getNonSharedUserFolder();
		$this->rootFolder->expects($this->exactly(2))
			->method('getUserFolder')
			->with('currentUser')
			->willReturn($userFolder);

		$userFolder->expects($this->once())
			->method('get')
			->with('valid-path')
			->willReturn($path);
		$userFolder->method('getById')
			->willReturn([]);

		$this->groupManager->method('groupExists')->with('validGroup')->willReturn(true);

		$this->shareManager->expects($this->once())
			->method('allowGroupSharing')
			->willReturn(true);

		$path->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$this->shareManager->method('createShare')
			->with($this->callback(function (IShare $share) use ($path) {
				return $share->getNode() === $path
				&& $share->getPermissions() === Constants::PERMISSION_ALL
				&& $share->getShareType() === IShare::TYPE_GROUP
				&& $share->getSharedWith() === 'validGroup'
				&& $share->getSharedBy() === 'currentUser';
			}))
			->willReturnArgument(0);

		$expected = new DataResponse([]);
		$result = $ocs->createShare('valid-path', Constants::PERMISSION_ALL, IShare::TYPE_GROUP, 'validGroup');

		$this->assertInstanceOf(get_class($expected), $result);
		$this->assertEquals($expected->getData(), $result->getData());
	}


	public function testCreateShareGroupNotAllowed(): void {
		$this->expectException(OCSNotFoundException::class);
		$this->expectExceptionMessage('Group sharing is disabled by the administrator');

		$share = $this->newShare();
		$this->shareManager->method('newShare')->willReturn($share);

		[$userFolder, $path] = $this->getNonSharedUserFolder();
		$this->rootFolder->method('getUserFolder')
			->with('currentUser')
			->willReturn($userFolder);

		$userFolder->expects($this->once())
			->method('get')
			->with('valid-path')
			->willReturn($path);
		$userFolder->method('getById')
			->willReturn([]);

		$this->groupManager->method('groupExists')->with('validGroup')->willReturn(true);

		$this->shareManager->expects($this->once())
			->method('allowGroupSharing')
			->willReturn(false);

		$this->ocs->createShare('valid-path', Constants::PERMISSION_ALL, IShare::TYPE_GROUP, 'invalidGroup');
	}


	public function testCreateShareLinkNoLinksAllowed(): void {
		$this->expectException(OCSNotFoundException::class);
		$this->expectExceptionMessage('Public link sharing is disabled by the administrator');

		$this->request
			->method('getParam')
			->willReturnMap([
				['path', null, 'valid-path'],
				['shareType', '-1', IShare::TYPE_LINK],
			]);

		$path = $this->getMockBuilder(Folder::class)->getMock();
		$path->method('getId')->willReturn(42);
		$storage = $this->createMock(IStorage::class);
		$storage->method('instanceOfStorage')
			->willReturnMap([
				['OCA\Files_Sharing\External\Storage', false],
				['OCA\Files_Sharing\SharedStorage', false],
			]);
		$path->method('getStorage')->willReturn($storage);
		$this->rootFolder->method('getUserFolder')->with($this->currentUser)->willReturnSelf();
		$this->rootFolder->method('get')->with('valid-path')->willReturn($path);
		$this->rootFolder->method('getById')
			->willReturn([]);

		$this->shareManager->method('newShare')->willReturn(Server::get(IManager::class)->newShare());
		$this->shareManager->method('shareApiLinkAllowPublicUpload')->willReturn(true);
		$this->shareManager->method('shareApiAllowLinks')->willReturn(false);

		$this->ocs->createShare('valid-path', Constants::PERMISSION_ALL, IShare::TYPE_LINK);
	}


	public function testCreateShareLinkNoPublicUpload(): void {
		$this->expectException(OCSForbiddenException::class);
		$this->expectExceptionMessage('Public upload disabled by the administrator');

		$path = $this->getMockBuilder(Folder::class)->getMock();
		$path->method('getId')->willReturn(42);
		$storage = $this->createMock(IStorage::class);
		$storage->method('instanceOfStorage')
			->willReturnMap([
				['OCA\Files_Sharing\External\Storage', false],
				['OCA\Files_Sharing\SharedStorage', false],
			]);
		$path->method('getStorage')->willReturn($storage);
		$this->rootFolder->method('getUserFolder')->with($this->currentUser)->willReturnSelf();
		$this->rootFolder->method('get')->with('valid-path')->willReturn($path);
		$this->rootFolder->method('getById')
			->willReturn([]);

		$this->shareManager->method('newShare')->willReturn(Server::get(IManager::class)->newShare());
		$this->shareManager->method('shareApiAllowLinks')->willReturn(true);

		$this->ocs->createShare('valid-path', Constants::PERMISSION_ALL, IShare::TYPE_LINK, null, 'true');
	}


	public function testCreateShareLinkPublicUploadFile(): void {
		$this->expectException(OCSBadRequestException::class);
		$this->expectExceptionMessage('Public upload is only possible for publicly shared folders');

		$storage = $this->createMock(IStorage::class);
		$storage->method('instanceOfStorage')
			->willReturnMap([
				['OCA\Files_Sharing\External\Storage', false],
				['OCA\Files_Sharing\SharedStorage', false],
			]);

		$file = $this->createMock(File::class);
		$file->method('getId')->willReturn(42);
		$file->method('getStorage')->willReturn($storage);

		$this->rootFolder->method('getUserFolder')->with($this->currentUser)->willReturnSelf();
		$this->rootFolder->method('get')->with('valid-path')->willReturn($file);
		$this->rootFolder->method('getById')
			->willReturn([]);

		$this->shareManager->method('newShare')->willReturn(Server::get(IManager::class)->newShare());
		$this->shareManager->method('shareApiAllowLinks')->willReturn(true);
		$this->shareManager->method('shareApiLinkAllowPublicUpload')->willReturn(true);

		$this->ocs->createShare('valid-path', Constants::PERMISSION_ALL, IShare::TYPE_LINK, null, 'true');
	}

	public function testCreateShareLinkPublicUploadFolder(): void {
		$ocs = $this->mockFormatShare();

		$path = $this->getMockBuilder(Folder::class)->getMock();
		$path->method('getId')->willReturn(1);
		$storage = $this->createMock(IStorage::class);
		$storage->method('instanceOfStorage')
			->willReturnMap([
				['OCA\Files_Sharing\External\Storage', false],
				['OCA\Files_Sharing\SharedStorage', false],
			]);
		$path->method('getStorage')->willReturn($storage);
		$this->rootFolder->method('getUserFolder')->with($this->currentUser)->willReturnSelf();
		$this->rootFolder->method('get')->with('valid-path')->willReturn($path);
		$this->rootFolder->method('getById')
			->willReturn([]);

		$this->shareManager->method('newShare')->willReturn(Server::get(IManager::class)->newShare());
		$this->shareManager->method('shareApiAllowLinks')->willReturn(true);
		$this->shareManager->method('shareApiLinkAllowPublicUpload')->willReturn(true);

		$this->shareManager->expects($this->once())->method('createShare')->with(
			$this->callback(function (IShare $share) use ($path) {
				return $share->getNode() === $path
					&& $share->getShareType() === IShare::TYPE_LINK
					&& $share->getPermissions() === (Constants::PERMISSION_READ | Constants::PERMISSION_CREATE | Constants::PERMISSION_UPDATE | Constants::PERMISSION_DELETE)
					&& $share->getSharedBy() === 'currentUser'
					&& $share->getPassword() === null
					&& $share->getExpirationDate() === null;
			})
		)->willReturnArgument(0);

		$expected = new DataResponse([]);
		$result = $ocs->createShare('valid-path', Constants::PERMISSION_ALL, IShare::TYPE_LINK, null, 'true', '', null, '');

		$this->assertInstanceOf(get_class($expected), $result);
		$this->assertEquals($expected->getData(), $result->getData());
	}

	public function testCreateShareLinkPassword(): void {
		$ocs = $this->mockFormatShare();

		$path = $this->getMockBuilder(Folder::class)->getMock();
		$path->method('getId')->willReturn(42);
		$storage = $this->createMock(IStorage::class);
		$storage->method('instanceOfStorage')
			->willReturnMap([
				['OCA\Files_Sharing\External\Storage', false],
				['OCA\Files_Sharing\SharedStorage', false],
			]);
		$path->method('getStorage')->willReturn($storage);
		$this->rootFolder->method('getUserFolder')->with($this->currentUser)->willReturnSelf();
		$this->rootFolder->method('get')->with('valid-path')->willReturn($path);
		$this->rootFolder->method('getById')
			->willReturn([]);

		$this->shareManager->method('newShare')->willReturn(Server::get(IManager::class)->newShare());
		$this->shareManager->method('shareApiAllowLinks')->willReturn(true);
		$this->shareManager->method('shareApiLinkAllowPublicUpload')->willReturn(true);

		$this->shareManager->expects($this->once())->method('createShare')->with(
			$this->callback(function (IShare $share) use ($path) {
				return $share->getNode() === $path
				&& $share->getShareType() === IShare::TYPE_LINK
				&& $share->getPermissions() === Constants::PERMISSION_READ // publicUpload was set to false
				&& $share->getSharedBy() === 'currentUser'
				&& $share->getPassword() === 'password'
				&& $share->getExpirationDate() === null;
			})
		)->willReturnArgument(0);

		$expected = new DataResponse([]);
		$result = $ocs->createShare('valid-path', Constants::PERMISSION_READ, IShare::TYPE_LINK, null, 'false', 'password', null, '');

		$this->assertInstanceOf(get_class($expected), $result);
		$this->assertEquals($expected->getData(), $result->getData());
	}

	public function testCreateShareLinkSendPasswordByTalk(): void {
		$ocs = $this->mockFormatShare();

		$path = $this->getMockBuilder(Folder::class)->getMock();
		$path->method('getId')->willReturn(42);
		$storage = $this->createMock(IStorage::class);
		$storage->method('instanceOfStorage')
			->willReturnMap([
				['OCA\Files_Sharing\External\Storage', false],
				['OCA\Files_Sharing\SharedStorage', false],
			]);
		$path->method('getStorage')->willReturn($storage);
		$this->rootFolder->method('getUserFolder')->with($this->currentUser)->willReturnSelf();
		$this->rootFolder->method('get')->with('valid-path')->willReturn($path);
		$this->rootFolder->method('getById')
			->willReturn([]);

		$this->shareManager->method('newShare')->willReturn(Server::get(IManager::class)->newShare());
		$this->shareManager->method('shareApiAllowLinks')->willReturn(true);
		$this->shareManager->method('shareApiLinkAllowPublicUpload')->willReturn(true);

		$this->appManager->method('isEnabledForUser')->with('spreed')->willReturn(true);

		$this->shareManager->expects($this->once())->method('createShare')->with(
			$this->callback(function (IShare $share) use ($path) {
				return $share->getNode() === $path
				&& $share->getShareType() === IShare::TYPE_LINK
				&& $share->getPermissions() === (Constants::PERMISSION_ALL & ~(Constants::PERMISSION_SHARE))
				&& $share->getSharedBy() === 'currentUser'
				&& $share->getPassword() === 'password'
				&& $share->getSendPasswordByTalk() === true
				&& $share->getExpirationDate() === null;
			})
		)->willReturnArgument(0);

		$expected = new DataResponse([]);
		$result = $ocs->createShare('valid-path', Constants::PERMISSION_ALL, IShare::TYPE_LINK, null, 'true', 'password', 'true', '');

		$this->assertInstanceOf(get_class($expected), $result);
		$this->assertEquals($expected->getData(), $result->getData());
	}


	public function testCreateShareLinkSendPasswordByTalkWithTalkDisabled(): void {
		$this->expectException(OCSForbiddenException::class);
		$this->expectExceptionMessage('Sharing valid-path sending the password by Nextcloud Talk failed because Nextcloud Talk is not enabled');

		$ocs = $this->mockFormatShare();

		$path = $this->getMockBuilder(Folder::class)->getMock();
		$path->method('getId')->willReturn(42);
		$storage = $this->createMock(IStorage::class);
		$storage->method('instanceOfStorage')
			->willReturnMap([
				['OCA\Files_Sharing\External\Storage', false],
				['OCA\Files_Sharing\SharedStorage', false],
			]);
		$path->method('getStorage')->willReturn($storage);
		$path->method('getPath')->willReturn('valid-path');
		$this->rootFolder->method('getUserFolder')->with($this->currentUser)->willReturnSelf();
		$this->rootFolder->method('get')->with('valid-path')->willReturn($path);
		$this->rootFolder->method('getById')
			->willReturn([]);

		$this->shareManager->method('newShare')->willReturn(Server::get(IManager::class)->newShare());
		$this->shareManager->method('shareApiAllowLinks')->willReturn(true);
		$this->shareManager->method('shareApiLinkAllowPublicUpload')->willReturn(true);

		$this->appManager->method('isEnabledForUser')->with('spreed')->willReturn(false);

		$this->shareManager->expects($this->never())->method('createShare');

		$ocs->createShare('valid-path', Constants::PERMISSION_ALL, IShare::TYPE_LINK, null, 'false', 'password', 'true', '');
	}

	public function testCreateShareValidExpireDate(): void {
		$ocs = $this->mockFormatShare();

		$this->request
			->method('getParam')
			->willReturnMap([
				['path', null, 'valid-path'],
				['shareType', '-1', IShare::TYPE_LINK],
				['publicUpload', null, 'false'],
				['expireDate', '', '2000-01-01'],
				['password', '', ''],
			]);

		$path = $this->getMockBuilder(Folder::class)->getMock();
		$path->method('getId')->willReturn(42);
		$storage = $this->createMock(IStorage::class);
		$storage->method('instanceOfStorage')
			->willReturnMap([
				['OCA\Files_Sharing\External\Storage', false],
				['OCA\Files_Sharing\SharedStorage', false],
			]);
		$path->method('getStorage')->willReturn($storage);
		$this->rootFolder->method('getUserFolder')->with($this->currentUser)->willReturnSelf();
		$this->rootFolder->method('get')->with('valid-path')->willReturn($path);
		$this->rootFolder->method('getById')
			->willReturn([]);

		$this->shareManager->method('newShare')->willReturn(Server::get(IManager::class)->newShare());
		$this->shareManager->method('shareApiAllowLinks')->willReturn(true);
		$this->shareManager->method('shareApiLinkAllowPublicUpload')->willReturn(true);

		$this->shareManager->expects($this->once())->method('createShare')->with(
			$this->callback(function (IShare $share) use ($path) {
				$date = new \DateTime('2000-01-01');
				$date->setTime(0, 0, 0);

				return $share->getNode() === $path
				&& $share->getShareType() === IShare::TYPE_LINK
				&& $share->getPermissions() === Constants::PERMISSION_READ | Constants::PERMISSION_SHARE
				&& $share->getSharedBy() === 'currentUser'
				&& $share->getPassword() === null
				&& $share->getExpirationDate() == $date;
			})
		)->willReturnArgument(0);

		$expected = new DataResponse([]);
		$result = $ocs->createShare('valid-path', null, IShare::TYPE_LINK, null, 'false', '', null, '2000-01-01');

		$this->assertInstanceOf(get_class($expected), $result);
		$this->assertEquals($expected->getData(), $result->getData());
	}


	public function testCreateShareInvalidExpireDate(): void {
		$this->expectException(OCSNotFoundException::class);
		$this->expectExceptionMessage('Invalid date. Format must be YYYY-MM-DD');

		$ocs = $this->mockFormatShare();

		$path = $this->getMockBuilder(Folder::class)->getMock();
		$path->method('getId')->willReturn(42);
		$storage = $this->createMock(IStorage::class);
		$storage->method('instanceOfStorage')
			->willReturnMap([
				['OCA\Files_Sharing\External\Storage', false],
				['OCA\Files_Sharing\SharedStorage', false],
			]);
		$path->method('getStorage')->willReturn($storage);
		$this->rootFolder->method('getUserFolder')->with($this->currentUser)->willReturnSelf();
		$this->rootFolder->method('get')->with('valid-path')->willReturn($path);
		$this->rootFolder->method('getById')
			->willReturn([]);

		$this->shareManager->method('newShare')->willReturn(Server::get(IManager::class)->newShare());
		$this->shareManager->method('shareApiAllowLinks')->willReturn(true);
		$this->shareManager->method('shareApiLinkAllowPublicUpload')->willReturn(true);

		$ocs->createShare('valid-path', Constants::PERMISSION_ALL, IShare::TYPE_LINK, null, 'false', '', null, 'a1b2d3');
	}

	public function testCreateShareRemote(): void {
		$share = $this->newShare();
		$this->shareManager->method('newShare')->willReturn($share);

		/** @var ShareAPIController $ocs */
		$ocs = $this->getMockBuilder(ShareAPIController::class)
			->setConstructorArgs([
				$this->appName,
				$this->request,
				$this->shareManager,
				$this->groupManager,
				$this->userManager,
				$this->rootFolder,
				$this->urlGenerator,
				$this->l,
				$this->config,
				$this->appConfig,
				$this->appManager,
				$this->serverContainer,
				$this->userStatusManager,
				$this->previewManager,
				$this->dateTimeZone,
				$this->logger,
				$this->factory,
				$this->mailer,
				$this->tagManager,
				$this->getEmailValidatorWithStrictEmailCheck(),
				$this->trustedServers,
				$this->currentUser,
			])->onlyMethods(['formatShare'])
			->getMock();

		[$userFolder, $path] = $this->getNonSharedUserFile();
		$this->rootFolder->expects($this->exactly(2))
			->method('getUserFolder')
			->with('currentUser')
			->willReturn($userFolder);

		$userFolder->expects($this->once())
			->method('get')
			->with('valid-path')
			->willReturn($path);
		$userFolder->method('getById')
			->willReturn([]);

		$this->userManager->method('userExists')->with('validUser')->willReturn(true);

		$path->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$this->shareManager->method('createShare')
			->with($this->callback(function (IShare $share) use ($path) {
				return $share->getNode() === $path
					&& $share->getPermissions() === (
						Constants::PERMISSION_ALL
						& ~Constants::PERMISSION_DELETE
						& ~Constants::PERMISSION_CREATE
					)
					&& $share->getShareType() === IShare::TYPE_REMOTE
					&& $share->getSharedWith() === 'user@example.org'
					&& $share->getSharedBy() === 'currentUser';
			}))
			->willReturnArgument(0);

		$this->shareManager->method('outgoingServer2ServerSharesAllowed')->willReturn(true);

		$expected = new DataResponse([]);
		$result = $ocs->createShare('valid-path', Constants::PERMISSION_ALL, IShare::TYPE_REMOTE, 'user@example.org');

		$this->assertInstanceOf(get_class($expected), $result);
		$this->assertEquals($expected->getData(), $result->getData());
	}

	public function testCreateShareRemoteGroup(): void {
		$share = $this->newShare();
		$this->shareManager->method('newShare')->willReturn($share);

		/** @var ShareAPIController $ocs */
		$ocs = $this->getMockBuilder(ShareAPIController::class)
			->setConstructorArgs([
				$this->appName,
				$this->request,
				$this->shareManager,
				$this->groupManager,
				$this->userManager,
				$this->rootFolder,
				$this->urlGenerator,
				$this->l,
				$this->config,
				$this->appConfig,
				$this->appManager,
				$this->serverContainer,
				$this->userStatusManager,
				$this->previewManager,
				$this->dateTimeZone,
				$this->logger,
				$this->factory,
				$this->mailer,
				$this->tagManager,
				$this->getEmailValidatorWithStrictEmailCheck(),
				$this->trustedServers,
				$this->currentUser,
			])->onlyMethods(['formatShare'])
			->getMock();

		[$userFolder, $path] = $this->getNonSharedUserFile();
		$this->rootFolder->expects($this->exactly(2))
			->method('getUserFolder')
			->with('currentUser')
			->willReturn($userFolder);

		$userFolder->expects($this->once())
			->method('get')
			->with('valid-path')
			->willReturn($path);
		$userFolder->method('getById')
			->willReturn([]);

		$this->userManager->method('userExists')->with('validUser')->willReturn(true);

		$path->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$this->shareManager->method('createShare')
			->with($this->callback(function (IShare $share) use ($path) {
				return $share->getNode() === $path
					&& $share->getPermissions() === (
						Constants::PERMISSION_ALL
						& ~Constants::PERMISSION_DELETE
						& ~Constants::PERMISSION_CREATE
					)
					&& $share->getShareType() === IShare::TYPE_REMOTE_GROUP
					&& $share->getSharedWith() === 'group@example.org'
					&& $share->getSharedBy() === 'currentUser';
			}))
			->willReturnArgument(0);

		$this->shareManager->method('outgoingServer2ServerGroupSharesAllowed')->willReturn(true);

		$expected = new DataResponse([]);
		$result = $ocs->createShare('valid-path', Constants::PERMISSION_ALL, IShare::TYPE_REMOTE_GROUP, 'group@example.org');

		$this->assertInstanceOf(get_class($expected), $result);
		$this->assertEquals($expected->getData(), $result->getData());
	}

	public function testCreateShareRoom(): void {
		$ocs = $this->mockFormatShare();

		$share = $this->newShare();
		$this->shareManager->method('newShare')->willReturn($share);

		[$userFolder, $path] = $this->getNonSharedUserFile();
		$this->rootFolder->expects($this->exactly(2))
			->method('getUserFolder')
			->with('currentUser')
			->willReturn($userFolder);

		$userFolder->expects($this->once())
			->method('get')
			->with('valid-path')
			->willReturn($path);
		$userFolder->method('getById')
			->willReturn([]);

		$path->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$this->appManager->method('isEnabledForUser')
			->with('spreed')
			->willReturn(true);

		// This is not possible anymore with PHPUnit 10+
		// as `setMethods` was removed and now real reflection is used, thus the class needs to exist.
		// $helper = $this->getMockBuilder('\OCA\Talk\Share\Helper\ShareAPIController')
		$helper = $this->getMockBuilder(\stdClass::class)
			->addMethods(['createShare'])
			->getMock();
		$helper->method('createShare')
			->with(
				$share,
				'recipientRoom',
				Constants::PERMISSION_ALL
				& ~Constants::PERMISSION_DELETE
				& ~Constants::PERMISSION_CREATE,
				''
			)->willReturnCallback(
				function ($share): void {
					$share->setSharedWith('recipientRoom');
					$share->setPermissions(Constants::PERMISSION_ALL);
				}
			);

		$this->serverContainer->method('get')
			->with('\OCA\Talk\Share\Helper\ShareAPIController')
			->willReturn($helper);

		$this->shareManager->method('createShare')
			->with($this->callback(function (IShare $share) use ($path) {
				return $share->getNode() === $path
					&& $share->getPermissions() === Constants::PERMISSION_ALL
					&& $share->getShareType() === IShare::TYPE_ROOM
					&& $share->getSharedWith() === 'recipientRoom'
					&& $share->getSharedBy() === 'currentUser';
			}))
			->willReturnArgument(0);

		$expected = new DataResponse([]);
		$result = $ocs->createShare('valid-path', Constants::PERMISSION_ALL, IShare::TYPE_ROOM, 'recipientRoom');

		$this->assertInstanceOf(get_class($expected), $result);
		$this->assertEquals($expected->getData(), $result->getData());
	}


	public function testCreateShareRoomHelperNotAvailable(): void {
		$this->expectException(OCSForbiddenException::class);
		$this->expectExceptionMessage('Sharing valid-path failed because the back end does not support room shares');

		$ocs = $this->mockFormatShare();

		$share = $this->newShare();
		$this->shareManager->method('newShare')->willReturn($share);

		[$userFolder, $path] = $this->getNonSharedUserFolder();
		$this->rootFolder->method('getUserFolder')
			->with('currentUser')
			->willReturn($userFolder);

		$path->method('getPath')->willReturn('valid-path');
		$userFolder->expects($this->once())
			->method('get')
			->with('valid-path')
			->willReturn($path);
		$userFolder->method('getById')
			->willReturn([]);

		$path->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$this->appManager->method('isEnabledForUser')
			->with('spreed')
			->willReturn(false);

		$this->shareManager->expects($this->never())->method('createShare');

		$ocs->createShare('valid-path', Constants::PERMISSION_ALL, IShare::TYPE_ROOM, 'recipientRoom');
	}


	public function testCreateShareRoomHelperThrowException(): void {
		$this->expectException(OCSNotFoundException::class);
		$this->expectExceptionMessage('Exception thrown by the helper');

		$ocs = $this->mockFormatShare();

		$share = $this->newShare();
		$share->setSharedBy('currentUser');
		$this->shareManager->method('newShare')->willReturn($share);

		[$userFolder, $path] = $this->getNonSharedUserFile();
		$this->rootFolder->method('getUserFolder')
			->with('currentUser')
			->willReturn($userFolder);

		$userFolder->expects($this->once())
			->method('get')
			->with('valid-path')
			->willReturn($path);
		$userFolder->method('getById')
			->willReturn([]);

		$path->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$this->appManager->method('isEnabledForUser')
			->with('spreed')
			->willReturn(true);

		// This is not possible anymore with PHPUnit 10+
		// as `setMethods` was removed and now real reflection is used, thus the class needs to exist.
		// $helper = $this->getMockBuilder('\OCA\Talk\Share\Helper\ShareAPIController')
		$helper = $this->getMockBuilder(\stdClass::class)
			->addMethods(['createShare'])
			->getMock();
		$helper->method('createShare')
			->with(
				$share,
				'recipientRoom',
				Constants::PERMISSION_ALL & ~(Constants::PERMISSION_CREATE | Constants::PERMISSION_DELETE),
				''
			)->willReturnCallback(
				function ($share): void {
					throw new OCSNotFoundException('Exception thrown by the helper');
				}
			);

		$this->serverContainer->method('get')
			->with('\OCA\Talk\Share\Helper\ShareAPIController')
			->willReturn($helper);

		$this->shareManager->expects($this->never())->method('createShare');

		$ocs->createShare('valid-path', Constants::PERMISSION_ALL, IShare::TYPE_ROOM, 'recipientRoom');
	}

	/**
	 * Test for https://github.com/owncloud/core/issues/22587
	 * TODO: Remove once proper solution is in place
	 */
	public function testCreateReshareOfFederatedMountNoDeletePermissions(): void {
		$share = Server::get(IManager::class)->newShare();
		$this->shareManager->method('newShare')->willReturn($share);

		/** @var ShareAPIController&MockObject $ocs */
		$ocs = $this->getMockBuilder(ShareAPIController::class)
			->setConstructorArgs([
				$this->appName,
				$this->request,
				$this->shareManager,
				$this->groupManager,
				$this->userManager,
				$this->rootFolder,
				$this->urlGenerator,
				$this->l,
				$this->config,
				$this->appConfig,
				$this->appManager,
				$this->serverContainer,
				$this->userStatusManager,
				$this->previewManager,
				$this->dateTimeZone,
				$this->logger,
				$this->factory,
				$this->mailer,
				$this->tagManager,
				$this->getEmailValidatorWithStrictEmailCheck(),
				$this->trustedServers,
				$this->currentUser,
			])->onlyMethods(['formatShare'])
			->getMock();

		$userFolder = $this->getMockBuilder(Folder::class)->getMock();
		$this->rootFolder->expects($this->exactly(2))
			->method('getUserFolder')
			->with('currentUser')
			->willReturn($userFolder);

		$path = $this->getMockBuilder(Folder::class)->getMock();
		$path->method('getId')->willReturn(42);

		$storage = $this->createMock(IStorage::class);
		$storage->method('instanceOfStorage')
			->willReturnMap([
				['OCA\Files_Sharing\External\Storage', true],
				['OCA\Files_Sharing\SharedStorage', false],
			]);
		$userFolder->method('getStorage')->willReturn($storage);
		$path->method('getStorage')->willReturn($storage);

		$path->method('getPermissions')->willReturn(Constants::PERMISSION_READ);
		$userFolder->expects($this->once())
			->method('get')
			->with('valid-path')
			->willReturn($path);
		$userFolder->method('getById')
			->willReturn([]);

		$this->userManager->method('userExists')->with('validUser')->willReturn(true);

		$this->shareManager
			->expects($this->once())
			->method('createShare')
			->with($this->callback(function (IShare $share) {
				return $share->getPermissions() === Constants::PERMISSION_READ;
			}))
			->willReturnArgument(0);

		$ocs->createShare('valid-path', Constants::PERMISSION_ALL, IShare::TYPE_USER, 'validUser');
	}


	public function testUpdateShareCantAccess(): void {
		$this->expectException(OCSNotFoundException::class);
		$this->expectExceptionMessage('Wrong share ID, share does not exist');

		[$userFolder, $node] = $this->getNonSharedUserFolder();
		$share = $this->newShare();
		$share->setNode($node);

		$node->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$this->shareManager->method('getShareById')->with('ocinternal:42')->willReturn($share);

		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$userFolder->method('getById')
			->with($share->getNodeId())
			->willReturn([$share->getNode()]);

		$this->ocs->updateShare(42);
	}


	public function testUpdateNoParametersLink(): void {
		$this->expectException(OCSBadRequestException::class);
		$this->expectExceptionMessage('Wrong or no update parameter given');

		$node = $this->getMockBuilder(Folder::class)->getMock();
		$share = $this->newShare();
		$share->setPermissions(Constants::PERMISSION_ALL)
			->setSharedBy($this->currentUser)
			->setShareType(IShare::TYPE_LINK)
			->setNode($node);

		$node->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$this->shareManager->method('getShareById')->with('ocinternal:42')->willReturn($share);

		$this->ocs->updateShare(42);
	}


	public function testUpdateNoParametersOther(): void {
		$this->expectException(OCSBadRequestException::class);
		$this->expectExceptionMessage('Wrong or no update parameter given');

		$node = $this->getMockBuilder(Folder::class)->getMock();
		$share = $this->newShare();
		$share->setPermissions(Constants::PERMISSION_ALL)
			->setSharedBy($this->currentUser)
			->setShareType(IShare::TYPE_GROUP)
			->setNode($node);

		$node->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$this->shareManager->method('getShareById')->with('ocinternal:42')->willReturn($share);

		$this->ocs->updateShare(42);
	}

	public function testUpdateLinkShareClear(): void {
		$ocs = $this->mockFormatShare();

		[$userFolder, $node] = $this->getNonSharedUserFolder();
		$node->method('getId')
			->willReturn(42);
		$share = $this->newShare();
		$share->setPermissions(Constants::PERMISSION_ALL)
			->setSharedBy($this->currentUser)
			->setShareType(IShare::TYPE_LINK)
			->setPassword('password')
			->setExpirationDate(new \DateTime())
			->setNote('note')
			->setLabel('label')
			->setHideDownload(true)
			->setPermissions(Constants::PERMISSION_ALL)
			->setNode($node);

		$node->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$this->shareManager->method('getShareById')->with('ocinternal:42')->willReturn($share);

		$this->shareManager->expects($this->once())->method('updateShare')->with(
			$this->callback(function (IShare $share) {
				return $share->getPermissions() === Constants::PERMISSION_READ
				&& $share->getPassword() === null
				&& $share->getExpirationDate() === null
				// Once set a note or a label are never back to null, only to an
				// empty string.
				&& $share->getNote() === ''
				&& $share->getLabel() === ''
				&& $share->getHideDownload() === false;
			})
		)->willReturnArgument(0);

		$this->shareManager->method('getSharedWith')
			->willReturn([]);

		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$userFolder->method('getById')
			->with(42)
			->willReturn([$node]);
		$userFolder->method('getFirstNodeById')
			->with(42)
			->willReturn($node);

		$mountPoint = $this->createMock(IMountPoint::class);
		$node->method('getMountPoint')
			->willReturn($mountPoint);
		$mountPoint->method('getStorageRootId')
			->willReturn(42);

		$expected = new DataResponse([]);
		$result = $ocs->updateShare(42, null, '', null, 'false', '', '', '', 'false');

		$this->assertInstanceOf(get_class($expected), $result);
		$this->assertEquals($expected->getData(), $result->getData());
	}

	public function testUpdateLinkShareSet(): void {
		$ocs = $this->mockFormatShare();

		[$userFolder, $folder] = $this->getNonSharedUserFolder();
		$folder->method('getId')
			->willReturn(42);

		$share = Server::get(IManager::class)->newShare();
		$share->setPermissions(Constants::PERMISSION_ALL)
			->setSharedBy($this->currentUser)
			->setShareType(IShare::TYPE_LINK)
			->setNode($folder);

		$this->shareManager->method('getShareById')->with('ocinternal:42')->willReturn($share);
		$this->shareManager->method('shareApiLinkAllowPublicUpload')->willReturn(true);

		$this->shareManager->expects($this->once())->method('updateShare')->with(
			$this->callback(function (IShare $share) {
				$date = new \DateTime('2000-01-01');
				$date->setTime(0, 0, 0);

				return $share->getPermissions() === (Constants::PERMISSION_READ | Constants::PERMISSION_CREATE | Constants::PERMISSION_UPDATE | Constants::PERMISSION_DELETE)
				&& $share->getPassword() === 'password'
				&& $share->getExpirationDate() == $date
				&& $share->getNote() === 'note'
				&& $share->getLabel() === 'label'
				&& $share->getHideDownload() === true;
			})
		)->willReturnArgument(0);

		$this->shareManager->method('getSharedWith')
			->willReturn([]);

		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$userFolder->method('getById')
			->with(42)
			->willReturn([$folder]);

		$mountPoint = $this->createMock(IMountPoint::class);
		$folder->method('getMountPoint')
			->willReturn($mountPoint);
		$mountPoint->method('getStorageRootId')
			->willReturn(42);

		$expected = new DataResponse([]);
		$result = $ocs->updateShare(42, null, 'password', null, 'true', '2000-01-01', 'note', 'label', 'true');

		$this->assertInstanceOf(get_class($expected), $result);
		$this->assertEquals($expected->getData(), $result->getData());
	}

	#[DataProvider(methodName: 'publicUploadParamsProvider')]
	public function testUpdateLinkShareEnablePublicUpload($permissions, $publicUpload, $expireDate, $password): void {
		$ocs = $this->mockFormatShare();

		[$userFolder, $folder] = $this->getNonSharedUserFolder();
		$folder->method('getId')
			->willReturn(42);

		$share = Server::get(IManager::class)->newShare();
		$share->setPermissions(Constants::PERMISSION_ALL)
			->setSharedBy($this->currentUser)
			->setShareType(IShare::TYPE_LINK)
			->setPassword('password')
			->setNode($folder);

		$this->shareManager->method('getShareById')->with('ocinternal:42')->willReturn($share);
		$this->shareManager->method('shareApiLinkAllowPublicUpload')->willReturn(true);
		$this->shareManager->method('getSharedWith')->willReturn([]);

		$this->shareManager->expects($this->once())->method('updateShare')->with(
			$this->callback(function (IShare $share) {
				return $share->getPermissions() === (Constants::PERMISSION_READ | Constants::PERMISSION_CREATE | Constants::PERMISSION_UPDATE | Constants::PERMISSION_DELETE)
				&& $share->getPassword() === 'password'
				&& $share->getExpirationDate() === null;
			})
		)->willReturnArgument(0);

		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$userFolder->method('getById')
			->with(42)
			->willReturn([$folder]);

		$mountPoint = $this->createMock(IMountPoint::class);
		$folder->method('getMountPoint')
			->willReturn($mountPoint);
		$mountPoint->method('getStorageRootId')
			->willReturn(42);

		$expected = new DataResponse([]);
		$result = $ocs->updateShare(42, $permissions, $password, null, $publicUpload, $expireDate);

		$this->assertInstanceOf(get_class($expected), $result);
		$this->assertEquals($expected->getData(), $result->getData());
	}


	public static function publicLinkValidPermissionsProvider() {
		return [
			[Constants::PERMISSION_CREATE],
			[Constants::PERMISSION_READ],
			[Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE],
			[Constants::PERMISSION_READ | Constants::PERMISSION_DELETE],
			[Constants::PERMISSION_READ | Constants::PERMISSION_CREATE],
		];
	}

	#[DataProvider(methodName: 'publicLinkValidPermissionsProvider')]
	public function testUpdateLinkShareSetCRUDPermissions($permissions): void {
		$ocs = $this->mockFormatShare();

		[$userFolder, $folder] = $this->getNonSharedUserFolder();
		$folder->method('getId')
			->willReturn(42);

		$share = Server::get(IManager::class)->newShare();
		$share->setPermissions(Constants::PERMISSION_ALL)
			->setSharedBy($this->currentUser)
			->setShareType(IShare::TYPE_LINK)
			->setPassword('password')
			->setNode($folder);

		$this->shareManager->method('getShareById')->with('ocinternal:42')->willReturn($share);
		$this->shareManager->method('shareApiLinkAllowPublicUpload')->willReturn(true);
		$this->shareManager->method('getSharedWith')->willReturn([]);

		$this->shareManager
			->expects($this->any())
			->method('updateShare')
			->willReturnArgument(0);

		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$userFolder->method('getById')
			->with(42)
			->willReturn([$folder]);

		$mountPoint = $this->createMock(IMountPoint::class);
		$folder->method('getMountPoint')
			->willReturn($mountPoint);
		$mountPoint->method('getStorageRootId')
			->willReturn(42);

		$expected = new DataResponse([]);
		$result = $ocs->updateShare(42, $permissions, 'password', null, null, null);

		$this->assertInstanceOf(get_class($expected), $result);
		$this->assertEquals($expected->getData(), $result->getData());
	}

	public static function publicLinkInvalidPermissionsProvider1() {
		return [
			[Constants::PERMISSION_DELETE],
			[Constants::PERMISSION_UPDATE],
			[Constants::PERMISSION_SHARE],
		];
	}

	#[DataProvider(methodName: 'publicLinkInvalidPermissionsProvider1')]
	public function testUpdateLinkShareSetInvalidCRUDPermissions1($permissions): void {
		$this->expectException(OCSBadRequestException::class);
		$this->expectExceptionMessage('Share must at least have READ or CREATE permissions');

		$this->testUpdateLinkShareSetCRUDPermissions($permissions, null);
	}

	public static function publicLinkInvalidPermissionsProvider2() {
		return [
			[Constants::PERMISSION_CREATE | Constants::PERMISSION_DELETE],
			[Constants::PERMISSION_CREATE | Constants::PERMISSION_UPDATE],
		];
	}

	#[DataProvider(methodName: 'publicLinkInvalidPermissionsProvider2')]
	public function testUpdateLinkShareSetInvalidCRUDPermissions2($permissions): void {
		$this->expectException(OCSBadRequestException::class);
		$this->expectExceptionMessage('Share must have READ permission if UPDATE or DELETE permission is set');

		$this->testUpdateLinkShareSetCRUDPermissions($permissions);
	}

	public function testUpdateLinkShareInvalidDate(): void {
		$this->expectException(OCSBadRequestException::class);
		$this->expectExceptionMessage('Invalid date. Format must be YYYY-MM-DD');

		$ocs = $this->mockFormatShare();
		[$userFolder, $folder] = $this->getNonSharedUserFolder();
		$userFolder->method('getById')
			->with(42)
			->willReturn([$folder]);
		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$folder->method('getId')
			->willReturn(42);

		$share = Server::get(IManager::class)->newShare();
		$share->setPermissions(Constants::PERMISSION_ALL)
			->setSharedBy($this->currentUser)
			->setShareType(IShare::TYPE_LINK)
			->setNode($folder);

		$this->shareManager->method('getShareById')->with('ocinternal:42')->willReturn($share);
		$this->shareManager->method('shareApiLinkAllowPublicUpload')->willReturn(true);

		$ocs->updateShare(42, null, 'password', null, 'true', '2000-01-a');
	}

	public static function publicUploadParamsProvider() {
		return [
			[null, 'true', null, 'password'],
			// legacy had no delete
			[
				Constants::PERMISSION_READ | Constants::PERMISSION_CREATE | Constants::PERMISSION_UPDATE,
				'true', null, 'password'
			],
			// correct
			[
				Constants::PERMISSION_READ | Constants::PERMISSION_CREATE | Constants::PERMISSION_UPDATE | Constants::PERMISSION_DELETE,
				null, null, 'password'
			],
		];
	}

	#[DataProvider(methodName: 'publicUploadParamsProvider')]
	public function testUpdateLinkSharePublicUploadNotAllowed($permissions, $publicUpload, $expireDate, $password): void {
		$this->expectException(OCSForbiddenException::class);
		$this->expectExceptionMessage('Public upload disabled by the administrator');

		$ocs = $this->mockFormatShare();
		[$userFolder, $folder] = $this->getNonSharedUserFolder();
		$userFolder->method('getById')
			->with(42)
			->willReturn([$folder]);
		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$folder->method('getId')->willReturn(42);

		$share = Server::get(IManager::class)->newShare();
		$share->setPermissions(Constants::PERMISSION_ALL)
			->setSharedBy($this->currentUser)
			->setShareType(IShare::TYPE_LINK)
			->setNode($folder);

		$this->shareManager->method('getShareById')->with('ocinternal:42')->willReturn($share);
		$this->shareManager->method('shareApiLinkAllowPublicUpload')->willReturn(false);

		$ocs->updateShare(42, $permissions, $password, null, $publicUpload, $expireDate);
	}


	public function testUpdateLinkSharePublicUploadOnFile(): void {
		$this->expectException(OCSBadRequestException::class);
		$this->expectExceptionMessage('Public upload is only possible for publicly shared folders');

		$ocs = $this->mockFormatShare();

		$file = $this->getMockBuilder(File::class)->getMock();
		$file->method('getId')
			->willReturn(42);
		[$userFolder, $folder] = $this->getNonSharedUserFolder();
		$userFolder->method('getById')
			->with(42)
			->willReturn([$folder]);
		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$share = Server::get(IManager::class)->newShare();
		$share->setPermissions(Constants::PERMISSION_ALL)
			->setSharedBy($this->currentUser)
			->setShareType(IShare::TYPE_LINK)
			->setNode($file);

		$this->shareManager
			->method('getShareById')
			->with('ocinternal:42')
			->willReturn($share);
		$this->shareManager
			->method('shareApiLinkAllowPublicUpload')
			->willReturn(true);
		$this->shareManager
			->method('updateShare')
			->with($share)
			->willThrowException(new \InvalidArgumentException('File shares cannot have create or delete permissions'));

		$ocs->updateShare(42, null, 'password', null, 'true', '');
	}

	public function testUpdateLinkSharePasswordDoesNotChangeOther(): void {
		$ocs = $this->mockFormatShare();

		$date = new \DateTime('2000-01-01');
		$date->setTime(0, 0, 0);

		[$userFolder, $node] = $this->getNonSharedUserFolder();
		$node->method('getId')->willReturn(42);
		$userFolder->method('getById')
			->with(42)
			->willReturn([$node]);
		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);
		$share = $this->newShare();
		$share->setPermissions(Constants::PERMISSION_ALL)
			->setSharedBy($this->currentUser)
			->setShareType(IShare::TYPE_LINK)
			->setPassword('password')
			->setSendPasswordByTalk(true)
			->setExpirationDate($date)
			->setNote('note')
			->setLabel('label')
			->setHideDownload(true)
			->setPermissions(Constants::PERMISSION_ALL)
			->setNode($node);

		$node->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$this->shareManager->method('getShareById')->with('ocinternal:42')->willReturn($share);

		$this->shareManager->expects($this->once())->method('updateShare')->with(
			$this->callback(function (IShare $share) use ($date) {
				return $share->getPermissions() === Constants::PERMISSION_ALL
				&& $share->getPassword() === 'newpassword'
				&& $share->getSendPasswordByTalk() === true
				&& $share->getExpirationDate() === $date
				&& $share->getNote() === 'note'
				&& $share->getLabel() === 'label'
				&& $share->getHideDownload() === true;
			})
		)->willReturnArgument(0);

		$expected = new DataResponse([]);
		$result = $ocs->updateShare(42, null, 'newpassword', null, null, null, null, null, null);

		$this->assertInstanceOf(get_class($expected), $result);
		$this->assertEquals($expected->getData(), $result->getData());
	}

	public function testUpdateLinkShareSendPasswordByTalkDoesNotChangeOther(): void {
		$ocs = $this->mockFormatShare();

		$date = new \DateTime('2000-01-01');
		$date->setTime(0, 0, 0);

		[$userFolder, $node] = $this->getNonSharedUserFolder();
		$userFolder->method('getById')
			->with(42)
			->willReturn([$node]);
		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);
		$node->method('getId')->willReturn(42);
		$share = $this->newShare();
		$share->setPermissions(Constants::PERMISSION_ALL)
			->setSharedBy($this->currentUser)
			->setShareType(IShare::TYPE_LINK)
			->setPassword('password')
			->setSendPasswordByTalk(false)
			->setExpirationDate($date)
			->setNote('note')
			->setLabel('label')
			->setHideDownload(true)
			->setPermissions(Constants::PERMISSION_ALL)
			->setNode($node);

		$node->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$this->shareManager->method('getShareById')->with('ocinternal:42')->willReturn($share);

		$this->appManager->method('isEnabledForUser')->with('spreed')->willReturn(true);

		$this->shareManager->expects($this->once())->method('updateShare')->with(
			$this->callback(function (IShare $share) use ($date) {
				return $share->getPermissions() === Constants::PERMISSION_ALL
				&& $share->getPassword() === 'password'
				&& $share->getSendPasswordByTalk() === true
				&& $share->getExpirationDate() === $date
				&& $share->getNote() === 'note'
				&& $share->getLabel() === 'label'
				&& $share->getHideDownload() === true;
			})
		)->willReturnArgument(0);

		$expected = new DataResponse([]);
		$result = $ocs->updateShare(42, null, null, 'true', null, null, null, null, null);

		$this->assertInstanceOf(get_class($expected), $result);
		$this->assertEquals($expected->getData(), $result->getData());
	}


	public function testUpdateLinkShareSendPasswordByTalkWithTalkDisabledDoesNotChangeOther(): void {
		$this->expectException(OCSForbiddenException::class);
		$this->expectExceptionMessage('"Sending the password by Nextcloud Talk" for sharing a file or folder failed because Nextcloud Talk is not enabled.');

		$ocs = $this->mockFormatShare();

		$date = new \DateTime('2000-01-01');
		$date->setTime(0, 0, 0);

		[$userFolder, $node] = $this->getNonSharedUserFolder();
		$userFolder->method('getById')
			->with(42)
			->willReturn([$node]);
		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);
		$node->method('getId')->willReturn(42);
		$share = $this->newShare();
		$share->setPermissions(Constants::PERMISSION_ALL)
			->setSharedBy($this->currentUser)
			->setShareType(IShare::TYPE_LINK)
			->setPassword('password')
			->setSendPasswordByTalk(false)
			->setExpirationDate($date)
			->setNote('note')
			->setLabel('label')
			->setHideDownload(true)
			->setPermissions(Constants::PERMISSION_ALL)
			->setNode($node);

		$node->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$this->shareManager->method('getShareById')->with('ocinternal:42')->willReturn($share);

		$this->appManager->method('isEnabledForUser')->with('spreed')->willReturn(false);

		$this->shareManager->expects($this->never())->method('updateShare');

		$ocs->updateShare(42, null, null, 'true', null, null, null, null, null);
	}

	public function testUpdateLinkShareDoNotSendPasswordByTalkDoesNotChangeOther(): void {
		$ocs = $this->mockFormatShare();

		$date = new \DateTime('2000-01-01');
		$date->setTime(0, 0, 0);

		[$userFolder, $node] = $this->getNonSharedUserFolder();
		$userFolder->method('getById')
			->with(42)
			->willReturn([$node]);
		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);
		$node->method('getId')->willReturn(42);
		$share = $this->newShare();
		$share->setPermissions(Constants::PERMISSION_ALL)
			->setSharedBy($this->currentUser)
			->setShareType(IShare::TYPE_LINK)
			->setPassword('password')
			->setSendPasswordByTalk(true)
			->setExpirationDate($date)
			->setNote('note')
			->setLabel('label')
			->setHideDownload(true)
			->setPermissions(Constants::PERMISSION_ALL)
			->setNode($node);

		$node->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$this->shareManager->method('getShareById')->with('ocinternal:42')->willReturn($share);

		$this->appManager->method('isEnabledForUser')->with('spreed')->willReturn(true);

		$this->shareManager->expects($this->once())->method('updateShare')->with(
			$this->callback(function (IShare $share) use ($date) {
				return $share->getPermissions() === Constants::PERMISSION_ALL
				&& $share->getPassword() === 'password'
				&& $share->getSendPasswordByTalk() === false
				&& $share->getExpirationDate() === $date
				&& $share->getNote() === 'note'
				&& $share->getLabel() === 'label'
				&& $share->getHideDownload() === true;
			})
		)->willReturnArgument(0);

		$expected = new DataResponse([]);
		$result = $ocs->updateShare(42, null, null, 'false', null, null, null, null, null);

		$this->assertInstanceOf(get_class($expected), $result);
		$this->assertEquals($expected->getData(), $result->getData());
	}

	public function testUpdateLinkShareDoNotSendPasswordByTalkWithTalkDisabledDoesNotChangeOther(): void {
		$ocs = $this->mockFormatShare();

		$date = new \DateTime('2000-01-01');
		$date->setTime(0, 0, 0);

		[$userFolder, $node] = $this->getNonSharedUserFolder();
		$node->method('getId')
			->willReturn(42);

		$share = $this->newShare();
		$share->setPermissions(Constants::PERMISSION_ALL)
			->setSharedBy($this->currentUser)
			->setShareType(IShare::TYPE_LINK)
			->setPassword('password')
			->setSendPasswordByTalk(true)
			->setExpirationDate($date)
			->setNote('note')
			->setLabel('label')
			->setHideDownload(true)
			->setPermissions(Constants::PERMISSION_ALL)
			->setNode($node);

		$node->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$this->shareManager->method('getShareById')->with('ocinternal:42')->willReturn($share);

		$this->appManager->method('isEnabledForUser')->with('spreed')->willReturn(false);

		$this->shareManager->expects($this->once())->method('updateShare')->with(
			$this->callback(function (IShare $share) use ($date) {
				return $share->getPermissions() === Constants::PERMISSION_ALL
				&& $share->getPassword() === 'password'
				&& $share->getSendPasswordByTalk() === false
				&& $share->getExpirationDate() === $date
				&& $share->getNote() === 'note'
				&& $share->getLabel() === 'label'
				&& $share->getHideDownload() === true;
			})
		)->willReturnArgument(0);

		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$userFolder->method('getById')
			->with(42)
			->willReturn([$node]);

		$mountPoint = $this->createMock(IMountPoint::class);
		$node->method('getMountPoint')
			->willReturn($mountPoint);
		$mountPoint->method('getStorageRootId')
			->willReturn(42);

		$mountPoint = $this->createMock(IMountPoint::class);
		$node->method('getMountPoint')
			->willReturn($mountPoint);
		$mountPoint->method('getStorageRootId')
			->willReturn(42);

		$expected = new DataResponse([]);
		$result = $ocs->updateShare(42, null, null, 'false', null, null, null, null, null);

		$this->assertInstanceOf(get_class($expected), $result);
		$this->assertEquals($expected->getData(), $result->getData());
	}

	public function testUpdateLinkShareExpireDateDoesNotChangeOther(): void {
		$ocs = $this->mockFormatShare();

		[$userFolder, $node] = $this->getNonSharedUserFolder();
		$node->method('getId')
			->willReturn(42);

		$share = $this->newShare();
		$share->setPermissions(Constants::PERMISSION_ALL)
			->setSharedBy($this->currentUser)
			->setShareType(IShare::TYPE_LINK)
			->setPassword('password')
			->setSendPasswordByTalk(true)
			->setExpirationDate(new \DateTime())
			->setNote('note')
			->setLabel('label')
			->setHideDownload(true)
			->setPermissions(Constants::PERMISSION_ALL)
			->setNode($node);

		$node->expects($this->once())
			->method('lock')
			->with(ILockingProvider::LOCK_SHARED);

		$this->shareManager->method('getShareById')->with('ocinternal:42')->willReturn($share);

		$this->shareManager->expects($this->once())->method('updateShare')->with(
			$this->callback(function (IShare $share) {
				$date = new \DateTime('2010-12-23');
				$date->setTime(0, 0, 0);

				return $share->getPermissions() === Constants::PERMISSION_ALL
				&& $share->getPassword() === 'password'
				&& $share->getSendPasswordByTalk() === true
				&& $share->getExpirationDate() == $date
				&& $share->getNote() === 'note'
				&& $share->getLabel() === 'label'
				&& $share->getHideDownload() === true;
			})
		)->willReturnArgument(0);

		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$userFolder->method('getById')
			->with(42)
			->willReturn([$node]);

		$mountPoint = $this->createMock(IMountPoint::class);
		$node->method('getMountPoint')
			->willReturn($mountPoint);
		$mountPoint->method('getStorageRootId')
			->willReturn(42);

		$expected = new DataResponse([]);
		$result = $ocs->updateShare(42, null, null, null, null, '2010-12-23', null, null, null);

		$this->assertInstanceOf(get_class($expected), $result);
		$this->assertEquals($expected->getData(), $result->getData());
	}

	public function testUpdateLinkSharePublicUploadDoesNotChangeOther(): void {
		$ocs = $this->mockFormatShare();

		$date = new \DateTime('2000-01-01');

		[$userFolder, $folder] = $this->getNonSharedUserFolder();
		$folder->method('getId')
			->willReturn(42);

		$share = Server::get(IManager::class)->newShare();
		$share->setPermissions(Constants::PERMISSION_ALL)
			->setSharedBy($this->currentUser)
			->setShareType(IShare::TYPE_LINK)
			->setPassword('password')
			->setSendPasswordByTalk(true)
			->setExpirationDate($date)
			->setNote('note')
			->setLabel('label')
			->setHideDownload(true)
			->setPermissions(Constants::PERMISSION_ALL)
			->setNode($folder);

		$this->shareManager->method('getShareById')->with('ocinternal:42')->willReturn($share);
		$this->shareManager->method('shareApiLinkAllowPublicUpload')->willReturn(true);

		$this->shareManager->expects($this->once())->method('updateShare')->with(
			$this->callback(function (IShare $share) use ($date) {
				return $share->getPermissions() === (Constants::PERMISSION_READ | Constants::PERMISSION_CREATE | Constants::PERMISSION_UPDATE | Constants::PERMISSION_DELETE)
				&& $share->getPassword() === 'password'
				&& $share->getSendPasswordByTalk() === true
				&& $share->getExpirationDate() === $date
				&& $share->getNote() === 'note'
				&& $share->getLabel() === 'label'
				&& $share->getHideDownload() === true;
			})
		)->willReturnArgument(0);

		$this->shareManager->method('getSharedWith')
			->willReturn([]);

		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$userFolder->method('getById')
			->with(42)
			->willReturn([$folder]);

		$mountPoint = $this->createMock(IMountPoint::class);
		$folder->method('getMountPoint')
			->willReturn($mountPoint);
		$mountPoint->method('getStorageRootId')
			->willReturn(42);

		$expected = new DataResponse([]);
		$result = $ocs->updateShare(42, null, null, null, 'true', null, null, null, null);

		$this->assertInstanceOf(get_class($expected), $result);
		$this->assertEquals($expected->getData(), $result->getData());
	}

	public function testUpdateLinkSharePermissions(): void {
		$ocs = $this->mockFormatShare();

		$date = new \DateTime('2000-01-01');

		[$userFolder, $folder] = $this->getNonSharedUserFolder();
		$folder->method('getId')
			->willReturn(42);

		$share = Server::get(IManager::class)->newShare();
		$share->setPermissions(Constants::PERMISSION_ALL)
			->setSharedBy($this->currentUser)
			->setShareType(IShare::TYPE_LINK)
			->setPassword('password')
			->setSendPasswordByTalk(true)
			->setExpirationDate($date)
			->setNote('note')
			->setLabel('label')
			->setHideDownload(true)
			->setPermissions(Constants::PERMISSION_ALL)
			->setNode($folder);

		$this->shareManager->method('getShareById')->with('ocinternal:42')->willReturn($share);
		$this->shareManager->method('shareApiLinkAllowPublicUpload')->willReturn(true);

		$this->shareManager->expects($this->once())->method('updateShare')->with(
			$this->callback(function (IShare $share) use ($date): bool {
				return $share->getPermissions() === (Constants::PERMISSION_READ | Constants::PERMISSION_CREATE | Constants::PERMISSION_UPDATE | Constants::PERMISSION_DELETE)
				&& $share->getPassword() === 'password'
				&& $share->getSendPasswordByTalk() === true
				&& $share->getExpirationDate() === $date
				&& $share->getNote() === 'note'
				&& $share->getLabel() === 'label'
				&& $share->getHideDownload() === true;
			})
		)->willReturnArgument(0);

		$this->shareManager->method('getSharedWith')->willReturn([]);

		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$userFolder->method('getById')
			->with(42)
			->willReturn([$folder]);

		$mountPoint = $this->createMock(IMountPoint::class);
		$folder->method('getMountPoint')
			->willReturn($mountPoint);
		$mountPoint->method('getStorageRootId')
			->willReturn(42);

		$expected = new DataResponse([]);
		$result = $ocs->updateShare(42, 7, null, null, 'true', null, null, null, null);

		$this->assertInstanceOf(get_class($expected), $result);
		$this->assertEquals($expected->getData(), $result->getData());
	}

	public function testUpdateLinkSharePermissionsShare(): void {
		$ocs = $this->mockFormatShare();

		$date = new \DateTime('2000-01-01');

		[$userFolder, $folder] = $this->getNonSharedUserFolder();
		$folder->method('getId')
			->willReturn(42);

		$share = Server::get(IManager::class)->newShare();
		$share->setPermissions(Constants::PERMISSION_ALL)
			->setSharedBy($this->currentUser)
			->setShareType(IShare::TYPE_LINK)
			->setPassword('password')
			->setSendPasswordByTalk(true)
			->setExpirationDate($date)
			->setNote('note')
			->setLabel('label')
			->setHideDownload(true)
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($folder);

		$this->shareManager->method('getShareById')->with('ocinternal:42')->willReturn($share);
		$this->shareManager->method('shareApiLinkAllowPublicUpload')->willReturn(true);

		$this->shareManager->expects($this->once())
			->method('updateShare')
			->with(
				$this->callback(function (IShare $share) use ($date) {
					return $share->getPermissions() === Constants::PERMISSION_ALL
						&& $share->getPassword() === 'password'
						&& $share->getSendPasswordByTalk() === true
						&& $share->getExpirationDate() === $date
						&& $share->getNote() === 'note'
						&& $share->getLabel() === 'label'
						&& $share->getHideDownload() === true;
				})
			)->willReturnArgument(0);

		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$userFolder->method('getById')
			->with(42)
			->willReturn([$folder]);

		$mountPoint = $this->createMock(IMountPoint::class);
		$folder->method('getMountPoint')
			->willReturn($mountPoint);
		$mountPoint->method('getStorageRootId')
			->willReturn(42);

		$this->shareManager->method('getSharedWith')->willReturn([]);

		$expected = new DataResponse([]);
		$result = $ocs->updateShare(42, Constants::PERMISSION_ALL, null, null, null, null, null, null, null);

		$this->assertInstanceOf(get_class($expected), $result);
		$this->assertEquals($expected->getData(), $result->getData());
	}

	public function testUpdateOtherPermissions(): void {
		$ocs = $this->mockFormatShare();

		[$userFolder, $file] = $this->getNonSharedUserFolder();
		$file->method('getId')
			->willReturn(42);

		$share = Server::get(IManager::class)->newShare();
		$share->setPermissions(Constants::PERMISSION_ALL)
			->setSharedBy($this->currentUser)
			->setShareType(IShare::TYPE_USER)
			->setNode($file);

		$this->shareManager->method('getShareById')->with('ocinternal:42')->willReturn($share);
		$this->shareManager->method('shareApiLinkAllowPublicUpload')->willReturn(true);

		$this->shareManager->expects($this->once())->method('updateShare')->with(
			$this->callback(function (IShare $share) {
				return $share->getPermissions() === Constants::PERMISSION_ALL;
			})
		)->willReturnArgument(0);

		$this->shareManager->method('getSharedWith')->willReturn([]);

		[$userFolder, $folder] = $this->getNonSharedUserFolder();
		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$userFolder->method('getById')
			->with(42)
			->willReturn([$file]);

		$mountPoint = $this->createMock(IMountPoint::class);
		$file->method('getMountPoint')
			->willReturn($mountPoint);
		$mountPoint->method('getStorageRootId')
			->willReturn(42);

		$expected = new DataResponse([]);
		$result = $ocs->updateShare(42, 31, null, null, null, null);

		$this->assertInstanceOf(get_class($expected), $result);
		$this->assertEquals($expected->getData(), $result->getData());
	}

	public function testUpdateShareCannotIncreasePermissions(): void {
		$ocs = $this->mockFormatShare();

		[$userFolder, $folder] = $this->getNonSharedUserFolder();
		$folder->method('getId')
			->willReturn(42);

		$share = Server::get(IManager::class)->newShare();
		$share
			->setId(42)
			->setSharedBy($this->currentUser)
			->setShareOwner('anotheruser')
			->setShareType(IShare::TYPE_GROUP)
			->setSharedWith('group1')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($folder);

		// note: updateShare will modify the received instance but getSharedWith will reread from the database,
		// so their values will be different
		$incomingShare = Server::get(IManager::class)->newShare();
		$incomingShare
			->setId(42)
			->setSharedBy($this->currentUser)
			->setShareOwner('anotheruser')
			->setShareType(IShare::TYPE_GROUP)
			->setSharedWith('group1')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($folder);

		$this->request
			->method('getParam')
			->willReturnMap([
				['permissions', null, '31'],
			]);

		$this->shareManager->method('getShareById')->with('ocinternal:42')->willReturn($share);

		$this->shareManager->expects($this->any())
			->method('getSharedWith')
			->willReturnMap([
				['currentUser', IShare::TYPE_USER, $share->getNode(), -1, 0, []],
				['currentUser', IShare::TYPE_GROUP, $share->getNode(), -1, 0, [$incomingShare]],
				['currentUser', IShare::TYPE_ROOM, $share->getNode(), -1, 0, []]
			]);

		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$userFolder->method('getById')
			->with(42)
			->willReturn([$folder]);
		$userFolder->method('getFirstNodeById')
			->with(42)
			->willReturn($folder);

		$mountPoint = $this->createMock(IMountPoint::class);
		$folder->method('getMountPoint')
			->willReturn($mountPoint);
		$mountPoint->method('getStorageRootId')
			->willReturn(42);

		$this->shareManager->expects($this->once())
			->method('updateShare')
			->with($share)
			->willThrowException(new GenericShareException('Cannot increase permissions of path/file', 'Cannot increase permissions of path/file', 404));

		try {
			$ocs->updateShare(42, 31);
			$this->fail();
		} catch (OCSException $e) {
			$this->assertEquals('Cannot increase permissions of path/file', $e->getMessage());
		}
	}

	public function testUpdateShareCanIncreasePermissionsIfOwner(): void {
		$ocs = $this->mockFormatShare();

		[$userFolder, $folder] = $this->getNonSharedUserFolder();
		$folder->method('getId')
			->willReturn(42);

		$share = Server::get(IManager::class)->newShare();
		$share
			->setId(42)
			->setSharedBy($this->currentUser)
			->setShareOwner($this->currentUser)
			->setShareType(IShare::TYPE_GROUP)
			->setSharedWith('group1')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($folder);

		// note: updateShare will modify the received instance but getSharedWith will reread from the database,
		// so their values will be different
		$incomingShare = Server::get(IManager::class)->newShare();
		$incomingShare
			->setId(42)
			->setSharedBy($this->currentUser)
			->setShareOwner($this->currentUser)
			->setShareType(IShare::TYPE_GROUP)
			->setSharedWith('group1')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($folder);

		$this->shareManager->method('getShareById')->with('ocinternal:42')->willReturn($share);

		$this->shareManager->expects($this->any())
			->method('getSharedWith')
			->willReturnMap([
				['currentUser', IShare::TYPE_USER, $share->getNode(), -1, 0, []],
				['currentUser', IShare::TYPE_GROUP, $share->getNode(), -1, 0, [$incomingShare]]
			]);

		$this->shareManager->expects($this->once())
			->method('updateShare')
			->with($share)
			->willReturn($share);

		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$userFolder->method('getById')
			->with(42)
			->willReturn([$folder]);

		$mountPoint = $this->createMock(IMountPoint::class);
		$folder->method('getMountPoint')
			->willReturn($mountPoint);
		$mountPoint->method('getStorageRootId')
			->willReturn(42);

		$result = $ocs->updateShare(42, 31);
		$this->assertInstanceOf(DataResponse::class, $result);
	}

	public function testUpdateShareOwnerless(): void {
		$ocs = $this->mockFormatShare();

		$mount = $this->createMock(IShareOwnerlessMount::class);

		$file = $this->createMock(File::class);
		$file
			->expects($this->exactly(2))
			->method('getPermissions')
			->willReturn(Constants::PERMISSION_SHARE);
		$file
			->expects($this->once())
			->method('getMountPoint')
			->willReturn($mount);

		$userFolder = $this->createMock(Folder::class);
		$userFolder->method('getById')
			->with(2)
			->willReturn([$file]);
		$userFolder->method('getFirstNodeById')
			->with(2)
			->willReturn($file);

		$this->rootFolder
			->method('getUserFolder')
			->with($this->currentUser)
			->willReturn($userFolder);

		$share = $this->createMock(IShare::class);
		$share
			->expects($this->once())
			->method('getNode')
			->willReturn($file);
		$share
			->expects($this->exactly(2))
			->method('getNodeId')
			->willReturn(2);
		$share
			->expects($this->exactly(2))
			->method('getPermissions')
			->willReturn(Constants::PERMISSION_SHARE);

		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with('ocinternal:1', $this->currentUser)
			->willReturn($share);

		$this->shareManager
			->expects($this->once())
			->method('updateShare')
			->with($share)
			->willReturn($share);

		$result = $ocs->updateShare(1, Constants::PERMISSION_ALL);
		$this->assertInstanceOf(DataResponse::class, $result);
	}

	public static function dataFormatShare(): array {
		$owner = ['getDisplayName' => 'ownerDN'];
		$initiator = ['getDisplayName' => 'initiatorDN'];
		$recipient = [
			'getDisplayName' => 'recipientDN',
			'getSystemEMailAddress' => 'recipient'
		];

		$folder = [
			'class' => Folder::class,
			'mimeType' => 'myFolderMimeType',
			'path' => 'folder',
			'id' => 2,
		];
		$file = [
			'class' => File::class,
			'mimeType' => 'myMimeType',
			'path' => 'file',
			'id' => 3,
		];
		$fileWithPreview = [
			'class' => File::class,
			'mimeType' => 'mimeWithPreview',
			'path' => 'fileWithPreview',
			'id' => 4,
		];

		$result = [];

		$share = [
			'type' => IShare::TYPE_USER,
			'owner' => 'owner',
			'sharedWith' => 'recipient',
			'attributes' => [
				'scope' => 'permissions',
				'key' => 'download',
				'value' => true
			],
			'node' => $file,
			'note' => 'personal note',
		];

		// User backend down
		$result[] = [
			[
				'id' => '42',
				'share_type' => IShare::TYPE_USER,
				'uid_owner' => 'initiator',
				'displayname_owner' => 'initiator',
				'permissions' => 1,
				'stime' => 946684862,
				'parent' => null,
				'expiration' => null,
				'token' => null,
				'uid_file_owner' => 'owner',
				'displayname_file_owner' => 'owner',
				'path' => 'file',
				'item_type' => 'file',
				'storage_id' => 'storageId',
				'storage' => 100,
				'item_source' => 3,
				'file_source' => 3,
				'file_parent' => 1,
				'file_target' => 'myTarget',
				'share_with' => 'recipient',
				'share_with_displayname' => 'recipient',
				'share_with_displayname_unique' => 'recipient',
				'note' => 'personal note',
				'label' => '',
				'mail_send' => 0,
				'mimetype' => 'myMimeType',
				'has_preview' => false,
				'hide_download' => 0,
				'can_edit' => false,
				'can_delete' => false,
				'item_size' => 123456,
				'item_mtime' => 1234567890,
				'is-mount-root' => false,
				'mount-type' => '',
				'attributes' => '[{"scope":"permissions","key":"download","value":true}]',
				'item_permissions' => 1,
			],
			$share,
			[], false
		];
		// User backend up
		$result[] = [
			[
				'id' => '42',
				'share_type' => IShare::TYPE_USER,
				'uid_owner' => 'initiator',
				'displayname_owner' => 'initiatorDN',
				'permissions' => 1,
				'stime' => 946684862,
				'parent' => null,
				'expiration' => null,
				'token' => null,
				'uid_file_owner' => 'owner',
				'displayname_file_owner' => 'ownerDN',
				'note' => 'personal note',
				'label' => '',
				'path' => 'file',
				'item_type' => 'file',
				'storage_id' => 'storageId',
				'storage' => 100,
				'item_source' => 3,
				'file_source' => 3,
				'file_parent' => 1,
				'file_target' => 'myTarget',
				'share_with' => 'recipient',
				'share_with_displayname' => 'recipientDN',
				'share_with_displayname_unique' => 'recipient',
				'mail_send' => 0,
				'mimetype' => 'myMimeType',
				'has_preview' => false,
				'hide_download' => 0,
				'can_edit' => false,
				'can_delete' => false,
				'item_size' => 123456,
				'item_mtime' => 1234567890,
				'is-mount-root' => false,
				'mount-type' => '',
				'attributes' => '[{"scope":"permissions","key":"download","value":true}]',
				'item_permissions' => 1,
			], $share, [
				['owner', $owner],
				['initiator', $initiator],
				['recipient', $recipient],
			], false
		];

		// Same but no attributes
		$share = [
			'type' => IShare::TYPE_USER,
			'owner' => 'owner',
			'sharedWith' => 'recipient',
			'node' => $file,
			'note' => 'personal note',
		];

		// User backend down
		$result[] = [
			[
				'id' => '42',
				'share_type' => IShare::TYPE_USER,
				'uid_owner' => 'initiator',
				'displayname_owner' => 'initiator',
				'permissions' => 1,
				'attributes' => null,
				'stime' => 946684862,
				'parent' => null,
				'expiration' => null,
				'token' => null,
				'uid_file_owner' => 'owner',
				'displayname_file_owner' => 'owner',
				'note' => 'personal note',
				'label' => '',
				'path' => 'file',
				'item_type' => 'file',
				'storage_id' => 'storageId',
				'storage' => 100,
				'item_source' => 3,
				'file_source' => 3,
				'file_parent' => 1,
				'file_target' => 'myTarget',
				'share_with' => 'recipient',
				'share_with_displayname' => 'recipient',
				'share_with_displayname_unique' => 'recipient',
				'mail_send' => 0,
				'mimetype' => 'myMimeType',
				'has_preview' => false,
				'hide_download' => 0,
				'can_edit' => false,
				'can_delete' => false,
				'item_size' => 123456,
				'item_mtime' => 1234567890,
				'is-mount-root' => false,
				'mount-type' => '',
				'attributes' => null,
				'item_permissions' => 1,
			], $share, [], false
		];

		$share['owner'] = 'currentUser';

		// User backend down
		$result[] = [
			[
				'id' => '42',
				'share_type' => IShare::TYPE_USER,
				'uid_owner' => 'initiator',
				'displayname_owner' => 'initiator',
				'permissions' => 1,
				'attributes' => null,
				'stime' => 946684862,
				'parent' => null,
				'expiration' => null,
				'token' => null,
				'uid_file_owner' => 'currentUser',
				'displayname_file_owner' => 'currentUser',
				'note' => 'personal note',
				'label' => '',
				'path' => 'file',
				'item_type' => 'file',
				'storage_id' => 'storageId',
				'storage' => 100,
				'item_source' => 3,
				'file_source' => 3,
				'file_parent' => 1,
				'file_target' => 'myTarget',
				'share_with' => 'recipient',
				'share_with_displayname' => 'recipient',
				'share_with_displayname_unique' => 'recipient',
				'mail_send' => 0,
				'mimetype' => 'myMimeType',
				'has_preview' => false,
				'hide_download' => 0,
				'can_edit' => true,
				'can_delete' => true,
				'item_size' => 123456,
				'item_mtime' => 1234567890,
				'is-mount-root' => false,
				'mount-type' => '',
				'attributes' => null,
				'item_permissions' => 11,
			], $share, [], false
		];

		// with existing group
		$share = [
			'type' => IShare::TYPE_GROUP,
			'owner' => 'owner',
			'sharedWith' => 'recipientGroup',
			'node' => $file,
			'note' => 'personal note',
		];

		$result[] = [
			[
				'id' => '42',
				'share_type' => IShare::TYPE_GROUP,
				'uid_owner' => 'initiator',
				'displayname_owner' => 'initiator',
				'permissions' => 1,
				'attributes' => null,
				'stime' => 946684862,
				'parent' => null,
				'expiration' => null,
				'token' => null,
				'uid_file_owner' => 'owner',
				'displayname_file_owner' => 'owner',
				'note' => 'personal note',
				'label' => '',
				'path' => 'file',
				'item_type' => 'file',
				'storage_id' => 'storageId',
				'storage' => 100,
				'item_source' => 3,
				'file_source' => 3,
				'file_parent' => 1,
				'file_target' => 'myTarget',
				'share_with' => 'recipientGroup',
				'share_with_displayname' => 'recipientGroupDisplayName',
				'mail_send' => 0,
				'mimetype' => 'myMimeType',
				'has_preview' => false,
				'hide_download' => 0,
				'can_edit' => false,
				'can_delete' => false,
				'item_size' => 123456,
				'item_mtime' => 1234567890,
				'is-mount-root' => false,
				'mount-type' => '',
				'attributes' => null,
				'item_permissions' => 1,
			], $share, [], false
		];

		// with unknown group / no group backend
		$share['sharedWith'] = 'recipientGroup2';

		$result[] = [
			[
				'id' => '42',
				'share_type' => IShare::TYPE_GROUP,
				'uid_owner' => 'initiator',
				'displayname_owner' => 'initiator',
				'permissions' => 1,
				'stime' => 946684862,
				'parent' => null,
				'expiration' => null,
				'token' => null,
				'uid_file_owner' => 'owner',
				'displayname_file_owner' => 'owner',
				'note' => 'personal note',
				'label' => '',
				'path' => 'file',
				'item_type' => 'file',
				'storage_id' => 'storageId',
				'storage' => 100,
				'item_source' => 3,
				'file_source' => 3,
				'file_parent' => 1,
				'file_target' => 'myTarget',
				'share_with' => 'recipientGroup2',
				'share_with_displayname' => 'recipientGroup2',
				'mail_send' => 0,
				'mimetype' => 'myMimeType',
				'has_preview' => false,
				'hide_download' => 0,
				'can_edit' => false,
				'can_delete' => false,
				'item_size' => 123456,
				'item_mtime' => 1234567890,
				'is-mount-root' => false,
				'mount-type' => '',
				'attributes' => null,
				'item_permissions' => 1,
			], $share, [], false
		];

		$share = [
			'type' => IShare::TYPE_LINK,
			'owner' => 'owner',
			'node' => $file,
			'note' => 'personal note',
			'password' => 'mypassword',
			'expirationDate' => new \DateTime('2001-01-02T00:00:00'),
			'token' => 'myToken',
			'label' => 'new link share',
		];

		$result[] = [
			[
				'id' => '42',
				'share_type' => IShare::TYPE_LINK,
				'uid_owner' => 'initiator',
				'displayname_owner' => 'initiator',
				'permissions' => 1,
				'attributes' => null,
				'stime' => 946684862,
				'parent' => null,
				'expiration' => '2001-01-02 00:00:00',
				'token' => 'myToken',
				'uid_file_owner' => 'owner',
				'displayname_file_owner' => 'owner',
				'note' => 'personal note',
				'label' => 'new link share',
				'path' => 'file',
				'item_type' => 'file',
				'storage_id' => 'storageId',
				'storage' => 100,
				'item_source' => 3,
				'file_source' => 3,
				'file_parent' => 1,
				'file_target' => 'myTarget',
				'password' => 'mypassword',
				'share_with' => 'mypassword',
				'share_with_displayname' => '(Shared link)',
				'send_password_by_talk' => false,
				'mail_send' => 0,
				'url' => 'myLink',
				'mimetype' => 'myMimeType',
				'has_preview' => false,
				'hide_download' => 0,
				'can_edit' => false,
				'can_delete' => false,
				'item_size' => 123456,
				'item_mtime' => 1234567890,
				'is-mount-root' => false,
				'mount-type' => '',
				'attributes' => null,
				'item_permissions' => 1,
			], $share, [], false
		];

		$share['sendPasswordByTalk'] = true;

		$result[] = [
			[
				'id' => '42',
				'share_type' => IShare::TYPE_LINK,
				'uid_owner' => 'initiator',
				'displayname_owner' => 'initiator',
				'permissions' => 1,
				'stime' => 946684862,
				'parent' => null,
				'expiration' => '2001-01-02 00:00:00',
				'token' => 'myToken',
				'uid_file_owner' => 'owner',
				'displayname_file_owner' => 'owner',
				'note' => 'personal note',
				'label' => 'new link share',
				'path' => 'file',
				'item_type' => 'file',
				'storage_id' => 'storageId',
				'storage' => 100,
				'item_source' => 3,
				'file_source' => 3,
				'file_parent' => 1,
				'file_target' => 'myTarget',
				'password' => 'mypassword',
				'share_with' => 'mypassword',
				'share_with_displayname' => '(Shared link)',
				'send_password_by_talk' => true,
				'mail_send' => 0,
				'url' => 'myLink',
				'mimetype' => 'myMimeType',
				'has_preview' => false,
				'hide_download' => 0,
				'can_edit' => false,
				'can_delete' => false,
				'item_size' => 123456,
				'item_mtime' => 1234567890,
				'is-mount-root' => false,
				'mount-type' => '',
				'attributes' => null,
				'item_permissions' => 1,
			], $share, [], false
		];

		$share = [
			'type' => IShare::TYPE_REMOTE,
			'owner' => 'owner',
			'sharedWith' => 'user@server.com',
			'node' => $folder,
			'note' => 'personal note',
			'expirationDate' => new \DateTime('2001-02-03T04:05:06'),
		];

		$result[] = [
			[
				'id' => '42',
				'share_type' => IShare::TYPE_REMOTE,
				'uid_owner' => 'initiator',
				'displayname_owner' => 'initiator',
				'permissions' => 1,
				'stime' => 946684862,
				'parent' => null,
				'expiration' => '2001-02-03 00:00:00',
				'token' => null,
				'uid_file_owner' => 'owner',
				'displayname_file_owner' => 'owner',
				'note' => 'personal note',
				'label' => '',
				'path' => 'folder',
				'item_type' => 'folder',
				'storage_id' => 'storageId',
				'storage' => 100,
				'item_source' => 2,
				'file_source' => 2,
				'file_parent' => 1,
				'file_target' => 'myTarget',
				'share_with' => 'user@server.com',
				'share_with_displayname' => 'foobar',
				'mail_send' => 0,
				'mimetype' => 'myFolderMimeType',
				'has_preview' => false,
				'hide_download' => 0,
				'can_edit' => false,
				'can_delete' => false,
				'item_size' => 123456,
				'item_mtime' => 1234567890,
				'is-mount-root' => false,
				'mount-type' => '',
				'attributes' => null,
				'item_permissions' => 1,
				'is_trusted_server' => false,
			], $share, [], false
		];

		$share = [
			'type' => IShare::TYPE_REMOTE_GROUP,
			'owner' => 'owner',
			'sharedWith' => 'user@server.com',
			'node' => $folder,
			'note' => 'personal note',
			'expirationDate' => new \DateTime('2001-02-03T04:05:06'),
		];

		$result[] = [
			[
				'id' => '42',
				'share_type' => IShare::TYPE_REMOTE_GROUP,
				'uid_owner' => 'initiator',
				'displayname_owner' => 'initiator',
				'permissions' => 1,
				'stime' => 946684862,
				'parent' => null,
				'expiration' => '2001-02-03 00:00:00',
				'token' => null,
				'uid_file_owner' => 'owner',
				'displayname_file_owner' => 'owner',
				'note' => 'personal note',
				'label' => '',
				'path' => 'folder',
				'item_type' => 'folder',
				'storage_id' => 'storageId',
				'storage' => 100,
				'item_source' => 2,
				'file_source' => 2,
				'file_parent' => 1,
				'file_target' => 'myTarget',
				'share_with' => 'user@server.com',
				'share_with_displayname' => 'foobar',
				'mail_send' => 0,
				'mimetype' => 'myFolderMimeType',
				'has_preview' => false,
				'hide_download' => 0,
				'can_edit' => false,
				'can_delete' => false,
				'item_size' => 123456,
				'item_mtime' => 1234567890,
				'is-mount-root' => false,
				'mount-type' => '',
				'attributes' => null,
				'item_permissions' => 1,
				'is_trusted_server' => false,
			], $share, [], false
		];

		// Circle with id, display name and avatar set by the Circles app
		$share = [
			'type' => IShare::TYPE_CIRCLE,
			'owner' => 'owner',
			'sharedWith' => 'Circle (Public circle, circleOwner) [4815162342]',
			'sharedWithDisplayName' => 'The display name',
			'sharedWithAvatar' => 'path/to/the/avatar',
			'node' => $folder,
		];

		$result[] = [
			[
				'id' => '42',
				'share_type' => IShare::TYPE_CIRCLE,
				'uid_owner' => 'initiator',
				'displayname_owner' => 'initiator',
				'permissions' => 1,
				'attributes' => null,
				'stime' => 946684862,
				'parent' => null,
				'expiration' => null,
				'token' => null,
				'uid_file_owner' => 'owner',
				'displayname_file_owner' => 'owner',
				'note' => '',
				'label' => '',
				'path' => 'folder',
				'item_type' => 'folder',
				'storage_id' => 'storageId',
				'storage' => 100,
				'item_source' => 2,
				'file_source' => 2,
				'file_parent' => 1,
				'file_target' => 'myTarget',
				'share_with' => '4815162342',
				'share_with_displayname' => 'The display name',
				'share_with_avatar' => 'path/to/the/avatar',
				'mail_send' => 0,
				'mimetype' => 'myFolderMimeType',
				'has_preview' => false,
				'hide_download' => 0,
				'can_edit' => false,
				'can_delete' => false,
				'item_size' => 123456,
				'item_mtime' => 1234567890,
				'is-mount-root' => false,
				'mount-type' => '',
				'attributes' => null,
				'item_permissions' => 1,
			], $share, [], false
		];

		// Circle with id set by the Circles app
		$share = [
			'type' => IShare::TYPE_CIRCLE,
			'owner' => 'owner',
			'sharedWith' => 'Circle (Public circle, circleOwner) [4815162342]',
			'node' => $folder,
		];

		$result[] = [
			[
				'id' => '42',
				'share_type' => IShare::TYPE_CIRCLE,
				'uid_owner' => 'initiator',
				'displayname_owner' => 'initiator',
				'permissions' => 1,
				'stime' => 946684862,
				'parent' => null,
				'expiration' => null,
				'token' => null,
				'uid_file_owner' => 'owner',
				'displayname_file_owner' => 'owner',
				'note' => '',
				'label' => '',
				'path' => 'folder',
				'item_type' => 'folder',
				'storage_id' => 'storageId',
				'storage' => 100,
				'item_source' => 2,
				'file_source' => 2,
				'file_parent' => 1,
				'file_target' => 'myTarget',
				'share_with' => '4815162342',
				'share_with_displayname' => 'Circle (Public circle, circleOwner)',
				'share_with_avatar' => '',
				'mail_send' => 0,
				'mimetype' => 'myFolderMimeType',
				'has_preview' => false,
				'hide_download' => 0,
				'can_edit' => false,
				'can_delete' => false,
				'item_size' => 123456,
				'item_mtime' => 1234567890,
				'is-mount-root' => false,
				'mount-type' => '',
				'attributes' => null,
				'item_permissions' => 1,
			], $share, [], false
		];

		// Circle with id not set by the Circles app
		$share = [
			'type' => IShare::TYPE_CIRCLE,
			'owner' => 'owner',
			'sharedWith' => 'Circle (Public circle, circleOwner)',
			'node' => $folder,
		];

		$result[] = [
			[
				'id' => '42',
				'share_type' => IShare::TYPE_CIRCLE,
				'uid_owner' => 'initiator',
				'displayname_owner' => 'initiator',
				'permissions' => 1,
				'stime' => 946684862,
				'parent' => null,
				'expiration' => null,
				'token' => null,
				'uid_file_owner' => 'owner',
				'displayname_file_owner' => 'owner',
				'note' => '',
				'label' => '',
				'path' => 'folder',
				'item_type' => 'folder',
				'storage_id' => 'storageId',
				'storage' => 100,
				'item_source' => 2,
				'file_source' => 2,
				'file_parent' => 1,
				'file_target' => 'myTarget',
				'share_with' => 'Circle',
				'share_with_displayname' => 'Circle (Public circle, circleOwner)',
				'share_with_avatar' => '',
				'mail_send' => 0,
				'mimetype' => 'myFolderMimeType',
				'has_preview' => false,
				'hide_download' => 0,
				'can_edit' => false,
				'can_delete' => false,
				'item_size' => 123456,
				'item_mtime' => 1234567890,
				'is-mount-root' => false,
				'mount-type' => '',
				'attributes' => null,
				'item_permissions' => 1,
			], $share, [], false
		];

		// No node
		$share = [
			'type' => IShare::TYPE_USER,
			'owner' => 'owner',
			'sharedWith' => 'recipient',
			'note' => 'personal note',
		];

		$result[] = [
			[], $share, [], true
		];

		$share = [
			'type' => IShare::TYPE_EMAIL,
			'owner' => 'owner',
			'sharedWith' => 'user@server.com',
			'node' => $folder,
			'password' => 'password',
		];

		$result[] = [
			[
				'id' => '42',
				'share_type' => IShare::TYPE_EMAIL,
				'uid_owner' => 'initiator',
				'displayname_owner' => 'initiator',
				'permissions' => 1,
				'stime' => 946684862,
				'parent' => null,
				'expiration' => null,
				'token' => null,
				'uid_file_owner' => 'owner',
				'displayname_file_owner' => 'owner',
				'note' => '',
				'label' => '',
				'path' => 'folder',
				'item_type' => 'folder',
				'storage_id' => 'storageId',
				'storage' => 100,
				'item_source' => 2,
				'file_source' => 2,
				'file_parent' => 1,
				'file_target' => 'myTarget',
				'share_with' => 'user@server.com',
				'share_with_displayname' => 'mail display name',
				'mail_send' => 0,
				'mimetype' => 'myFolderMimeType',
				'has_preview' => false,
				'password' => 'password',
				'send_password_by_talk' => false,
				'hide_download' => 0,
				'can_edit' => false,
				'can_delete' => false,
				'password_expiration_time' => null,
				'item_size' => 123456,
				'item_mtime' => 1234567890,
				'is-mount-root' => false,
				'mount-type' => '',
				'attributes' => null,
				'item_permissions' => 1,
			], $share, [], false
		];

		$share['sendPasswordByTalk'] = true;

		$result[] = [
			[
				'id' => '42',
				'share_type' => IShare::TYPE_EMAIL,
				'uid_owner' => 'initiator',
				'displayname_owner' => 'initiator',
				'permissions' => 1,
				'stime' => 946684862,
				'parent' => null,
				'expiration' => null,
				'token' => null,
				'uid_file_owner' => 'owner',
				'displayname_file_owner' => 'owner',
				'note' => '',
				'label' => '',
				'path' => 'folder',
				'item_type' => 'folder',
				'storage_id' => 'storageId',
				'storage' => 100,
				'item_source' => 2,
				'file_source' => 2,
				'file_parent' => 1,
				'file_target' => 'myTarget',
				'share_with' => 'user@server.com',
				'share_with_displayname' => 'mail display name',
				'mail_send' => 0,
				'mimetype' => 'myFolderMimeType',
				'has_preview' => false,
				'password' => 'password',
				'send_password_by_talk' => true,
				'hide_download' => 0,
				'can_edit' => false,
				'can_delete' => false,
				'password_expiration_time' => null,
				'item_size' => 123456,
				'item_mtime' => 1234567890,
				'is-mount-root' => false,
				'mount-type' => '',
				'attributes' => null,
				'item_permissions' => 1,
			], $share, [], false
		];

		// Preview is available
		$share = [
			'type' => IShare::TYPE_USER,
			'owner' => 'currentUser',
			'sharedWith' => 'recipient',
			'node' => $fileWithPreview,
			'note' => 'personal note',
		];

		$result[] = [
			[
				'id' => '42',
				'share_type' => IShare::TYPE_USER,
				'uid_owner' => 'initiator',
				'displayname_owner' => 'initiator',
				'permissions' => 1,
				'stime' => 946684862,
				'parent' => null,
				'expiration' => null,
				'token' => null,
				'uid_file_owner' => 'currentUser',
				'displayname_file_owner' => 'currentUser',
				'note' => 'personal note',
				'label' => '',
				'path' => 'fileWithPreview',
				'item_type' => 'file',
				'storage_id' => 'storageId',
				'storage' => 100,
				'item_source' => 4,
				'file_source' => 4,
				'file_parent' => 1,
				'file_target' => 'myTarget',
				'share_with' => 'recipient',
				'share_with_displayname' => 'recipient',
				'share_with_displayname_unique' => 'recipient',
				'mail_send' => 0,
				'mimetype' => 'mimeWithPreview',
				'has_preview' => true,
				'hide_download' => 0,
				'can_edit' => true,
				'can_delete' => true,
				'item_size' => 123456,
				'item_mtime' => 1234567890,
				'is-mount-root' => false,
				'mount-type' => '',
				'attributes' => null,
				'item_permissions' => 11,
			], $share, [], false
		];

		return $result;
	}

	#[DataProvider(methodName: 'dataFormatShare')]
	public function testFormatShare(
		array $expects,
		array $shareParams,
		array $users,
		bool $exception,
	): void {
		$users = array_map(
			function ($user) {
				$mock = $this->createMock(IUser::class);
				foreach ($user[1] as $method => $return) {
					$mock->method($method)->willReturn($return);
				}
				return [$user[0],$mock];
			},
			$users
		);

		$share = Server::get(IManager::class)->newShare();
		$share->setShareType($shareParams['type'])
			->setSharedBy('initiator')
			->setShareOwner($shareParams['owner'])
			->setPermissions(Constants::PERMISSION_READ)
			->setShareTime(new \DateTime('2000-01-01T00:01:02'))
			->setTarget('myTarget')
			->setId(42);
		if (isset($shareParams['sharedWith'])) {
			$share->setSharedWith($shareParams['sharedWith']);
		}
		if (isset($shareParams['sharedWithDisplayName'])) {
			$share->setSharedWithDisplayName($shareParams['sharedWithDisplayName']);
		}
		if (isset($shareParams['sharedWithAvatar'])) {
			$share->setSharedWithAvatar($shareParams['sharedWithAvatar']);
		}
		if (isset($shareParams['attributes'])) {
			$shareAttributes = $this->createMock(IShareAttributes::class);
			$shareAttributes->method('toArray')->willReturn($shareParams['attributes']);
			$shareAttributes->method('getAttribute')->with('permissions', 'download')->willReturn(true);
			$share->setAttributes($shareAttributes);

			$expects['attributes'] = \json_encode($shareParams['attributes']);
		}
		if (isset($shareParams['node'])) {
			$node = $this->createMock($shareParams['node']['class']);

			$node->method('getMimeType')->willReturn($shareParams['node']['mimeType']);

			$mountPoint = $this->createMock(IMountPoint::class);
			$mountPoint->method('getMountType')->willReturn('');
			$node->method('getMountPoint')->willReturn($mountPoint);

			$node->method('getPath')->willReturn($shareParams['node']['path']);
			$node->method('getId')->willReturn($shareParams['node']['id']);

			$parent = $this->createMock(Folder::class);
			$parent->method('getId')->willReturn(1);
			$node->method('getParent')->willReturn($parent);

			$node->method('getSize')->willReturn(123456);
			$node->method('getMTime')->willReturn(1234567890);

			$cache = $this->createMock(ICache::class);
			$cache->method('getNumericStorageId')->willReturn(100);
			$storage = $this->createMock(IStorage::class);
			$storage->method('getId')->willReturn('storageId');
			$storage->method('getCache')->willReturn($cache);

			$node->method('getStorage')->willReturn($storage);

			$share->setNode($node);
		}
		if (isset($shareParams['note'])) {
			$share->setNote($shareParams['note']);
		}
		if (isset($shareParams['expirationDate'])) {
			$share->setExpirationDate($shareParams['expirationDate']);
		}
		if (isset($shareParams['token'])) {
			$share->setToken($shareParams['token']);
		}
		if (isset($shareParams['label'])) {
			$share->setLabel($shareParams['label']);
		}
		if (isset($shareParams['password'])) {
			$share->setPassword($shareParams['password']);
		}
		if (isset($shareParams['sendPasswordByTalk'])) {
			$share->setSendPasswordByTalk($shareParams['sendPasswordByTalk']);
		}

		$this->userManager->method('get')->willReturnMap($users);

		$recipientGroup = $this->createMock(IGroup::class);
		$recipientGroup->method('getDisplayName')->willReturn('recipientGroupDisplayName');
		$this->groupManager->method('get')->willReturnMap([
			['recipientGroup', $recipientGroup],
		]);

		$this->urlGenerator->method('linkToRouteAbsolute')
			->with('files_sharing.sharecontroller.showShare', ['token' => 'myToken'])
			->willReturn('myLink');

		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturnSelf();
		$this->dateTimeZone->method('getTimezone')->willReturn(new \DateTimeZone('UTC'));

		if (!$exception) {
			$this->rootFolder->method('getFirstNodeById')
				->with($share->getNodeId())
				->willReturn($share->getNode());

			$this->rootFolder->method('getRelativePath')
				->with($share->getNode()->getPath())
				->willReturnArgument(0);
		}

		$cm = $this->createMock(\OCP\Contacts\IManager::class);
		$this->overwriteService(\OCP\Contacts\IManager::class, $cm);

		$cm->method('search')
			->willReturnMap([
				['user@server.com', ['CLOUD'], [
					'limit' => 1,
					'enumeration' => false,
					'strict_search' => true,
				],
					[
						[
							'CLOUD' => [
								'user@server.com',
							],
							'FN' => 'foobar',
						],
					],
				],
				['user@server.com', ['EMAIL'], [
					'limit' => 1,
					'enumeration' => false,
					'strict_search' => true,
				],
					[
						[
							'EMAIL' => [
								'user@server.com',
							],
							'FN' => 'mail display name',
						],
					],
				],
			]);

		try {
			$result = $this->invokePrivate($this->ocs, 'formatShare', [$share]);
			$this->assertFalse($exception);
			$this->assertEquals($expects, $result);
		} catch (NotFoundException $e) {
			$this->assertTrue($exception);
		}
	}

	public static function dataFormatRoomShare(): array {
		$result = [];

		$result[] = [
			[
				'id' => '42',
				'share_type' => IShare::TYPE_ROOM,
				'uid_owner' => 'initiator',
				'displayname_owner' => 'initiator',
				'permissions' => 1,
				'stime' => 946684862,
				'parent' => null,
				'expiration' => null,
				'token' => null,
				'uid_file_owner' => 'owner',
				'displayname_file_owner' => 'owner',
				'note' => 'personal note',
				'path' => 'file',
				'item_type' => 'file',
				'storage_id' => 'storageId',
				'storage' => 100,
				'item_source' => 3,
				'file_source' => 3,
				'file_parent' => 1,
				'file_target' => 'myTarget',
				'share_with' => 'recipientRoom',
				'share_with_displayname' => '',
				'mail_send' => 0,
				'mimetype' => 'myMimeType',
				'has_preview' => false,
				'hide_download' => 0,
				'label' => '',
				'can_edit' => false,
				'can_delete' => false,
				'item_size' => 123456,
				'item_mtime' => 1234567890,
				'is-mount-root' => false,
				'mount-type' => '',
				'attributes' => null,
				'item_permissions' => 1,
			], false, []
		];

		$result[] = [
			[
				'id' => '42',
				'share_type' => IShare::TYPE_ROOM,
				'uid_owner' => 'initiator',
				'displayname_owner' => 'initiator',
				'permissions' => 1,
				'stime' => 946684862,
				'parent' => null,
				'expiration' => null,
				'token' => null,
				'uid_file_owner' => 'owner',
				'displayname_file_owner' => 'owner',
				'note' => 'personal note',
				'path' => 'file',
				'item_type' => 'file',
				'storage_id' => 'storageId',
				'storage' => 100,
				'item_source' => 3,
				'file_source' => 3,
				'file_parent' => 1,
				'file_target' => 'myTarget',
				'share_with' => 'recipientRoom',
				'share_with_displayname' => 'recipientRoomName',
				'mail_send' => 0,
				'mimetype' => 'myMimeType',
				'has_preview' => false,
				'hide_download' => 0,
				'label' => '',
				'can_edit' => false,
				'can_delete' => false,
				'item_size' => 123456,
				'item_mtime' => 1234567890,
				'is-mount-root' => false,
				'mount-type' => '',
				'attributes' => null,
				'item_permissions' => 9,
			], true, [
				'share_with_displayname' => 'recipientRoomName'
			]
		];

		return $result;
	}

	/**
	 *
	 * @param array $expects
	 * @param IShare $share
	 * @param bool $helperAvailable
	 * @param array $formatShareByHelper
	 */
	#[DataProvider(methodName: 'dataFormatRoomShare')]
	public function testFormatRoomShare(array $expects, bool $helperAvailable, array $formatShareByHelper): void {
		$file = $this->createMock(File::class);

		$file->method('getMimeType')->willReturn('myMimeType');
		$file->method('getPath')->willReturn('file');
		$file->method('getId')->willReturn(3);

		$parent = $this->createMock(Folder::class);
		$parent->method('getId')->willReturn(1);
		$file->method('getParent')->willReturn($parent);

		$file->method('getSize')->willReturn(123456);
		$file->method('getMTime')->willReturn(1234567890);

		$mountPoint = $this->createMock(IMountPoint::class);
		$mountPoint->method('getMountType')->willReturn('');
		$file->method('getMountPoint')->willReturn($mountPoint);

		$cache = $this->createMock(ICache::class);
		$cache->method('getNumericStorageId')->willReturn(100);
		$storage = $this->createMock(IStorage::class);
		$storage->method('getId')->willReturn('storageId');
		$storage->method('getCache')->willReturn($cache);

		$file->method('getStorage')->willReturn($storage);

		$share = Server::get(IManager::class)->newShare();
		$share->setShareType(IShare::TYPE_ROOM)
			->setSharedWith('recipientRoom')
			->setSharedBy('initiator')
			->setShareOwner('owner')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($file)
			->setShareTime(new \DateTime('2000-01-01T00:01:02'))
			->setTarget('myTarget')
			->setNote('personal note')
			->setId(42);

		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturnSelf();

		$this->rootFolder->method('getFirstNodeById')
			->with($share->getNodeId())
			->willReturn($share->getNode());

		$this->rootFolder->method('getRelativePath')
			->with($share->getNode()->getPath())
			->willReturnArgument(0);

		if (!$helperAvailable) {
			$this->appManager->method('isEnabledForUser')
				->with('spreed')
				->willReturn(false);
		} else {
			$this->appManager->method('isEnabledForUser')
				->with('spreed')
				->willReturn(true);

			// This is not possible anymore with PHPUnit 10+
			// as `setMethods` was removed and now real reflection is used, thus the class needs to exist.
			// $helper = $this->getMockBuilder('\OCA\Talk\Share\Helper\ShareAPIController')
			$helper = $this->getMockBuilder(\stdClass::class)
				->addMethods(['formatShare', 'canAccessShare'])
				->getMock();
			$helper->method('formatShare')
				->with($share)
				->willReturn($formatShareByHelper);
			$helper->method('canAccessShare')
				->with($share)
				->willReturn(true);

			$this->serverContainer->method('get')
				->with('\OCA\Talk\Share\Helper\ShareAPIController')
				->willReturn($helper);
		}

		$result = $this->invokePrivate($this->ocs, 'formatShare', [$share]);
		$this->assertEquals($expects, $result);
	}

	/**
	 * @return list{Folder, Folder}
	 */
	private function getNonSharedUserFolder(): array {
		$node = $this->getMockBuilder(Folder::class)->getMock();
		$userFolder = $this->getMockBuilder(Folder::class)->getMock();
		$storage = $this->createMock(IStorage::class);
		$storage->method('instanceOfStorage')
			->willReturnMap([
				['OCA\Files_Sharing\External\Storage', false],
				['OCA\Files_Sharing\SharedStorage', false],
			]);
		$userFolder->method('getStorage')->willReturn($storage);
		$node->method('getStorage')->willReturn($storage);
		$node->method('getId')->willReturn(42);
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn($this->currentUser);
		$node->method('getOwner')->willReturn($user);
		return [$userFolder, $node];
	}

	/**
	 * @return list{Folder, File}
	 */
	private function getNonSharedUserFile(): array {
		$node = $this->getMockBuilder(File::class)->getMock();
		$userFolder = $this->getMockBuilder(Folder::class)->getMock();
		$storage = $this->createMock(IStorage::class);
		$storage->method('instanceOfStorage')
			->willReturnMap([
				['OCA\Files_Sharing\External\Storage', false],
				['OCA\Files_Sharing\SharedStorage', false],
			]);
		$userFolder->method('getStorage')->willReturn($storage);
		$node->method('getStorage')->willReturn($storage);
		$node->method('getId')->willReturn(42);
		return [$userFolder, $node];
	}

	public function testPopulateTags(): void {
		$tagger = $this->createMock(ITags::class);
		$this->tagManager->method('load')
			->with('files')
			->willReturn($tagger);
		$data = [
			['file_source' => 10],
			['file_source' => 22, 'foo' => 'bar'],
			['file_source' => 42, 'x' => 'y'],
		];
		$tags = [
			10 => ['tag3'],
			42 => ['tag1', 'tag2'],
		];
		$tagger->method('getTagsForObjects')
			->with([10, 22, 42])
			->willReturn($tags);

		$result = self::invokePrivate($this->ocs, 'populateTags', [$data]);
		$this->assertSame([
			['file_source' => 10, 'tags' => ['tag3']],
			['file_source' => 22, 'foo' => 'bar', 'tags' => []],
			['file_source' => 42, 'x' => 'y', 'tags' => ['tag1', 'tag2']],
		], $result);
	}

	public static function trustedServerProvider(): array {
		return [
			'Trusted server' => [true, true],
			'Untrusted server' => [false, false],
		];
	}

	#[DataProvider(methodName: 'trustedServerProvider')]
	public function testFormatShareWithFederatedShare(bool $isKnownServer, bool $isTrusted): void {
		$nodeId = 12;
		$nodePath = '/test.txt';

		$node = $this->createMock(File::class);
		$node->method('getId')->willReturn($nodeId);
		$node->method('getPath')->willReturn($nodePath);
		$node->method('getInternalPath')->willReturn(ltrim($nodePath, '/'));
		$mountPoint = $this->createMock(IMountPoint::class);
		$mountPoint->method('getMountType')->willReturn('local');
		$node->method('getMountPoint')->willReturn($mountPoint);
		$node->method('getMimetype')->willReturn('text/plain');
		$storage = $this->createMock(IStorage::class);
		$storageCache = $this->createMock(ICache::class);
		$storageCache->method('getNumericStorageId')->willReturn(1);
		$storage->method('getCache')->willReturn($storageCache);
		$storage->method('getId')->willReturn('home::shareOwner');
		$node->method('getStorage')->willReturn($storage);
		$parent = $this->createMock(Folder::class);
		$parent->method('getId')->willReturn(2);
		$node->method('getParent')->willReturn($parent);
		$node->method('getSize')->willReturn(1234);
		$node->method('getMTime')->willReturn(1234567890);

		$share = $this->createShare(
			1,
			IShare::TYPE_REMOTE,
			'recipient@remoteserver.com', // shared with
			'sender@testserver.com',      // shared by
			'shareOwner',                 // share owner
			$node,
			Constants::PERMISSION_READ,
			time(),
			null,
			2,
			$nodePath,
			$nodeId
		);

		$this->previewManager->method('isAvailable')->with($node)->willReturn(false);

		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturnSelf();

		$this->rootFolder->method('getFirstNodeById')
			->with($share->getNodeId())
			->willReturn($node);

		$this->rootFolder->method('getRelativePath')
			->with($node->getPath())
			->willReturnArgument(0);

		$serverName = 'remoteserver.com';
		$this->trustedServers->method('isTrustedServer')
			->with($serverName)
			->willReturn($isKnownServer);

		$result = $this->invokePrivate($this->ocs, 'formatShare', [$share]);

		$this->assertSame($isTrusted, $result['is_trusted_server']);
	}

	public function testFormatShareWithFederatedShareWithAtInUsername(): void {
		$nodeId = 12;
		$nodePath = '/test.txt';

		$node = $this->createMock(File::class);
		$node->method('getId')->willReturn($nodeId);
		$node->method('getPath')->willReturn($nodePath);
		$node->method('getInternalPath')->willReturn(ltrim($nodePath, '/'));
		$mountPoint = $this->createMock(IMountPoint::class);
		$mountPoint->method('getMountType')->willReturn('local');
		$node->method('getMountPoint')->willReturn($mountPoint);
		$node->method('getMimetype')->willReturn('text/plain');
		$storage = $this->createMock(IStorage::class);
		$storageCache = $this->createMock(ICache::class);
		$storageCache->method('getNumericStorageId')->willReturn(1);
		$storage->method('getCache')->willReturn($storageCache);
		$storage->method('getId')->willReturn('home::shareOwner');
		$node->method('getStorage')->willReturn($storage);
		$parent = $this->createMock(Folder::class);
		$parent->method('getId')->willReturn(2);
		$node->method('getParent')->willReturn($parent);
		$node->method('getSize')->willReturn(1234);
		$node->method('getMTime')->willReturn(1234567890);

		$share = $this->createShare(
			1,
			IShare::TYPE_REMOTE,
			'recipient@domain.com@remoteserver.com',
			'sender@testserver.com',
			'shareOwner',
			$node,
			Constants::PERMISSION_READ,
			time(),
			null,
			2,
			$nodePath,
			$nodeId
		);

		$this->previewManager->method('isAvailable')->with($node)->willReturn(false);

		$this->rootFolder->method('getUserFolder')
			->with($this->currentUser)
			->willReturnSelf();

		$this->rootFolder->method('getFirstNodeById')
			->with($share->getNodeId())
			->willReturn($node);

		$this->rootFolder->method('getRelativePath')
			->with($node->getPath())
			->willReturnArgument(0);

		$serverName = 'remoteserver.com';
		$this->trustedServers->method('isTrustedServer')
			->with($serverName)
			->willReturn(true);

		$result = $this->invokePrivate($this->ocs, 'formatShare', [$share]);

		$this->assertTrue($result['is_trusted_server']);
	}

	public function testOwnerCanAlwaysDownload(): void {
		$ocs = $this->mockFormatShare();

		$share = $this->createMock(IShare::class);
		$node = $this->createMock(File::class);
		$userFolder = $this->createMock(Folder::class);
		$owner = $this->createMock(IUser::class);

		$share->method('getSharedBy')->willReturn('sharedByUser');
		$share->method('getNodeId')->willReturn(42);
		$node->method('getOwner')->willReturn($owner);
		$owner->method('getUID')->willReturn('sharedByUser');

		$userFolder->method('getById')->with(42)->willReturn([$node]);
		$this->rootFolder->method('getUserFolder')->with('sharedByUser')->willReturn($userFolder);

		// Expect hideDownload to be set to false since owner can always download
		$share->expects($this->once())->method('setHideDownload')->with(false);

		$this->invokePrivate($ocs, 'checkInheritedAttributes', [$share]);
	}

	public function testParentHideDownloadEnforcedOnChild(): void {
		$ocs = $this->mockFormatShare();

		$share = $this->createMock(IShare::class);
		$node = $this->createMock(File::class);
		$userFolder = $this->createMock(Folder::class);
		$owner = $this->createMock(IUser::class);
		$storage = $this->createMock(SharedStorage::class);
		$originalShare = $this->createMock(IShare::class);

		$share->method('getSharedBy')->willReturn('sharedByUser');
		$share->method('getNodeId')->willReturn(42);
		$share->method('getHideDownload')->willReturn(false); // User wants to allow downloads
		$node->method('getOwner')->willReturn($owner);
		$owner->method('getUID')->willReturn('differentOwner');
		$node->method('getStorage')->willReturn($storage);
		$storage->method('instanceOfStorage')->with(SharedStorage::class)->willReturn(true);
		$storage->method('getInstanceOfStorage')->with(SharedStorage::class)->willReturn($storage);
		$storage->method('getShare')->willReturn($originalShare);
		$originalShare->method('getHideDownload')->willReturn(true); // Parent hides download
		$originalShare->method('getAttributes')->willReturn(null);

		$userFolder->method('getById')->with(42)->willReturn([$node]);
		$this->rootFolder->method('getUserFolder')->with('sharedByUser')->willReturn($userFolder);

		// Should be forced to hide download due to parent restriction
		$share->expects($this->once())->method('setHideDownload')->with(true);

		$this->invokePrivate($ocs, 'checkInheritedAttributes', [$share]);
	}

	public function testUserCanHideWhenParentAllows(): void {
		$ocs = $this->mockFormatShare();

		$share = $this->createMock(IShare::class);
		$node = $this->createMock(File::class);
		$userFolder = $this->createMock(Folder::class);
		$owner = $this->createMock(IUser::class);
		$storage = $this->createMock(SharedStorage::class);
		$originalShare = $this->createMock(IShare::class);

		$share->method('getSharedBy')->willReturn('sharedByUser');
		$share->method('getNodeId')->willReturn(42);
		$share->method('getHideDownload')->willReturn(true); // User chooses to hide downloads
		$node->method('getOwner')->willReturn($owner);
		$owner->method('getUID')->willReturn('differentOwner');
		$node->method('getStorage')->willReturn($storage);
		$storage->method('instanceOfStorage')->with(SharedStorage::class)->willReturn(true);
		$storage->method('getInstanceOfStorage')->with(SharedStorage::class)->willReturn($storage);
		$storage->method('getShare')->willReturn($originalShare);
		$originalShare->method('getHideDownload')->willReturn(false); // Parent allows download
		$originalShare->method('getAttributes')->willReturn(null);

		$userFolder->method('getById')->with(42)->willReturn([$node]);
		$this->rootFolder->method('getUserFolder')->with('sharedByUser')->willReturn($userFolder);

		// Should respect user's choice to hide downloads
		$share->expects($this->once())->method('setHideDownload')->with(true);

		$this->invokePrivate($ocs, 'checkInheritedAttributes', [$share]);
	}

	public function testParentDownloadAttributeInherited(): void {
		$ocs = $this->mockFormatShare();

		$share = $this->createMock(IShare::class);
		$node = $this->createMock(File::class);
		$userFolder = $this->createMock(Folder::class);
		$owner = $this->createMock(IUser::class);
		$storage = $this->createMock(SharedStorage::class);
		$originalShare = $this->createMock(IShare::class);
		$attributes = $this->createMock(IShareAttributes::class);
		$shareAttributes = $this->createMock(IShareAttributes::class);

		$share->method('getSharedBy')->willReturn('sharedByUser');
		$share->method('getNodeId')->willReturn(42);
		$share->method('getHideDownload')->willReturn(false); // User wants to allow downloads
		$share->method('getAttributes')->willReturn($shareAttributes);
		$share->method('newAttributes')->willReturn($shareAttributes);
		$node->method('getOwner')->willReturn($owner);
		$owner->method('getUID')->willReturn('differentOwner');
		$node->method('getStorage')->willReturn($storage);
		$storage->method('instanceOfStorage')->with(SharedStorage::class)->willReturn(true);
		$storage->method('getInstanceOfStorage')->with(SharedStorage::class)->willReturn($storage);
		$storage->method('getShare')->willReturn($originalShare);
		$originalShare->method('getHideDownload')->willReturn(false);
		$originalShare->method('getAttributes')->willReturn($attributes);
		$attributes->method('getAttribute')->with('permissions', 'download')->willReturn(false); // Parent forbids download

		$userFolder->method('getById')->with(42)->willReturn([$node]);
		$this->rootFolder->method('getUserFolder')->with('sharedByUser')->willReturn($userFolder);

		// Should be forced to hide download and set download attribute to false
		$share->expects($this->once())->method('setHideDownload')->with(true);
		$shareAttributes->expects($this->once())->method('setAttribute')->with('permissions', 'download', false);
		$share->expects($this->once())->method('setAttributes')->with($shareAttributes);

		$this->invokePrivate($ocs, 'checkInheritedAttributes', [$share]);
	}

	public function testFederatedStorageRespectsUserChoice(): void {
		$ocs = $this->mockFormatShare();

		$share = $this->createMock(IShare::class);
		$node = $this->createMock(File::class);
		$userFolder = $this->createMock(Folder::class);
		$owner = $this->createMock(IUser::class);
		$storage = $this->createMock(Storage::class);

		$share->method('getSharedBy')->willReturn('sharedByUser');
		$share->method('getNodeId')->willReturn(42);
		$share->method('getHideDownload')->willReturn(true); // User chooses to hide downloads
		$node->method('getOwner')->willReturn($owner);
		$owner->method('getUID')->willReturn('differentOwner');
		$node->method('getStorage')->willReturn($storage);
		$storage->method('instanceOfStorage')->willReturnMap([
			[SharedStorage::class, false],
			[Storage::class, true]
		]);

		$userFolder->method('getById')->with(42)->willReturn([$node]);
		$this->rootFolder->method('getUserFolder')->with('sharedByUser')->willReturn($userFolder);

		// For federated storage, should respect user's choice
		$share->expects($this->once())->method('setHideDownload')->with(true);

		$this->invokePrivate($ocs, 'checkInheritedAttributes', [$share]);
	}

	public function testUserAllowsDownloadWhenParentPermits(): void {
		$ocs = $this->mockFormatShare();

		$share = $this->createMock(IShare::class);
		$node = $this->createMock(File::class);
		$userFolder = $this->createMock(Folder::class);
		$owner = $this->createMock(IUser::class);
		$storage = $this->createMock(SharedStorage::class);
		$originalShare = $this->createMock(IShare::class);

		$share->method('getSharedBy')->willReturn('sharedByUser');
		$share->method('getNodeId')->willReturn(42);
		$share->method('getHideDownload')->willReturn(false); // User wants to allow downloads
		$node->method('getOwner')->willReturn($owner);
		$owner->method('getUID')->willReturn('differentOwner');
		$node->method('getStorage')->willReturn($storage);
		$storage->method('instanceOfStorage')->with(SharedStorage::class)->willReturn(true);
		$storage->method('getInstanceOfStorage')->with(SharedStorage::class)->willReturn($storage);
		$storage->method('getShare')->willReturn($originalShare);
		$originalShare->method('getHideDownload')->willReturn(false); // Parent allows download
		$originalShare->method('getAttributes')->willReturn(null);

		$userFolder->method('getById')->with(42)->willReturn([$node]);
		$this->rootFolder->method('getUserFolder')->with('sharedByUser')->willReturn($userFolder);

		// Should allow downloads as both user and parent permit it
		$share->expects($this->once())->method('setHideDownload')->with(false);

		$this->invokePrivate($ocs, 'checkInheritedAttributes', [$share]);
	}

	public function testWrapperStorageUnwrapped(): void {
		$ocs = $this->mockFormatShare();

		$share = $this->createMock(IShare::class);
		$node = $this->createMock(File::class);
		$userFolder = $this->createMock(Folder::class);
		$owner = $this->createMock(IUser::class);
		$wrapperStorage = $this->createMock(Wrapper::class);
		$innerStorage = $this->createMock(SharedStorage::class);
		$originalShare = $this->createMock(IShare::class);

		$share->method('getSharedBy')->willReturn('sharedByUser');
		$share->method('getNodeId')->willReturn(42);
		$share->method('getHideDownload')->willReturn(false);
		$node->method('getOwner')->willReturn($owner);
		$owner->method('getUID')->willReturn('differentOwner');
		$node->method('getStorage')->willReturn($wrapperStorage);
		$wrapperStorage->method('instanceOfStorage')->with(SharedStorage::class)->willReturn(true);
		$wrapperStorage->method('getInstanceOfStorage')->with(SharedStorage::class)->willReturn($innerStorage);
		$innerStorage->method('getShare')->willReturn($originalShare);
		$originalShare->method('getHideDownload')->willReturn(false);
		$originalShare->method('getAttributes')->willReturn(null);

		$userFolder->method('getById')->with(42)->willReturn([$node]);
		$this->rootFolder->method('getUserFolder')->with('sharedByUser')->willReturn($userFolder);

		$share->expects($this->once())->method('setHideDownload')->with(false);

		$this->invokePrivate($ocs, 'checkInheritedAttributes', [$share]);
	}
}
