<?php

namespace Test\Share20;

use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IUserManager;
use OC\Share20\LinkShareProvider;

/**
 * Class LinkShareProviderTest
 *
 * @package Test\Share20
 * @group DB
 */
class LinkShareProviderTest extends \Test\TestCase {

	/** @var IDBConnection */
	protected $dbConn;

	/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject */
	protected $userManager;

	/** @var IRootFolder | \PHPUnit_Framework_MockObject_MockObject */
	protected $rootFolder;

	/** @var LinkShareProvider */
	protected $provider;

	public function setUp() {
		$this->dbConn = \OC::$server->getDatabaseConnection();
		$this->userManager = $this->getMockBuilder('OCP\IUserManager')->getMock();
		$this->rootFolder = $this->getMockBuilder('OCP\Files\IRootFolder')->getMock();

		$this->userManager->expects($this->any())->method('userExists')->willReturn(true);

		//Empty share table
		$this->dbConn->getQueryBuilder()->delete('share')->execute();

		$this->provider = new LinkShareProvider(
			$this->dbConn,
			$this->userManager,
			$this->rootFolder
		);
	}

	public function tearDown() {
		$this->dbConn->getQueryBuilder()->delete('share')->execute();
	}

	/**
	 * @param int $shareType
	 * @param string $sharedWith
	 * @param string $sharedBy
	 * @param string $shareOwner
	 * @param string $itemType
	 * @param int $fileSource
	 * @param string $fileTarget
	 * @param int $permissions
	 * @param $token
	 * @param $expiration
	 * @return int
	 */
	private function addShareToDB($shareType, $sharedWith, $sharedBy, $shareOwner,
								  $itemType, $fileSource, $fileTarget, $permissions, $token, $expiration,
								  $parent = null) {
		$qb = $this->dbConn->getQueryBuilder();
		$qb->insert('share');

		if ($shareType) $qb->setValue('share_type', $qb->expr()->literal($shareType));
		if ($sharedWith) $qb->setValue('share_with', $qb->expr()->literal($sharedWith));
		if ($sharedBy) $qb->setValue('uid_initiator', $qb->expr()->literal($sharedBy));
		if ($shareOwner) $qb->setValue('uid_owner', $qb->expr()->literal($shareOwner));
		if ($itemType) $qb->setValue('item_type', $qb->expr()->literal($itemType));
		if ($fileSource) $qb->setValue('file_source', $qb->expr()->literal($fileSource));
		if ($fileTarget) $qb->setValue('file_target', $qb->expr()->literal($fileTarget));
		if ($permissions) $qb->setValue('permissions', $qb->expr()->literal($permissions));
		if ($token) $qb->setValue('token', $qb->expr()->literal($token));
		if ($expiration) $qb->setValue('expiration', $qb->createNamedParameter($expiration, IQueryBuilder::PARAM_DATE));
		if ($parent) $qb->setValue('parent', $qb->expr()->literal($parent));

		$this->assertEquals(1, $qb->execute());
		return$qb->getLastInsertId();
	}

	public function testGetShareByIdLinkShare() {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->insert('share')
			->values([
				'share_type' => $qb->expr()->literal(\OCP\Share::SHARE_TYPE_LINK),
				'share_with' => $qb->expr()->literal('sharedWith'),
				'uid_owner' => $qb->expr()->literal('shareOwner'),
				'uid_initiator' => $qb->expr()->literal('sharedBy'),
				'item_type'   => $qb->expr()->literal('file'),
				'file_source' => $qb->expr()->literal(42),
				'file_target' => $qb->expr()->literal('myTarget'),
				'permissions' => $qb->expr()->literal(13),
				'token' => $qb->expr()->literal('token'),
				'expiration' => $qb->expr()->literal('2000-01-02 00:00:00'),
			]);
		$this->assertEquals(1, $qb->execute());

		$id = $qb->getLastInsertId();

		$ownerPath = $this->getMockBuilder('OCP\Files\Folder')->getMock();
		$shareOwnerFolder = $this->getMockBuilder('OCP\Files\Folder')->getMock();
		$shareOwnerFolder->method('getById')->with(42)->willReturn([$ownerPath]);

		$this->rootFolder
			->method('getUserFolder')
			->will($this->returnValueMap([
				['shareOwner', $shareOwnerFolder],
			]));

		$share = $this->provider->getShareById($id);

		$this->assertEquals($id, $share->getId());
		$this->assertEquals(\OCP\Share::SHARE_TYPE_LINK, $share->getShareType());
		$this->assertEquals('sharedWith', $share->getPassword());
		$this->assertEquals('sharedBy', $share->getSharedBy());
		$this->assertEquals('shareOwner', $share->getShareOwner());
		$this->assertEquals($ownerPath, $share->getNode());
		$this->assertEquals(13, $share->getPermissions());
		$this->assertEquals('token', $share->getToken());
		$this->assertEquals(\DateTime::createFromFormat('Y-m-d H:i:s', '2000-01-02 00:00:00'), $share->getExpirationDate());
		$this->assertEquals('myTarget', $share->getTarget());
	}

	public function testCreateLinkShare() {
		$share = new \OC\Share20\Share($this->rootFolder, $this->userManager);

		$shareOwner = $this->getMockBuilder('OCP\IUser')->getMock();
		$shareOwner->method('getUID')->willReturn('shareOwner');

		$path = $this->getMockBuilder('OCP\Files\Folder')->getMock();
		$path->method('getId')->willReturn(100);
		$path->method('getOwner')->willReturn($shareOwner);

		$ownerFolder = $this->getMockBuilder('OCP\Files\Folder')->getMock();
		$userFolder = $this->getMockBuilder('OCP\Files\Folder')->getMock();
		$this->rootFolder
			->method('getUserFolder')
			->will($this->returnValueMap([
				['sharedBy', $userFolder],
				['shareOwner', $ownerFolder],
			]));

		$userFolder->method('getById')
			->with(100)
			->willReturn([$path]);
		$ownerFolder->method('getById')
			->with(100)
			->willReturn([$path]);

		$share->setShareType(\OCP\Share::SHARE_TYPE_LINK);
		$share->setSharedBy('sharedBy');
		$share->setShareOwner('shareOwner');
		$share->setNode($path);
		$share->setPermissions(1);
		$share->setPassword('password');
		$share->setToken('token');
		$expireDate = new \DateTime();
		$share->setExpirationDate($expireDate);
		$share->setTarget('/target');

		$share2 = $this->provider->create($share);

		$this->assertNotNull($share2->getId());
		$this->assertSame('nclink:'.$share2->getId(), $share2->getFullId());
		$this->assertSame(\OCP\Share::SHARE_TYPE_LINK, $share2->getShareType());
		$this->assertSame('sharedBy', $share2->getSharedBy());
		$this->assertSame('shareOwner', $share2->getShareOwner());
		$this->assertSame(1, $share2->getPermissions());
		$this->assertSame('/target', $share2->getTarget());
		$this->assertLessThanOrEqual(new \DateTime(), $share2->getShareTime());
		$this->assertSame($path, $share2->getNode());
		$this->assertSame('password', $share2->getPassword());
		$this->assertSame('token', $share2->getToken());
		$this->assertEquals($expireDate, $share2->getExpirationDate());
	}

	public function testGetShareByToken() {
		$qb = $this->dbConn->getQueryBuilder();

		$qb->insert('share')
			->values([
				'share_type'    => $qb->expr()->literal(\OCP\Share::SHARE_TYPE_LINK),
				'share_with'    => $qb->expr()->literal('password'),
				'uid_owner'     => $qb->expr()->literal('shareOwner'),
				'uid_initiator' => $qb->expr()->literal('sharedBy'),
				'item_type'     => $qb->expr()->literal('file'),
				'file_source'   => $qb->expr()->literal(42),
				'file_target'   => $qb->expr()->literal('myTarget'),
				'permissions'   => $qb->expr()->literal(13),
				'token'         => $qb->expr()->literal('secrettoken'),
			]);
		$qb->execute();
		$id = $qb->getLastInsertId();

		$file = $this->getMockBuilder('OCP\Files\File')->getMock();

		$this->rootFolder->method('getUserFolder')->with('shareOwner')->will($this->returnSelf());
		$this->rootFolder->method('getById')->with(42)->willReturn([$file]);

		$share = $this->provider->getShareByToken('secrettoken');
		$this->assertEquals($id, $share->getId());
		$this->assertSame('shareOwner', $share->getShareOwner());
		$this->assertSame('sharedBy', $share->getSharedBy());
		$this->assertSame('secrettoken', $share->getToken());
		$this->assertSame('password', $share->getPassword());
		$this->assertSame(null, $share->getSharedWith());
	}

	public function testUpdateLink() {
		$id = $this->addShareToDB(\OCP\Share::SHARE_TYPE_LINK, null, 'user1', 'user2',
			'file', 42, 'target', 31, null, null);

		$users = [];
		for($i = 0; $i < 6; $i++) {
			$user = $this->getMockBuilder('OCP\IUser')->getMock();
			$user->method('getUID')->willReturn('user'.$i);
			$users['user'.$i] = $user;
		}

		$this->userManager->method('get')->will(
			$this->returnCallback(function($userId) use ($users) {
				return $users[$userId];
			})
		);

		$file1 = $this->getMockBuilder('OCP\Files\File')->getMock();
		$file1->method('getId')->willReturn(42);
		$file2 = $this->getMockBuilder('OCP\Files\File')->getMock();
		$file2->method('getId')->willReturn(43);

		$folder1 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
		$folder1->method('getById')->with(42)->willReturn([$file1]);
		$folder2 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
		$folder2->method('getById')->with(43)->willReturn([$file2]);

		$this->rootFolder->method('getUserFolder')->will($this->returnValueMap([
			['user2', $folder1],
			['user5', $folder2],
		]));

		$share = $this->provider->getShareById($id);

		$share->setPassword('password');
		$share->setSharedBy('user4');
		$share->setShareOwner('user5');
		$share->setNode($file2);
		$share->setPermissions(1);

		$share2 = $this->provider->update($share);

		$this->assertEquals($id, $share2->getId());
		$this->assertEquals('password', $share->getPassword());
		$this->assertSame('user4', $share2->getSharedBy());
		$this->assertSame('user5', $share2->getShareOwner());
		$this->assertSame(1, $share2->getPermissions());
	}

	public function testUpdateLinkRemovePassword() {
		$id = $this->addShareToDB(\OCP\Share::SHARE_TYPE_LINK, 'foo', 'user1', 'user2',
			'file', 42, 'target', 31, null, null);

		$users = [];
		for($i = 0; $i < 6; $i++) {
			$user = $this->getMockBuilder('OCP\IUser')->getMock();
			$user->method('getUID')->willReturn('user'.$i);
			$users['user'.$i] = $user;
		}

		$this->userManager->method('get')->will(
			$this->returnCallback(function($userId) use ($users) {
				return $users[$userId];
			})
		);

		$file1 = $this->getMockBuilder('OCP\Files\File')->getMock();
		$file1->method('getId')->willReturn(42);
		$file2 = $this->getMockBuilder('OCP\Files\File')->getMock();
		$file2->method('getId')->willReturn(43);

		$folder1 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
		$folder1->method('getById')->with(42)->willReturn([$file1]);
		$folder2 = $this->getMockBuilder('OCP\Files\Folder')->getMock();
		$folder2->method('getById')->with(43)->willReturn([$file2]);

		$this->rootFolder->method('getUserFolder')->will($this->returnValueMap([
			['user2', $folder1],
			['user5', $folder2],
		]));

		$share = $this->provider->getShareById($id);

		$share->setPassword(null);
		$share->setSharedBy('user4');
		$share->setShareOwner('user5');
		$share->setNode($file2);
		$share->setPermissions(1);

		$share2 = $this->provider->update($share);

		$this->assertEquals($id, $share2->getId());
		$this->assertEquals(null, $share->getPassword());
		$this->assertSame('user4', $share2->getSharedBy());
		$this->assertSame('user5', $share2->getShareOwner());
		$this->assertSame(1, $share2->getPermissions());
	}

	/**
	 * @expectedException \OC\Share20\Exception\ProviderException
	 * @expectedExceptionMessage Not supported
	 */
	public function testDeleteFromSelfLink() {
		$qb = $this->dbConn->getQueryBuilder();
		$stmt = $qb->insert('share')
			->values([
				'share_type' => $qb->expr()->literal(\OCP\Share::SHARE_TYPE_LINK),
				'uid_owner' => $qb->expr()->literal('user1'),
				'uid_initiator' => $qb->expr()->literal('user1'),
				'item_type' => $qb->expr()->literal('file'),
				'file_source' => $qb->expr()->literal(1),
				'file_target' => $qb->expr()->literal('myTarget1'),
				'permissions' => $qb->expr()->literal(2),
				'token' => $qb->expr()->literal('token'),
			])->execute();
		$this->assertEquals(1, $stmt);
		$id = $qb->getLastInsertId();

		$user1 = $this->getMockBuilder('OCP\IUser')->getMock();
		$user1->method('getUID')->willReturn('user1');
		$this->userManager->method('get')->will($this->returnValueMap([
			['user1', $user1],
		]));

		$file = $this->getMockBuilder('OCP\Files\File')->getMock();
		$file->method('getId')->willReturn(1);

		$this->rootFolder->method('getUserFolder')->with('user1')->will($this->returnSelf());
		$this->rootFolder->method('getById')->with(1)->willReturn([$file]);

		$share = $this->provider->getShareById($id);

		$this->provider->deleteFromSelf($share, $user1);
	}

	public function dataDeleteUser() {
		return [
			[\OCP\Share::SHARE_TYPE_USER, 'a', 'b', 'c', 'a', false],
			[\OCP\Share::SHARE_TYPE_USER, 'a', 'b', 'c', 'b', false],
			[\OCP\Share::SHARE_TYPE_USER, 'a', 'b', 'c', 'c', false],
			[\OCP\Share::SHARE_TYPE_USER, 'a', 'b', 'c', 'd', false],
			[\OCP\Share::SHARE_TYPE_GROUP, 'a', 'b', 'c', 'a', false],
			[\OCP\Share::SHARE_TYPE_GROUP, 'a', 'b', 'c', 'b', false],
			// The group c is still valid but user c is deleted so group share stays
			[\OCP\Share::SHARE_TYPE_GROUP, 'a', 'b', 'c', 'c', false],
			[\OCP\Share::SHARE_TYPE_GROUP, 'a', 'b', 'c', 'd', false],
			[\OCP\Share::SHARE_TYPE_LINK, 'a', 'b', 'c', 'a', true],
			// To avoid invisible link shares delete initiated link shares as well (see #22327)
			[\OCP\Share::SHARE_TYPE_LINK, 'a', 'b', 'c', 'b', true],
			[\OCP\Share::SHARE_TYPE_LINK, 'a', 'b', 'c', 'c', false],
			[\OCP\Share::SHARE_TYPE_LINK, 'a', 'b', 'c', 'd', false],
		];
	}

	/**
	 * @dataProvider dataDeleteUser
	 *
	 * @param int $type The shareType (user/group/link)
	 * @param string $owner The owner of the share (uid)
	 * @param string $initiator The initiator of the share (uid)
	 * @param string $recipient The recipient of the share (uid/gid/pass)
	 * @param string $deletedUser The user that is deleted
	 * @param bool $rowDeleted Is the row deleted in this setup
	 */
	public function testDeleteUser($type, $owner, $initiator, $recipient, $deletedUser, $rowDeleted) {
		$qb = $this->dbConn->getQueryBuilder();
		$qb->insert('share')
			->setValue('share_type', $qb->createNamedParameter($type))
			->setValue('uid_owner', $qb->createNamedParameter($owner))
			->setValue('uid_initiator', $qb->createNamedParameter($initiator))
			->setValue('share_with', $qb->createNamedParameter($recipient))
			->setValue('item_type', $qb->createNamedParameter('file'))
			->setValue('item_source', $qb->createNamedParameter(42))
			->setValue('file_source', $qb->createNamedParameter(42))
			->execute();

		$id = $qb->getLastInsertId();

		$this->provider->userDeleted($deletedUser, $type);

		$qb = $this->dbConn->getQueryBuilder();
		$qb->select('*')
			->from('share')
			->where(
				$qb->expr()->eq('id', $qb->createNamedParameter($id))
			);
		$cursor = $qb->execute();
		$data = $cursor->fetchAll();
		$cursor->closeCursor();

		$this->assertCount($rowDeleted ? 0 : 1, $data);
	}
}
