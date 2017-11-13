<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Juan Pablo Villafáñez <jvillafanez@solidgear.es>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Roger Szabo <roger.szabo@web.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 *
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

namespace OCA\User_LDAP\Tests\User;

use OCA\User_LDAP\Access;
use OCA\User_LDAP\Connection;
use OCA\User_LDAP\FilesystemHelper;
use OCA\User_LDAP\ILDAPWrapper;
use OCA\User_LDAP\LogWrapper;
use OCA\User_LDAP\User\IUserTools;
use OCA\User_LDAP\User\User;
use OCP\IAvatar;
use OCP\IAvatarManager;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\Image;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Notification\IManager as INotificationManager;
use OCP\Notification\INotification;

/**
 * Class UserTest
 *
 * @group DB
 *
 * @package OCA\User_LDAP\Tests\User
 */
class UserTest extends \Test\TestCase {
	/** @var  Access|\PHPUnit_Framework_MockObject_MockObject */
	protected $access;
	/** @var  Connection|\PHPUnit_Framework_MockObject_MockObject */
	protected $connection;

	public function setUp() {
		/** @var Access|\PHPUnit_Framework_MockObject_MockObject access */
		$this->access = $this->createMock(Access::class);
		$this->connection = $this->createMock(Connection::class);

		$this->access->connection = $this->connection;
		$this->access->expects($this->any())
			->method('getConnection')
			->willReturn($this->connection);

		parent::setUp();
	}

	private function getTestInstances() {
		$access  = $this->createMock(IUserTools::class);
		$config  = $this->createMock(IConfig::class);
		$filesys = $this->createMock(FilesystemHelper::class);
		$log     = $this->createMock(LogWrapper::class);
		$avaMgr  = $this->createMock(IAvatarManager::class);
		$image   = $this->createMock(Image::class);
		$dbc     = $this->createMock(IDBConnection::class);
		$userMgr  = $this->createMock(IUserManager::class);
		$notiMgr  = $this->createMock(INotificationManager::class);

		return array($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr, $notiMgr);
	}

	public function testGetDNandUsername() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $db, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$this->assertSame($dn, $user->getDN());
		$this->assertSame($uid, $user->getUsername());
	}

	public function testUpdateEmailProvided() {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapEmailAttribute'))
			->will($this->returnValue('email'));

		$this->access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('email'))
			->will($this->returnValue(array('alice@foo.bar')));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$uuser = $this->getMockBuilder(IUser::class)
			->disableOriginalConstructor()
			->getMock();
		$uuser->expects($this->once())
			->method('setEMailAddress')
			->with('alice@foo.bar');
		/** @var IUserManager | \PHPUnit_Framework_MockObject_MockObject $userMgr */
		$userMgr->expects($this->any())
			->method('get')
			->willReturn($uuser);
		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$user->updateEmail();
	}

	public function testUpdateEmailNotProvided() {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapEmailAttribute'))
			->will($this->returnValue('email'));
		$this->access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('email'))
			->will($this->returnValue(false));

		$config->expects($this->never())
			->method('setUserValue');

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$user->updateEmail();
	}

	public function testUpdateEmailNotConfigured() {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$this->connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapEmailAttribute'))
			->will($this->returnValue(''));

		$this->access->expects($this->never())
			->method('readAttribute');

		$config->expects($this->never())
			->method('setUserValue');

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$user->updateEmail();
	}

	public function testUpdateQuotaAllProvided() {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$this->connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('myquota'));
		$this->connection->expects($this->exactly(1))
			->method('__get');

		$this->access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('myquota'))
			->will($this->returnValue(array('42 GB')));

		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('setQuota')
			->with('42 GB');

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$user->updateQuota();
	}

	public function testUpdateQuotaToDefaultAllProvided() {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$this->connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('myquota'));
		$this->connection->expects($this->exactly(1))
			->method('__get');

		$this->access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('myquota'))
			->will($this->returnValue(array('default')));

		$user = $this->createMock('\OCP\IUser');
		$user->expects($this->once())
			->method('setQuota')
			->with('default');

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$user->updateQuota();
	}

	public function testUpdateQuotaToNoneAllProvided() {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$this->connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('myquota'));
		$this->connection->expects($this->exactly(1))
			->method('__get');

		$this->access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('myquota'))
			->will($this->returnValue(array('none')));

		$user = $this->createMock('\OCP\IUser');
		$user->expects($this->once())
			->method('setQuota')
			->with('none');

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$user->updateQuota();
	}

	public function testUpdateQuotaDefaultProvided() {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$this->connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('myquota'));
		$this->connection->expects($this->at(1))
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'))
			->will($this->returnValue('25 GB'));
		$this->connection->expects($this->exactly(2))
			->method('__get');

		$this->access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('myquota'))
			->will($this->returnValue(false));

		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('setQuota')
			->with('25 GB');

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$user->updateQuota();
	}

	public function testUpdateQuotaIndividualProvided() {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$this->connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('myquota'));
		$this->connection->expects($this->exactly(1))
			->method('__get');

		$this->access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('myquota'))
			->will($this->returnValue(array('27 GB')));

		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('setQuota')
			->with('27 GB');

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$user->updateQuota();
	}

	public function testUpdateQuotaNoneProvided() {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$this->connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('myquota'));
		$this->connection->expects($this->at(1))
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'))
			->will($this->returnValue(''));
		$this->connection->expects($this->exactly(2))
			->method('__get');

		$this->access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('myquota'))
			->will($this->returnValue(false));

		$user = $this->createMock('\OCP\IUser');
		$user->expects($this->never())
			->method('setQuota');

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$config->expects($this->never())
			->method('setUserValue');

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$user->updateQuota();
	}

	public function testUpdateQuotaNoneConfigured() {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$this->connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue(''));
		$this->connection->expects($this->at(1))
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'))
			->will($this->returnValue(''));
		$this->connection->expects($this->exactly(2))
			->method('__get');

		$user = $this->createMock('\OCP\IUser');
		$user->expects($this->never())
			->method('setQuota');

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$this->access->expects($this->never())
			->method('readAttribute');

		$config->expects($this->never())
			->method('setUserValue');

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$user->updateQuota();
	}

	public function testUpdateQuotaFromValue() {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$readQuota = '19 GB';

		$this->connection->expects($this->never())
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'));

		$this->access->expects($this->never())
			->method('readAttribute');

		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('setQuota')
			->with($readQuota);

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$user->updateQuota($readQuota);
	}

	/**
	 * Unparseable quota will fallback to use the LDAP default
	 */
	public function testUpdateWrongQuotaAllProvided() {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$this->connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('myquota'));
		$this->connection->expects($this->at(1))
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'))
			->will($this->returnValue('23 GB'));
		$this->connection->expects($this->exactly(2))
			->method('__get');

		$this->access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('myquota'))
			->will($this->returnValue(array('42 GBwos')));

		$user = $this->createMock('\OCP\IUser');
		$user->expects($this->once())
			->method('setQuota')
			->with('23 GB');

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$user->updateQuota();
	}

	/**
	 * No user quota and wrong default will set 'default' as quota
	 */
	public function testUpdateWrongDefaultQuotaProvided() {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$this->connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('myquota'));
		$this->connection->expects($this->at(1))
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'))
			->will($this->returnValue('23 GBwowowo'));
		$this->connection->expects($this->exactly(2))
			->method('__get');

		$this->access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('myquota'))
			->will($this->returnValue(false));

		$user = $this->createMock('\OCP\IUser');
		$user->expects($this->never())
			->method('setQuota');

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$user->updateQuota();
	}

	/**
	 * Wrong user quota and wrong default will set 'default' as quota
	 */
	public function testUpdateWrongQuotaAndDefaultAllProvided() {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$this->connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('myquota'));
		$this->connection->expects($this->at(1))
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'))
			->will($this->returnValue('23 GBwowowo'));
		$this->connection->expects($this->exactly(2))
			->method('__get');

		$this->access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('myquota'))
			->will($this->returnValue(array('23 flush')));

		$user = $this->createMock('\OCP\IUser');
		$user->expects($this->never())
			->method('setQuota');

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$user->updateQuota();
	}

	/**
	 * No quota attribute set and wrong default will set 'default' as quota
	 */
	public function testUpdateWrongDefaultQuotaNotProvided() {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$this->connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue(''));
		$this->connection->expects($this->at(1))
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'))
			->will($this->returnValue('23 GBwowowo'));
		$this->connection->expects($this->exactly(2))
			->method('__get');

		$this->access->expects($this->never())
			->method('readAttribute');

		$user = $this->createMock('\OCP\IUser');
		$user->expects($this->never())
			->method('setQuota');

		$userMgr->expects($this->once())
			->method('get')
			->with('alice')
			->will($this->returnValue($user));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$user->updateQuota();
	}

	//the testUpdateAvatar series also implicitely tests getAvatarImage
	public function testUpdateAvatarJpegPhotoProvided() {
		list(, $config, $filesys, $image, $log, $avaMgr, , $userMgr, $notiMgr) =
			$this->getTestInstances();

		$this->access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('jpegPhoto'))
			->will($this->returnValue(array('this is a photo')));

		$image->expects($this->once())
			->method('valid')
			->will($this->returnValue(true));
		$image->expects($this->once())
			->method('width')
			->will($this->returnValue(128));
		$image->expects($this->once())
			->method('height')
			->will($this->returnValue(128));
		$image->expects($this->once())
			->method('centerCrop')
			->will($this->returnValue(true));

		$filesys->expects($this->once())
			->method('isLoaded')
			->will($this->returnValue(true));

		$avatar = $this->createMock(IAvatar::class);
		$avatar->expects($this->once())
			->method('set')
			->with($this->isInstanceOf($image));

		$avaMgr->expects($this->once())
			->method('getAvatar')
			->with($this->equalTo('alice'))
			->will($this->returnValue($avatar));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$user->updateAvatar();
	}

	public function testUpdateAvatarThumbnailPhotoProvided() {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$this->access->expects($this->any())
			->method('readAttribute')
			->willReturnCallback(function($dn, $attr) {
				if($dn === 'uid=alice,dc=foo,dc=bar'
					&& $attr === 'jpegPhoto')
				{
					return false;
				} elseif($dn === 'uid=alice,dc=foo,dc=bar'
					&& $attr === 'thumbnailPhoto')
				{
					return ['this is a photo'];
				}
				return null;
			});

		$image->expects($this->once())
			->method('valid')
			->will($this->returnValue(true));
		$image->expects($this->once())
			->method('width')
			->will($this->returnValue(128));
		$image->expects($this->once())
			->method('height')
			->will($this->returnValue(128));
		$image->expects($this->once())
			->method('centerCrop')
			->will($this->returnValue(true));

		$filesys->expects($this->once())
			->method('isLoaded')
			->will($this->returnValue(true));

		$avatar = $this->createMock(IAvatar::class);
		$avatar->expects($this->once())
			->method('set')
			->with($this->isInstanceOf($image));

		$avaMgr->expects($this->once())
			->method('getAvatar')
			->with($this->equalTo('alice'))
			->will($this->returnValue($avatar));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$user->updateAvatar();
	}

	public function testUpdateAvatarNotProvided() {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$this->access->expects($this->any())
			->method('readAttribute')
			->willReturnCallback(function($dn, $attr) {
				if($dn === 'uid=alice,dc=foo,dc=bar'
					&& $attr === 'jpegPhoto')
				{
					return false;
				} elseif($dn === 'uid=alice,dc=foo,dc=bar'
					&& $attr === 'thumbnailPhoto')
				{
					return false;
				}
				return null;
			});

		$image->expects($this->never())
			->method('valid');
		$image->expects($this->never())
			->method('width');
		$image->expects($this->never())
			->method('height');
		$image->expects($this->never())
			->method('centerCrop');

		$filesys->expects($this->never())
			->method('isLoaded');

		$avaMgr->expects($this->never())
			->method('getAvatar');

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$user->updateAvatar();
	}

	public function testUpdateBeforeFirstLogin() {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$config->expects($this->at(0))
			->method('getUserValue')
			->with($this->equalTo('alice'), $this->equalTo('user_ldap'),
				$this->equalTo(User::USER_PREFKEY_FIRSTLOGIN),
				$this->equalTo(0))
			->will($this->returnValue(0));
		$config->expects($this->at(1))
			->method('getUserValue')
			->with($this->equalTo('alice'), $this->equalTo('user_ldap'),
				$this->equalTo(User::USER_PREFKEY_LASTREFRESH),
				$this->equalTo(0))
			->will($this->returnValue(0));
		$config->expects($this->exactly(2))
			->method('getUserValue');
		$config->expects($this->never())
			->method('setUserValue');

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$user->update();
	}

	public function testUpdateAfterFirstLogin() {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$config->expects($this->at(0))
			->method('getUserValue')
			->with($this->equalTo('alice'), $this->equalTo('user_ldap'),
				$this->equalTo(User::USER_PREFKEY_FIRSTLOGIN),
				$this->equalTo(0))
			->will($this->returnValue(1));
		$config->expects($this->at(1))
			->method('getUserValue')
			->with($this->equalTo('alice'), $this->equalTo('user_ldap'),
				$this->equalTo(User::USER_PREFKEY_LASTREFRESH),
				$this->equalTo(0))
			->will($this->returnValue(0));
		$config->expects($this->exactly(2))
			->method('getUserValue');
		$config->expects($this->once())
			->method('setUserValue')
			->with($this->equalTo('alice'), $this->equalTo('user_ldap'),
				$this->equalTo(User::USER_PREFKEY_LASTREFRESH),
				$this->anything())
			->will($this->returnValue(true));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$user->update();
	}

	public function testUpdateNoRefresh() {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$config->expects($this->at(0))
			->method('getUserValue')
			->with($this->equalTo('alice'), $this->equalTo('user_ldap'),
				$this->equalTo(User::USER_PREFKEY_FIRSTLOGIN),
				$this->equalTo(0))
			->will($this->returnValue(1));
		$config->expects($this->at(1))
			->method('getUserValue')
			->with($this->equalTo('alice'), $this->equalTo('user_ldap'),
				$this->equalTo(User::USER_PREFKEY_LASTREFRESH),
				$this->equalTo(0))
			->will($this->returnValue(time() - 10));

		$config->expects($this->once())
			->method('getAppValue')
			->with($this->equalTo('user_ldap'),
				$this->equalTo('updateAttributesInterval'),
				$this->anything())
			->will($this->returnValue(1800));
		$config->expects($this->exactly(2))
			->method('getUserValue');
		$config->expects($this->never())
			->method('setUserValue');

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$user->update();
	}

	public function testMarkLogin() {
		list(, $config, $filesys, $image, $log, $avaMgr, $db, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$config->expects($this->once())
			->method('setUserValue')
			->with($this->equalTo('alice'),
				$this->equalTo('user_ldap'),
				$this->equalTo(User::USER_PREFKEY_FIRSTLOGIN),
				$this->equalTo(1))
			->will($this->returnValue(true));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$user->markLogin();
	}

	public function testGetAvatarImageProvided() {
		list(, $config, $filesys, $image, $log, $avaMgr, $db, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$this->access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('jpegPhoto'))
			->will($this->returnValue(array('this is a photo')));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$photo = $user->getAvatarImage();
		$this->assertSame('this is a photo', $photo);
		//make sure readAttribute is not called again but the already fetched
		//photo is returned
		$photo = $user->getAvatarImage();
	}

	public function testProcessAttributes() {
		list(, $config, $filesys, $image, $log, $avaMgr, , $userMgr, $notiMgr) =
			$this->getTestInstances();

		$uid = 'alice';
		$dn = 'uid=alice';

		$requiredMethods = array(
			'markRefreshTime',
			'updateQuota',
			'updateEmail',
			'composeAndStoreDisplayName',
			'storeLDAPUserName',
			'getHomePath',
			'updateAvatar'
		);

		$userMock = $this->getMockBuilder('OCA\User_LDAP\User\User')
			->setConstructorArgs(array($uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr))
			->setMethods($requiredMethods)
			->getMock();

		$this->connection->setConfiguration(array(
			'homeFolderNamingRule' => 'homeDirectory'
		));
		$this->connection->expects($this->any())
			->method('__get')
			//->will($this->returnArgument(0));
			->will($this->returnCallback(function($name) {
				if($name === 'homeFolderNamingRule') {
					return 'attr:homeDirectory';
				}
				return $name;
			}));

		$record = array(
			strtolower($this->connection->ldapQuotaAttribute) => array('4096'),
			strtolower($this->connection->ldapEmailAttribute) => array('alice@wonderland.org'),
			strtolower($this->connection->ldapUserDisplayName) => array('Aaaaalice'),
			'uid' => array($uid),
			'homedirectory' => array('Alice\'s Folder'),
			'memberof' => array('cn=groupOne', 'cn=groupTwo'),
			'jpegphoto' => array('here be an image')
		);

		foreach($requiredMethods as $method) {
			$userMock->expects($this->once())
				->method($method);
		}
		\OC_Hook::clear();//disconnect irrelevant hooks 
		$userMock->processAttributes($record);
		\OC_Hook::emit('OC_User', 'post_login', array('uid' => $uid));
	}

	public function emptyHomeFolderAttributeValueProvider() {
		return array(
			'empty' => array(''),
			'prefixOnly' => array('attr:'),
		);
	}

	/**
	 * @dataProvider emptyHomeFolderAttributeValueProvider
	 */
	public function testGetHomePathNotConfigured($attributeValue) {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$this->connection->expects($this->any())
			->method('__get')
			->with($this->equalTo('homeFolderNamingRule'))
			->will($this->returnValue($attributeValue));

		$this->access->expects($this->never())
			->method('readAttribute');

		$config->expects($this->never())
			->method('getAppValue');

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$path = $user->getHomePath();
		$this->assertSame($path, false);
	}

	public function testGetHomePathConfiguredNotAvailableAllowed() {
		list(, $config, $filesys, $image, $log, $avaMgr, , $userMgr, $notiMgr) =
			$this->getTestInstances();

		$this->connection->expects($this->any())
			->method('__get')
			->with($this->equalTo('homeFolderNamingRule'))
			->will($this->returnValue('attr:foobar'));

		$this->access->expects($this->once())
			->method('readAttribute')
			->will($this->returnValue(false));

		// asks for "enforce_home_folder_naming_rule"
		$config->expects($this->once())
			->method('getAppValue')
			->will($this->returnValue(false));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$path = $user->getHomePath();

		$this->assertSame($path, false);
	}

	/**
	 * @expectedException \Exception
	 */
	public function testGetHomePathConfiguredNotAvailableNotAllowed() {
		list(, $config, $filesys, $image, $log, $avaMgr, , $userMgr, $notiMgr) =
			$this->getTestInstances();

		$this->connection->expects($this->any())
			->method('__get')
			->with($this->equalTo('homeFolderNamingRule'))
			->will($this->returnValue('attr:foobar'));

		$this->access->expects($this->once())
			->method('readAttribute')
			->will($this->returnValue(false));

		// asks for "enforce_home_folder_naming_rule"
		$config->expects($this->once())
			->method('getAppValue')
			->will($this->returnValue(true));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$user->getHomePath();
	}

	public function displayNameProvider() {
		return [
			['Roland Deschain', '', 'Roland Deschain'],
			['Roland Deschain', null, 'Roland Deschain'],
			['Roland Deschain', 'gunslinger@darktower.com', 'Roland Deschain (gunslinger@darktower.com)'],
		];
	}

	/**
	 * @dataProvider displayNameProvider
	 */
	public function testComposeAndStoreDisplayName($part1, $part2, $expected) {
		list(, $config, $filesys, $image, $log, $avaMgr, , $userMgr, $notiMgr) =
			$this->getTestInstances();

		$config->expects($this->once())
			->method('setUserValue');

		$user = new User(
			'user', 'cn=user', $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		$displayName = $user->composeAndStoreDisplayName($part1, $part2);
		$this->assertSame($expected, $displayName);
	}

	public function testHandlePasswordExpiryWarningDefaultPolicy() {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr, $notiMgr) =
			$this->getTestInstances();

		$uid = 'alice';
		$dn = 'uid=alice';

		$this->connection->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function($name) {
				if($name === 'ldapDefaultPPolicyDN') {
					return 'cn=default,ou=policies,dc=foo,dc=bar';
				}
				if($name === 'turnOnPasswordChange') {
					return '1';
				}
				return $name;
			}));

		$this->access->expects($this->any())
			->method('search')
			->will($this->returnCallback(function($filter, $base) {
				if($base === 'uid=alice') {
					return array(
						array(
							'pwdchangedtime' => array((new \DateTime())->sub(new \DateInterval('P28D'))->format('Ymdhis').'Z'),
							'pwdgraceusetime' => [],
						),
					);
				}
				if($base === 'cn=default,ou=policies,dc=foo,dc=bar') {
					return array(
						array(
							'pwdmaxage' => array('2592000'),
							'pwdexpirewarning' => array('2591999'),
						),
					);
				}
				return array();
			}));

		$notification = $this->getMockBuilder(INotification::class)
			->disableOriginalConstructor()
			->getMock();
		$notification->expects($this->any())
			->method('setApp')
			->will($this->returnValue($notification));
		$notification->expects($this->any())
			->method('setUser')
			->will($this->returnValue($notification));
		$notification->expects($this->any())
			->method('setObject')
			->will($this->returnValue($notification));
		$notification->expects($this->any())
			->method('setDateTime')
			->will($this->returnValue($notification));
		$notiMgr->expects($this->exactly(2))
			->method('createNotification')
			->will($this->returnValue($notification));
		$notiMgr->expects($this->exactly(1))
			->method('notify');

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		\OC_Hook::clear();//disconnect irrelevant hooks
		\OCP\Util::connectHook('OC_User', 'post_login', $user, 'handlePasswordExpiry');
		\OC_Hook::emit('OC_User', 'post_login', array('uid' => $uid));
	}

	public function testHandlePasswordExpiryWarningCustomPolicy() {
		list(, $config, $filesys, $image, $log, $avaMgr, , $userMgr, $notiMgr) =
			$this->getTestInstances();

		$uid = 'alice';
		$dn = 'uid=alice';

		$this->connection->expects($this->any())
			->method('__get')
			->will($this->returnCallback(function($name) {
				if($name === 'ldapDefaultPPolicyDN') {
					return 'cn=default,ou=policies,dc=foo,dc=bar';
				}
				if($name === 'turnOnPasswordChange') {
					return '1';
				}
				return $name;
			}));

		$this->access->expects($this->any())
			->method('search')
			->will($this->returnCallback(function($filter, $base) {
				if($base === 'uid=alice') {
					return array(
						array(
							'pwdpolicysubentry' => array('cn=custom,ou=policies,dc=foo,dc=bar'),
							'pwdchangedtime' => array((new \DateTime())->sub(new \DateInterval('P28D'))->format('Ymdhis').'Z'),
							'pwdgraceusetime' => [],
						)
					);
				}
				if($base === 'cn=custom,ou=policies,dc=foo,dc=bar') {
					return array(
						array(
							'pwdmaxage' => array('2592000'),
							'pwdexpirewarning' => array('2591999'),
						)
					);
				}
				return array();
			}));

		$notification = $this->getMockBuilder(INotification::class)
			->disableOriginalConstructor()
			->getMock();
		$notification->expects($this->any())
			->method('setApp')
			->will($this->returnValue($notification));
		$notification->expects($this->any())
			->method('setUser')
			->will($this->returnValue($notification));
		$notification->expects($this->any())
			->method('setObject')
			->will($this->returnValue($notification));
		$notification->expects($this->any())
			->method('setDateTime')
			->will($this->returnValue($notification));
		$notiMgr->expects($this->exactly(2))
			->method('createNotification')
			->will($this->returnValue($notification));
		$notiMgr->expects($this->exactly(1))
			->method('notify');

		$user = new User(
			$uid, $dn, $this->access, $config, $filesys, $image, $log, $avaMgr, $userMgr, $notiMgr);

		\OC_Hook::clear();//disconnect irrelevant hooks 
		\OCP\Util::connectHook('OC_User', 'post_login', $user, 'handlePasswordExpiry');
		\OC_Hook::emit('OC_User', 'post_login', array('uid' => $uid));
	}
}
