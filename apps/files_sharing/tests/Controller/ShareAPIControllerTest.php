<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Files_Sharing\Tests\Controller;

use OCA\Files_Sharing\Controller\ShareAPIController;
use OCP\App\IAppManager;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSBadRequestException;
use OCP\AppFramework\OCS\OCSException;
use OCP\AppFramework\OCS\OCSForbiddenException;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\Constants;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Mount\IMountPoint;
use OCP\Files\Mount\IShareOwnerlessMount;
use OCP\Files\NotFoundException;
use OCP\Files\Storage\IStorage;
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
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Test\TestCase;

/**
 * Class ShareAPIControllerTest
 *
 * @package OCA\Files_Sharing\Tests\Controller
 * @group DB
 */
class ShareAPIControllerTest extends TestCase {

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
	private IAppManager&MockObject $appManager;
	private ContainerInterface&MockObject $serverContainer;
	private IUserStatusManager&MockObject $userStatusManager;
	private IPreview&MockObject $previewManager;
	private IDateTimeZone&MockObject $dateTimeZone;
	private LoggerInterface&MockObject $logger;
	private IProviderFactory&MockObject $factory;
	private IMailer&MockObject $mailer;
	private ITagManager&MockObject $tagManager;

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
			$this->appManager,
			$this->serverContainer,
			$this->userStatusManager,
			$this->previewManager,
			$this->dateTimeZone,
			$this->logger,
			$this->factory,
			$this->mailer,
			$this->tagManager,
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
				$this->appManager,
				$this->serverContainer,
				$this->userStatusManager,
				$this->previewManager,
				$this->dateTimeZone,
				$this->logger,
				$this->factory,
				$this->mailer,
				$this->tagManager,
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
			->expects($this->exactly(7))
			->method('getShareById')
			->willReturnCallback(function ($id): void {
				if ($id === 'ocinternal:42' || $id === 'ocRoomShare:42' || $id === 'ocFederatedSharing:42' || $id === 'ocCircleShare:42' || $id === 'ocMailShare:42' || $id === 'deck:42' || $id === 'sciencemesh:42') {
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
			->will($this->throwException(new LockedException('mypath')));

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

	public function createShare($id, $shareType, $sharedWith, $sharedBy, $shareOwner, $path, $permissions,
		$shareTime, $expiration, $parent, $target, $mail_send, $note = '', $token = null,
		$password = null, $label = '', $attributes = null) {
		$share = $this->getMockBuilder(IShare::class)->getMock();
		$share->method('getId')->willReturn($id);
		$share->method('getShareType')->willReturn($shareType);
		$share->method('getSharedWith')->willReturn($sharedWith);
		$share->method('getSharedBy')->willReturn($sharedBy);
		$share->method('getShareOwner')->willReturn($shareOwner);
		$share->method('getNode')->willReturn($path);
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

		if ($shareType === IShare::TYPE_USER ||
			$shareType === IShare::TYPE_GROUP ||
			$shareType === IShare::TYPE_LINK) {
			$share->method('getFullId')->willReturn('ocinternal:' . $id);
		}

		return $share;
	}

	public function dataGetShare() {
		$data = [];

		$cache = $this->getMockBuilder('OC\Files\Cache\Cache')
			->disableOriginalConstructor()
			->getMock();
		$cache->method('getNumericStorageId')->willReturn(101);

		$storage = $this->getMockBuilder(IStorage::class)
			->disableOriginalConstructor()
			->getMock();
		$storage->method('getId')->willReturn('STORAGE');
		$storage->method('getCache')->willReturn($cache);

		$parentFolder = $this->getMockBuilder(Folder::class)->getMock();
		$parentFolder->method('getId')->willReturn(3);
		$mountPoint = $this->createMock(IMountPoint::class);
		$mountPoint->method('getMountType')->willReturn('');

		$file = $this->getMockBuilder('OCP\Files\File')->getMock();
		$file->method('getId')->willReturn(1);
		$file->method('getPath')->willReturn('file');
		$file->method('getStorage')->willReturn($storage);
		$file->method('getParent')->willReturn($parentFolder);
		$file->method('getSize')->willReturn(123465);
		$file->method('getMTime')->willReturn(1234567890);
		$file->method('getMimeType')->willReturn('myMimeType');
		$file->method('getMountPoint')->willReturn($mountPoint);

		$folder = $this->getMockBuilder(Folder::class)->getMock();
		$folder->method('getId')->willReturn(2);
		$folder->method('getPath')->willReturn('folder');
		$folder->method('getStorage')->willReturn($storage);
		$folder->method('getParent')->willReturn($parentFolder);
		$folder->method('getSize')->willReturn(123465);
		$folder->method('getMTime')->willReturn(1234567890);
		$folder->method('getMimeType')->willReturn('myFolderMimeType');
		$folder->method('getMountPoint')->willReturn($mountPoint);

		[$shareAttributes, $shareAttributesReturnJson] = $this->mockShareAttributes();

		// File shared with user
		$share = $this->createShare(
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
			$shareAttributes,
		);
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
			'attributes' => $shareAttributesReturnJson,
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
			'attributes' => null,
			'item_permissions' => 4,
			'is-mount-root' => false,
			'mount-type' => '',
		];
		$data[] = [$share, $expected];

		// Folder shared with group
		$share = $this->createShare(
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
			$shareAttributes,
		);
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
			'attributes' => $shareAttributesReturnJson,
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
			'attributes' => null,
			'item_permissions' => 4,
			'is-mount-root' => false,
			'mount-type' => '',
		];
		$data[] = [$share, $expected];

		// File shared by link with Expire
		$expire = \DateTime::createFromFormat('Y-m-d h:i:s', '2000-01-02 01:02:03');
		$share = $this->createShare(
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
		);
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
			'attributes' => null,
			'item_permissions' => 4,
			'is-mount-root' => false,
			'mount-type' => '',
		];
		$data[] = [$share, $expected];

		return $data;
	}

	/**
	 * @dataProvider dataGetShare
	 */
	public function testGetShare(IShare $share, array $result): void {
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
				$this->appManager,
				$this->serverContainer,
				$this->userStatusManager,
				$this->previewManager,
				$this->dateTimeZone,
				$this->logger,
				$this->factory,
				$this->mailer,
				$this->tagManager,
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

		$data = $ocs->getShare($share->getId())->getData()[0];
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

		$this->ocs->getShare(42);
	}

	public function dataGetShares() {
		$folder = $this->getMockBuilder(Folder::class)->getMock();
		$file1 = $this->getMockBuilder(File::class)->getMock();
		$file1->method('getName')
			->willReturn('file1');
		$file2 = $this->getMockBuilder(File::class)->getMock();
		$file2->method('getName')
			->willReturn('file2');

		$folder->method('getDirectoryListing')
			->willReturn([$file1, $file2]);

		$file1UserShareOwner = Server::get(IManager::class)->newShare();
		$file1UserShareOwner->setShareType(IShare::TYPE_USER)
			->setSharedWith('recipient')
			->setSharedBy('initiator')
			->setShareOwner('currentUser')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($file1)
			->setId(4);

		$file1UserShareOwnerExpected = [
			'id' => 4,
			'share_type' => IShare::TYPE_USER,
		];

		$file1UserShareInitiator = Server::get(IManager::class)->newShare();
		$file1UserShareInitiator->setShareType(IShare::TYPE_USER)
			->setSharedWith('recipient')
			->setSharedBy('currentUser')
			->setShareOwner('owner')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($file1)
			->setId(8);

		$file1UserShareInitiatorExpected = [
			'id' => 8,
			'share_type' => IShare::TYPE_USER,
		];

		$file1UserShareRecipient = Server::get(IManager::class)->newShare();
		$file1UserShareRecipient->setShareType(IShare::TYPE_USER)
			->setSharedWith('currentUser')
			->setSharedBy('initiator')
			->setShareOwner('owner')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($file1)
			->setId(15);

		$file1UserShareRecipientExpected = [
			'id' => 15,
			'share_type' => IShare::TYPE_USER,
		];

		$file1UserShareOther = Server::get(IManager::class)->newShare();
		$file1UserShareOther->setShareType(IShare::TYPE_USER)
			->setSharedWith('recipient')
			->setSharedBy('initiator')
			->setShareOwner('owner')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($file1)
			->setId(16);

		$file1UserShareOtherExpected = [
			'id' => 16,
			'share_type' => IShare::TYPE_USER,
		];

		$file1GroupShareOwner = Server::get(IManager::class)->newShare();
		$file1GroupShareOwner->setShareType(IShare::TYPE_GROUP)
			->setSharedWith('recipient')
			->setSharedBy('initiator')
			->setShareOwner('currentUser')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($file1)
			->setId(23);

		$file1GroupShareOwnerExpected = [
			'id' => 23,
			'share_type' => IShare::TYPE_GROUP,
		];

		$file1GroupShareRecipient = Server::get(IManager::class)->newShare();
		$file1GroupShareRecipient->setShareType(IShare::TYPE_GROUP)
			->setSharedWith('currentUserGroup')
			->setSharedBy('initiator')
			->setShareOwner('owner')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($file1)
			->setId(42);

		$file1GroupShareRecipientExpected = [
			'id' => 42,
			'share_type' => IShare::TYPE_GROUP,
		];

		$file1GroupShareOther = Server::get(IManager::class)->newShare();
		$file1GroupShareOther->setShareType(IShare::TYPE_GROUP)
			->setSharedWith('recipient')
			->setSharedBy('initiator')
			->setShareOwner('owner')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($file1)
			->setId(108);

		$file1LinkShareOwner = Server::get(IManager::class)->newShare();
		$file1LinkShareOwner->setShareType(IShare::TYPE_LINK)
			->setSharedWith('recipient')
			->setSharedBy('initiator')
			->setShareOwner('currentUser')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($file1)
			->setId(415);

		$file1LinkShareOwnerExpected = [
			'id' => 415,
			'share_type' => IShare::TYPE_LINK,
		];

		$file1EmailShareOwner = Server::get(IManager::class)->newShare();
		$file1EmailShareOwner->setShareType(IShare::TYPE_EMAIL)
			->setSharedWith('recipient')
			->setSharedBy('initiator')
			->setShareOwner('currentUser')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($file1)
			->setId(416);

		$file1EmailShareOwnerExpected = [
			'id' => 416,
			'share_type' => IShare::TYPE_EMAIL,
		];

		$file1CircleShareOwner = Server::get(IManager::class)->newShare();
		$file1CircleShareOwner->setShareType(IShare::TYPE_CIRCLE)
			->setSharedWith('recipient')
			->setSharedBy('initiator')
			->setShareOwner('currentUser')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($file1)
			->setId(423);

		$file1CircleShareOwnerExpected = [
			'id' => 423,
			'share_type' => IShare::TYPE_CIRCLE,
		];

		$file1RoomShareOwner = Server::get(IManager::class)->newShare();
		$file1RoomShareOwner->setShareType(IShare::TYPE_ROOM)
			->setSharedWith('recipient')
			->setSharedBy('initiator')
			->setShareOwner('currentUser')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($file1)
			->setId(442);

		$file1RoomShareOwnerExpected = [
			'id' => 442,
			'share_type' => IShare::TYPE_ROOM,
		];

		$file1RemoteShareOwner = Server::get(IManager::class)->newShare();
		$file1RemoteShareOwner->setShareType(IShare::TYPE_REMOTE)
			->setSharedWith('recipient')
			->setSharedBy('initiator')
			->setShareOwner('currentUser')
			->setPermissions(Constants::PERMISSION_READ)
			->setExpirationDate(new \DateTime('2000-01-01T01:02:03'))
			->setNode($file1)
			->setId(815);

		$file1RemoteShareOwnerExpected = [
			'id' => 815,
			'share_type' => IShare::TYPE_REMOTE,
		];

		$file1RemoteGroupShareOwner = Server::get(IManager::class)->newShare();
		$file1RemoteGroupShareOwner->setShareType(IShare::TYPE_REMOTE_GROUP)
			->setSharedWith('recipient')
			->setSharedBy('initiator')
			->setShareOwner('currentUser')
			->setPermissions(Constants::PERMISSION_READ)
			->setExpirationDate(new \DateTime('2000-01-02T01:02:03'))
			->setNode($file1)
			->setId(816);

		$file1RemoteGroupShareOwnerExpected = [
			'id' => 816,
			'share_type' => IShare::TYPE_REMOTE_GROUP,
		];

		$file2UserShareOwner = Server::get(IManager::class)->newShare();
		$file2UserShareOwner->setShareType(IShare::TYPE_USER)
			->setSharedWith('recipient')
			->setSharedBy('initiator')
			->setShareOwner('currentUser')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($file2)
			->setId(823);

		$file2UserShareOwnerExpected = [
			'id' => 823,
			'share_type' => IShare::TYPE_USER,
		];

		$data = [
			[
				[
					'path' => $file1,
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
					'path' => $file1,
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
					'path' => $file1,
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
					'path' => $file1,
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
					'path' => $file1,
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
					'path' => $file1,
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
					'path' => $file1,
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
					'path' => $folder,
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
					'path' => $folder,
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
					'path' => $folder,
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
					'path' => $folder,
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
					'path' => $folder,
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
					'path' => $folder,
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
					'path' => $folder,
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
					'path' => $folder,
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

	/**
	 * @dataProvider dataGetShares
	 */
	public function testGetShares(array $getSharesParameters, array $shares, array $extraShareTypes, array $expected): void {
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
				$this->appManager,
				$this->serverContainer,
				$this->userStatusManager,
				$this->previewManager,
				$this->dateTimeZone,
				$this->logger,
				$this->factory,
				$this->mailer,
				$this->tagManager,
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
			->willReturn($getSharesParameters['path']);

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

	/**
	 * @dataProvider dataCanAccessShareWithPermissions
	 */
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

	/**
	 * @dataProvider dataCanAccessShareAsGroupMember
	 */
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

	public function dataCanAccessRoomShare() {
		$result = [];

		$share = $this->createMock(IShare::class);
		$share->method('getShareType')->willReturn(IShare::TYPE_ROOM);
		$share->method('getSharedWith')->willReturn('recipientRoom');

		$result[] = [
			false, $share, false, false
		];

		$result[] = [
			false, $share, false, true
		];

		$result[] = [
			true, $share, true, true
		];

		$result[] = [
			false, $share, true, false
		];

		return $result;
	}

	/**
	 * @dataProvider dataCanAccessRoomShare
	 *
	 * @param bool $expects
	 * @param IShare $share
	 * @param bool helperAvailable
	 * @param bool canAccessShareByHelper
	 */
	public function testCanAccessRoomShare(bool $expected, IShare $share, bool $helperAvailable, bool $canAccessShareByHelper): void {
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
			->will($this->throwException(new NotFoundException()));

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
				$this->appManager,
				$this->serverContainer,
				$this->userStatusManager,
				$this->previewManager,
				$this->dateTimeZone,
				$this->logger,
				$this->factory,
				$this->mailer,
				$this->tagManager,
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
				return $share->getNode() === $path &&
					$share->getPermissions() === (
						Constants::PERMISSION_ALL &
						~Constants::PERMISSION_DELETE &
						~Constants::PERMISSION_CREATE
					) &&
					$share->getShareType() === IShare::TYPE_USER &&
					$share->getSharedWith() === 'validUser' &&
					$share->getSharedBy() === 'currentUser';
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
				$this->appManager,
				$this->serverContainer,
				$this->userStatusManager,
				$this->previewManager,
				$this->dateTimeZone,
				$this->logger,
				$this->factory,
				$this->mailer,
				$this->tagManager,
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
				return $share->getNode() === $path &&
				$share->getPermissions() === Constants::PERMISSION_ALL &&
				$share->getShareType() === IShare::TYPE_GROUP &&
				$share->getSharedWith() === 'validGroup' &&
				$share->getSharedBy() === 'currentUser';
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
				return $share->getNode() === $path &&
					$share->getShareType() === IShare::TYPE_LINK &&
					$share->getPermissions() === (Constants::PERMISSION_READ | Constants::PERMISSION_CREATE | Constants::PERMISSION_UPDATE | Constants::PERMISSION_DELETE) &&
					$share->getSharedBy() === 'currentUser' &&
					$share->getPassword() === null &&
					$share->getExpirationDate() === null;
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
				return $share->getNode() === $path &&
				$share->getShareType() === IShare::TYPE_LINK &&
				$share->getPermissions() === (Constants::PERMISSION_ALL & ~(Constants::PERMISSION_SHARE)) &&
				$share->getSharedBy() === 'currentUser' &&
				$share->getPassword() === 'password' &&
				$share->getSendPasswordByTalk() === true &&
				$share->getExpirationDate() === null;
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

				return $share->getNode() === $path &&
				$share->getShareType() === IShare::TYPE_LINK &&
				$share->getPermissions() === Constants::PERMISSION_READ | Constants::PERMISSION_SHARE &&
				$share->getSharedBy() === 'currentUser' &&
				$share->getPassword() === null &&
				$share->getExpirationDate() == $date;
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
				$this->appManager,
				$this->serverContainer,
				$this->userStatusManager,
				$this->previewManager,
				$this->dateTimeZone,
				$this->logger,
				$this->factory,
				$this->mailer,
				$this->tagManager,
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
				return $share->getNode() === $path &&
					$share->getPermissions() === (
						Constants::PERMISSION_ALL &
						~Constants::PERMISSION_DELETE &
						~Constants::PERMISSION_CREATE
					) &&
					$share->getShareType() === IShare::TYPE_REMOTE &&
					$share->getSharedWith() === 'user@example.org' &&
					$share->getSharedBy() === 'currentUser';
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
				$this->appManager,
				$this->serverContainer,
				$this->userStatusManager,
				$this->previewManager,
				$this->dateTimeZone,
				$this->logger,
				$this->factory,
				$this->mailer,
				$this->tagManager,
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
				return $share->getNode() === $path &&
					$share->getPermissions() === (
						Constants::PERMISSION_ALL &
						~Constants::PERMISSION_DELETE &
						~Constants::PERMISSION_CREATE
					) &&
					$share->getShareType() === IShare::TYPE_REMOTE_GROUP &&
					$share->getSharedWith() === 'group@example.org' &&
					$share->getSharedBy() === 'currentUser';
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
				Constants::PERMISSION_ALL &
				~Constants::PERMISSION_DELETE &
				~Constants::PERMISSION_CREATE,
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
				$this->appManager,
				$this->serverContainer,
				$this->userStatusManager,
				$this->previewManager,
				$this->dateTimeZone,
				$this->logger,
				$this->factory,
				$this->mailer,
				$this->tagManager,
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
				return $share->getPermissions() === Constants::PERMISSION_READ &&
				$share->getPassword() === null &&
				$share->getExpirationDate() === null &&
				// Once set a note or a label are never back to null, only to an
				// empty string.
				$share->getNote() === '' &&
				$share->getLabel() === '' &&
				$share->getHideDownload() === false;
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

				return $share->getPermissions() === (Constants::PERMISSION_READ | Constants::PERMISSION_CREATE | Constants::PERMISSION_UPDATE | Constants::PERMISSION_DELETE) &&
				$share->getPassword() === 'password' &&
				$share->getExpirationDate() == $date &&
				$share->getNote() === 'note' &&
				$share->getLabel() === 'label' &&
				$share->getHideDownload() === true;
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

	/**
	 * @dataProvider publicUploadParamsProvider
	 */
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
				return $share->getPermissions() === (Constants::PERMISSION_READ | Constants::PERMISSION_CREATE | Constants::PERMISSION_UPDATE | Constants::PERMISSION_DELETE) &&
				$share->getPassword() === 'password' &&
				$share->getExpirationDate() === null;
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


	public function publicLinkValidPermissionsProvider() {
		return [
			[Constants::PERMISSION_CREATE],
			[Constants::PERMISSION_READ],
			[Constants::PERMISSION_READ | Constants::PERMISSION_UPDATE],
			[Constants::PERMISSION_READ | Constants::PERMISSION_DELETE],
			[Constants::PERMISSION_READ | Constants::PERMISSION_CREATE],
		];
	}

	/**
	 * @dataProvider publicLinkValidPermissionsProvider
	 */
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

	public function publicLinkInvalidPermissionsProvider1() {
		return [
			[Constants::PERMISSION_DELETE],
			[Constants::PERMISSION_UPDATE],
			[Constants::PERMISSION_SHARE],
		];
	}

	/**
	 * @dataProvider publicLinkInvalidPermissionsProvider1
	 */
	public function testUpdateLinkShareSetInvalidCRUDPermissions1($permissions): void {
		$this->expectException(OCSBadRequestException::class);
		$this->expectExceptionMessage('Share must at least have READ or CREATE permissions');

		$this->testUpdateLinkShareSetCRUDPermissions($permissions, null);
	}

	public function publicLinkInvalidPermissionsProvider2() {
		return [
			[Constants::PERMISSION_CREATE | Constants::PERMISSION_DELETE],
			[Constants::PERMISSION_CREATE | Constants::PERMISSION_UPDATE],
		];
	}

	/**
	 * @dataProvider publicLinkInvalidPermissionsProvider2
	 */
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

	public function publicUploadParamsProvider() {
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

	/**
	 * @dataProvider publicUploadParamsProvider
	 */
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
				return $share->getPermissions() === Constants::PERMISSION_ALL &&
				$share->getPassword() === 'newpassword' &&
				$share->getSendPasswordByTalk() === true &&
				$share->getExpirationDate() === $date &&
				$share->getNote() === 'note' &&
				$share->getLabel() === 'label' &&
				$share->getHideDownload() === true;
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
				return $share->getPermissions() === Constants::PERMISSION_ALL &&
				$share->getPassword() === 'password' &&
				$share->getSendPasswordByTalk() === true &&
				$share->getExpirationDate() === $date &&
				$share->getNote() === 'note' &&
				$share->getLabel() === 'label' &&
				$share->getHideDownload() === true;
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
				return $share->getPermissions() === Constants::PERMISSION_ALL &&
				$share->getPassword() === 'password' &&
				$share->getSendPasswordByTalk() === false &&
				$share->getExpirationDate() === $date &&
				$share->getNote() === 'note' &&
				$share->getLabel() === 'label' &&
				$share->getHideDownload() === true;
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
				return $share->getPermissions() === Constants::PERMISSION_ALL &&
				$share->getPassword() === 'password' &&
				$share->getSendPasswordByTalk() === false &&
				$share->getExpirationDate() === $date &&
				$share->getNote() === 'note' &&
				$share->getLabel() === 'label' &&
				$share->getHideDownload() === true;
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

				return $share->getPermissions() === Constants::PERMISSION_ALL &&
				$share->getPassword() === 'password' &&
				$share->getSendPasswordByTalk() === true &&
				$share->getExpirationDate() == $date &&
				$share->getNote() === 'note' &&
				$share->getLabel() === 'label' &&
				$share->getHideDownload() === true;
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
				return $share->getPermissions() === (Constants::PERMISSION_READ | Constants::PERMISSION_CREATE | Constants::PERMISSION_UPDATE | Constants::PERMISSION_DELETE) &&
				$share->getPassword() === 'password' &&
				$share->getSendPasswordByTalk() === true &&
				$share->getExpirationDate() === $date &&
				$share->getNote() === 'note' &&
				$share->getLabel() === 'label' &&
				$share->getHideDownload() === true;
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
				return $share->getPermissions() === (Constants::PERMISSION_READ | Constants::PERMISSION_CREATE | Constants::PERMISSION_UPDATE | Constants::PERMISSION_DELETE) &&
				$share->getPassword() === 'password' &&
				$share->getSendPasswordByTalk() === true &&
				$share->getExpirationDate() === $date &&
				$share->getNote() === 'note' &&
				$share->getLabel() === 'label' &&
				$share->getHideDownload() === true;
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
					return $share->getPermissions() === Constants::PERMISSION_ALL &&
						$share->getPassword() === 'password' &&
						$share->getSendPasswordByTalk() === true &&
						$share->getExpirationDate() === $date &&
						$share->getNote() === 'note' &&
						$share->getLabel() === 'label' &&
						$share->getHideDownload() === true;
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

	public function dataFormatShare() {
		$file = $this->getMockBuilder(File::class)->getMock();
		$folder = $this->getMockBuilder(Folder::class)->getMock();
		$parent = $this->getMockBuilder(Folder::class)->getMock();
		$fileWithPreview = $this->getMockBuilder(File::class)->getMock();

		$file->method('getMimeType')->willReturn('myMimeType');
		$folder->method('getMimeType')->willReturn('myFolderMimeType');
		$fileWithPreview->method('getMimeType')->willReturn('mimeWithPreview');

		$mountPoint = $this->createMock(IMountPoint::class);
		$mountPoint->method('getMountType')->willReturn('');
		$file->method('getMountPoint')->willReturn($mountPoint);
		$folder->method('getMountPoint')->willReturn($mountPoint);
		$fileWithPreview->method('getMountPoint')->willReturn($mountPoint);

		$file->method('getPath')->willReturn('file');
		$folder->method('getPath')->willReturn('folder');
		$fileWithPreview->method('getPath')->willReturn('fileWithPreview');

		$parent->method('getId')->willReturn(1);
		$folder->method('getId')->willReturn(2);
		$file->method('getId')->willReturn(3);
		$fileWithPreview->method('getId')->willReturn(4);

		$file->method('getParent')->willReturn($parent);
		$folder->method('getParent')->willReturn($parent);
		$fileWithPreview->method('getParent')->willReturn($parent);

		$file->method('getSize')->willReturn(123456);
		$folder->method('getSize')->willReturn(123456);
		$fileWithPreview->method('getSize')->willReturn(123456);
		$file->method('getMTime')->willReturn(1234567890);
		$folder->method('getMTime')->willReturn(1234567890);
		$fileWithPreview->method('getMTime')->willReturn(1234567890);

		$cache = $this->getMockBuilder('OCP\Files\Cache\ICache')->getMock();
		$cache->method('getNumericStorageId')->willReturn(100);
		$storage = $this->createMock(IStorage::class);
		$storage->method('getId')->willReturn('storageId');
		$storage->method('getCache')->willReturn($cache);

		$file->method('getStorage')->willReturn($storage);
		$folder->method('getStorage')->willReturn($storage);
		$fileWithPreview->method('getStorage')->willReturn($storage);


		$mountPoint = $this->getMockBuilder(IMountPoint::class)->getMock();
		$mountPoint->method('getMountType')->willReturn('');
		$file->method('getMountPoint')->willReturn($mountPoint);
		$folder->method('getMountPoint')->willReturn($mountPoint);

		$owner = $this->getMockBuilder(IUser::class)->getMock();
		$owner->method('getDisplayName')->willReturn('ownerDN');
		$initiator = $this->getMockBuilder(IUser::class)->getMock();
		$initiator->method('getDisplayName')->willReturn('initiatorDN');
		$recipient = $this->getMockBuilder(IUser::class)->getMock();
		$recipient->method('getDisplayName')->willReturn('recipientDN');
		$recipient->method('getSystemEMailAddress')->willReturn('recipient');
		[$shareAttributes, $shareAttributesReturnJson] = $this->mockShareAttributes();

		$result = [];

		$share = Server::get(IManager::class)->newShare();
		$share->setShareType(IShare::TYPE_USER)
			->setSharedWith('recipient')
			->setSharedBy('initiator')
			->setShareOwner('owner')
			->setPermissions(Constants::PERMISSION_READ)
			->setAttributes($shareAttributes)
			->setNode($file)
			->setShareTime(new \DateTime('2000-01-01T00:01:02'))
			->setTarget('myTarget')
			->setNote('personal note')
			->setId(42);

		// User backend down
		$result[] = [
			[
				'id' => '42',
				'share_type' => IShare::TYPE_USER,
				'uid_owner' => 'initiator',
				'displayname_owner' => 'initiator',
				'permissions' => 1,
				'attributes' => $shareAttributesReturnJson,
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
			], $share, [], false
		];
		// User backend up
		$result[] = [
			[
				'id' => '42',
				'share_type' => IShare::TYPE_USER,
				'uid_owner' => 'initiator',
				'displayname_owner' => 'initiatorDN',
				'permissions' => 1,
				'attributes' => $shareAttributesReturnJson,
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

		$share = Server::get(IManager::class)->newShare();
		$share->setShareType(IShare::TYPE_USER)
			->setSharedWith('recipient')
			->setSharedBy('initiator')
			->setShareOwner('owner')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($file)
			->setShareTime(new \DateTime('2000-01-01T00:01:02'))
			->setTarget('myTarget')
			->setNote('personal note')
			->setId(42);
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

		$share = Server::get(IManager::class)->newShare();
		$share->setShareType(IShare::TYPE_USER)
			->setSharedWith('recipient')
			->setSharedBy('initiator')
			->setShareOwner('currentUser')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($file)
			->setShareTime(new \DateTime('2000-01-01T00:01:02'))
			->setTarget('myTarget')
			->setNote('personal note')
			->setId(42);
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

		$share = Server::get(IManager::class)->newShare();
		$share->setShareType(IShare::TYPE_GROUP)
			->setSharedWith('recipientGroup')
			->setSharedBy('initiator')
			->setShareOwner('owner')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($file)
			->setShareTime(new \DateTime('2000-01-01T00:01:02'))
			->setTarget('myTarget')
			->setNote('personal note')
			->setId(42);

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
		$share = Server::get(IManager::class)->newShare();
		$share->setShareType(IShare::TYPE_GROUP)
			->setSharedWith('recipientGroup2')
			->setSharedBy('initiator')
			->setShareOwner('owner')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($file)
			->setShareTime(new \DateTime('2000-01-01T00:01:02'))
			->setTarget('myTarget')
			->setNote('personal note')
			->setId(42);
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

		$share = Server::get(IManager::class)->newShare();
		$share->setShareType(IShare::TYPE_LINK)
			->setSharedBy('initiator')
			->setShareOwner('owner')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($file)
			->setShareTime(new \DateTime('2000-01-01T00:01:02'))
			->setTarget('myTarget')
			->setPassword('mypassword')
			->setExpirationDate(new \DateTime('2001-01-02T00:00:00'))
			->setToken('myToken')
			->setNote('personal note')
			->setLabel('new link share')
			->setId(42);

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

		$share = Server::get(IManager::class)->newShare();
		$share->setShareType(IShare::TYPE_LINK)
			->setSharedBy('initiator')
			->setShareOwner('owner')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($file)
			->setShareTime(new \DateTime('2000-01-01T00:01:02'))
			->setTarget('myTarget')
			->setPassword('mypassword')
			->setSendPasswordByTalk(true)
			->setExpirationDate(new \DateTime('2001-01-02T00:00:00'))
			->setToken('myToken')
			->setNote('personal note')
			->setLabel('new link share')
			->setId(42);

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

		$share = Server::get(IManager::class)->newShare();
		$share->setShareType(IShare::TYPE_REMOTE)
			->setSharedBy('initiator')
			->setSharedWith('user@server.com')
			->setShareOwner('owner')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($folder)
			->setShareTime(new \DateTime('2000-01-01T00:01:02'))
			->setExpirationDate(new \DateTime('2001-02-03T04:05:06'))
			->setTarget('myTarget')
			->setNote('personal note')
			->setId(42);

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
			], $share, [], false
		];

		$share = Server::get(IManager::class)->newShare();
		$share->setShareType(IShare::TYPE_REMOTE_GROUP)
			->setSharedBy('initiator')
			->setSharedWith('user@server.com')
			->setShareOwner('owner')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($folder)
			->setShareTime(new \DateTime('2000-01-01T00:01:02'))
			->setExpirationDate(new \DateTime('2001-02-03T04:05:06'))
			->setTarget('myTarget')
			->setNote('personal note')
			->setId(42);

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
			], $share, [], false
		];

		// Circle with id, display name and avatar set by the Circles app
		$share = Server::get(IManager::class)->newShare();
		$share->setShareType(IShare::TYPE_CIRCLE)
			->setSharedBy('initiator')
			->setSharedWith('Circle (Public circle, circleOwner) [4815162342]')
			->setSharedWithDisplayName('The display name')
			->setSharedWithAvatar('path/to/the/avatar')
			->setShareOwner('owner')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($folder)
			->setShareTime(new \DateTime('2000-01-01T00:01:02'))
			->setTarget('myTarget')
			->setId(42);

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
		$share = Server::get(IManager::class)->newShare();
		$share->setShareType(IShare::TYPE_CIRCLE)
			->setSharedBy('initiator')
			->setSharedWith('Circle (Public circle, circleOwner) [4815162342]')
			->setShareOwner('owner')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($folder)
			->setShareTime(new \DateTime('2000-01-01T00:01:02'))
			->setTarget('myTarget')
			->setId(42);

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
		$share = Server::get(IManager::class)->newShare();
		$share->setShareType(IShare::TYPE_CIRCLE)
			->setSharedBy('initiator')
			->setSharedWith('Circle (Public circle, circleOwner)')
			->setShareOwner('owner')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($folder)
			->setShareTime(new \DateTime('2000-01-01T00:01:02'))
			->setTarget('myTarget')
			->setId(42);

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

		$share = Server::get(IManager::class)->newShare();
		$share->setShareType(IShare::TYPE_USER)
			->setSharedBy('initiator')
			->setSharedWith('recipient')
			->setShareOwner('owner')
			->setPermissions(Constants::PERMISSION_READ)
			->setShareTime(new \DateTime('2000-01-01T00:01:02'))
			->setTarget('myTarget')
			->setNote('personal note')
			->setId(42);

		$result[] = [
			[], $share, [], true
		];

		$share = Server::get(IManager::class)->newShare();
		$share->setShareType(IShare::TYPE_EMAIL)
			->setSharedBy('initiator')
			->setSharedWith('user@server.com')
			->setShareOwner('owner')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($folder)
			->setShareTime(new \DateTime('2000-01-01T00:01:02'))
			->setTarget('myTarget')
			->setId(42)
			->setPassword('password');

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

		$share = Server::get(IManager::class)->newShare();
		$share->setShareType(IShare::TYPE_EMAIL)
			->setSharedBy('initiator')
			->setSharedWith('user@server.com')
			->setShareOwner('owner')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($folder)
			->setShareTime(new \DateTime('2000-01-01T00:01:02'))
			->setTarget('myTarget')
			->setId(42)
			->setPassword('password')
			->setSendPasswordByTalk(true);

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
		$share = Server::get(IManager::class)->newShare();
		$share->setShareType(IShare::TYPE_USER)
			->setSharedWith('recipient')
			->setSharedBy('initiator')
			->setShareOwner('currentUser')
			->setPermissions(Constants::PERMISSION_READ)
			->setNode($fileWithPreview)
			->setShareTime(new \DateTime('2000-01-01T00:01:02'))
			->setTarget('myTarget')
			->setNote('personal note')
			->setId(42);

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

	/**
	 * @dataProvider dataFormatShare
	 *
	 * @param array $expects
	 * @param IShare $share
	 * @param array $users
	 * @param $exception
	 */
	public function testFormatShare(array $expects, IShare $share, array $users, $exception): void {
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

	public function dataFormatRoomShare() {
		$file = $this->getMockBuilder(File::class)->getMock();
		$parent = $this->getMockBuilder(Folder::class)->getMock();

		$file->method('getMimeType')->willReturn('myMimeType');

		$file->method('getPath')->willReturn('file');

		$parent->method('getId')->willReturn(1);
		$file->method('getId')->willReturn(3);

		$file->method('getParent')->willReturn($parent);

		$file->method('getSize')->willReturn(123456);
		$file->method('getMTime')->willReturn(1234567890);

		$mountPoint = $this->getMockBuilder(IMountPoint::class)->getMock();
		$mountPoint->method('getMountType')->willReturn('');
		$file->method('getMountPoint')->willReturn($mountPoint);

		$cache = $this->getMockBuilder('OCP\Files\Cache\ICache')->getMock();
		$cache->method('getNumericStorageId')->willReturn(100);
		$storage = $this->createMock(IStorage::class);
		$storage->method('getId')->willReturn('storageId');
		$storage->method('getCache')->willReturn($cache);

		$file->method('getStorage')->willReturn($storage);

		$result = [];

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
			], $share, false, []
		];

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
			], $share, true, [
				'share_with_displayname' => 'recipientRoomName'
			]
		];

		return $result;
	}

	/**
	 * @dataProvider dataFormatRoomShare
	 *
	 * @param array $expects
	 * @param IShare $share
	 * @param bool $helperAvailable
	 * @param array $formatShareByHelper
	 */
	public function testFormatRoomShare(array $expects, IShare $share, bool $helperAvailable, array $formatShareByHelper): void {
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
}
