<?php
/**
 * @author Arthur Schiwon <blizzz@owncloud.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Morris Jobke <hey@morrisjobke.de>
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

namespace OCA\user_ldap\tests;

use OCA\user_ldap\lib\user\User;

class Test_User_User extends \Test\TestCase {

	private function getTestInstances() {
		$access  = $this->getMock('\OCA\user_ldap\lib\user\IUserTools');
		$config  = $this->getMock('\OCP\IConfig');
		$filesys = $this->getMock('\OCA\user_ldap\lib\FilesystemHelper');
		$log     = $this->getMock('\OCA\user_ldap\lib\LogWrapper');
		$avaMgr  = $this->getMock('\OCP\IAvatarManager');
		$image   = $this->getMock('\OCP\Image');
		$dbc     = $this->getMock('\OCP\IDBConnection');

		return array($access, $config, $filesys, $image, $log, $avaMgr, $dbc);
	}

	private function getAdvancedMocks($cfMock, $fsMock, $logMock, $avaMgr, $dbc) {
		static $conMethods;
		static $accMethods;
		static $umMethods;

		if(is_null($conMethods) || is_null($accMethods)) {
			$conMethods = get_class_methods('\OCA\user_ldap\lib\Connection');
			$accMethods = get_class_methods('\OCA\user_ldap\lib\Access');
			//getConnection shall not be replaced
			unset($accMethods[array_search('getConnection', $accMethods)]);
			$umMethods = get_class_methods('\OCA\user_ldap\lib\user\Manager');
		}
		$lw = $this->getMock('\OCA\user_ldap\lib\ILDAPWrapper');
		$im = $this->getMock('\OCP\Image');
		$um = $this->getMock('\OCA\user_ldap\lib\user\Manager',
			$umMethods, array($cfMock, $fsMock, $logMock, $avaMgr, $im, $dbc));
		$connector = $this->getMock('\OCA\user_ldap\lib\Connection',
			$conMethods, array($lw, null, null));
		$access = $this->getMock('\OCA\user_ldap\lib\Access',
			$accMethods, array($connector, $lw, $um));

		return array($access, $connector);
	}

	public function testGetDNandUsername() {
		list($access, $config, $filesys, $image, $log, $avaMgr) =
			$this->getTestInstances();

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr);

		$this->assertSame($dn, $user->getDN());
		$this->assertSame($uid, $user->getUsername());
	}

	public function testUpdateEmailProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapEmailAttribute'))
			->will($this->returnValue('email'));

		$access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('email'))
			->will($this->returnValue(array('alice@foo.bar')));

		$config->expects($this->once())
			->method('setUserValue')
			->with($this->equalTo('alice'), $this->equalTo('settings'),
				$this->equalTo('email'),
				$this->equalTo('alice@foo.bar'))
			->will($this->returnValue(true));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr);

		$user->updateEmail();
	}

	public function testUpdateEmailNotProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapEmailAttribute'))
			->will($this->returnValue('email'));

		$access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('email'))
			->will($this->returnValue(false));

		$config->expects($this->never())
			->method('setUserValue');

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr);

		$user->updateEmail();
	}

	public function testUpdateEmailNotConfigured() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->once())
			->method('__get')
			->with($this->equalTo('ldapEmailAttribute'))
			->will($this->returnValue(''));

		$access->expects($this->never())
			->method('readAttribute');

		$config->expects($this->never())
			->method('setUserValue');

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr);

		$user->updateEmail();
	}

	public function testUpdateQuotaAllProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'))
			->will($this->returnValue('23 GB'));

		$connection->expects($this->at(1))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('myquota'));

		$connection->expects($this->exactly(2))
			->method('__get');

		$access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('myquota'))
			->will($this->returnValue(array('42 GB')));

		$config->expects($this->once())
			->method('setUserValue')
			->with($this->equalTo('alice'),
				$this->equalTo('files'),
				$this->equalTo('quota'),
				$this->equalTo('42 GB'))
			->will($this->returnValue(true));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr);

		$user->updateQuota();
	}

	public function testUpdateQuotaDefaultProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'))
			->will($this->returnValue('25 GB'));

		$connection->expects($this->at(1))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('myquota'));

		$connection->expects($this->exactly(2))
			->method('__get');

		$access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('myquota'))
			->will($this->returnValue(false));

		$config->expects($this->once())
			->method('setUserValue')
			->with($this->equalTo('alice'),
				$this->equalTo('files'),
				$this->equalTo('quota'),
				$this->equalTo('25 GB'))
			->will($this->returnValue(true));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr);

		$user->updateQuota();
	}

	public function testUpdateQuotaIndividualProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'))
			->will($this->returnValue(''));

		$connection->expects($this->at(1))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('myquota'));

		$connection->expects($this->exactly(2))
			->method('__get');

		$access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('myquota'))
			->will($this->returnValue(array('27 GB')));

		$config->expects($this->once())
			->method('setUserValue')
			->with($this->equalTo('alice'),
				$this->equalTo('files'),
				$this->equalTo('quota'),
				$this->equalTo('27 GB'))
			->will($this->returnValue(true));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr);

		$user->updateQuota();
	}

	public function testUpdateQuotaNoneProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'))
			->will($this->returnValue(''));

		$connection->expects($this->at(1))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue('myquota'));

		$connection->expects($this->exactly(2))
			->method('__get');

		$access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('myquota'))
			->will($this->returnValue(false));

		$config->expects($this->never())
			->method('setUserValue');

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr);

		$user->updateQuota();
	}

	public function testUpdateQuotaNoneConfigured() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'))
			->will($this->returnValue(''));

		$connection->expects($this->at(1))
			->method('__get')
			->with($this->equalTo('ldapQuotaAttribute'))
			->will($this->returnValue(''));

		$connection->expects($this->exactly(2))
			->method('__get');

		$access->expects($this->never())
			->method('readAttribute');

		$config->expects($this->never())
			->method('setUserValue');

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr);

		$user->updateQuota();
	}

	public function testUpdateQuotaFromValue() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$readQuota = '19 GB';

		$connection->expects($this->at(0))
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'))
			->will($this->returnValue(''));

		$connection->expects($this->once(1))
			->method('__get')
			->with($this->equalTo('ldapQuotaDefault'))
			->will($this->returnValue(null));

		$access->expects($this->never())
			->method('readAttribute');

		$config->expects($this->once())
			->method('setUserValue')
			->with($this->equalTo('alice'),
				$this->equalTo('files'),
				$this->equalTo('quota'),
				$this->equalTo($readQuota))
			->will($this->returnValue(true));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr);

		$user->updateQuota($readQuota);
	}

	//the testUpdateAvatar series also implicitely tests getAvatarImage
	public function testUpdateAvatarJpegPhotoProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$access->expects($this->once())
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

		$avatar = $this->getMock('\OCP\IAvatar');
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
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr);

		$user->updateAvatar();
	}

	public function testUpdateAvatarThumbnailPhotoProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$access->expects($this->at(0))
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('jpegPhoto'))
			->will($this->returnValue(false));

		$access->expects($this->at(1))
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('thumbnailPhoto'))
			->will($this->returnValue(array('this is a photo')));

		$access->expects($this->exactly(2))
			->method('readAttribute');

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

		$avatar = $this->getMock('\OCP\IAvatar');
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
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr);

		$user->updateAvatar();
	}

	public function testUpdateAvatarNotProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$access->expects($this->at(0))
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('jpegPhoto'))
			->will($this->returnValue(false));

		$access->expects($this->at(1))
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('thumbnailPhoto'))
			->will($this->returnValue(false));

		$access->expects($this->exactly(2))
			->method('readAttribute');

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
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr);

		$user->updateAvatar();
	}

	public function testUpdateBeforeFirstLogin() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

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
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr);

		$user->update();
	}

	public function testUpdateAfterFirstLogin() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

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
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr);

		$user->update();
	}

	public function testUpdateNoRefresh() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

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
			->will($this->returnValue(time()));

		$config->expects($this->exactly(2))
			->method('getUserValue');

		$config->expects($this->never())
			->method('setUserValue');

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr);

		$user->update();
	}

	public function testMarkLogin() {
		list($access, $config, $filesys, $image, $log, $avaMgr) =
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
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr);

		$user->markLogin();
	}

	public function testGetAvatarImageProvided() {
		list($access, $config, $filesys, $image, $log, $avaMgr) =
			$this->getTestInstances();

		$access->expects($this->once())
			->method('readAttribute')
			->with($this->equalTo('uid=alice,dc=foo,dc=bar'),
				$this->equalTo('jpegPhoto'))
			->will($this->returnValue(array('this is a photo')));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr);

		$photo = $user->getAvatarImage();
		$this->assertSame('this is a photo', $photo);
		//make sure readAttribute is not called again but the already fetched
		//photo is returned
		$photo = $user->getAvatarImage();
	}

	public function testProcessAttributes() {
		list(, $config, $filesys, $image, $log, $avaMgr, $dbc) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

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

		$userMock = $this->getMockBuilder('OCA\user_ldap\lib\user\User')
			->setConstructorArgs(array($uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr))
			->setMethods($requiredMethods)
			->getMock();

		$connection->setConfiguration(array(
			'homeFolderNamingRule' => 'homeDirectory'
		));

		$connection->expects($this->any())
			->method('__get')
			//->will($this->returnArgument(0));
			->will($this->returnCallback(function($name) {
				if($name === 'homeFolderNamingRule') {
					return 'attr:homeDirectory';
				}
				return $name;
			}));

		$record = array(
			strtolower($connection->ldapQuotaAttribute) => array('4096'),
			strtolower($connection->ldapEmailAttribute) => array('alice@wonderland.org'),
			strtolower($connection->ldapUserDisplayName) => array('Aaaaalice'),
			'uid' => array($uid),
			'homedirectory' => array('Alice\'s Folder'),
			'memberof' => array('cn=groupOne', 'cn=groupTwo'),
			'jpegphoto' => array('here be an image')
		);

		foreach($requiredMethods as $method) {
			$userMock->expects($this->once())
				->method($method);
		}

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
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->any())
			->method('__get')
			->with($this->equalTo('homeFolderNamingRule'))
			->will($this->returnValue($attributeValue));

		$access->expects($this->never())
			->method('readAttribute');

		$config->expects($this->never())
			->method('getAppValue');

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr);

		$path = $user->getHomePath();
		$this->assertSame($path, false);
	}

	public function testGetHomePathConfiguredNotAvailableAllowed() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->any())
			->method('__get')
			->with($this->equalTo('homeFolderNamingRule'))
			->will($this->returnValue('attr:foobar'));

		$access->expects($this->once())
			->method('readAttribute')
			->will($this->returnValue(false));

		// asks for "enforce_home_folder_naming_rule"
		$config->expects($this->once())
			->method('getAppValue')
			->will($this->returnValue(false));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr);

		$path = $user->getHomePath();

		$this->assertSame($path, false);
	}

	/**
	 * @expectedException \Exception
	 */
	public function testGetHomePathConfiguredNotAvailableNotAllowed() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc) =
			$this->getTestInstances();

		list($access, $connection) =
			$this->getAdvancedMocks($config, $filesys, $log, $avaMgr, $dbc);

		$connection->expects($this->any())
			->method('__get')
			->with($this->equalTo('homeFolderNamingRule'))
			->will($this->returnValue('attr:foobar'));

		$access->expects($this->once())
			->method('readAttribute')
			->will($this->returnValue(false));

		// asks for "enforce_home_folder_naming_rule"
		$config->expects($this->once())
			->method('getAppValue')
			->will($this->returnValue(true));

		$uid = 'alice';
		$dn  = 'uid=alice,dc=foo,dc=bar';

		$user = new User(
			$uid, $dn, $access, $config, $filesys, $image, $log, $avaMgr);

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
		list($access, $config, $filesys, $image, $log, $avaMgr) =
			$this->getTestInstances();

		$config->expects($this->once())
			->method('setUserValue');

		$user = new User(
			'user', 'cn=user', $access, $config, $filesys, $image, $log, $avaMgr);

		$displayName = $user->composeAndStoreDisplayName($part1, $part2);
		$this->assertSame($expected, $displayName);
	}
}
