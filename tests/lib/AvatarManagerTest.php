<?php
/**
 * @author Roeland Jago Douma <rullzer@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @copyright Copyright (c) 2016, Lukas Reschke <lukas@statuscode.ch>
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

namespace Test;

use OC\Avatar;
use OC\AvatarManager;
use OC\Files\AppData\AppData;
use OC\User\Manager as UserManager;
use OC\User\User;
use OCP\Files\IAppData;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IAvatar;
use OCP\IConfig;
use OCP\IL10N;
use OCP\ILogger;
use OCP\IUser;

/**
 * Class AvatarManagerTest
 */
class AvatarManagerTest extends \Test\TestCase {
	/** @var UserManager|\PHPUnit_Framework_MockObject_MockObject */
	private $userManager;
	/** @var IAppData|\PHPUnit_Framework_MockObject_MockObject */
	private $appData;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	private $l10n;
	/** @var ILogger|\PHPUnit_Framework_MockObject_MockObject */
	private $logger;
	/** @var IConfig|\PHPUnit_Framework_MockObject_MockObject */
	private $config;
	/** @var callable **/
	private $changeUserCallback;
	/** @var AvatarManager | \PHPUnit_Framework_MockObject_MockObject */
	private $avatarManager;

	public function setUp() {
		parent::setUp();

		$this->userManager = $this->createMock(UserManager::class);
		$this->appData = $this->createMock(IAppData::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->logger = $this->createMock(ILogger::class);
		$this->config = $this->createMock(IConfig::class);

		$this->userManager
			->expects($this->any())
			->method('listen')
			->with(
				'\OC\User',
				'changeUser',
				$this->callback(function($changeUserCallback) {
					$this->changeUserCallback = $changeUserCallback;

					return true;
				})
			);

		$this->avatarManager = new AvatarManager(
			$this->userManager,
			$this->appData,
			$this->l10n,
			$this->logger,
			$this->config
		);
	}

	/**
	 * @expectedException \Exception
	 * @expectedExceptionMessage user does not exist
	 */
	public function testGetAvatarInvalidUser() {
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('invalidUser')
			->willReturn(null);

		$this->avatarManager->getAvatar('invalidUser');
	}

	public function testGetAvatarValidUser() {
		$user = $this->createMock(IUser::class);
		$user
			->expects($this->once())
			->method('getUID')
			->willReturn('valid-user');
		$this->userManager
			->expects($this->once())
			->method('get')
			->with('valid-user')
			->willReturn($user);
		$folder = $this->createMock(ISimpleFolder::class);
		$this->appData
			->expects($this->once())
			->method('getFolder')
			->with('valid-user')
			->willReturn($folder);

		$expected = new Avatar($folder, $this->l10n, $user, $this->logger, $this->config);
		$this->assertEquals($expected, $this->avatarManager->getAvatar('valid-user'));
	}

	public function testGetAvatarValidUserDifferentCasing() {
		$user = $this->createMock(IUser::class);
		$this->userManager->expects($this->once())
			->method('get')
			->with('vaLid-USER')
			->willReturn($user);

		$user->expects($this->once())
			->method('getUID')
			->willReturn('valid-user');

		$folder = $this->createMock(ISimpleFolder::class);
		$this->appData
			->expects($this->once())
			->method('getFolder')
			->with('valid-user')
			->willReturn($folder);

		$expected = new Avatar($folder, $this->l10n, $user, $this->logger, $this->config);
		$this->assertEquals($expected, $this->avatarManager->getAvatar('vaLid-USER'));
	}

	public function testUpdateAvatarVersionOnDisplayNameChange() {
		$this->avatarManager =
			$this->getMockBuilder(AvatarManager::class)
				->setMethods(['getAvatar'])
				->setConstructorArgs([
					$this->userManager,
					$this->appData,
					$this->l10n,
					$this->logger,
					$this->config
				])->getMock();

		$user = $this->createMock(User::class);

		$user->expects($this->any())
			->method('getUID')
			->will($this->returnValue(42));

		$avatar = $this->createMock(IAvatar::class);

		$this->avatarManager
			->expects($this->once())
			->method('getAvatar')
			->with(42)
			->will($this->returnValue($avatar));

		$avatar->expects($this->once())
			->method('exists')
			->will($this->returnValue(false));

		$this->config
			->expects($this->once())
			->method('getUserValue')
			->with(42, 'avatar', 'version', 0)
			->will($this->returnValue(15));

		$this->config
			->expects($this->once())
			->method('setUserValue')
			->with(42, 'avatar', 'version', 16);

		call_user_func($this->changeUserCallback, $user, 'displayName', 'newValue', 'oldValue');
	}

	public function testUpdateAvatarVersionOnDisplayNameChangeUserWithAvatar() {
		$this->avatarManager =
			$this->getMockBuilder(AvatarManager::class)
				->setMethods(['getAvatar'])
				->setConstructorArgs([
					$this->userManager,
					$this->appData,
					$this->l10n,
					$this->logger,
					$this->config
				])->getMock();

		$user = $this->createMock(User::class);

		$user->expects($this->any())
			->method('getUID')
			->will($this->returnValue(42));

		$avatar = $this->createMock(IAvatar::class);

		$this->avatarManager
			->expects($this->once())
			->method('getAvatar')
			->with(42)
			->will($this->returnValue($avatar));

		$avatar->expects($this->once())
			->method('exists')
			->will($this->returnValue(true));

		$this->config
			->expects($this->never())
			->method('setUserValue');

		call_user_func($this->changeUserCallback, $user, 'displayName', 'newValue', 'oldValue');
	}

	public function testUpdateAvatarVersionOnDisplayNameChangeDifferentChangedAttribute() {
		$user = $this->createMock(User::class);

		$this->config
			->expects($this->never())
			->method('setUserValue');

		call_user_func($this->changeUserCallback, $user, 'otherAttribute', 'newValue', 'oldValue');
	}
}
