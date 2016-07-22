<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Philippe Jung <phil.jung@free.fr>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

use OCA\User_LDAP\User\Manager;

/**
 * Class Test_User_Manager
 *
 * @group DB
 *
 * @package OCA\User_LDAP\Tests\User
 */
class ManagerTest extends \Test\TestCase {

	private function getTestInstances() {
		$access = $this->getMock('\OCA\User_LDAP\User\IUserTools');
		$config = $this->getMock('\OCP\IConfig');
		$filesys = $this->getMock('\OCA\User_LDAP\FilesystemHelper');
		$log = $this->getMock('\OCA\User_LDAP\LogWrapper');
		$avaMgr = $this->getMock('\OCP\IAvatarManager');
		$image = $this->getMock('\OCP\Image');
		$dbc = $this->getMock('\OCP\IDBConnection');
		$userMgr = $this->getMock('\OCP\IUserManager');

		$connection = new \OCA\User_LDAP\Connection(
			$lw  = $this->getMock('\OCA\User_LDAP\ILDAPWrapper'),
			'',
			null
		);

		$access->expects($this->any())
			->method('getConnection')
			->will($this->returnValue($connection));

		return array($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr);
	}

	public function testGetByDNExisting() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		$inputDN = 'cn=foo,dc=foobar,dc=bar';
		$uid = '563418fc-423b-1033-8d1c-ad5f418ee02e';

		$access->expects($this->once())
			->method('stringResemblesDN')
			->with($this->equalTo($inputDN))
			->will($this->returnValue(true));

		$access->expects($this->once())
			->method('dn2username')
			->with($this->equalTo($inputDN))
			->will($this->returnValue($uid));

		$access->expects($this->never())
			->method('username2dn');

		$manager = new Manager($config, $filesys, $log, $avaMgr, $image, $dbc, $userMgr);
		$manager->setLdapAccess($access);
		$user = $manager->get($inputDN);

		// Now we fetch the user again. If this leads to a failing test,
		// runtime caching the manager is broken.
		$user = $manager->get($inputDN);

		$this->assertInstanceOf('\OCA\User_LDAP\User\User', $user);
	}

	public function testGetByEDirectoryDN() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		$inputDN = 'uid=foo,o=foobar,c=bar';
		$uid = '563418fc-423b-1033-8d1c-ad5f418ee02e';

		$access->expects($this->once())
			->method('stringResemblesDN')
			->with($this->equalTo($inputDN))
			->will($this->returnValue(true));

		$access->expects($this->once())
			->method('dn2username')
			->with($this->equalTo($inputDN))
			->will($this->returnValue($uid));

		$access->expects($this->never())
			->method('username2dn');

		$manager = new Manager($config, $filesys, $log, $avaMgr, $image, $dbc, $userMgr);
		$manager->setLdapAccess($access);
		$user = $manager->get($inputDN);

		$this->assertInstanceOf('\OCA\User_LDAP\User\User', $user);
	}

	public function testGetByExoticDN() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		$inputDN = 'ab=cde,f=ghei,mno=pq';
		$uid = '563418fc-423b-1033-8d1c-ad5f418ee02e';

		$access->expects($this->once())
			->method('stringResemblesDN')
			->with($this->equalTo($inputDN))
			->will($this->returnValue(true));

		$access->expects($this->once())
			->method('dn2username')
			->with($this->equalTo($inputDN))
			->will($this->returnValue($uid));

		$access->expects($this->never())
			->method('username2dn');

		$manager = new Manager($config, $filesys, $log, $avaMgr, $image, $dbc, $userMgr);
		$manager->setLdapAccess($access);
		$user = $manager->get($inputDN);

		$this->assertInstanceOf('\OCA\User_LDAP\User\User', $user);
	}

	public function testGetByDNNotExisting() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		$inputDN = 'cn=gone,dc=foobar,dc=bar';

		$access->expects($this->once())
			->method('stringResemblesDN')
			->with($this->equalTo($inputDN))
			->will($this->returnValue(true));

		$access->expects($this->once())
			->method('dn2username')
			->with($this->equalTo($inputDN))
			->will($this->returnValue(false));

		$access->expects($this->once())
			->method('username2dn')
			->with($this->equalTo($inputDN))
			->will($this->returnValue(false));

		$manager = new Manager($config, $filesys, $log, $avaMgr, $image, $dbc, $userMgr);
		$manager->setLdapAccess($access);
		$user = $manager->get($inputDN);

		$this->assertNull($user);
	}

	public function testGetByUidExisting() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		$dn = 'cn=foo,dc=foobar,dc=bar';
		$uid = '563418fc-423b-1033-8d1c-ad5f418ee02e';

		$access->expects($this->never())
			->method('dn2username');

		$access->expects($this->once())
			->method('username2dn')
			->with($this->equalTo($uid))
			->will($this->returnValue($dn));

		$access->expects($this->once())
			->method('stringResemblesDN')
			->with($this->equalTo($uid))
			->will($this->returnValue(false));

		$manager = new Manager($config, $filesys, $log, $avaMgr, $image, $dbc, $userMgr);
		$manager->setLdapAccess($access);
		$user = $manager->get($uid);

		// Now we fetch the user again. If this leads to a failing test,
		// runtime caching the manager is broken.
		$user = $manager->get($uid);

		$this->assertInstanceOf('\OCA\User_LDAP\User\User', $user);
	}

	public function testGetByUidNotExisting() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		$dn = 'cn=foo,dc=foobar,dc=bar';
		$uid = 'gone';

		$access->expects($this->never())
			->method('dn2username');

		$access->expects($this->exactly(1))
			->method('username2dn')
			->with($this->equalTo($uid))
			->will($this->returnValue(false));

		$manager = new Manager($config, $filesys, $log, $avaMgr, $image, $dbc, $userMgr);
		$manager->setLdapAccess($access);
		$user = $manager->get($uid);

		$this->assertNull($user);
	}

	public function testGetAttributesAll() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		$manager = new Manager($config, $filesys, $log, $avaMgr, $image, $dbc, $userMgr);
		$manager->setLdapAccess($access);

		$connection = $access->getConnection();
		$connection->setConfiguration(array('ldapEmailAttribute' => 'mail'));

		$attributes = $manager->getAttributes();

		$this->assertTrue(in_array('dn', $attributes));
		$this->assertTrue(in_array($access->getConnection()->ldapEmailAttribute, $attributes));
		$this->assertTrue(in_array('jpegphoto', $attributes));
		$this->assertTrue(in_array('thumbnailphoto', $attributes));
	}

	public function testGetAttributesMinimal() {
		list($access, $config, $filesys, $image, $log, $avaMgr, $dbc, $userMgr) =
			$this->getTestInstances();

		$manager = new Manager($config, $filesys, $log, $avaMgr, $image, $dbc, $userMgr);
		$manager->setLdapAccess($access);

		$attributes = $manager->getAttributes(true);

		$this->assertTrue(in_array('dn', $attributes));
		$this->assertTrue(!in_array('jpegphoto', $attributes));
		$this->assertTrue(!in_array('thumbnailphoto', $attributes));
	}

}
