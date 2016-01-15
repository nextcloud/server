<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OCA\Files_Sharing\Tests\API;

use OC\Share20\IShare;
use OCA\Files_Sharing\API\Share20OCS;
use OCP\IGroupManager;
use OCP\IUserManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Files\IRootFolder;

class Share20OCSTest extends \Test\TestCase {

	/** @var \OC\Share20\Manager */
	private $shareManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IUserManager */
	private $userManager;

	/** @var IRequest */
	private $request;

	/** @var IRootFolder */
	private $rootFolder;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IUser */
	private $currentUser;

	/** @var Share20OCS */
	private $ocs;

	protected function setUp() {
		$this->shareManager = $this->getMockBuilder('OC\Share20\Manager')
			->disableOriginalConstructor()
			->getMock();
		$this->groupManager = $this->getMock('OCP\IGroupManager');
		$this->userManager = $this->getMock('OCP\IUserManager');
		$this->request = $this->getMock('OCP\IRequest');
		$this->rootFolder = $this->getMock('OCP\Files\IRootFolder');
		$this->urlGenerator = $this->getMock('OCP\IURLGenerator');
		$this->currentUser = $this->getMock('OCP\IUser');
		$this->currentUser->method('getUID')->willReturn('currentUser');

		$this->ocs = new Share20OCS(
				$this->shareManager,
				$this->groupManager,
				$this->userManager,
				$this->request,
				$this->rootFolder,
				$this->urlGenerator,
				$this->currentUser
		);
	}

	public function testDeleteShareShareNotFound() {
		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with('ocinternal:42')
			->will($this->throwException(new \OC\Share20\Exception\ShareNotFound()));

		$expected = new \OC_OCS_Result(null, 404, 'wrong share ID, share doesn\'t exist.');
		$this->assertEquals($expected, $this->ocs->deleteShare(42));
	}

	public function testDeleteShareCouldNotDelete() {
		$share = $this->getMock('OC\Share20\IShare');
		$share->method('getShareOwner')->willReturn($this->currentUser);
		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with('ocinternal:42')
			->willReturn($share);
		$this->shareManager
			->expects($this->once())
			->method('deleteShare')
			->with($share)
			->will($this->throwException(new \OC\Share20\Exception\BackendError()));


		$expected = new \OC_OCS_Result(null, 404, 'could not delete share');
		$this->assertEquals($expected, $this->ocs->deleteShare(42));
	}

	public function testDeleteShare() {
		$share = $this->getMock('OC\Share20\IShare');
		$share->method('getSharedBy')->willReturn($this->currentUser);
		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with('ocinternal:42')
			->willReturn($share);
		$this->shareManager
			->expects($this->once())
			->method('deleteShare')
			->with($share);

		$expected = new \OC_OCS_Result();
		$this->assertEquals($expected, $this->ocs->deleteShare(42));
	}

	/*
	 * FIXME: Enable once we have a federated Share Provider

	public function testGetGetShareNotExists() {
		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with('ocinternal:42')
			->will($this->throwException(new \OC\Share20\Exception\ShareNotFound()));

		$expected = new \OC_OCS_Result(null, 404, 'wrong share ID, share doesn\'t exist.');
		$this->assertEquals($expected, $this->ocs->getShare(42));
	}
	*/

	public function createShare($id, $shareType, $sharedWith, $sharedBy, $shareOwner, $path, $permissions,
								$shareTime, $expiration, $parent, $target, $mail_send, $token=null,
								$password=null) {
		$share = $this->getMock('OC\Share20\IShare');
		$share->method('getId')->willReturn($id);
		$share->method('getShareType')->willReturn($shareType);
		$share->method('getSharedWith')->willReturn($sharedWith);
		$share->method('getSharedBy')->willReturn($sharedBy);
		$share->method('getShareOwner')->willReturn($shareOwner);
		$share->method('getPath')->willReturn($path);
		$share->method('getPermissions')->willReturn($permissions);
		$share->method('getShareTime')->willReturn($shareTime);
		$share->method('getExpirationDate')->willReturn($expiration);
		$share->method('getParent')->willReturn($parent);
		$share->method('getTarget')->willReturn($target);
		$share->method('getMailSend')->willReturn($mail_send);
		$share->method('getToken')->willReturn($token);
		$share->method('getPassword')->willReturn($password);

		if ($shareType === \OCP\Share::SHARE_TYPE_USER  ||
			$shareType === \OCP\Share::SHARE_TYPE_GROUP ||
			$shareType === \OCP\Share::SHARE_TYPE_LINK) {
			$share->method('getFullId')->willReturn('ocinternal:'.$id);
		}

		return $share;
	}

	public function dataGetShare() {
		$data = [];

		$initiator = $this->getMock('OCP\IUser');
		$initiator->method('getUID')->willReturn('initiatorId');
		$initiator->method('getDisplayName')->willReturn('initiatorDisplay');

		$owner = $this->getMock('OCP\IUser');
		$owner->method('getUID')->willReturn('ownerId');
		$owner->method('getDisplayName')->willReturn('ownerDisplay');

		$user = $this->getMock('OCP\IUser');
		$user->method('getUID')->willReturn('userId');
		$user->method('getDisplayName')->willReturn('userDisplay');

		$group = $this->getMock('OCP\IGroup');
		$group->method('getGID')->willReturn('groupId');

		$cache = $this->getMockBuilder('OC\Files\Cache\Cache')
			->disableOriginalConstructor()
			->getMock();
		$cache->method('getNumericStorageId')->willReturn(101);

		$storage = $this->getMockBuilder('OC\Files\Storage\Storage')
			->disableOriginalConstructor()
			->getMock();
		$storage->method('getId')->willReturn('STORAGE');
		$storage->method('getCache')->willReturn($cache);

		$parentFolder = $this->getMock('OCP\Files\Folder');
		$parentFolder->method('getId')->willReturn(3);

		$file = $this->getMock('OCP\Files\File');
		$file->method('getId')->willReturn(1);
		$file->method('getPath')->willReturn('file');
		$file->method('getStorage')->willReturn($storage);
		$file->method('getParent')->willReturn($parentFolder);

		$folder = $this->getMock('OCP\Files\Folder');
		$folder->method('getId')->willReturn(2);
		$folder->method('getPath')->willReturn('folder');
		$folder->method('getStorage')->willReturn($storage);
		$folder->method('getParent')->willReturn($parentFolder);

		// File shared with user
		$share = $this->createShare(
			100,
			\OCP\Share::SHARE_TYPE_USER,
			$user,
			$initiator,
			$owner,
			$file,
			4,
			5,
			null,
			6,
			'target',
			0
		);
		$expected = [
			'id' => 100,
			'share_type' => \OCP\Share::SHARE_TYPE_USER,
			'share_with' => 'userId',
			'share_with_displayname' => 'userDisplay',
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
			'parent' => 6,
			'storage_id' => 'STORAGE',
			'path' => 'file',
			'storage' => 101,
			'mail_send' => 0,
			'uid_file_owner' => 'ownerId',
			'displayname_file_owner' => 'ownerDisplay'
		];
		$data[] = [$share, $expected];

		// Folder shared with group
		$share = $this->createShare(
			101,
			\OCP\Share::SHARE_TYPE_GROUP,
			$group,
			$initiator,
			$owner,
			$folder,
			4,
			5,
			null,
			6,
			'target',
			0
		);
		$expected = [
			'id' => 101,
			'share_type' => \OCP\Share::SHARE_TYPE_GROUP,
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
			'parent' => 6,
			'storage_id' => 'STORAGE',
			'path' => 'folder',
			'storage' => 101,
			'mail_send' => 0,
			'uid_file_owner' => 'ownerId',
			'displayname_file_owner' => 'ownerDisplay'
		];
		$data[] = [$share, $expected];

		// File shared by link with Expire
		$expire = \DateTime::createFromFormat('Y-m-d h:i:s', '2000-01-02 01:02:03');
		$share = $this->createShare(
			101,
			\OCP\Share::SHARE_TYPE_LINK,
			null,
			$initiator,
			$owner,
			$folder,
			4,
			5,
			$expire,
			6,
			'target',
			0,
			'token',
			'password'
		);
		$expected = [
			'id' => 101,
			'share_type' => \OCP\Share::SHARE_TYPE_LINK,
			'share_with' => 'password',
			'share_with_displayname' => 'password',
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
			'stime' => 5,
			'parent' => 6,
			'storage_id' => 'STORAGE',
			'path' => 'folder',
			'storage' => 101,
			'mail_send' => 0,
			'url' => 'url',
			'uid_file_owner' => 'ownerId',
			'displayname_file_owner' => 'ownerDisplay'
		];
		$data[] = [$share, $expected];

		return $data;
	}

	/**
	 * @dataProvider dataGetShare
	 */
	public function testGetShare(\OC\Share20\IShare $share, array $result) {
		$ocs = $this->getMockBuilder('OCA\Files_Sharing\API\Share20OCS')
				->setConstructorArgs([
					$this->shareManager,
					$this->groupManager,
					$this->userManager,
					$this->request,
					$this->rootFolder,
					$this->urlGenerator,
					$this->currentUser
				])->setMethods(['canAccessShare'])
				->getMock();

		$ocs->method('canAccessShare')->willReturn(true);

		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with($share->getFullId())
			->willReturn($share);

		$userFolder = $this->getMock('OCP\Files\Folder');
		$userFolder
			->method('getRelativePath')
			->will($this->returnArgument(0));

		$this->rootFolder->method('getUserFolder')
			->with($share->getShareOwner()->getUID())
			->willReturn($userFolder);

		$this->urlGenerator
			->method('linkToRouteAbsolute')
			->willReturn('url');

		$expected = new \OC_OCS_Result($result);
		$this->assertEquals($expected->getData(), $ocs->getShare($share->getId())->getData());
	}

	public function testCanAccessShare() {
		$share = $this->getMock('OC\Share20\IShare');
		$share->method('getShareOwner')->willReturn($this->currentUser);
		$this->assertTrue($this->invokePrivate($this->ocs, 'canAccessShare', [$share]));

		$share = $this->getMock('OC\Share20\IShare');
		$share->method('getSharedBy')->willReturn($this->currentUser);
		$this->assertTrue($this->invokePrivate($this->ocs, 'canAccessShare', [$share]));

		$share = $this->getMock('OC\Share20\IShare');
		$share->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_USER);
		$share->method('getSharedWith')->willReturn($this->currentUser);
		$this->assertTrue($this->invokePrivate($this->ocs, 'canAccessShare', [$share]));

		$share = $this->getMock('OC\Share20\IShare');
		$share->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_USER);
		$share->method('getSharedWith')->willReturn($this->getMock('OCP\IUser'));
		$this->assertFalse($this->invokePrivate($this->ocs, 'canAccessShare', [$share]));

		$share = $this->getMock('OC\Share20\IShare');
		$share->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_GROUP);
		$group = $this->getMock('OCP\IGroup');
		$group->method('inGroup')->with($this->currentUser)->willReturn(true);
		$share->method('getSharedWith')->willReturn($group);
		$this->assertTrue($this->invokePrivate($this->ocs, 'canAccessShare', [$share]));

		$share = $this->getMock('OC\Share20\IShare');
		$share->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_GROUP);
		$group = $this->getMock('OCP\IGroup');
		$group->method('inGroup')->with($this->currentUser)->willReturn(false);
		$share->method('getSharedWith')->willReturn($group);
		$this->assertFalse($this->invokePrivate($this->ocs, 'canAccessShare', [$share]));

		$share = $this->getMock('OC\Share20\IShare');
		$share->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_LINK);
		$this->assertFalse($this->invokePrivate($this->ocs, 'canAccessShare', [$share]));
	}

	public function testCreateShareNoPath() {
		$expected = new \OC_OCS_Result(null, 404, 'please specify a file or folder path');

		$result = $this->ocs->createShare();

		$this->assertEquals($expected->getMeta(), $result->getMeta());
		$this->assertEquals($expected->getData(), $result->getData());
	}

	public function testCreateShareInvalidPath() {
		$this->request
			->method('getParam')
			->will($this->returnValueMap([
				['path', null, 'invalid-path'],
			]));

		$userFolder = $this->getMock('\OCP\Files\Folder');
		$this->rootFolder->expects($this->once())
			->method('getUserFolder')
			->with('currentUser')
			->willReturn($userFolder);

		$userFolder->expects($this->once())
			->method('get')
			->with('invalid-path')
			->will($this->throwException(new \OCP\Files\NotFoundException()));

		$expected = new \OC_OCS_Result(null, 404, 'wrong path, file/folder doesn\'t exist');

		$result = $this->ocs->createShare();

		$this->assertEquals($expected->getMeta(), $result->getMeta());
		$this->assertEquals($expected->getData(), $result->getData());
	}

	public function testCreateShareInvalidPermissions() {
		$share = $this->getMock('\OC\Share20\IShare');
		$this->shareManager->method('newShare')->willReturn($share);

		$this->request
			->method('getParam')
			->will($this->returnValueMap([
				['path', null, 'valid-path'],
				['permissions', null, 32],
			]));

		$userFolder = $this->getMock('\OCP\Files\Folder');
		$this->rootFolder->expects($this->once())
				->method('getUserFolder')
				->with('currentUser')
				->willReturn($userFolder);

		$path = $this->getMock('\OCP\Files\File');
		$userFolder->expects($this->once())
				->method('get')
				->with('valid-path')
				->willReturn($path);

		$expected = new \OC_OCS_Result(null, 404, 'invalid permissions');

		$result = $this->ocs->createShare();

		$this->assertEquals($expected->getMeta(), $result->getMeta());
		$this->assertEquals($expected->getData(), $result->getData());
	}

	public function testCreateShareUserNoShareWith() {
		$share = $this->getMock('\OC\Share20\IShare');
		$this->shareManager->method('newShare')->willReturn($share);

		$this->request
			->method('getParam')
			->will($this->returnValueMap([
				['path', null, 'valid-path'],
				['permissions', null, \OCP\Constants::PERMISSION_ALL],
				['shareType', $this->any(), \OCP\Share::SHARE_TYPE_USER],
			]));

		$userFolder = $this->getMock('\OCP\Files\Folder');
		$this->rootFolder->expects($this->once())
			->method('getUserFolder')
			->with('currentUser')
			->willReturn($userFolder);

		$path = $this->getMock('\OCP\Files\File');
		$userFolder->expects($this->once())
			->method('get')
			->with('valid-path')
			->willReturn($path);

		$expected = new \OC_OCS_Result(null, 404, 'please specify a valid user');

		$result = $this->ocs->createShare();

		$this->assertEquals($expected->getMeta(), $result->getMeta());
		$this->assertEquals($expected->getData(), $result->getData());
	}

	public function testCreateShareUserNoValidShareWith() {
		$share = $this->getMock('\OC\Share20\IShare');
		$this->shareManager->method('newShare')->willReturn($share);

		$this->request
			->method('getParam')
			->will($this->returnValueMap([
				['path', null, 'valid-path'],
				['permissions', null, \OCP\Constants::PERMISSION_ALL],
				['shareType', $this->any(), \OCP\Share::SHARE_TYPE_USER],
				['shareWith', $this->any(), 'invalidUser'],
			]));

		$userFolder = $this->getMock('\OCP\Files\Folder');
		$this->rootFolder->expects($this->once())
			->method('getUserFolder')
			->with('currentUser')
			->willReturn($userFolder);

		$path = $this->getMock('\OCP\Files\File');
		$userFolder->expects($this->once())
			->method('get')
			->with('valid-path')
			->willReturn($path);

		$expected = new \OC_OCS_Result(null, 404, 'please specify a valid user');

		$result = $this->ocs->createShare();

		$this->assertEquals($expected->getMeta(), $result->getMeta());
		$this->assertEquals($expected->getData(), $result->getData());
	}

	public function testCreateShareUser() {
		$share = $this->getMock('\OC\Share20\IShare');
		$this->shareManager->method('newShare')->willReturn($share);

		$ocs = $this->getMockBuilder('OCA\Files_Sharing\API\Share20OCS')
			->setConstructorArgs([
				$this->shareManager,
				$this->groupManager,
				$this->userManager,
				$this->request,
				$this->rootFolder,
				$this->urlGenerator,
				$this->currentUser
			])->setMethods(['formatShare'])
			->getMock();

		$this->request
			->method('getParam')
			->will($this->returnValueMap([
				['path', null, 'valid-path'],
				['permissions', null, \OCP\Constants::PERMISSION_ALL],
				['shareType', $this->any(), \OCP\Share::SHARE_TYPE_USER],
				['shareWith', null, 'validUser'],
			]));

		$userFolder = $this->getMock('\OCP\Files\Folder');
		$this->rootFolder->expects($this->once())
				->method('getUserFolder')
				->with('currentUser')
				->willReturn($userFolder);

		$path = $this->getMock('\OCP\Files\File');
		$userFolder->expects($this->once())
				->method('get')
				->with('valid-path')
				->willReturn($path);

		$user = $this->getMock('\OCP\IUser');
		$this->userManager->method('userExists')->with('validUser')->willReturn(true);
		$this->userManager->method('get')->with('validUser')->willReturn($user);

		$share->method('setPath')->with($path);
		$share->method('setPermissions')
			->with(
				\OCP\Constants::PERMISSION_ALL &
				~\OCP\Constants::PERMISSION_DELETE &
				~\OCP\Constants::PERMISSION_CREATE);
		$share->method('setShareType')->with(\OCP\Share::SHARE_TYPE_USER);
		$share->method('setSharedWith')->with($user);
		$share->method('setSharedBy')->with($this->currentUser);

		$expected = new \OC_OCS_Result();
		$result = $ocs->createShare();

		$this->assertEquals($expected->getMeta(), $result->getMeta());
		$this->assertEquals($expected->getData(), $result->getData());
	}

	public function testCreateShareGroupNoValidShareWith() {
		$share = $this->getMock('\OC\Share20\IShare');
		$this->shareManager->method('newShare')->willReturn($share);

		$this->request
				->method('getParam')
				->will($this->returnValueMap([
						['path', null, 'valid-path'],
						['permissions', null, \OCP\Constants::PERMISSION_ALL],
						['shareType', $this->any(), \OCP\Share::SHARE_TYPE_GROUP],
						['shareWith', $this->any(), 'invalidGroup'],
				]));

		$userFolder = $this->getMock('\OCP\Files\Folder');
		$this->rootFolder->expects($this->once())
				->method('getUserFolder')
				->with('currentUser')
				->willReturn($userFolder);

		$path = $this->getMock('\OCP\Files\File');
		$userFolder->expects($this->once())
				->method('get')
				->with('valid-path')
				->willReturn($path);

		$expected = new \OC_OCS_Result(null, 404, 'please specify a valid user');

		$result = $this->ocs->createShare();

		$this->assertEquals($expected->getMeta(), $result->getMeta());
		$this->assertEquals($expected->getData(), $result->getData());
	}

	public function testCreateShareGroup() {
		$share = $this->getMock('\OC\Share20\IShare');
		$this->shareManager->method('newShare')->willReturn($share);

		$ocs = $this->getMockBuilder('OCA\Files_Sharing\API\Share20OCS')
			->setConstructorArgs([
				$this->shareManager,
				$this->groupManager,
				$this->userManager,
				$this->request,
				$this->rootFolder,
				$this->urlGenerator,
				$this->currentUser
			])->setMethods(['formatShare'])
			->getMock();

		$this->request
			->method('getParam')
			->will($this->returnValueMap([
				['path', null, 'valid-path'],
				['permissions', null, \OCP\Constants::PERMISSION_ALL],
				['shareType', '-1', \OCP\Share::SHARE_TYPE_GROUP],
				['shareWith', null, 'validGroup'],
			]));

		$userFolder = $this->getMock('\OCP\Files\Folder');
		$this->rootFolder->expects($this->once())
				->method('getUserFolder')
				->with('currentUser')
				->willReturn($userFolder);

		$path = $this->getMock('\OCP\Files\Folder');
		$userFolder->expects($this->once())
				->method('get')
				->with('valid-path')
				->willReturn($path);

		$group = $this->getMock('\OCP\IGroup');
		$this->groupManager->method('groupExists')->with('validGroup')->willReturn(true);
		$this->groupManager->method('get')->with('validGroup')->willReturn($group);

		$share->method('setPath')->with($path);
		$share->method('setPermissions')->with(\OCP\Constants::PERMISSION_ALL);
		$share->method('setShareType')->with(\OCP\Share::SHARE_TYPE_GROUP);
		$share->method('setSharedWith')->with($group);
		$share->method('setSharedBy')->with($this->currentUser);

		$expected = new \OC_OCS_Result();
		$result = $ocs->createShare();

		$this->assertEquals($expected->getMeta(), $result->getMeta());
		$this->assertEquals($expected->getData(), $result->getData());
	}
}
