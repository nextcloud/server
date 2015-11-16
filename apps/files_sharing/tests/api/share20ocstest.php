<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
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

use OCA\Files_Sharing\API\Share20OCS;

class Share20OCSTest extends \Test\TestCase {

	/** @var OC\Share20\Manager */
	private $shareManager;

	/** @var OCP\IGroupManager */
	private $groupManager;

	/** @var OCP\IUserManager */
	private $userManager;

	/** @var OCP\IRequest */
	private $request;

	/** @var OCP\Files\Folder */
	private $userFolder;

	/** @var OCP\IURLGenerator */
	private $urlGenerator;

	/** @var OCS */
	private $ocs;

	protected function setUp() {
		$this->shareManager = $this->getMockBuilder('OC\Share20\Manager')
			->disableOriginalConstructor()
			->getMock();
		$this->groupManager = $this->getMock('OCP\IGroupManager');
		$this->userManager = $this->getMock('OCP\IUserManager');
		$this->request = $this->getMock('OCP\IRequest');
		$this->userFolder = $this->getMock('OCP\Files\Folder');
		$this->urlGenerator = $this->getMock('OCP\IURLGenerator');

		$this->ocs = new Share20OCS($this->shareManager,
									$this->groupManager,
									$this->userManager,
									$this->request,
									$this->userFolder,
									$this->urlGenerator);
	}

	public function testDeleteShareShareNotFound() {
		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with(42)
			->will($this->throwException(new \OC\Share20\Exception\ShareNotFound()));

		$expected = new \OC_OCS_Result(null, 404, 'wrong share ID, share doesn\'t exist.');
		$this->assertEquals($expected, $this->ocs->deleteShare(42));
	}

	public function testDeleteShareCouldNotDelete() {
		$share = $this->getMock('OC\Share20\IShare');
		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with(42)
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
		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with(42)
			->willReturn($share);
		$this->shareManager
			->expects($this->once())
			->method('deleteShare')
			->with($share);

		$expected = new \OC_OCS_Result();
		$this->assertEquals($expected, $this->ocs->deleteShare(42));
	}

	public function testGetGetShareNotExists() {
		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with(42)
			->will($this->throwException(new \OC\Share20\Exception\ShareNotFound()));

		$expected = new \OC_OCS_Result(null, 404, 'wrong share ID, share doesn\'t exist.');
		$this->assertEquals($expected, $this->ocs->getShare(42));
	}

	public function createShare($id, $shareType, $sharedWith, $sharedBy, $path, $permissions,
								$shareTime, $expiration, $parent, $target, $mail_send, $token=null,
								$password=null) {
		$share = $this->getMock('OC\Share20\IShare');
		$share->method('getId')->willReturn($id);
		$share->method('getShareType')->willReturn($shareType);
		$share->method('getSharedWith')->willReturn($sharedWith);
		$share->method('getSharedBy')->willReturn($sharedBy);
		$share->method('getPath')->willReturn($path);
		$share->method('getPermissions')->willReturn($permissions);
		$share->method('getShareTime')->willReturn($shareTime);
		$share->method('getExpirationDate')->willReturn($expiration);
		$share->method('getParent')->willReturn($parent);
		$share->method('getTarget')->willReturn($target);
		$share->method('getMailSend')->willReturn($mail_send);
		$share->method('getToken')->willReturn($token);
		$share->method('getPassword')->willReturn($password);

		return $share;
	}

	public function dataGetShare() {
		$data = [];

		$owner = $this->getMock('OCP\IUser');
		$owner->method('getUID')->willReturn('ownerId');
		$owner->method('getDisplayName')->willReturn('ownerDisplay');

		$user = $this->getMock('OCP\IUser');
		$user->method('getUID')->willReturn('userId');
		$user->method('getDisplayName')->willReturn('userDisplay');

		$group = $this->getMock('OCP\IGroup');
		$group->method('getGID')->willReturn('groupId');

		$storage = $this->getMock('OCP\Files\Storage');
		$storage->method('getId')->willReturn('STORAGE');

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
		$share = $this->createShare(100, 
									\OCP\Share::SHARE_TYPE_USER,
									$user,
									$owner,
									$file,
									4,
									5,
									null,
									6,
									'target',
									0);
		$expected = [
			'id' => 100,
			'share_type' => \OCP\Share::SHARE_TYPE_USER,
			'share_with' => 'userId',
			'share_with_displayname' => 'userDisplay',
			'uid_owner' => 'ownerId',
			'displayname_owner' => 'ownerDisplay',
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
			'storage' => null, // HACK around static function
			'mail_send' => 0,
		];
		$data[] = [$share, $expected];

		// Folder shared with group
		$share = $this->createShare(101, 
									\OCP\Share::SHARE_TYPE_GROUP,
									$group,
									$owner,
									$folder,
									4,
									5,
									null,
									6,
									'target',
									0);
		$expected = [
			'id' => 101,
			'share_type' => \OCP\Share::SHARE_TYPE_GROUP,
			'share_with' => 'groupId',
			'share_with_displayname' => 'groupId',
			'uid_owner' => 'ownerId',
			'displayname_owner' => 'ownerDisplay',
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
			'storage' => null, // HACK around static function
			'mail_send' => 0,
		];
		$data[] = [$share, $expected];

		// Folder shared with remote
		$share = $this->createShare(101, 
									\OCP\Share::SHARE_TYPE_REMOTE,
									'user@remote.com',
									$owner,
									$folder,
									4,
									5,
									null,
									6,
									'target',
									0);
		$expected = [
			'id' => 101,
			'share_type' => \OCP\Share::SHARE_TYPE_REMOTE,
			'share_with' => 'user@remote.com',
			'share_with_displayname' => 'user@remote.com',
			'uid_owner' => 'ownerId',
			'displayname_owner' => 'ownerDisplay',
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
			'storage' => null, // HACK around static function
			'mail_send' => 0,
		];
		$data[] = [$share, $expected];

		// File shared by link with Expire
		$expire = \DateTime::createFromFormat('Y-m-d h:i:s', '2000-01-02 01:02:03');
		$share = $this->createShare(101, 
									\OCP\Share::SHARE_TYPE_LINK,
									null,
									$owner,
									$folder,
									4,
									5,
									$expire,
									6,
									'target',
									0,
									'token',
									'password');
		$expected = [
			'id' => 101,
			'share_type' => \OCP\Share::SHARE_TYPE_LINK,
			'share_with' => 'password',
			'share_with_displayname' => 'password',
			'uid_owner' => 'ownerId',
			'displayname_owner' => 'ownerDisplay',
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
			'storage' => null, // HACK around static function
			'mail_send' => 0,
			'url' => 'url',
		];
		$data[] = [$share, $expected];

		return $data;
	}

	/**
	 * @dataProvider dataGetShare
	 */
	public function testGetShare(\OC\Share20\IShare $share, array $result) {
		$this->shareManager
			->expects($this->once())
			->method('getShareById')
			->with($share->getId())
			->willReturn($share);

		$this->userFolder
			->method('getRelativePath')
			->will($this->returnArgument(0));

		$this->urlGenerator
			->method('linkToRouteAbsolute')
			->willReturn('url');

		$expected = new \OC_OCS_Result($result);
		$this->assertEquals($expected->getData(), $this->ocs->getShare($share->getId())->getData());	}
}
