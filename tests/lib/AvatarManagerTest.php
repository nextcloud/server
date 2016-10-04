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

namespace Test;

use OC\AvatarManager;
use Test\Traits\UserTrait;
use Test\Traits\MountProviderTrait;

/**
 * Class AvatarManagerTest
 * @group DB
 */
class AvatarManagerTest extends \Test\TestCase {
	use MountProviderTrait;

	/** @var AvatarManager */
	private $avatarManager;

	/** @var \OC\Files\Storage\Temporary */
	private $storage;

	/** @var \OCP\IUser */
	private $user;

	public function setUp() {
		parent::setUp();

		\OC_User::clearBackends();
		// needed for loginName2UserName mapping
		$userBackend = $this->getMock('\OC\User\Database');
		\OC::$server->getUserManager()->registerBackend($userBackend);

		$userBackend->expects($this->any())
			->method('userExists')
			->will($this->returnValueMap([
				['valid-user', true],
				['vaLid-USER', true],
			]));
		$userBackend->expects($this->any())
			->method('loginName2UserName')
			->will($this->returnValueMap([
				['valid-user', 'valid-user'],
				['vaLid-USER', 'valid-user'],
			]));

		$this->storage = new \OC\Files\Storage\Temporary();
		$this->registerMount('valid-user', $this->storage, '/valid-user/');

		$this->avatarManager = \OC::$server->getAvatarManager();
	}

	public function tearDown() {
		\OC_User::clearBackends();
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage user does not exist
	 */
	public function testGetAvatarInvalidUser() {
		$this->avatarManager->getAvatar('invalidUser');
	}

	public function testGetAvatarValidUser() {
		$avatar = $this->avatarManager->getAvatar('valid-user');

		$this->assertInstanceOf('\OCP\IAvatar', $avatar);
		$this->assertFalse($this->storage->file_exists('files'));
	}

	public function testGetAvatarValidUserDifferentCasing() {
		$avatar = $this->avatarManager->getAvatar('vaLid-USER');

		$this->assertInstanceOf('\OCP\IAvatar', $avatar);
		$this->assertFalse($this->storage->file_exists('files'));
	}

}
