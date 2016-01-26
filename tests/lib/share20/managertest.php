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
namespace Test\Share20;

use OC\Share20\IProviderFactory;
use OC\Share20\IShare;
use OC\Share20\Manager;
use OC\Share20\Exception;

use OC\Share20\Share;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IConfig;
use OC\Share20\IShareProvider;
use OCP\Security\ISecureRandom;
use OCP\Security\IHasher;
use OCP\Files\Mount\IMountManager;
use OCP\IGroupManager;
use Sabre\VObject\Property\VCard\DateTime;

/**
 * Class ManagerTest
 *
 * @package Test\Share20
 * @group DB
 */
class ManagerTest extends \Test\TestCase {

	/** @var Manager */
	protected $manager;

	/** @var ILogger */
	protected $logger;

	/** @var IConfig */
	protected $config;

	/** @var ISecureRandom */
	protected $secureRandom;

	/** @var IHasher */
	protected $hasher;

	/** @var IShareProvider | \PHPUnit_Framework_MockObject_MockObject */
	protected $defaultProvider;

	/** @var  IMountManager */
	protected $mountManager;

	/** @var  IGroupManager */
	protected $groupManager;

	/** @var IL10N */
	protected $l;

	/** @var DummyFactory */
	protected $factory;

	public function setUp() {
		
		$this->logger = $this->getMock('\OCP\ILogger');
		$this->config = $this->getMock('\OCP\IConfig');
		$this->secureRandom = $this->getMock('\OCP\Security\ISecureRandom');
		$this->hasher = $this->getMock('\OCP\Security\IHasher');
		$this->mountManager = $this->getMock('\OCP\Files\Mount\IMountManager');
		$this->groupManager = $this->getMock('\OCP\IGroupManager');

		$this->l = $this->getMock('\OCP\IL10N');
		$this->l->method('t')
			->will($this->returnCallback(function($text, $parameters = []) {
 				return vsprintf($text, $parameters);
 			}));

		$this->factory = new DummyFactory(\OC::$server);

		$this->manager = new Manager(
			$this->logger,
			$this->config,
			$this->secureRandom,
			$this->hasher,
			$this->mountManager,
			$this->groupManager,
			$this->l,
			$this->factory
		);

		$this->defaultProvider = $this->getMock('\OC\Share20\IShareProvider');
		$this->defaultProvider->method('identifier')->willReturn('default');
		$this->factory->setProvider($this->defaultProvider);


	}

	/**
	 * @return \PHPUnit_Framework_MockObject_MockBuilder
	 */
	private function createManagerMock() {
		return 	$this->getMockBuilder('\OC\Share20\Manager')
			->setConstructorArgs([
				$this->logger,
				$this->config,
				$this->secureRandom,
				$this->hasher,
				$this->mountManager,
				$this->groupManager,
				$this->l,
				$this->factory
			]);
	}

	/**
	 * @expectedException \OC\Share20\Exception\ShareNotFound
	 */
	public function testDeleteNoShareId() {
		$share = $this->getMock('\OC\Share20\IShare');

		$share
			->expects($this->once())
			->method('getFullId')
			->with()
			->willReturn(null);

		$this->manager->deleteShare($share);
	}

	public function dataTestDelete() {
		$user = $this->getMock('\OCP\IUser');
		$user->method('getUID')->willReturn('sharedWithUser');

		$group = $this->getMock('\OCP\IGroup');
		$group->method('getGID')->willReturn('sharedWithGroup');
	
		return [
			[\OCP\Share::SHARE_TYPE_USER, $user, 'sharedWithUser'],
			[\OCP\Share::SHARE_TYPE_GROUP, $group, 'sharedWithGroup'],
			[\OCP\Share::SHARE_TYPE_LINK, '', ''],
			[\OCP\Share::SHARE_TYPE_REMOTE, 'foo@bar.com', 'foo@bar.com'],
		];
	}

	/**
	 * @dataProvider dataTestDelete
	 */
	public function testDelete($shareType, $sharedWith, $sharedWith_string) {
		$manager = $this->createManagerMock()
			->setMethods(['getShareById', 'deleteChildren'])
			->getMock();

		$sharedBy = $this->getMock('\OCP\IUser');
		$sharedBy->method('getUID')->willReturn('sharedBy');

		$path = $this->getMock('\OCP\Files\File');
		$path->method('getId')->willReturn(1);

		$share = $this->getMock('\OC\Share20\IShare');
		$share->method('getId')->willReturn(42);
		$share->method('getFullId')->willReturn('prov:42');
		$share->method('getShareType')->willReturn($shareType);
		$share->method('getSharedWith')->willReturn($sharedWith);
		$share->method('getSharedBy')->willReturn($sharedBy);
		$share->method('getPath')->willReturn($path);
		$share->method('getTarget')->willReturn('myTarget');

		$manager->expects($this->once())->method('getShareById')->with('prov:42')->willReturn($share);
		$manager->expects($this->once())->method('deleteChildren')->with($share);

		$this->defaultProvider
			->expects($this->once())
			->method('delete')
			->with($share);

		$hookListner = $this->getMockBuilder('Dummy')->setMethods(['pre', 'post'])->getMock();
		\OCP\Util::connectHook('OCP\Share', 'pre_unshare', $hookListner, 'pre');
		\OCP\Util::connectHook('OCP\Share', 'post_unshare', $hookListner, 'post');

		$hookListnerExpectsPre = [
			'id' => 42,
			'itemType' => 'file',
			'itemSource' => 1,
			'shareType' => $shareType,
			'shareWith' => $sharedWith_string,
			'itemparent' => null,
			'uidOwner' => 'sharedBy',
			'fileSource' => 1,
			'fileTarget' => 'myTarget',
		];

		$hookListnerExpectsPost = [
			'id' => 42,
			'itemType' => 'file',
			'itemSource' => 1,
			'shareType' => $shareType,
			'shareWith' => $sharedWith_string,
			'itemparent' => null,
			'uidOwner' => 'sharedBy',
			'fileSource' => 1,
			'fileTarget' => 'myTarget',
			'deletedShares' => [
				[
					'id' => 42,
					'itemType' => 'file',
					'itemSource' => 1,
					'shareType' => $shareType,
					'shareWith' => $sharedWith_string,
					'itemparent' => null,
					'uidOwner' => 'sharedBy',
					'fileSource' => 1,
					'fileTarget' => 'myTarget',
				],
			],
		];


		$hookListner
			->expects($this->exactly(1))
			->method('pre')
			->with($hookListnerExpectsPre);
		$hookListner
			->expects($this->exactly(1))
			->method('post')
			->with($hookListnerExpectsPost);

		$manager->deleteShare($share);
	}

	public function testDeleteNested() {
		$manager = $this->createManagerMock()
			->setMethods(['getShareById'])
			->getMock();

		$sharedBy1 = $this->getMock('\OCP\IUser');
		$sharedBy1->method('getUID')->willReturn('sharedBy1');
		$sharedBy2 = $this->getMock('\OCP\IUser');
		$sharedBy2->method('getUID')->willReturn('sharedBy2');
		$sharedBy3 = $this->getMock('\OCP\IUser');
		$sharedBy3->method('getUID')->willReturn('sharedBy3');

		$sharedWith1 = $this->getMock('\OCP\IUser');
		$sharedWith1->method('getUID')->willReturn('sharedWith1');
		$sharedWith2 = $this->getMock('\OCP\IGroup');
		$sharedWith2->method('getGID')->willReturn('sharedWith2');

		$path = $this->getMock('\OCP\Files\File');
		$path->method('getId')->willReturn(1);

		$share1 = $this->getMock('\OC\Share20\IShare');
		$share1->method('getId')->willReturn(42);
		$share1->method('getFullId')->willReturn('prov:42');
		$share1->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_USER);
		$share1->method('getSharedWith')->willReturn($sharedWith1);
		$share1->method('getSharedBy')->willReturn($sharedBy1);
		$share1->method('getPath')->willReturn($path);
		$share1->method('getTarget')->willReturn('myTarget1');

		$share2 = $this->getMock('\OC\Share20\IShare');
		$share2->method('getId')->willReturn(43);
		$share2->method('getFullId')->willReturn('prov:43');
		$share2->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_GROUP);
		$share2->method('getSharedWith')->willReturn($sharedWith2);
		$share2->method('getSharedBy')->willReturn($sharedBy2);
		$share2->method('getPath')->willReturn($path);
		$share2->method('getTarget')->willReturn('myTarget2');
		$share2->method('getParent')->willReturn(42);

		$share3 = $this->getMock('\OC\Share20\IShare');
		$share3->method('getId')->willReturn(44);
		$share3->method('getFullId')->willReturn('prov:44');
		$share3->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_LINK);
		$share3->method('getSharedBy')->willReturn($sharedBy3);
		$share3->method('getPath')->willReturn($path);
		$share3->method('getTarget')->willReturn('myTarget3');
		$share3->method('getParent')->willReturn(43);

		$manager->expects($this->once())->method('getShareById')->with('prov:42')->willReturn($share1);

		$this->defaultProvider
			->method('getChildren')
			->will($this->returnValueMap([
				[$share1, [$share2]],
				[$share2, [$share3]],
				[$share3, []],
			]));

		$this->defaultProvider
			->method('delete')
			->withConsecutive($share3, $share2, $share1);

		$hookListner = $this->getMockBuilder('Dummy')->setMethods(['pre', 'post'])->getMock();
		\OCP\Util::connectHook('OCP\Share', 'pre_unshare', $hookListner, 'pre');
		\OCP\Util::connectHook('OCP\Share', 'post_unshare', $hookListner, 'post');

		$hookListnerExpectsPre = [
			'id' => 42,
			'itemType' => 'file',
			'itemSource' => 1,
			'shareType' => \OCP\Share::SHARE_TYPE_USER,
			'shareWith' => 'sharedWith1',
			'itemparent' => null,
			'uidOwner' => 'sharedBy1',
			'fileSource' => 1,
			'fileTarget' => 'myTarget1',
		];

		$hookListnerExpectsPost = [
			'id' => 42,
			'itemType' => 'file',
			'itemSource' => 1,
			'shareType' => \OCP\Share::SHARE_TYPE_USER,
			'shareWith' => 'sharedWith1',
			'itemparent' => null,
			'uidOwner' => 'sharedBy1',
			'fileSource' => 1,
			'fileTarget' => 'myTarget1',
			'deletedShares' => [
				[
					'id' => 44,
					'itemType' => 'file',
					'itemSource' => 1,
					'shareType' => \OCP\Share::SHARE_TYPE_LINK,
					'shareWith' => '',
					'itemparent' => 43,
					'uidOwner' => 'sharedBy3',
					'fileSource' => 1,
					'fileTarget' => 'myTarget3',
				],
				[
					'id' => 43,
					'itemType' => 'file',
					'itemSource' => 1,
					'shareType' => \OCP\Share::SHARE_TYPE_GROUP,
					'shareWith' => 'sharedWith2',
					'itemparent' => 42,
					'uidOwner' => 'sharedBy2',
					'fileSource' => 1,
					'fileTarget' => 'myTarget2',
				],
				[
					'id' => 42,
					'itemType' => 'file',
					'itemSource' => 1,
					'shareType' => \OCP\Share::SHARE_TYPE_USER,
					'shareWith' => 'sharedWith1',
					'itemparent' => null,
					'uidOwner' => 'sharedBy1',
					'fileSource' => 1,
					'fileTarget' => 'myTarget1',
				],
			],
		];

		$hookListner
			->expects($this->exactly(1))
			->method('pre')
			->with($hookListnerExpectsPre);
		$hookListner
			->expects($this->exactly(1))
			->method('post')
			->with($hookListnerExpectsPost);

		$manager->deleteShare($share1);
	}

	public function testDeleteChildren() {
		$manager = $this->createManagerMock()
			->setMethods(['deleteShare'])
			->getMock();

		$share = $this->getMock('\OC\Share20\IShare');
		$share->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_USER);

		$child1 = $this->getMock('\OC\Share20\IShare');
		$child1->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_USER);
		$child2 = $this->getMock('\OC\Share20\IShare');
		$child2->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_USER);
		$child3 = $this->getMock('\OC\Share20\IShare');
		$child3->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_USER);

		$shares = [
			$child1,
			$child2,
			$child3,
		];

		$this->defaultProvider
			->expects($this->exactly(4))
			->method('getChildren')
			->will($this->returnCallback(function($_share) use ($share, $shares) {
				if ($_share === $share) {
					return $shares;
				}
				return [];
			}));

		$this->defaultProvider
			->expects($this->exactly(3))
			->method('delete')
			->withConsecutive($child1, $child2, $child3);

		$result = $this->invokePrivate($manager, 'deleteChildren', [$share]);
		$this->assertSame($shares, $result);
	}

	public function testGetShareById() {
		$share = $this->getMock('\OC\Share20\IShare');

		$this->defaultProvider
			->expects($this->once())
			->method('getShareById')
			->with(42)
			->willReturn($share);

		$this->assertEquals($share, $this->manager->getShareById('default:42'));
	}

	/**
	 * @expectedException        InvalidArgumentException
	 * @expectedExceptionMessage Passwords are enforced for link shares
	 */
	public function testVerifyPasswordNullButEnforced() {
		$this->config->method('getAppValue')->will($this->returnValueMap([
			['core', 'shareapi_enforce_links_password', 'no', 'yes'],
		]));

		$this->invokePrivate($this->manager, 'verifyPassword', [null]);
	}

	public function testVerifyPasswordNull() {
		$this->config->method('getAppValue')->will($this->returnValueMap([
				['core', 'shareapi_enforce_links_password', 'no', 'no'],
		]));

		$result = $this->invokePrivate($this->manager, 'verifyPassword', [null]);
		$this->assertNull($result);
	}

	public function testVerifyPasswordHook() {
		$this->config->method('getAppValue')->will($this->returnValueMap([
				['core', 'shareapi_enforce_links_password', 'no', 'no'],
		]));

		$hookListner = $this->getMockBuilder('Dummy')->setMethods(['listner'])->getMock();
		\OCP\Util::connectHook('\OC\Share', 'verifyPassword', $hookListner, 'listner');

		$hookListner->expects($this->once())
			->method('listner')
			->with([
				'password' => 'password',
				'accepted' => true,
				'message' => ''
			]);

		$result = $this->invokePrivate($this->manager, 'verifyPassword', ['password']);
		$this->assertNull($result);
	}

	/**
	 * @expectedException        Exception
	 * @expectedExceptionMessage password not accepted
	 */
	public function testVerifyPasswordHookFails() {
		$this->config->method('getAppValue')->will($this->returnValueMap([
				['core', 'shareapi_enforce_links_password', 'no', 'no'],
		]));

		$dummy = new DummyPassword();
		\OCP\Util::connectHook('\OC\Share', 'verifyPassword', $dummy, 'listner');
		$this->invokePrivate($this->manager, 'verifyPassword', ['password']);
	}

	public function createShare($id, $type, $path, $sharedWith, $sharedBy, $shareOwner,
		$permissions, $expireDate = null, $password = null) {
		$share = $this->getMock('\OC\Share20\IShare');

		$share->method('getShareType')->willReturn($type);
		$share->method('getSharedWith')->willReturn($sharedWith);
		$share->method('getSharedBy')->willReturn($sharedBy);
		$share->method('getSharedOwner')->willReturn($shareOwner);
		$share->method('getPath')->willReturn($path);
		$share->method('getPermissions')->willReturn($permissions);
		$share->method('getExpirationDate')->willReturn($expireDate);
		$share->method('getPassword')->willReturn($password);

		return $share;
	}

	public function dataGeneralChecks() {
		$user = $this->getMock('\OCP\IUser');
		$user2 = $this->getMock('\OCP\IUser');
		$group = $this->getMock('\OCP\IGroup');

		$file = $this->getMock('\OCP\Files\File');
		$node = $this->getMock('\OCP\Files\Node');

		$data = [
			[$this->createShare(null, \OCP\Share::SHARE_TYPE_USER,  $file, null, $user, $user, 31, null, null), 'SharedWith should be an IUser', true],
			[$this->createShare(null, \OCP\Share::SHARE_TYPE_USER,  $file, $group, $user, $user, 31, null, null), 'SharedWith should be an IUser', true],
			[$this->createShare(null, \OCP\Share::SHARE_TYPE_USER,  $file, 'foo@bar.com', $user, $user, 31, null, null), 'SharedWith should be an IUser', true],
			[$this->createShare(null, \OCP\Share::SHARE_TYPE_GROUP, $file, null, $user, $user, 31, null, null), 'SharedWith should be an IGroup', true],
			[$this->createShare(null, \OCP\Share::SHARE_TYPE_GROUP, $file, $user2, $user, $user, 31, null, null), 'SharedWith should be an IGroup', true],
			[$this->createShare(null, \OCP\Share::SHARE_TYPE_GROUP, $file, 'foo@bar.com', $user, $user, 31, null, null), 'SharedWith should be an IGroup', true],
			[$this->createShare(null, \OCP\Share::SHARE_TYPE_LINK,  $file, $user2, $user, $user, 31, null, null), 'SharedWith should be empty', true],
			[$this->createShare(null, \OCP\Share::SHARE_TYPE_LINK,  $file, $group, $user, $user, 31, null, null), 'SharedWith should be empty', true],
			[$this->createShare(null, \OCP\Share::SHARE_TYPE_LINK,  $file, 'foo@bar.com', $user, $user, 31, null, null), 'SharedWith should be empty', true],
			[$this->createShare(null, -1, $file, null, $user, $user, 31, null, null), 'unkown share type', true],

			[$this->createShare(null, \OCP\Share::SHARE_TYPE_USER,  $file, $user2, null, $user, 31, null, null), 'SharedBy should be set', true],
			[$this->createShare(null, \OCP\Share::SHARE_TYPE_GROUP, $file, $group, null, $user, 31, null, null), 'SharedBy should be set', true],
			[$this->createShare(null, \OCP\Share::SHARE_TYPE_LINK,  $file, null, null, $user, 31, null, null), 'SharedBy should be set', true],

			[$this->createShare(null, \OCP\Share::SHARE_TYPE_USER,  $file, $user, $user, $user, 31, null, null), 'Can\'t share with yourself', true],

			[$this->createShare(null, \OCP\Share::SHARE_TYPE_USER,  null, $user2, $user, $user, 31, null, null), 'Path should be set', true],
			[$this->createShare(null, \OCP\Share::SHARE_TYPE_GROUP, null, $group, $user, $user, 31, null, null), 'Path should be set', true],
			[$this->createShare(null, \OCP\Share::SHARE_TYPE_LINK,  null, null, $user, $user, 31, null, null), 'Path should be set', true],

			[$this->createShare(null, \OCP\Share::SHARE_TYPE_USER,  $node, $user2, $user, $user, 31, null, null), 'Path should be either a file or a folder', true],
			[$this->createShare(null, \OCP\Share::SHARE_TYPE_GROUP, $node, $group, $user, $user, 31, null, null), 'Path should be either a file or a folder', true],
			[$this->createShare(null, \OCP\Share::SHARE_TYPE_LINK,  $node, null, $user, $user, 31, null, null), 'Path should be either a file or a folder', true],
		];

		$nonShareAble = $this->getMock('\OCP\Files\Folder');
		$nonShareAble->method('isShareable')->willReturn(false);
		$nonShareAble->method('getPath')->willReturn('path');

		$data[] = [$this->createShare(null, \OCP\Share::SHARE_TYPE_USER,  $nonShareAble, $user2, $user, $user, 31, null, null), 'You are not allowed to share path', true];
		$data[] = [$this->createShare(null, \OCP\Share::SHARE_TYPE_GROUP, $nonShareAble, $group, $user, $user, 31, null, null), 'You are not allowed to share path', true];
		$data[] = [$this->createShare(null, \OCP\Share::SHARE_TYPE_LINK,  $nonShareAble, null, $user, $user, 31, null, null), 'You are not allowed to share path', true];

		$limitedPermssions = $this->getMock('\OCP\Files\File');
		$limitedPermssions->method('isShareable')->willReturn(true);
		$limitedPermssions->method('getPermissions')->willReturn(\OCP\Constants::PERMISSION_READ);
		$limitedPermssions->method('getPath')->willReturn('path');

		$data[] = [$this->createShare(null, \OCP\Share::SHARE_TYPE_USER,  $limitedPermssions, $user2, $user, $user, null, null, null), 'A share requires permissions', true];
		$data[] = [$this->createShare(null, \OCP\Share::SHARE_TYPE_GROUP, $limitedPermssions, $group, $user, $user, null, null, null), 'A share requires permissions', true];
		$data[] = [$this->createShare(null, \OCP\Share::SHARE_TYPE_LINK,  $limitedPermssions, null, $user, $user, null, null, null), 'A share requires permissions', true];

		$data[] = [$this->createShare(null, \OCP\Share::SHARE_TYPE_USER,  $limitedPermssions, $user2, $user, $user, 31, null, null), 'Cannot increase permissions of path', true];
		$data[] = [$this->createShare(null, \OCP\Share::SHARE_TYPE_GROUP, $limitedPermssions, $group, $user, $user, 17, null, null), 'Cannot increase permissions of path', true];
		$data[] = [$this->createShare(null, \OCP\Share::SHARE_TYPE_LINK,  $limitedPermssions, null, $user, $user, 3, null, null), 'Cannot increase permissions of path', true];

		$allPermssions = $this->getMock('\OCP\Files\Folder');
		$allPermssions->method('isShareable')->willReturn(true);
		$allPermssions->method('getPermissions')->willReturn(\OCP\Constants::PERMISSION_ALL);

		$data[] = [$this->createShare(null, \OCP\Share::SHARE_TYPE_USER,  $allPermssions, $user2, $user, $user, 30, null, null), 'Shares need at least read permissions', true];
		$data[] = [$this->createShare(null, \OCP\Share::SHARE_TYPE_GROUP, $allPermssions, $group, $user, $user, 2, null, null), 'Shares need at least read permissions', true];
		$data[] = [$this->createShare(null, \OCP\Share::SHARE_TYPE_LINK,  $allPermssions, null, $user, $user, 16, null, null), 'Shares need at least read permissions', true];

		$data[] = [$this->createShare(null, \OCP\Share::SHARE_TYPE_USER,  $allPermssions, $user2, $user, $user, 31, null, null), null, false];
		$data[] = [$this->createShare(null, \OCP\Share::SHARE_TYPE_GROUP, $allPermssions, $group, $user, $user, 3, null, null), null, false];
		$data[] = [$this->createShare(null, \OCP\Share::SHARE_TYPE_LINK,  $allPermssions, null, $user, $user, 17, null, null), null, false];

		return $data;
	}

	/**
	 * @dataProvider dataGeneralChecks
	 *
	 * @param $share
	 * @param $exceptionMessage
	 */
	public function testGeneralChecks($share, $exceptionMessage, $exception) {
		$thrown = null;

		try {
			$this->invokePrivate($this->manager, 'generalCreateChecks', [$share]);
			$thrown = false;
		} catch (\OC\HintException $e) {
			$this->assertEquals($exceptionMessage, $e->getHint());
			$thrown = true;
		} catch(\InvalidArgumentException $e) {
			$this->assertEquals($exceptionMessage, $e->getMessage());
			$thrown = true;
		}

		$this->assertSame($exception, $thrown);
	}

	/**
	 * @expectedException \OC\HintException
	 * @expectedExceptionMessage Expiration date is in the past
	 */
	public function testvalidateExpirationDateInPast() {

		// Expire date in the past
		$past = new \DateTime();
		$past->sub(new \DateInterval('P1D'));

		$this->invokePrivate($this->manager, 'validateExpirationDate', [$past]);
	}

	/**
	 * @expectedException InvalidArgumentException
	 * @expectedExceptionMessage Expiration date is enforced
	 */
	public function testvalidateExpirationDateEnforceButNotSet() {
		$this->config->method('getAppValue')
			->will($this->returnValueMap([
				['core', 'shareapi_enforce_expire_date', 'no', 'yes'],
			]));

		$this->invokePrivate($this->manager, 'validateExpirationDate', [null]);
	}

	public function testvalidateExpirationDateEnforceToFarIntoFuture() {
		// Expire date in the past
		$future = new \DateTime();
		$future->add(new \DateInterval('P7D'));

		$this->config->method('getAppValue')
			->will($this->returnValueMap([
				['core', 'shareapi_enforce_expire_date', 'no', 'yes'],
				['core', 'shareapi_expire_after_n_days', '7', '3'],
			]));

		try {
			$this->invokePrivate($this->manager, 'validateExpirationDate', [$future]);
		} catch (\OC\HintException $e) {
			$this->assertEquals('Cannot set expiration date more than 3 days in the future', $e->getMessage());
			$this->assertEquals('Cannot set expiration date more than 3 days in the future', $e->getHint());
			$this->assertEquals(404, $e->getCode());
		}
	}

	public function testvalidateExpirationDateEnforceValid() {
		// Expire date in the past
		$future = new \DateTime();
		$future->add(new \DateInterval('P2D'));
		$future->setTime(0,0,0);
		$expected = $future->format(\DateTime::ISO8601);
		$future->setTime(1,2,3);

		$this->config->method('getAppValue')
			->will($this->returnValueMap([
				['core', 'shareapi_enforce_expire_date', 'no', 'yes'],
				['core', 'shareapi_expire_after_n_days', '7', '3'],
			]));

		$future = $this->invokePrivate($this->manager, 'validateExpirationDate', [$future]);

		$this->assertEquals($expected, $future->format(\DateTime::ISO8601));
	}

	public function testvalidateExpirationDateNoDateNoDefaultNull() {
		$date = new \DateTime();
		$date->add(new \DateInterval('P5D'));

		$res = $this->invokePrivate($this->manager, 'validateExpirationDate', [$date]);

		$this->assertEquals($date, $res);
	}

	public function testvalidateExpirationDateNoDateNoDefault() {
		$date = $this->invokePrivate($this->manager, 'validateExpirationDate', [null]);

		$this->assertNull($date);
	}

	public function testvalidateExpirationDateNoDateDefault() {
		$future = new \DateTime();
		$future->add(new \DateInterval('P3D'));
		$future->setTime(0,0,0);

		$this->config->method('getAppValue')
			->will($this->returnValueMap([
				['core', 'shareapi_default_expire_date', 'no', 'yes'],
				['core', 'shareapi_expire_after_n_days', '7', '3'],
			]));

		$date = $this->invokePrivate($this->manager, 'validateExpirationDate', [null]);

		$this->assertEquals($future->format(\DateTime::ISO8601), $date->format(\DateTime::ISO8601));
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Only sharing with group members is allowed
	 */
	public function testUserCreateChecksShareWithGroupMembersOnlyDifferentGroups() {
		$share = new \OC\Share20\Share();

		$sharedBy = $this->getMock('\OCP\IUser');
		$sharedWith = $this->getMock('\OCP\IUser');
		$share->setSharedBy($sharedBy)->setSharedWith($sharedWith);

		$this->groupManager
			->method('getUserGroupIds')
			->will(
				$this->returnValueMap([
					[$sharedBy, ['group1']],
					[$sharedWith, ['group2']],
				])
			);

		$this->config
			->method('getAppValue')
			->will($this->returnValueMap([
				['core', 'shareapi_only_share_with_group_members', 'no', 'yes'],
			]));

		$this->invokePrivate($this->manager, 'userCreateChecks', [$share]);
	}

	public function testUserCreateChecksShareWithGroupMembersOnlySharedGroup() {
		$share = new \OC\Share20\Share();

		$sharedBy = $this->getMock('\OCP\IUser');
		$sharedWith = $this->getMock('\OCP\IUser');
		$share->setSharedBy($sharedBy)->setSharedWith($sharedWith);

		$path = $this->getMock('\OCP\Files\Node');
		$share->setPath($path);

		$this->groupManager
			->method('getUserGroupIds')
			->will(
				$this->returnValueMap([
					[$sharedBy, ['group1', 'group3']],
					[$sharedWith, ['group2', 'group3']],
				])
			);

		$this->config
			->method('getAppValue')
			->will($this->returnValueMap([
				['core', 'shareapi_only_share_with_group_members', 'no', 'yes'],
			]));

		$this->defaultProvider
			->method('getSharesByPath')
			->with($path)
			->willReturn([]);

		$this->invokePrivate($this->manager, 'userCreateChecks', [$share]);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage  Path already shared with this user
	 */
	public function testUserCreateChecksIdenticalShareExists() {
		$share  = new \OC\Share20\Share();
		$share2 = new \OC\Share20\Share();

		$sharedWith = $this->getMock('\OCP\IUser');
		$path = $this->getMock('\OCP\Files\Node');

		$share->setSharedWith($sharedWith)->setPath($path)
			->setProviderId('foo')->setId('bar');

		$share2->setSharedWith($sharedWith)->setPath($path)
			->setProviderId('foo')->setId('baz');

		$this->defaultProvider
			->method('getSharesByPath')
			->with($path)
			->willReturn([$share2]);

		$this->invokePrivate($this->manager, 'userCreateChecks', [$share]);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage  Path already shared with this user
	 */
 	public function testUserCreateChecksIdenticalPathSharedViaGroup() {
		$share  = new \OC\Share20\Share();

		$sharedWith = $this->getMock('\OCP\IUser');
		$owner = $this->getMock('\OCP\IUser');
		$path = $this->getMock('\OCP\Files\Node');

		$share->setSharedWith($sharedWith)
			->setPath($path)
			->setShareOwner($owner)
			->setProviderId('foo')
			->setId('bar');

		$share2 = new \OC\Share20\Share();
		$owner2 = $this->getMock('\OCP\IUser');
		$share2->setShareType(\OCP\Share::SHARE_TYPE_GROUP)
			->setShareOwner($owner2)
			->setProviderId('foo')
			->setId('baz');

		$group = $this->getMock('\OCP\IGroup');
		$group->method('inGroup')
			->with($sharedWith)
			->willReturn(true);

		$share2->setSharedWith($group);

		$this->defaultProvider
			->method('getSharesByPath')
			->with($path)
			->willReturn([$share2]);

		$this->invokePrivate($this->manager, 'userCreateChecks', [$share]);
	}

	public function testUserCreateChecksIdenticalPathNotSharedWithUser() {
		$share = new \OC\Share20\Share();
		$sharedWith = $this->getMock('\OCP\IUser');
		$owner = $this->getMock('\OCP\IUser');
		$path = $this->getMock('\OCP\Files\Node');
		$share->setSharedWith($sharedWith)
			->setPath($path)
			->setShareOwner($owner)
			->setProviderId('foo')
			->setId('bar');

		$share2 = new \OC\Share20\Share();
		$owner2 = $this->getMock('\OCP\IUser');
		$share2->setShareType(\OCP\Share::SHARE_TYPE_GROUP)
			->setShareOwner($owner2)
			->setProviderId('foo')
			->setId('baz');

		$group = $this->getMock('\OCP\IGroup');
		$group->method('inGroup')
			->with($sharedWith)
			->willReturn(false);

		$share2->setSharedWith($group);

		$this->defaultProvider
			->method('getSharesByPath')
			->with($path)
			->willReturn([$share2]);

		$this->invokePrivate($this->manager, 'userCreateChecks', [$share]);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Only sharing within your own groups is allowed
	 */
	public function testGroupCreateChecksShareWithGroupMembersOnlyNotInGroup() {
		$share = new \OC\Share20\Share();

		$sharedBy = $this->getMock('\OCP\IUser');
		$sharedWith = $this->getMock('\OCP\IGroup');
		$share->setSharedBy($sharedBy)->setSharedWith($sharedWith);

		$sharedWith->method('inGroup')->with($sharedBy)->willReturn(false);

		$this->config
			->method('getAppValue')
			->will($this->returnValueMap([
				['core', 'shareapi_only_share_with_group_members', 'no', 'yes'],
			]));

		$this->invokePrivate($this->manager, 'groupCreateChecks', [$share]);
	}

	public function testGroupCreateChecksShareWithGroupMembersOnlyInGroup() {
		$share = new \OC\Share20\Share();

		$sharedBy = $this->getMock('\OCP\IUser');
		$sharedWith = $this->getMock('\OCP\IGroup');
		$share->setSharedBy($sharedBy)->setSharedWith($sharedWith);

		$sharedWith->method('inGroup')->with($sharedBy)->willReturn(true);

		$path = $this->getMock('\OCP\Files\Node');
		$share->setPath($path);

		$this->defaultProvider->method('getSharesByPath')
			->with($path)
			->willReturn([]);

		$this->config
			->method('getAppValue')
			->will($this->returnValueMap([
				['core', 'shareapi_only_share_with_group_members', 'no', 'yes'],
			]));

		$this->invokePrivate($this->manager, 'groupCreateChecks', [$share]);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Path already shared with this group
	 */
	public function testGroupCreateChecksPathAlreadySharedWithSameGroup() {
		$share = new \OC\Share20\Share();

		$sharedWith = $this->getMock('\OCP\IGroup');
		$path = $this->getMock('\OCP\Files\Node');
		$share->setSharedWith($sharedWith)
			->setPath($path)
			->setProviderId('foo')
			->setId('bar');

		$share2 = new \OC\Share20\Share();
		$share2->setSharedWith($sharedWith)
			->setProviderId('foo')
			->setId('baz');

		$this->defaultProvider->method('getSharesByPath')
			->with($path)
			->willReturn([$share2]);

		$this->invokePrivate($this->manager, 'groupCreateChecks', [$share]);
	}

	public function testGroupCreateChecksPathAlreadySharedWithDifferentGroup() {
		$share = new \OC\Share20\Share();

		$sharedWith = $this->getMock('\OCP\IGroup');
		$share->setSharedWith($sharedWith);

		$path = $this->getMock('\OCP\Files\Node');
		$share->setPath($path);

		$share2 = new \OC\Share20\Share();
		$sharedWith2 = $this->getMock('\OCP\IGroup');
		$share2->setSharedWith($sharedWith2);

		$this->defaultProvider->method('getSharesByPath')
			->with($path)
			->willReturn([$share2]);

		$this->invokePrivate($this->manager, 'groupCreateChecks', [$share]);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Link sharing not allowed
	 */
	public function testLinkCreateChecksNoLinkSharesAllowed() {
		$share = new \OC\Share20\Share();

		$this->config
			->method('getAppValue')
			->will($this->returnValueMap([
				['core', 'shareapi_allow_links', 'yes', 'no'],
			]));

		$this->invokePrivate($this->manager, 'linkCreateChecks', [$share]);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Link shares can't have reshare permissions
	 */
	public function testLinkCreateChecksSharePermissions() {
		$share = new \OC\Share20\Share();

		$share->setPermissions(\OCP\Constants::PERMISSION_SHARE);

		$this->config
			->method('getAppValue')
			->will($this->returnValueMap([
				['core', 'shareapi_allow_links', 'yes', 'yes'],
			]));

		$this->invokePrivate($this->manager, 'linkCreateChecks', [$share]);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Link shares can't have delete permissions
	 */
	public function testLinkCreateChecksDeletePermissions() {
		$share = new \OC\Share20\Share();

		$share->setPermissions(\OCP\Constants::PERMISSION_DELETE);

		$this->config
			->method('getAppValue')
			->will($this->returnValueMap([
				['core', 'shareapi_allow_links', 'yes', 'yes'],
			]));

		$this->invokePrivate($this->manager, 'linkCreateChecks', [$share]);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Public upload not allowed
	 */
	public function testLinkCreateChecksNoPublicUpload() {
		$share = new \OC\Share20\Share();

		$share->setPermissions(\OCP\Constants::PERMISSION_CREATE | \OCP\Constants::PERMISSION_UPDATE);

		$this->config
			->method('getAppValue')
			->will($this->returnValueMap([
				['core', 'shareapi_allow_links', 'yes', 'yes'],
				['core', 'shareapi_allow_public_upload', 'yes', 'no']
			]));

		$this->invokePrivate($this->manager, 'linkCreateChecks', [$share]);
	}

	public function testLinkCreateChecksPublicUpload() {
		$share = new \OC\Share20\Share();

		$share->setPermissions(\OCP\Constants::PERMISSION_CREATE | \OCP\Constants::PERMISSION_UPDATE);

		$this->config
			->method('getAppValue')
			->will($this->returnValueMap([
				['core', 'shareapi_allow_links', 'yes', 'yes'],
				['core', 'shareapi_allow_public_upload', 'yes', 'yes']
			]));

		$this->invokePrivate($this->manager, 'linkCreateChecks', [$share]);
	}

	public function testLinkCreateChecksReadOnly() {
		$share = new \OC\Share20\Share();

		$share->setPermissions(\OCP\Constants::PERMISSION_READ);

		$this->config
			->method('getAppValue')
			->will($this->returnValueMap([
				['core', 'shareapi_allow_links', 'yes', 'yes'],
				['core', 'shareapi_allow_public_upload', 'yes', 'no']
			]));

		$this->invokePrivate($this->manager, 'linkCreateChecks', [$share]);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Path contains files shared with you
	 */
	public function testPathCreateChecksContainsSharedMount() {
		$path = $this->getMock('\OCP\Files\Folder');
		$path->method('getPath')->willReturn('path');

		$mount = $this->getMock('\OCP\Files\Mount\IMountPoint');
		$storage = $this->getMock('\OCP\Files\Storage');
		$mount->method('getStorage')->willReturn($storage);
		$storage->method('instanceOfStorage')->with('\OCA\Files_Sharing\ISharedStorage')->willReturn(true);

		$this->mountManager->method('findIn')->with('path')->willReturn([$mount]);

		$this->invokePrivate($this->manager, 'pathCreateChecks', [$path]);
	}

	public function testPathCreateChecksContainsNoSharedMount() {
		$path = $this->getMock('\OCP\Files\Folder');
		$path->method('getPath')->willReturn('path');

		$mount = $this->getMock('\OCP\Files\Mount\IMountPoint');
		$storage = $this->getMock('\OCP\Files\Storage');
		$mount->method('getStorage')->willReturn($storage);
		$storage->method('instanceOfStorage')->with('\OCA\Files_Sharing\ISharedStorage')->willReturn(false);

		$this->mountManager->method('findIn')->with('path')->willReturn([$mount]);

		$this->invokePrivate($this->manager, 'pathCreateChecks', [$path]);
	}

	public function testPathCreateChecksContainsNoFolder() {
		$path = $this->getMock('\OCP\Files\File');

		$this->invokePrivate($this->manager, 'pathCreateChecks', [$path]);
	}

	public function dataIsSharingDisabledForUser() {
		$data = [];

		// No exclude groups
		$data[] = ['no', null, null, null, false];

		// empty exclude list, user no groups
		$data[] = ['yes', '', json_encode(['']), [], false];

		// empty exclude list, user groups
		$data[] = ['yes', '', json_encode(['']), ['group1', 'group2'], false];

		// Convert old list to json
		$data[] = ['yes', 'group1,group2', json_encode(['group1', 'group2']), [], false];

		// Old list partly groups in common
		$data[] = ['yes', 'group1,group2', json_encode(['group1', 'group2']), ['group1', 'group3'], false];

		// Old list only groups in common
		$data[] = ['yes', 'group1,group2', json_encode(['group1', 'group2']), ['group1'], true];

		// New list partly in common
		$data[] = ['yes', json_encode(['group1', 'group2']), null, ['group1', 'group3'], false];

		// New list only groups in common
		$data[] = ['yes', json_encode(['group1', 'group2']), null, ['group2'], true];

		return $data;
	}

	/**
	 * @dataProvider dataIsSharingDisabledForUser
	 *
	 * @param string $excludeGroups
	 * @param string $groupList
	 * @param string $setList
	 * @param string[] $groupIds
	 * @param bool $expected
	 */
	public function testIsSharingDisabledForUser($excludeGroups, $groupList, $setList, $groupIds, $expected) {
		$user = $this->getMock('\OCP\IUser');

		$this->config->method('getAppValue')
			->will($this->returnValueMap([
				['core', 'shareapi_exclude_groups', 'no', $excludeGroups],
				['core', 'shareapi_exclude_groups_list', '', $groupList],
			]));

		if ($setList !== null) {
			$this->config->expects($this->once())
				->method('setAppValue')
				->with('core', 'shareapi_exclude_groups_list', $setList);
		} else {
			$this->config->expects($this->never())
				->method('setAppValue');
		}

		$this->groupManager->method('getUserGroupIds')
			->with($user)
			->willReturn($groupIds);

		$res = $this->manager->isSharingDisabledForUser($user);
		$this->assertEquals($expected, $res);
	}

	public function dataCanShare() {
		$data = [];

		/*
		 * [expected, sharing enabled, disabled for user]
		 */

		$data[] = [false, 'no', false];
		$data[] = [false, 'no', true];
		$data[] = [true, 'yes', false];
		$data[] = [false, 'yes', true];

		return $data;
	}

	/**
	 * @dataProvider dataCanShare
	 *
	 * @param bool $expected
	 * @param string $sharingEnabled
	 * @param bool $disabledForUser
	 */
	public function testCanShare($expected, $sharingEnabled, $disabledForUser) {
		$this->config->method('getAppValue')
			->will($this->returnValueMap([
				['core', 'shareapi_enabled', 'yes', $sharingEnabled],
			]));

		$manager = $this->createManagerMock()
			->setMethods(['isSharingDisabledForUser'])
			->getMock();

		$manager->method('isSharingDisabledForUser')->willReturn($disabledForUser);

		$user = $this->getMock('\OCP\IUser');
		$share = new \OC\Share20\Share();
		$share->setSharedBy($user);

		$res = $this->invokePrivate($manager, 'canShare', [$share]);
		$this->assertEquals($expected, $res);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage The Share API is disabled
	 */
	public function testCreateShareCantShare() {
		$manager = $this->createManagerMock()
			->setMethods(['canShare'])
			->getMock();

		$manager->expects($this->once())->method('canShare')->willReturn(false);
		$share = new \OC\Share20\Share();
		$manager->createShare($share);
	}

	public function testCreateShareUser() {
		$manager = $this->createManagerMock()
			->setMethods(['canShare', 'generalCreateChecks', 'userCreateChecks', 'pathCreateChecks'])
			->getMock();

		$sharedWith = $this->getMock('\OCP\IUser');
		$sharedBy = $this->getMock('\OCP\IUser');
		$shareOwner = $this->getMock('\OCP\IUser');

		$path = $this->getMock('\OCP\Files\File');
		$path->method('getOwner')->willReturn($shareOwner);
		$path->method('getName')->willReturn('target');

		$share = $this->createShare(
			null,
			\OCP\Share::SHARE_TYPE_USER,
			$path,
			$sharedWith,
			$sharedBy,
			null,
			\OCP\Constants::PERMISSION_ALL);

		$manager->expects($this->once())
			->method('canShare')
			->with($share)
			->willReturn(true);
		$manager->expects($this->once())
			->method('generalCreateChecks')
			->with($share);;
		$manager->expects($this->once())
			->method('userCreateChecks')
			->with($share);;
		$manager->expects($this->once())
			->method('pathCreateChecks')
			->with($path);

		$this->defaultProvider
			->expects($this->once())
			->method('create')
			->with($share)
			->will($this->returnArgument(0));

		$share->expects($this->once())
			->method('setShareOwner')
			->with($shareOwner);
		$share->expects($this->once())
			->method('setTarget')
			->with('/target');

		$manager->createShare($share);
	}

	public function testCreateShareGroup() {
		$manager = $this->createManagerMock()
			->setMethods(['canShare', 'generalCreateChecks', 'groupCreateChecks', 'pathCreateChecks'])
			->getMock();

		$sharedWith = $this->getMock('\OCP\IGroup');
		$sharedBy = $this->getMock('\OCP\IUser');
		$shareOwner = $this->getMock('\OCP\IUser');

		$path = $this->getMock('\OCP\Files\File');
		$path->method('getOwner')->willReturn($shareOwner);
		$path->method('getName')->willReturn('target');

		$share = $this->createShare(
			null,
			\OCP\Share::SHARE_TYPE_GROUP,
			$path,
			$sharedWith,
			$sharedBy,
			null,
			\OCP\Constants::PERMISSION_ALL);

		$manager->expects($this->once())
			->method('canShare')
			->with($share)
			->willReturn(true);
		$manager->expects($this->once())
			->method('generalCreateChecks')
			->with($share);;
		$manager->expects($this->once())
			->method('groupCreateChecks')
			->with($share);;
		$manager->expects($this->once())
			->method('pathCreateChecks')
			->with($path);

		$this->defaultProvider
			->expects($this->once())
			->method('create')
			->with($share)
			->will($this->returnArgument(0));

		$share->expects($this->once())
			->method('setShareOwner')
			->with($shareOwner);
		$share->expects($this->once())
			->method('setTarget')
			->with('/target');

		$manager->createShare($share);
	}

	public function testCreateShareLink() {
		$manager = $this->createManagerMock()
			->setMethods([
				'canShare',
				'generalCreateChecks',
				'linkCreateChecks',
				'pathCreateChecks',
				'validateExpirationDate',
				'verifyPassword',
			])
			->getMock();

		$sharedBy = $this->getMock('\OCP\IUser');
		$sharedBy->method('getUID')->willReturn('sharedBy');
		$shareOwner = $this->getMock('\OCP\IUser');

		$path = $this->getMock('\OCP\Files\File');
		$path->method('getOwner')->willReturn($shareOwner);
		$path->method('getName')->willReturn('target');
		$path->method('getId')->willReturn(1);

		$date = new \DateTime();

		$share = new \OC\Share20\Share();
		$share->setShareType(\OCP\Share::SHARE_TYPE_LINK)
			->setPath($path)
			->setSharedBy($sharedBy)
			->setPermissions(\OCP\Constants::PERMISSION_ALL)
			->setExpirationDate($date)
			->setPassword('password');

		$manager->expects($this->once())
			->method('canShare')
			->with($share)
			->willReturn(true);
		$manager->expects($this->once())
			->method('generalCreateChecks')
			->with($share);;
		$manager->expects($this->once())
			->method('linkCreateChecks')
			->with($share);;
		$manager->expects($this->once())
			->method('pathCreateChecks')
			->with($path);
		$manager->expects($this->once())
			->method('validateExpirationDate')
			->with($date)
			->will($this->returnArgument(0));
		$manager->expects($this->once())
			->method('verifyPassword')
			->with('password');

		$this->hasher->expects($this->once())
			->method('hash')
			->with('password')
			->willReturn('hashed');

		$this->secureRandom->method('getMediumStrengthGenerator')
			->will($this->returnSelf());
		$this->secureRandom->method('generate')
			->willReturn('token');

		$this->defaultProvider
			->expects($this->once())
			->method('create')
			->with($share)
			->will($this->returnCallback(function(Share $share) {
				return $share->setId(42);
			}));

		$hookListner = $this->getMockBuilder('Dummy')->setMethods(['pre', 'post'])->getMock();
		\OCP\Util::connectHook('OCP\Share', 'pre_shared',  $hookListner, 'pre');
		\OCP\Util::connectHook('OCP\Share', 'post_shared', $hookListner, 'post');

		$hookListnerExpectsPre = [
			'itemType' => 'file',
			'itemSource' => 1,
			'shareType' => \OCP\Share::SHARE_TYPE_LINK,
			'uidOwner' => 'sharedBy',
			'permissions' => 31,
			'fileSource' => 1,
			'expiration' => $date,
			'token' => 'token',
			'run' => true,
			'error' => '',
			'itemTarget' => '/target',
			'shareWith' => null,
		];

		$hookListnerExpectsPost = [
			'itemType' => 'file',
			'itemSource' => 1,
			'shareType' => \OCP\Share::SHARE_TYPE_LINK,
			'uidOwner' => 'sharedBy',
			'permissions' => 31,
			'fileSource' => 1,
			'expiration' => $date,
			'token' => 'token',
			'id' => 42,
			'itemTarget' => '/target',
			'fileTarget' => '/target',
			'shareWith' => null,
		];

		$hookListner->expects($this->once())
			->method('pre')
			->with($this->equalTo($hookListnerExpectsPre));
		$hookListner->expects($this->once())
			->method('post')
			->with($this->equalTo($hookListnerExpectsPost));

		/** @var IShare $share */
		$share = $manager->createShare($share);

		$this->assertSame($shareOwner, $share->getShareOwner());
		$this->assertEquals('/target', $share->getTarget());
		$this->assertSame($date, $share->getExpirationDate());
		$this->assertEquals('token', $share->getToken());
		$this->assertEquals('hashed', $share->getPassword());
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage I won't let you share
	 */
	public function testCreateShareHookError() {
		$manager = $this->createManagerMock()
			->setMethods([
				'canShare',
				'generalCreateChecks',
				'userCreateChecks',
				'pathCreateChecks',
			])
			->getMock();

		$sharedWith = $this->getMock('\OCP\IUser');
		$sharedBy = $this->getMock('\OCP\IUser');
		$shareOwner = $this->getMock('\OCP\IUser');

		$path = $this->getMock('\OCP\Files\File');
		$path->method('getOwner')->willReturn($shareOwner);
		$path->method('getName')->willReturn('target');

		$share = $this->createShare(
			null,
			\OCP\Share::SHARE_TYPE_USER,
			$path,
			$sharedWith,
			$sharedBy,
			null,
			\OCP\Constants::PERMISSION_ALL);

		$manager->expects($this->once())
			->method('canShare')
			->with($share)
			->willReturn(true);
		$manager->expects($this->once())
			->method('generalCreateChecks')
			->with($share);;
		$manager->expects($this->once())
			->method('userCreateChecks')
			->with($share);;
		$manager->expects($this->once())
			->method('pathCreateChecks')
			->with($path);

		$share->expects($this->once())
			->method('setShareOwner')
			->with($shareOwner);
		$share->expects($this->once())
			->method('setTarget')
			->with('/target');

		$dummy = new DummyCreate();
		\OCP\Util::connectHook('OCP\Share', 'pre_shared', $dummy, 'listner');

		$manager->createShare($share);
	}

	public function testGetShareByToken() {
		$factory = $this->getMock('\OC\Share20\IProviderFactory');

		$manager = new Manager(
			$this->logger,
			$this->config,
			$this->secureRandom,
			$this->hasher,
			$this->mountManager,
			$this->groupManager,
			$this->l,
			$factory
		);

		$share = $this->getMock('\OC\Share20\IShare');

		$factory->expects($this->once())
			->method('getProviderForType')
			->with(\OCP\Share::SHARE_TYPE_LINK)
			->willReturn($this->defaultProvider);

		$this->defaultProvider->expects($this->once())
			->method('getShareByToken')
			->with('token')
			->willReturn($share);

		$ret = $manager->getShareByToken('token');
		$this->assertSame($share, $ret);
	}

	public function testCheckPasswordNoLinkShare() {
		$share = $this->getMock('\OC\Share20\IShare');
		$share->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_USER);
		$this->assertFalse($this->manager->checkPassword($share, 'password'));
	}

	public function testCheckPasswordNoPassword() {
		$share = $this->getMock('\OC\Share20\IShare');
		$share->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_LINK);
		$this->assertFalse($this->manager->checkPassword($share, 'password'));

		$share->method('getPassword')->willReturn('password');
		$this->assertFalse($this->manager->checkPassword($share, null));
	}

	public function testCheckPasswordInvalidPassword() {
		$share = $this->getMock('\OC\Share20\IShare');
		$share->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_LINK);
		$share->method('getPassword')->willReturn('password');

		$this->hasher->method('verify')->with('invalidpassword', 'password', '')->willReturn(false);

		$this->assertFalse($this->manager->checkPassword($share, 'invalidpassword'));
	}

	public function testCheckPasswordValidPassword() {
		$share = $this->getMock('\OC\Share20\IShare');
		$share->method('getShareType')->willReturn(\OCP\Share::SHARE_TYPE_LINK);
		$share->method('getPassword')->willReturn('passwordHash');

		$this->hasher->method('verify')->with('password', 'passwordHash', '')->willReturn(true);

		$this->assertTrue($this->manager->checkPassword($share, 'password'));
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage The Share API is disabled
	 */
	public function testUpdateShareCantShare() {
		$manager = $this->createManagerMock()
			->setMethods(['canShare'])
			->getMock();

		$manager->expects($this->once())->method('canShare')->willReturn(false);
		$share = new \OC\Share20\Share();
		$manager->updateShare($share);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Can't change share type
	 */
	public function testUpdateShareCantChangeShareType() {
		$manager = $this->createManagerMock()
			->setMethods([
				'canShare',
				'getShareById'
			])
			->getMock();

		$originalShare = new \OC\Share20\Share();
		$originalShare->setShareType(\OCP\Share::SHARE_TYPE_GROUP);

		$manager->expects($this->once())->method('canShare')->willReturn(true);
		$manager->expects($this->once())->method('getShareById')->with('foo:42')->willReturn($originalShare);

		$share = new \OC\Share20\Share();
		$share->setProviderId('foo')
			->setId('42')
			->setShareType(\OCP\Share::SHARE_TYPE_USER);

		$manager->updateShare($share);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Can only update recipient on user shares
	 */
	public function testUpdateShareCantChangeRecipientForGroupShare() {
		$manager = $this->createManagerMock()
			->setMethods([
				'canShare',
				'getShareById'
			])
			->getMock();

		$origGroup = $this->getMock('\OCP\IGroup');
		$newGroup = $this->getMock('\OCP\IGroup');

		$originalShare = new \OC\Share20\Share();
		$originalShare->setShareType(\OCP\Share::SHARE_TYPE_GROUP)
			->setSharedWith($origGroup);

		$manager->expects($this->once())->method('canShare')->willReturn(true);
		$manager->expects($this->once())->method('getShareById')->with('foo:42')->willReturn($originalShare);

		$share = new \OC\Share20\Share();
		$share->setProviderId('foo')
			->setId('42')
			->setShareType(\OCP\Share::SHARE_TYPE_GROUP)
			->setSharedWith($newGroup);

		$manager->updateShare($share);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Can't share with the share owner
	 */
	public function testUpdateShareCantShareWithOwner() {
		$manager = $this->createManagerMock()
			->setMethods([
				'canShare',
				'getShareById'
			])
			->getMock();

		$origUser = $this->getMock('\OCP\IUser');
		$newUser = $this->getMock('\OCP\IUser');

		$originalShare = new \OC\Share20\Share();
		$originalShare->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setSharedWith($origUser);

		$manager->expects($this->once())->method('canShare')->willReturn(true);
		$manager->expects($this->once())->method('getShareById')->with('foo:42')->willReturn($originalShare);

		$share = new \OC\Share20\Share();
		$share->setProviderId('foo')
			->setId('42')
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setSharedWith($newUser)
			->setShareOwner($newUser);

		$manager->updateShare($share);
	}

	public function testUpdateShareUser() {
		$manager = $this->createManagerMock()
			->setMethods([
				'canShare',
				'getShareById',
				'generalCreateChecks',
				'userCreateChecks',
				'pathCreateChecks',
			])
			->getMock();

		$origUser = $this->getMock('\OCP\IUser');
		$newUser = $this->getMock('\OCP\IUser');

		$originalShare = new \OC\Share20\Share();
		$originalShare->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setSharedWith($origUser);

		$manager->expects($this->once())->method('canShare')->willReturn(true);
		$manager->expects($this->once())->method('getShareById')->with('foo:42')->willReturn($originalShare);

		$share = new \OC\Share20\Share();
		$share->setProviderId('foo')
			->setId('42')
			->setShareType(\OCP\Share::SHARE_TYPE_USER)
			->setSharedWith($origUser)
			->setShareOwner($newUser);

		$this->defaultProvider->expects($this->once())
			->method('update')
			->with($share)
			->willReturn($share);

		$hookListner = $this->getMockBuilder('Dummy')->setMethods(['post'])->getMock();
		\OCP\Util::connectHook('OCP\Share', 'post_set_expiration_date', $hookListner, 'post');
		$hookListner->expects($this->never())->method('post');


		$manager->updateShare($share);
	}

	public function testUpdateShareGroup() {
		$manager = $this->createManagerMock()
			->setMethods([
				'canShare',
				'getShareById',
				'generalCreateChecks',
				'groupCreateChecks',
				'pathCreateChecks',
			])
			->getMock();

		$origGroup = $this->getMock('\OCP\IGroup');
		$user = $this->getMock('\OCP\IUser');

		$originalShare = new \OC\Share20\Share();
		$originalShare->setShareType(\OCP\Share::SHARE_TYPE_GROUP)
			->setSharedWith($origGroup);

		$manager->expects($this->once())->method('canShare')->willReturn(true);
		$manager->expects($this->once())->method('getShareById')->with('foo:42')->willReturn($originalShare);

		$share = new \OC\Share20\Share();
		$share->setProviderId('foo')
			->setId('42')
			->setShareType(\OCP\Share::SHARE_TYPE_GROUP)
			->setSharedWith($origGroup)
			->setShareOwner($user);

		$this->defaultProvider->expects($this->once())
			->method('update')
			->with($share)
			->willReturn($share);

		$hookListner = $this->getMockBuilder('Dummy')->setMethods(['post'])->getMock();
		\OCP\Util::connectHook('OCP\Share', 'post_set_expiration_date', $hookListner, 'post');
		$hookListner->expects($this->never())->method('post');


		$manager->updateShare($share);
	}

	public function testUpdateShareLink() {
		$manager = $this->createManagerMock()
			->setMethods([
				'canShare',
				'getShareById',
				'generalCreateChecks',
				'linkCreateChecks',
				'pathCreateChecks',
				'verifyPassword',
				'validateExpirationDate',
			])
			->getMock();

		$user = $this->getMock('\OCP\IUser');
		$user->method('getUID')->willReturn('owner');

		$originalShare = new \OC\Share20\Share();
		$originalShare->setShareType(\OCP\Share::SHARE_TYPE_LINK);

		$tomorrow = new \DateTime();
		$tomorrow->setTime(0,0,0);
		$tomorrow->add(new \DateInterval('P1D'));

		$manager->expects($this->once())->method('canShare')->willReturn(true);
		$manager->expects($this->once())->method('getShareById')->with('foo:42')->willReturn($originalShare);
		$manager->expects($this->once())->method('validateExpirationDate')->with($tomorrow)->willReturn($tomorrow);

		$file = $this->getMock('OCP\Files\File', [], [], 'File');
		$file->method('getId')->willReturn(100);

		$share = new \OC\Share20\Share();
		$share->setProviderId('foo')
			->setId('42')
			->setShareType(\OCP\Share::SHARE_TYPE_LINK)
			->setSharedBy($user)
			->setShareOwner($user)
			->setPassword('password')
			->setExpirationDate($tomorrow)
			->setPath($file);

		$this->defaultProvider->expects($this->once())
			->method('update')
			->with($share)
			->willReturn($share);

		$hookListner = $this->getMockBuilder('Dummy')->setMethods(['post'])->getMock();
		\OCP\Util::connectHook('OCP\Share', 'post_set_expiration_date', $hookListner, 'post');
		$hookListner->expects($this->once())->method('post')->with([
			'itemType' => 'file',
			'itemSource' => 100,
			'date' => $tomorrow,
			'uidOwner' => 'owner',
		]);


		$manager->updateShare($share);
	}
}

class DummyPassword {
	public function listner($array) {
		$array['accepted'] = false;
		$array['message'] = 'password not accepted';
	}
}

class DummyCreate {
	public function listner($array) {
		$array['run'] = false;
		$array['error'] = 'I won\'t let you share!';
	}
}

class DummyFactory implements IProviderFactory {

	/** @var IShareProvider */
	private $provider;

	public function __construct(\OCP\IServerContainer $serverContainer) {

	}

	/**
	 * @param IShareProvider $provider
	 */
	public function setProvider($provider) {
		$this->provider = $provider;
	}

	/**
	 * @param string $id
	 * @return IShareProvider
	 */
	public function getProvider($id) {
		return $this->provider;
	}

	/**
	 * @param int $shareType
	 * @return IShareProvider
	 */
	public function getProviderForType($shareType) {
		return $this->provider;
	}
}